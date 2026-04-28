<?php

namespace App\Services;

use App\Enums\ClienteBiometricoEstatus;
use App\Enums\ClienteEstatus;
use App\Enums\DispositivoEstatus;
use App\Exceptions\ApiException;
use App\Models\Cliente;
use App\Models\ClienteBiometrico;
use App\Models\Dispositivo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BiometricoService
{
    public function __construct(private readonly AuditoriaService $auditoriaService) {}

    public function index(Request $request): LengthAwarePaginator
    {
        $this->assertBiometricTableExists();
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $table = ClienteBiometrico::tableName();

        $query = ClienteBiometrico::query();
        $this->loadRelations($query);

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search, $table): void {
                if (Schema::hasColumn($table, 'identificador_biometrico')) {
                    $builder->orWhere('identificador_biometrico', 'like', "%{$search}%");
                }

                $builder->orWhereHas('cliente', function (Builder $clienteQuery) use ($search): void {
                    foreach (['nombre_completo', 'nombre', 'apellido_paterno', 'apellido_materno', 'email', 'telefono'] as $column) {
                        if (Schema::hasColumn('clientes', $column)) {
                            $clienteQuery->orWhere($column, 'like', "%{$search}%");
                        }
                    }
                });
            });
        }

        foreach (['cliente_id', 'dispositivo_id'] as $filter) {
            if (Schema::hasColumn($table, $filter) && $request->filled($filter)) {
                $query->where($filter, (int) $request->query($filter));
            }
        }

        if (Schema::hasColumn($table, 'dedo') && $request->filled('dedo')) {
            $query->where('dedo', mb_strtoupper(trim((string) $request->query('dedo'))));
        }

        if (Schema::hasColumn($table, 'estatus')) {
            $estatus = mb_strtoupper(trim((string) $request->query('estatus', '')));
            if ($estatus !== '' && $estatus !== 'ALL') {
                $query->where('estatus', $estatus);
            }
        }

        if (Schema::hasColumn($table, 'es_principal') && $request->filled('es_principal')) {
            $query->where('es_principal', filter_var($request->query('es_principal'), FILTER_VALIDATE_BOOLEAN));
        }

        if (Schema::hasColumn($table, 'activo')) {
            $activo = strtolower(trim((string) $request->query('activo', 'true')));
            if ($activo !== 'all') {
                $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);
            }
        }

        if (Schema::hasColumn($table, 'enrolado_at')) {
            $query->orderByDesc('enrolado_at');
        }

        $query->orderByDesc('id');

        return $query->paginate($perPage)->appends($request->query());
    }

    public function porCliente(Request $request, int $clienteId): LengthAwarePaginator
    {
        $this->assertBiometricTableExists();
        $this->findClienteOrFail($clienteId);

        $table = ClienteBiometrico::tableName();
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $query = ClienteBiometrico::query()->where('cliente_id', $clienteId);
        $this->loadRelations($query);

        if (Schema::hasColumn($table, 'estatus')) {
            $estatus = mb_strtoupper(trim((string) $request->query('estatus', '')));
            if ($estatus !== '' && $estatus !== 'ALL') {
                $query->where('estatus', $estatus);
            }
        }

        if (Schema::hasColumn($table, 'activo')) {
            $activo = strtolower(trim((string) $request->query('activo', 'true')));
            if ($activo !== 'all') {
                $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);
            }
        }

        if (Schema::hasColumn($table, 'es_principal') && $request->filled('es_principal')) {
            $query->where('es_principal', filter_var($request->query('es_principal'), FILTER_VALIDATE_BOOLEAN));
        }

        if (Schema::hasColumn($table, 'es_principal')) {
            $query->orderByDesc('es_principal');
        }

        if (Schema::hasColumn($table, 'enrolado_at')) {
            $query->orderByDesc('enrolado_at');
        }

        $query->orderByDesc('id');

        return $query->paginate($perPage)->appends($request->query());
    }

    public function findOrFail(int $id): ClienteBiometrico
    {
        $this->assertBiometricTableExists();
        $biometrico = ClienteBiometrico::query()->find($id);

        if (! $biometrico) {
            throw new ApiException('Biométrico no encontrado.', 404);
        }

        $this->loadRelationsForModel($biometrico);

        return $biometrico;
    }

    /** @return array{biometrico: ClienteBiometrico, quality_low: bool} */
    public function enrolar(array $payload): array
    {
        $this->assertBiometricTableExists();
        $table = ClienteBiometrico::tableName();
        $cliente = $this->findClienteActivaOrFail((int) $payload['cliente_id']);

        $dispositivo = null;
        if (Schema::hasColumn($table, 'dispositivo_id') && ! empty($payload['dispositivo_id'])) {
            $dispositivo = $this->findDispositivoActivoOrFail((int) $payload['dispositivo_id']);
        }

        $qualityLow = Schema::hasColumn($table, 'calidad_lectura')
            && isset($payload['calidad_lectura'])
            && (int) $payload['calidad_lectura'] < 60;

        try {
            $biometrico = DB::transaction(function () use ($payload, $table, $cliente, $dispositivo): ClienteBiometrico {
                if (Schema::hasColumn($table, 'dedo') && empty($payload['dedo'])) {
                    $payload['dedo'] = 'INDICE_DERECHO';
                }

                if (Schema::hasColumn($table, 'dedo')) {
                    $this->assertNoDuplicateFinger($cliente->id, (string) ($payload['dedo'] ?? ''), null);
                }

                $hasAnyActive = $this->hasActiveBiometric($cliente->id);
                $hasPrincipal = $this->hasPrincipalBiometric($cliente->id);

                $isPrincipal = (bool) Arr::get($payload, 'es_principal', false);
                if (! $hasAnyActive || $isPrincipal || ! $hasPrincipal) {
                    $isPrincipal = true;
                }

                if (Schema::hasColumn($table, 'es_principal') && $isPrincipal) {
                    $this->clearPrincipal($cliente->id);
                }

                $attributes = $this->sanitizePayload($payload);
                if (Schema::hasColumn($table, 'es_principal')) {
                    $attributes['es_principal'] = $isPrincipal;
                }

                if (Schema::hasColumn($table, 'estatus')) {
                    $attributes['estatus'] = $attributes['estatus'] ?? ClienteBiometricoEstatus::ACTIVO->value;
                }

                if (Schema::hasColumn($table, 'activo')) {
                    $attributes['activo'] = $attributes['activo'] ?? true;
                }

                if (Schema::hasColumn($table, 'enrolado_at')) {
                    $attributes['enrolado_at'] = $attributes['enrolado_at'] ?? Carbon::now();
                }

                if ($dispositivo && Schema::hasColumn($table, 'dispositivo_id')) {
                    $attributes['dispositivo_id'] = $dispositivo->id;
                }

                $created = ClienteBiometrico::query()->create($attributes);
                $this->loadRelationsForModel($created);

                return $created;
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                throw new ApiException('El identificador biométrico ya está registrado en un biométrico activo.', 422);
            }

            throw $exception;
        }

        $this->auditoriaService->registrar(
            accion: 'ENROLAR_HUELLA',
            entidad: 'Biometrico',
            entidadId: $biometrico->id,
            descripcion: 'Biométrico enrolado correctamente.',
            datosDespues: $this->auditBiometricoPayload($biometrico),
            sucursalId: (int) ($cliente->sucursal_id ?? 0) ?: null,
        );

        return [
            'biometrico' => $biometrico,
            'quality_low' => $qualityLow,
        ];
    }

    public function marcarPrincipal(int $id): ClienteBiometrico
    {
        $this->assertBiometricTableExists();
        $table = ClienteBiometrico::tableName();
        $biometrico = $this->findOrFail($id);

        $this->assertActiveBiometric($biometrico);

        DB::transaction(function () use ($table, $biometrico): void {
            if (Schema::hasColumn($table, 'es_principal')) {
                $this->clearPrincipal((int) $biometrico->cliente_id);
                $biometrico->forceFill(['es_principal' => true])->save();
            }
        });

        $biometrico->refresh();
        $this->loadRelationsForModel($biometrico);

        $this->auditoriaService->registrar(
            accion: 'AJUSTAR',
            entidad: 'Biometrico',
            entidadId: $biometrico->id,
            descripcion: 'Biométrico marcado como principal.',
            datosDespues: $this->auditBiometricoPayload($biometrico),
            sucursalId: (int) ($biometrico->cliente?->sucursal_id ?? 0) ?: null,
        );

        return $biometrico;
    }

    public function desactivar(int $id, ?string $motivo = null): ClienteBiometrico
    {
        return $this->changeStatus($id, ClienteBiometricoEstatus::INACTIVO->value, $motivo);
    }

    public function revocar(int $id, ?string $motivo = null): ClienteBiometrico
    {
        return $this->changeStatus($id, ClienteBiometricoEstatus::REVOCADO->value, $motivo);
    }

    private function changeStatus(int $id, string $estatus, ?string $motivo): ClienteBiometrico
    {
        $this->assertBiometricTableExists();
        $table = ClienteBiometrico::tableName();
        $biometrico = $this->findOrFail($id);

        DB::transaction(function () use ($biometrico, $estatus, $motivo, $table): void {
            $wasPrincipal = Schema::hasColumn($table, 'es_principal') && (bool) $biometrico->es_principal;

            $attributes = [];
            if (Schema::hasColumn($table, 'activo')) {
                $attributes['activo'] = false;
            }

            if (Schema::hasColumn($table, 'estatus')) {
                $attributes['estatus'] = $estatus;
            }

            if (Schema::hasColumn($table, 'es_principal')) {
                $attributes['es_principal'] = false;
            }

            if (Schema::hasColumn($table, 'observaciones') && filled($motivo)) {
                $attributes['observaciones'] = trim(((string) $biometrico->observaciones.' | Motivo: '.$motivo), ' |');
            }

            if ($attributes !== []) {
                $biometrico->forceFill($attributes)->save();
            }

            if ($wasPrincipal) {
                $this->assignNextPrincipal((int) $biometrico->cliente_id, $biometrico->id);
            }
        });

        $biometrico->refresh();
        $this->loadRelationsForModel($biometrico);

        $this->auditoriaService->registrar(
            accion: 'CAMBIAR_ESTATUS',
            entidad: 'Biometrico',
            entidadId: $biometrico->id,
            descripcion: $estatus === ClienteBiometricoEstatus::REVOCADO->value
                ? 'Biométrico revocado correctamente.'
                : 'Biométrico desactivado correctamente.',
            datosDespues: array_merge(
                $this->auditBiometricoPayload($biometrico),
                filled($motivo) ? ['motivo' => $motivo] : [],
            ),
            sucursalId: (int) ($biometrico->cliente?->sucursal_id ?? 0) ?: null,
        );

        return $biometrico;
    }

    private function findClienteOrFail(int $id): Cliente
    {
        $cliente = Cliente::query()->find($id);
        if (! $cliente) {
            throw new ApiException('Cliente no encontrado.', 404);
        }

        return $cliente;
    }

    private function findClienteActivaOrFail(int $id): Cliente
    {
        $cliente = $this->findClienteOrFail($id);

        if (Schema::hasColumn('clientes', 'activo') && ! $cliente->activo) {
            throw new ApiException('No es posible enrolar biométricos para un cliente inactivo.', 422);
        }

        if (Schema::hasColumn('clientes', 'estatus')) {
            $estatus = mb_strtoupper((string) $cliente->estatus);
            if (in_array($estatus, [ClienteEstatus::BLOQUEADO->value, ClienteEstatus::INACTIVO->value], true)) {
                throw new ApiException('No es posible enrolar biométricos para clientes bloqueados o inactivos.', 422);
            }
        }

        return $cliente;
    }

    private function findDispositivoActivoOrFail(int $id): Dispositivo
    {
        $dispositivo = Dispositivo::query()->find($id);
        if (! $dispositivo) {
            throw new ApiException('Dispositivo no encontrado.', 422);
        }

        if (Schema::hasColumn('dispositivos', 'activo') && ! $dispositivo->activo) {
            throw new ApiException('El dispositivo no está activo.', 422);
        }

        if (Schema::hasColumn('dispositivos', 'estatus') && (string) $dispositivo->estatus !== DispositivoEstatus::ACTIVO->value) {
            throw new ApiException('El dispositivo no tiene estatus ACTIVO.', 422);
        }

        return $dispositivo;
    }

    private function loadRelations(Builder $query): void
    {
        if (Schema::hasColumn(ClienteBiometrico::tableName(), 'cliente_id')) {
            $query->with(['cliente' => function ($relation): void {
                $relation->select($this->clienteColumns());
            }]);
        }

        if (Schema::hasColumn(ClienteBiometrico::tableName(), 'dispositivo_id')) {
            $query->with(['dispositivo' => function ($relation): void {
                $relation->select($this->dispositivoColumns());
            }]);
        }
    }

    private function loadRelationsForModel(Model $model): void
    {
        $relations = [];

        if (Schema::hasColumn(ClienteBiometrico::tableName(), 'cliente_id')) {
            $relations['cliente'] = function ($relation): void {
                $relation->select($this->clienteColumns());
            };
        }

        if (Schema::hasColumn(ClienteBiometrico::tableName(), 'dispositivo_id')) {
            $relations['dispositivo'] = function ($relation): void {
                $relation->select($this->dispositivoColumns());
            };
        }

        if ($relations !== []) {
            $model->loadMissing($relations);
        }
    }

    /** @return list<string> */
    private function clienteColumns(): array
    {
        $columns = ['id'];
        foreach (['nombre_completo', 'nombre', 'apellido_paterno', 'apellido_materno', 'telefono', 'email', 'estatus'] as $column) {
            if (Schema::hasColumn('clientes', $column)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    /** @return list<string> */
    private function dispositivoColumns(): array
    {
        $columns = ['id'];
        foreach (['nombre', 'clave', 'identificador', 'estatus', 'activo'] as $column) {
            if (Schema::hasColumn('dispositivos', $column)) {
                $columns[] = $column;
            }
        }

        return $columns;
    }

    private function assertBiometricTableExists(): void
    {
        if (! Schema::hasTable(ClienteBiometrico::tableName())) {
            throw new ApiException('No existe tabla biométrica en el esquema actual. Se requiere crearla antes de usar el módulo.', 422);
        }
    }

    private function assertNoDuplicateFinger(int $clienteId, string $dedo, ?int $ignoreId): void
    {
        if ($dedo === '') {
            return;
        }

        $table = ClienteBiometrico::tableName();
        $query = ClienteBiometrico::query()
            ->where('cliente_id', $clienteId)
            ->where('dedo', $dedo);

        if (Schema::hasColumn($table, 'activo')) {
            $query->where('activo', true);
        }

        if (Schema::hasColumn($table, 'estatus')) {
            $query->where('estatus', ClienteBiometricoEstatus::ACTIVO->value);
        }

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($query->exists()) {
            throw new ApiException('Ya existe una huella activa para ese dedo en el cliente.', 422, [
                'dedo' => ['Ya existe una huella activa para ese dedo en el cliente.'],
            ]);
        }
    }

    private function hasActiveBiometric(int $clienteId): bool
    {
        $query = ClienteBiometrico::query()->where('cliente_id', $clienteId);
        $table = ClienteBiometrico::tableName();

        if (Schema::hasColumn($table, 'activo')) {
            $query->where('activo', true);
        }

        if (Schema::hasColumn($table, 'estatus')) {
            $query->where('estatus', ClienteBiometricoEstatus::ACTIVO->value);
        }

        return $query->exists();
    }

    private function hasPrincipalBiometric(int $clienteId): bool
    {
        $table = ClienteBiometrico::tableName();
        if (! Schema::hasColumn($table, 'es_principal')) {
            return false;
        }

        $query = ClienteBiometrico::query()
            ->where('cliente_id', $clienteId)
            ->where('es_principal', true);

        if (Schema::hasColumn($table, 'activo')) {
            $query->where('activo', true);
        }

        if (Schema::hasColumn($table, 'estatus')) {
            $query->where('estatus', ClienteBiometricoEstatus::ACTIVO->value);
        }

        return $query->exists();
    }

    private function clearPrincipal(int $clienteId): void
    {
        $table = ClienteBiometrico::tableName();
        if (! Schema::hasColumn($table, 'es_principal')) {
            return;
        }

        $query = ClienteBiometrico::query()->where('cliente_id', $clienteId);

        if (Schema::hasColumn($table, 'activo')) {
            $query->where('activo', true);
        }

        if (Schema::hasColumn($table, 'estatus')) {
            $query->where('estatus', ClienteBiometricoEstatus::ACTIVO->value);
        }

        $query->update(['es_principal' => false]);
    }

    private function assignNextPrincipal(int $clienteId, int $skipId): void
    {
        $table = ClienteBiometrico::tableName();
        if (! Schema::hasColumn($table, 'es_principal')) {
            return;
        }

        $query = ClienteBiometrico::query()
            ->where('cliente_id', $clienteId)
            ->where('id', '!=', $skipId);

        if (Schema::hasColumn($table, 'activo')) {
            $query->where('activo', true);
        }

        if (Schema::hasColumn($table, 'estatus')) {
            $query->where('estatus', ClienteBiometricoEstatus::ACTIVO->value);
        }

        if (Schema::hasColumn($table, 'enrolado_at')) {
            $query->orderByDesc('enrolado_at');
        }

        $next = $query->orderByDesc('id')->first();
        if ($next) {
            $next->forceFill(['es_principal' => true])->save();
        }
    }

    private function assertActiveBiometric(ClienteBiometrico $biometrico): void
    {
        $table = ClienteBiometrico::tableName();

        if (Schema::hasColumn($table, 'activo') && ! $biometrico->activo) {
            throw new ApiException('Solo se pueden operar biométricos activos.', 422);
        }

        if (Schema::hasColumn($table, 'estatus') && (string) $biometrico->estatus !== ClienteBiometricoEstatus::ACTIVO->value) {
            throw new ApiException('Solo se pueden operar biométricos con estatus ACTIVO.', 422);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        $columns = [
            'cliente_id',
            'dispositivo_id',
            'identificador_biometrico',
            'dedo',
            'es_principal',
            'calidad_lectura',
            'intentos_fallidos',
            'estatus',
            'observaciones',
            'enrolado_at',
            'activo',
        ];

        return Arr::only($payload, array_values(array_filter($columns, static fn (string $column): bool => Schema::hasColumn(ClienteBiometrico::tableName(), $column))));
    }

    /**
     * @return array<string, mixed>
     */
    private function auditBiometricoPayload(ClienteBiometrico $biometrico): array
    {
        return Arr::only($biometrico->toArray(), [
            'id',
            'cliente_id',
            'dispositivo_id',
            'dedo',
            'es_principal',
            'estatus',
            'activo',
            'enrolado_at',
        ]);
    }
}
