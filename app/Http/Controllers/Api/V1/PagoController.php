<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PagoEstatus;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Pagos\CancelarPagoRequest;
use App\Http\Requests\Pagos\StorePagoRequest;
use App\Http\Resources\Pagos\PagoResource;
use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\MetodoPago;
use App\Models\Pago;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PagoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $query = Pago::query();
        $this->loadRelations($query);

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                if (Schema::hasColumn('pagos', 'referencia')) {
                    $builder->orWhere('referencia', 'like', "%{$search}%");
                }

                $builder->orWhereHas('cliente', function (Builder $clienteQuery) use ($search): void {
                    foreach (['nombre', 'apellido_paterno', 'apellido_materno', 'nombre_completo', 'email', 'telefono'] as $column) {
                        if (Schema::hasColumn('clientes', $column)) {
                            $clienteQuery->orWhere($column, 'like', "%{$search}%");
                        }
                    }
                });
            });
        }

        foreach (['cliente_id', 'membresia_id', 'sucursal_id', 'metodo_pago_id', 'usuario_id'] as $filter) {
            if (Schema::hasColumn('pagos', $filter) && $request->filled($filter)) {
                $query->where($filter, (int) $request->query($filter));
            }
        }

        if (Schema::hasColumn('pagos', 'concepto')) {
            $concepto = mb_strtoupper(trim((string) $request->query('concepto', '')));
            if ($concepto !== '' && $concepto !== 'ALL') {
                $query->where('concepto', $concepto);
            }
        }

        if (Schema::hasColumn('pagos', 'estatus')) {
            $estatus = mb_strtoupper(trim((string) $request->query('estatus', $this->paidStatusValue())));
            if ($estatus !== 'ALL') {
                $query->where('estatus', $estatus);
            }
        }

        if (Schema::hasColumn('pagos', 'activo')) {
            $activo = strtolower(trim((string) $request->query('activo', 'true')));
            if ($activo !== 'all') {
                $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);
            }
        }

        if (Schema::hasColumn('pagos', 'fecha_pago')) {
            if ($request->filled('fecha_desde')) {
                $query->whereDate('fecha_pago', '>=', (string) $request->query('fecha_desde'));
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('fecha_pago', '<=', (string) $request->query('fecha_hasta'));
            }

            $query->orderByDesc('fecha_pago');
        }

        $query->orderByDesc('id');

        $paginator = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'message' => 'Pagos obtenidos correctamente.',
            'data' => PagoResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(int $pago): JsonResponse
    {
        $pagoModel = $this->findPagoOrFail($pago);
        $this->loadRelationsForModel($pagoModel);

        return response()->json([
            'message' => 'Pago obtenido correctamente.',
            'data' => new PagoResource($pagoModel),
        ]);
    }

    public function porCliente(Request $request, int $cliente): JsonResponse
    {
        $this->findClienteOrFail($cliente);

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $query = Pago::query()->where('cliente_id', $cliente);
        $this->loadRelations($query);

        if (Schema::hasColumn('pagos', 'estatus')) {
            $estatus = mb_strtoupper(trim((string) $request->query('estatus', $this->paidStatusValue())));
            if ($estatus !== 'ALL') {
                $query->where('estatus', $estatus);
            }
        }

        if (Schema::hasColumn('pagos', 'concepto')) {
            $concepto = mb_strtoupper(trim((string) $request->query('concepto', '')));
            if ($concepto !== '' && $concepto !== 'ALL') {
                $query->where('concepto', $concepto);
            }
        }

        if (Schema::hasColumn('pagos', 'activo')) {
            $activo = strtolower(trim((string) $request->query('activo', 'true')));
            if ($activo !== 'all') {
                $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);
            }
        }

        if (Schema::hasColumn('pagos', 'fecha_pago')) {
            if ($request->filled('fecha_desde')) {
                $query->whereDate('fecha_pago', '>=', (string) $request->query('fecha_desde'));
            }

            if ($request->filled('fecha_hasta')) {
                $query->whereDate('fecha_pago', '<=', (string) $request->query('fecha_hasta'));
            }

            $query->orderByDesc('fecha_pago');
        }

        $query->orderByDesc('id');

        $paginator = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'message' => 'Pagos del cliente obtenidos correctamente.',
            'data' => PagoResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StorePagoRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $cliente = $this->findClienteOrFail((int) $payload['cliente_id']);
        $this->assertClienteActivo($cliente);

        $metodoPago = $this->findMetodoPagoOrFail((int) $payload['metodo_pago_id']);
        $this->assertMetodoPagoActivo($metodoPago);

        if ($metodoPago->requiere_referencia && blank($payload['referencia'] ?? null)) {
            throw new ApiException('El método de pago seleccionado requiere referencia.', 422);
        }

        if (Schema::hasColumn('pagos', 'membresia_id') && ! empty($payload['membresia_id'])) {
            $membresia = $this->findMembresiaOrFail((int) $payload['membresia_id']);
            $this->assertMembresiaPerteneceCliente($membresia, $cliente);
            $this->assertMembresiaNoCancelada($membresia);
        }

        if (Schema::hasColumn('pagos', 'sucursal_id')) {
            $payload['sucursal_id'] = $payload['sucursal_id']
                ?? (Schema::hasColumn('clientes', 'sucursal_id') ? $cliente->sucursal_id : null);

            if (! empty($payload['sucursal_id'])) {
                $this->assertSucursalActiva((int) $payload['sucursal_id']);
            }
        }

        if (Schema::hasColumn('pagos', 'fecha_pago')) {
            $payload['fecha_pago'] = $payload['fecha_pago'] ?? Carbon::now()->toDateTimeString();
        }

        if (Schema::hasColumn('pagos', 'usuario_id')) {
            $payload['usuario_id'] = $this->resolveUsuario()->id;
        }

        if (Schema::hasColumn('pagos', 'estatus')) {
            $payload['estatus'] = $this->paidStatusValue();
        }

        if (Schema::hasColumn('pagos', 'activo')) {
            $payload['activo'] = true;
        }

        $pago = DB::transaction(function () use ($payload): Pago {
            return Pago::query()->create($this->sanitizePayload($payload));
        });

        $this->loadRelationsForModel($pago);

        // TODO: Registrar pago en bitácora/auditoría cuando el módulo esté disponible.

        return response()->json([
            'message' => 'Pago registrado correctamente.',
            'data' => new PagoResource($pago),
        ], 201);
    }

    public function cancelar(CancelarPagoRequest $request, int $pago): JsonResponse
    {
        $pagoModel = $this->findPagoOrFail($pago);

        if (($pagoModel->estatus ?? null) === $this->cancelledStatusValue()) {
            throw new ApiException('El pago ya está cancelado.', 422);
        }

        $payload = [];

        if (Schema::hasColumn('pagos', 'estatus')) {
            $payload['estatus'] = $this->cancelledStatusValue();
        }

        if (Schema::hasColumn('pagos', 'motivo_cancelacion')) {
            $payload['motivo_cancelacion'] = trim((string) $request->validated('motivo'));
        }

        if (Schema::hasColumn('pagos', 'cancelado_at')) {
            $payload['cancelado_at'] = Carbon::now()->toDateTimeString();
        }

        if (Schema::hasColumn('pagos', 'cancelado_por_usuario_id')) {
            $payload['cancelado_por_usuario_id'] = $this->resolveUsuario()->id;
        }

        if (Schema::hasColumn('pagos', 'activo')) {
            $payload['activo'] = false;
        }

        DB::transaction(function () use ($pagoModel, $payload): void {
            $pagoModel->forceFill($this->sanitizePayload($payload))->save();
        });

        $this->loadRelationsForModel($pagoModel);

        // TODO: Registrar cancelación de pago en bitácora/auditoría cuando el módulo esté disponible.

        return response()->json([
            'message' => 'Pago cancelado correctamente.',
            'data' => new PagoResource($pagoModel),
        ]);
    }

    private function findPagoOrFail(int $id): Pago
    {
        $pago = Pago::query()->find($id);

        if (! $pago) {
            throw new ApiException('Pago no encontrado.', 404);
        }

        return $pago;
    }

    private function findClienteOrFail(int $id): Cliente
    {
        $cliente = Cliente::query()->find($id);

        if (! $cliente) {
            throw new ApiException('Cliente no encontrado.', 404);
        }

        return $cliente;
    }

    private function findMetodoPagoOrFail(int $id): MetodoPago
    {
        $metodoPago = MetodoPago::query()->find($id);

        if (! $metodoPago) {
            throw new ApiException('Método de pago no encontrado.', 404);
        }

        return $metodoPago;
    }

    private function findMembresiaOrFail(int $id): Membresia
    {
        $membresia = Membresia::query()->find($id);

        if (! $membresia) {
            throw new ApiException('Membresía no encontrada.', 404);
        }

        return $membresia;
    }

    private function assertClienteActivo(Cliente $cliente): void
    {
        if (Schema::hasColumn('clientes', 'activo') && ! $cliente->activo) {
            throw new ApiException('El cliente está inactivo.', 422);
        }
    }

    private function assertMetodoPagoActivo(MetodoPago $metodoPago): void
    {
        if (Schema::hasColumn('metodos_pago', 'activo') && ! $metodoPago->activo) {
            throw new ApiException('El método de pago está inactivo.', 422);
        }
    }

    private function assertMembresiaPerteneceCliente(Membresia $membresia, Cliente $cliente): void
    {
        if ((int) $membresia->cliente_id !== (int) $cliente->id) {
            throw new ApiException('La membresía no pertenece al cliente seleccionado.', 422);
        }
    }

    private function assertMembresiaNoCancelada(Membresia $membresia): void
    {
        if (Schema::hasColumn('membresias', 'estatus') && ($membresia->estatus ?? null) === 'CANCELADA') {
            throw new ApiException('No se puede registrar pago para una membresía cancelada.', 422);
        }
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

    private function resolveUsuario(): Usuario
    {
        $usuario = request()->user();

        if (! $usuario instanceof Usuario) {
            throw new ApiException('No autenticado.', 401);
        }

        return $usuario;
    }

    private function loadRelations(Builder $query): void
    {
        $with = ['cliente:id,nombre,apellido_paterno,apellido_materno,nombre_completo,telefono,email'];

        if (Schema::hasColumn('pagos', 'membresia_id')) {
            $with[] = 'membresia:id,plan_id,fecha_inicio,fecha_vencimiento,estatus';
            $with[] = 'membresia.plan:id,nombre,clave';
        }

        if (Schema::hasColumn('pagos', 'sucursal_id')) {
            $with[] = 'sucursal:id,nombre,clave';
        }

        $with[] = 'metodoPago:id,nombre,clave';

        if (Schema::hasColumn('pagos', 'usuario_id')) {
            $with[] = 'usuario:id,nombre,apellido_paterno,apellido_materno,username';
        }

        $query->with($with);
    }

    private function loadRelationsForModel(Pago $pago): void
    {
        $query = Pago::query()->whereKey($pago->id);
        $this->loadRelations($query);

        $fresh = $query->first();
        if ($fresh) {
            $pago->setRelations($fresh->getRelations());
            $pago->setRawAttributes($fresh->getAttributes(), true);
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        return collect($payload)
            ->only(array_values(array_filter([
                'cliente_id',
                'membresia_id',
                'sucursal_id',
                'metodo_pago_id',
                'usuario_id',
                'concepto',
                'monto',
                'fecha_pago',
                'referencia',
                'observaciones',
                'estatus',
                'motivo_cancelacion',
                'cancelado_at',
                'cancelado_por_usuario_id',
                'activo',
            ], static fn (string $column): bool => Schema::hasColumn('pagos', $column))))
            ->all();
    }

    private function paidStatusValue(): string
    {
        return enum_exists(PagoEstatus::class) && defined(PagoEstatus::class.'::APLICADO')
            ? PagoEstatus::APLICADO->value
            : 'PAGADO';
    }

    private function cancelledStatusValue(): string
    {
        return enum_exists(PagoEstatus::class) && defined(PagoEstatus::class.'::CANCELADO')
            ? PagoEstatus::CANCELADO->value
            : 'CANCELADO';
    }
}
