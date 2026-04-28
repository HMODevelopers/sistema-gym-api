<?php

namespace App\Services;

use App\Enums\ClienteEstatus;
use App\Exceptions\ApiException;
use App\Models\Acceso;
use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AccesoService
{
    public function index(Request $request): LengthAwarePaginator
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $query = Acceso::query();
        $this->loadRelations($query);

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->whereHas('cliente', function (Builder $clienteQuery) use ($search): void {
                    foreach (['nombre', 'apellido_paterno', 'apellido_materno', 'nombre_completo', 'email', 'telefono'] as $column) {
                        if (Schema::hasColumn('clientes', $column)) {
                            $clienteQuery->orWhere($column, 'like', "%{$search}%");
                        }
                    }
                });
            });
        }

        foreach (['cliente_id', 'membresia_id', 'sucursal_id', 'usuario_id'] as $filter) {
            if (Schema::hasColumn('accesos', $filter) && $request->filled($filter)) {
                $query->where($filter, (int) $request->query($filter));
            }
        }

        if (Schema::hasColumn('accesos', 'metodo')) {
            $metodo = mb_strtoupper(trim((string) $request->query('metodo', '')));
            if ($metodo !== '' && $metodo !== 'ALL') {
                $query->where('metodo', $metodo);
            }
        }

        if (Schema::hasColumn('accesos', 'resultado')) {
            $resultado = mb_strtoupper(trim((string) $request->query('resultado', '')));
            if ($resultado !== '' && $resultado !== 'ALL') {
                $query->where('resultado', $resultado);
            }
        }

        if (Schema::hasColumn('accesos', 'motivo_rechazo')) {
            $motivo = mb_strtoupper(trim((string) $request->query('motivo_rechazo', '')));
            if ($motivo !== '' && $motivo !== 'ALL') {
                $query->where('motivo_rechazo', $motivo);
            }
        }

        if (Schema::hasColumn('accesos', 'activo')) {
            $activo = strtolower(trim((string) $request->query('activo', 'true')));
            if ($activo !== 'all') {
                $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);
            }
        }

        if (Schema::hasColumn('accesos', 'fecha_acceso')) {
            if ($request->filled('fecha_desde')) {
                $query->whereDate('fecha_acceso', '>=', (string) $request->query('fecha_desde'));
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('fecha_acceso', '<=', (string) $request->query('fecha_hasta'));
            }

            $query->orderByDesc('fecha_acceso');
        }

        $query->orderByDesc('id');

        return $query->paginate($perPage)->appends($request->query());
    }

    public function porCliente(Request $request, int $clienteId): LengthAwarePaginator
    {
        $this->findClienteOrFail($clienteId);

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $query = Acceso::query()->where('cliente_id', $clienteId);
        $this->loadRelations($query);

        if (Schema::hasColumn('accesos', 'metodo')) {
            $metodo = mb_strtoupper(trim((string) $request->query('metodo', '')));
            if ($metodo !== '' && $metodo !== 'ALL') {
                $query->where('metodo', $metodo);
            }
        }

        if (Schema::hasColumn('accesos', 'resultado')) {
            $resultado = mb_strtoupper(trim((string) $request->query('resultado', '')));
            if ($resultado !== '' && $resultado !== 'ALL') {
                $query->where('resultado', $resultado);
            }
        }

        if (Schema::hasColumn('accesos', 'activo')) {
            $activo = strtolower(trim((string) $request->query('activo', 'true')));
            if ($activo !== 'all') {
                $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);
            }
        }

        if (Schema::hasColumn('accesos', 'fecha_acceso')) {
            if ($request->filled('fecha_desde')) {
                $query->whereDate('fecha_acceso', '>=', (string) $request->query('fecha_desde'));
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('fecha_acceso', '<=', (string) $request->query('fecha_hasta'));
            }

            $query->orderByDesc('fecha_acceso');
        }

        $query->orderByDesc('id');

        return $query->paginate($perPage)->appends($request->query());
    }

    public function findOrFail(int $id): Acceso
    {
        $acceso = Acceso::query()->find($id);

        if (! $acceso) {
            throw new ApiException('Acceso no encontrado.', 404);
        }

        $this->loadRelationsForModel($acceso);

        return $acceso;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function validar(array $payload, ?Usuario $usuario): array
    {
        $sucursalId = (int) $payload['sucursal_id'];
        $this->assertSucursalActiva($sucursalId);

        $cliente = $this->resolveCliente($payload);
        $membresia = null;
        $resultado = 'DENEGADO';
        $motivo = null;

        if (! $cliente) {
            $motivo = 'CLIENTE_NO_ENCONTRADO';
        } else {
            $motivo = $this->evaluateCliente($cliente);
            $membresia = $this->resolveMembresiaVigente($cliente, $sucursalId);

            if (! $motivo) {
                $motivo = $this->evaluateMembresia($membresia, $sucursalId);
            }

            if (! $motivo) {
                $resultado = 'PERMITIDO';
            }
        }

        $acceso = DB::transaction(function () use ($payload, $usuario, $cliente, $membresia, $resultado, $motivo, $sucursalId): Acceso {
            $acceso = $this->registrarAcceso(
                cliente: $cliente,
                membresia: $membresia,
                sucursalId: $sucursalId,
                usuario: $usuario,
                metodo: mb_strtoupper((string) $payload['metodo']),
                resultado: $resultado,
                motivoRechazo: $motivo,
                observaciones: (string) ($payload['observaciones'] ?? '')
            );

            if ($resultado === 'PERMITIDO' && $membresia) {
                $this->descontarAccesoSiAplica($membresia);
            }

            return $acceso;
        });

        $this->loadRelationsForModel($acceso);

        return [
            'resultado' => $resultado,
            'motivo_rechazo' => $resultado === 'PERMITIDO' ? null : $motivo,
            'cliente' => $cliente,
            'membresia' => $membresia,
            'acceso' => $acceso,
            'http_message' => $resultado === 'PERMITIDO' ? 'Acceso permitido.' : 'Acceso denegado.',
        ];
    }

    private function resolveCliente(array $payload): ?Cliente
    {
        if (! empty($payload['cliente_id'])) {
            return Cliente::query()->find((int) $payload['cliente_id']);
        }

        if (empty($payload['codigo'])) {
            return null;
        }

        foreach (['codigo', 'clave', 'folio'] as $column) {
            if (Schema::hasColumn('clientes', $column)) {
                return Cliente::query()->where($column, (string) $payload['codigo'])->first();
            }
        }

        throw new ApiException('No es posible validar por código: no existe una columna de código en clientes.', 422);
    }

    private function evaluateCliente(Cliente $cliente): ?string
    {
        if (Schema::hasColumn('clientes', 'activo') && ! $cliente->activo) {
            return 'CLIENTE_INACTIVO';
        }

        if (! Schema::hasColumn('clientes', 'estatus') || blank($cliente->estatus)) {
            return null;
        }

        $estatus = mb_strtoupper((string) $cliente->estatus);

        return match ($estatus) {
            ClienteEstatus::INACTIVO->value => 'CLIENTE_INACTIVO',
            ClienteEstatus::SUSPENDIDO->value => 'CLIENTE_SUSPENDIDO',
            ClienteEstatus::BLOQUEADO->value => 'CLIENTE_BLOQUEADO',
            default => null,
        };
    }

    private function resolveMembresiaVigente(Cliente $cliente, int $sucursalId): ?Membresia
    {
        $query = Membresia::query()->where('cliente_id', $cliente->id);

        if (Schema::hasColumn('membresias', 'activo')) {
            $query->where('activo', true);
        }

        if (Schema::hasColumn('membresias', 'estatus')) {
            $query->whereIn('estatus', ['ACTIVA', 'VIGENTE']);
        }

        if (Schema::hasColumn('membresias', 'fecha_vencimiento')) {
            $query->whereDate('fecha_vencimiento', '>=', Carbon::today()->toDateString())
                ->orderBy('fecha_vencimiento');
        }

        if (Schema::hasColumn('membresias', 'fecha_fin')) {
            $query->whereDate('fecha_fin', '>=', Carbon::today()->toDateString())
                ->orderBy('fecha_fin');
        }

        if (Schema::hasColumn('membresias', 'sucursal_id')) {
            $query->orderByRaw('CASE WHEN sucursal_id = ? THEN 0 ELSE 1 END', [$sucursalId]);
        }

        return $query->orderByDesc('id')->first();
    }

    private function evaluateMembresia(?Membresia $membresia, int $sucursalId): ?string
    {
        if (! $membresia) {
            return 'SIN_MEMBRESIA';
        }

        if (Schema::hasColumn('membresias', 'estatus')) {
            $estatus = mb_strtoupper((string) $membresia->estatus);

            if ($estatus === 'SUSPENDIDA') {
                return 'MEMBRESIA_SUSPENDIDA';
            }

            if ($estatus === 'CANCELADA') {
                return 'MEMBRESIA_CANCELADA';
            }
        }

        if (Schema::hasColumn('membresias', 'fecha_vencimiento')
            && filled($membresia->fecha_vencimiento)
            && Carbon::parse((string) $membresia->fecha_vencimiento)->isBefore(Carbon::today())) {
            return 'MEMBRESIA_VENCIDA';
        }

        if (Schema::hasColumn('membresias', 'fecha_fin')
            && filled($membresia->fecha_fin)
            && Carbon::parse((string) $membresia->fecha_fin)->isBefore(Carbon::today())) {
            return 'MEMBRESIA_VENCIDA';
        }

        if (Schema::hasColumn('membresias', 'accesos_disponibles') && (int) $membresia->accesos_disponibles <= 0) {
            return 'SIN_ACCESOS_DISPONIBLES';
        }

        if (Schema::hasColumn('membresias', 'sucursal_id')
            && filled($membresia->sucursal_id)
            && (int) $membresia->sucursal_id !== $sucursalId) {
            return 'SUCURSAL_NO_PERMITIDA';
        }

        return null;
    }

    private function assertSucursalActiva(int $sucursalId): void
    {
        $exists = DB::table('sucursales')
            ->where('id', $sucursalId)
            ->when(Schema::hasColumn('sucursales', 'activo'), fn ($query) => $query->where('activo', true))
            ->exists();

        if (! $exists) {
            throw new ApiException('La sucursal seleccionada no existe o está inactiva.', 422);
        }
    }

    private function findClienteOrFail(int $id): Cliente
    {
        $cliente = Cliente::query()->find($id);

        if (! $cliente) {
            throw new ApiException('Cliente no encontrado.', 404);
        }

        return $cliente;
    }

    private function registrarAcceso(
        ?Cliente $cliente,
        ?Membresia $membresia,
        int $sucursalId,
        ?Usuario $usuario,
        string $metodo,
        string $resultado,
        ?string $motivoRechazo,
        string $observaciones,
    ): Acceso {
        $payload = [
            'cliente_id' => $cliente?->id,
            'membresia_id' => $membresia?->id,
            'sucursal_id' => $sucursalId,
            'usuario_id' => $usuario?->id,
            'metodo' => $metodo,
            'resultado' => $resultado,
            'motivo_rechazo' => $resultado === 'DENEGADO' ? $motivoRechazo : null,
            'fecha_acceso' => Carbon::now()->toDateTimeString(),
            'observaciones' => $observaciones !== '' ? $observaciones : null,
            'activo' => true,
        ];

        try {
            /** @var Acceso $acceso */
            $acceso = Acceso::query()->create($this->sanitizePayload($payload));

            return $acceso;
        } catch (QueryException $exception) {
            if ($cliente === null) {
                throw new ApiException('No fue posible registrar el intento denegado sin cliente. La columna cliente_id no admite nulos.', 422);
            }

            throw $exception;
        }
    }

    private function descontarAccesoSiAplica(Membresia $membresia): void
    {
        if (! Schema::hasColumn('membresias', 'accesos_usados')) {
            return;
        }

        $membresia->accesos_usados = (int) $membresia->accesos_usados + 1;

        if (Schema::hasColumn('membresias', 'accesos_disponibles')) {
            $membresia->accesos_disponibles = max((int) $membresia->accesos_disponibles - 1, 0);
        }

        $membresia->save();
    }

    private function loadRelations(Builder $query): void
    {
        $relations = [];

        if (Schema::hasColumn('accesos', 'cliente_id')) {
            $relations[] = 'cliente';
        }

        if (Schema::hasColumn('accesos', 'membresia_id')) {
            $relations[] = 'membresia.plan';
        }

        if (Schema::hasColumn('accesos', 'sucursal_id')) {
            $relations[] = 'sucursal';
        }

        if (Schema::hasColumn('accesos', 'usuario_id')) {
            $relations[] = 'usuario';
        }

        if ($relations !== []) {
            $query->with($relations);
        }
    }

    private function loadRelationsForModel(Model $model): void
    {
        $relations = [];

        if (Schema::hasColumn('accesos', 'cliente_id')) {
            $relations[] = 'cliente';
        }

        if (Schema::hasColumn('accesos', 'membresia_id')) {
            $relations[] = 'membresia.plan';
        }

        if (Schema::hasColumn('accesos', 'sucursal_id')) {
            $relations[] = 'sucursal';
        }

        if (Schema::hasColumn('accesos', 'usuario_id')) {
            $relations[] = 'usuario';
        }

        if ($relations !== []) {
            $model->loadMissing($relations);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        return Arr::only($payload, array_values(array_filter([
            'cliente_id',
            'membresia_id',
            'sucursal_id',
            'usuario_id',
            'dispositivo_id',
            'metodo',
            'resultado',
            'motivo_rechazo',
            'fecha_acceso',
            'observaciones',
            'activo',
        ], static fn (string $column): bool => Schema::hasColumn('accesos', $column))));
    }
}
