<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ClienteEstatus;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Membresias\CambiarEstadoMembresiaRequest;
use App\Http\Requests\Membresias\RenovarMembresiaRequest;
use App\Http\Requests\Membresias\StoreMembresiaRequest;
use App\Http\Requests\Membresias\UpdateMembresiaRequest;
use App\Http\Resources\Membresias\MembresiaResource;
use App\Models\Cliente;
use App\Models\Membresia;
use App\Models\Plan;
use App\Services\AuditoriaService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MembresiaController extends Controller
{
    public function __construct(private readonly AuditoriaService $auditoriaService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $query = Membresia::query();
        $this->loadRelations($query);

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->whereHas('cliente', function ($clienteQuery) use ($search): void {
                    foreach (['nombre', 'apellido_paterno', 'apellido_materno', 'nombre_completo', 'email', 'telefono'] as $column) {
                        if (Schema::hasColumn('clientes', $column)) {
                            $clienteQuery->orWhere($column, 'like', "%{$search}%");
                        }
                    }
                });

                $builder->orWhereHas('plan', function ($planQuery) use ($search): void {
                    foreach (['nombre', 'clave'] as $column) {
                        if (Schema::hasColumn('planes', $column)) {
                            $planQuery->orWhere($column, 'like', "%{$search}%");
                        }
                    }
                });
            });
        }

        foreach (['cliente_id', 'plan_id', 'sucursal_id'] as $filter) {
            if (Schema::hasColumn('membresias', $filter) && $request->filled($filter)) {
                $query->where($filter, (int) $request->query($filter));
            }
        }

        if (Schema::hasColumn('membresias', 'estatus')) {
            $estatus = mb_strtoupper(trim((string) $request->query('estatus', '')));
            if ($estatus !== '' && $estatus !== 'ALL') {
                $query->where('estatus', $estatus);
            }
        }

        if (Schema::hasColumn('membresias', 'activo')) {
            $activo = strtolower(trim((string) $request->query('activo', 'true')));
            if ($activo !== 'all') {
                $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);
            }
        }

        if (Schema::hasColumn('membresias', 'fecha_inicio')) {
            if ($request->filled('fecha_inicio_desde')) {
                $query->whereDate('fecha_inicio', '>=', (string) $request->query('fecha_inicio_desde'));
            }

            if ($request->filled('fecha_inicio_hasta')) {
                $query->whereDate('fecha_inicio', '<=', (string) $request->query('fecha_inicio_hasta'));
            }
        }

        if (Schema::hasColumn('membresias', 'fecha_vencimiento')) {
            if ($request->filled('fecha_vencimiento_desde')) {
                $query->whereDate('fecha_vencimiento', '>=', (string) $request->query('fecha_vencimiento_desde'));
            }

            if ($request->filled('fecha_vencimiento_hasta')) {
                $query->whereDate('fecha_vencimiento', '<=', (string) $request->query('fecha_vencimiento_hasta'));
            }

            if ($request->boolean('vigentes')) {
                $query->whereDate('fecha_vencimiento', '>=', Carbon::today()->toDateString());
            }

            if ($request->boolean('vencidas')) {
                $query->whereDate('fecha_vencimiento', '<', Carbon::today()->toDateString());
            }
        }

        if (Schema::hasColumn('membresias', 'fecha_vencimiento')) {
            $query->orderByDesc('fecha_vencimiento');
        } else {
            $query->orderByDesc('id');
        }

        $paginator = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'message' => 'Membresías obtenidas correctamente.',
            'data' => MembresiaResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(int $membresia): JsonResponse
    {
        $membresiaModel = $this->findMembresiaOrFail($membresia);
        $this->loadRelationsForModel($membresiaModel);

        return response()->json([
            'message' => 'Membresía obtenida correctamente.',
            'data' => new MembresiaResource($membresiaModel),
        ]);
    }

    public function porCliente(Request $request, int $cliente): JsonResponse
    {
        $this->findClienteOrFail($cliente);

        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $query = Membresia::query()->where('cliente_id', $cliente);
        $this->loadRelations($query);

        if (Schema::hasColumn('membresias', 'estatus')) {
            $estatus = mb_strtoupper(trim((string) $request->query('estatus', '')));
            if ($estatus !== '' && $estatus !== 'ALL') {
                $query->where('estatus', $estatus);
            }
        }

        if (Schema::hasColumn('membresias', 'activo')) {
            $activo = strtolower(trim((string) $request->query('activo', 'all')));
            if ($activo !== 'all') {
                $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);
            }
        }

        $query->orderByDesc('id');

        $paginator = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'message' => 'Membresías del cliente obtenidas correctamente.',
            'data' => MembresiaResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function store(StoreMembresiaRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $cliente = $this->findClienteOrFail((int) $payload['cliente_id']);
        $this->assertClienteDisponible($cliente);

        $plan = $this->findPlanOrFail((int) $payload['plan_id']);
        $this->assertPlanActivo($plan);

        if (Schema::hasColumn('membresias', 'sucursal_id')) {
            $payload['sucursal_id'] = $payload['sucursal_id']
                ?? (Schema::hasColumn('clientes', 'sucursal_id') ? $cliente->sucursal_id : null);

            if (! empty($payload['sucursal_id'])) {
                $this->assertSucursalActiva((int) $payload['sucursal_id']);
            }
        }

        if (Schema::hasColumn('membresias', 'fecha_inicio')) {
            $payload['fecha_inicio'] = $payload['fecha_inicio'] ?? Carbon::today()->toDateString();
        }

        $payload = $this->setVencimientoAndPrecioDefaults($payload, $plan);
        $payload = $this->setAccesosDefaults($payload, $plan);

        if (Schema::hasColumn('membresias', 'estatus')) {
            $payload['estatus'] = 'ACTIVA';
        }

        if (Schema::hasColumn('membresias', 'activo')) {
            $payload['activo'] = $payload['activo'] ?? true;
        }

        $this->assertNoMembresiaActivaVigente((int) $payload['cliente_id']);

        $membresia = Membresia::query()->create($this->sanitizePayload($payload));
        $this->loadRelationsForModel($membresia);

        $this->auditoriaService->registrar(
            accion: 'CREAR',
            entidad: 'Membresia',
            entidadId: $membresia->id,
            descripcion: 'Membresía creada correctamente.',
            datosDespues: $membresia->toArray(),
            sucursalId: (int) ($membresia->sucursal_id ?? 0) ?: null,
        );

        return response()->json([
            'message' => 'Membresía creada correctamente.',
            'data' => new MembresiaResource($membresia),
        ], 201);
    }

    public function update(UpdateMembresiaRequest $request, int $membresia): JsonResponse
    {
        $membresiaModel = $this->findMembresiaOrFail($membresia);
        $valoresAnteriores = $membresiaModel->toArray();
        $payload = $request->validated();

        if (array_key_exists('plan_id', $payload)) {
            $plan = $this->findPlanOrFail((int) $payload['plan_id']);
            $this->assertPlanActivo($plan);

            if (Schema::hasColumn('membresias', 'precio') && ! array_key_exists('precio', $payload) && Schema::hasColumn('planes', 'precio')) {
                $payload['precio'] = $plan->precio;
            }

            if (Schema::hasColumn('membresias', 'fecha_vencimiento') && ! array_key_exists('fecha_vencimiento', $payload) && Schema::hasColumn('planes', 'duracion_dias') && filled($plan->duracion_dias)) {
                $inicio = $payload['fecha_inicio']
                    ?? (Schema::hasColumn('membresias', 'fecha_inicio') ? $membresiaModel->fecha_inicio : Carbon::today()->toDateString());

                if (filled($inicio)) {
                    $payload['fecha_vencimiento'] = Carbon::parse($inicio)->addDays((int) $plan->duracion_dias)->toDateString();
                }
            }

            $payload = $this->setAccesosDefaults($payload, $plan, true);
        }

        if (array_key_exists('sucursal_id', $payload) && ! empty($payload['sucursal_id'])) {
            $this->assertSucursalActiva((int) $payload['sucursal_id']);
        }

        if (Schema::hasColumn('membresias', 'accesos_totales') && Schema::hasColumn('membresias', 'accesos_usados')) {
            $totales = (int) ($payload['accesos_totales'] ?? $membresiaModel->accesos_totales ?? 0);
            $usados = (int) ($payload['accesos_usados'] ?? $membresiaModel->accesos_usados ?? 0);

            if ($totales > 0 && $usados > $totales) {
                throw new ApiException('Los accesos usados no pueden exceder los accesos totales.', 422);
            }

            if (Schema::hasColumn('membresias', 'accesos_disponibles') && $totales > 0) {
                $payload['accesos_disponibles'] = max($totales - $usados, 0);
            }
        }

        $membresiaModel->fill($this->sanitizePayload($payload));
        $membresiaModel->save();
        $this->loadRelationsForModel($membresiaModel);

        $this->auditoriaService->registrar(
            accion: 'EDITAR',
            entidad: 'Membresia',
            entidadId: $membresiaModel->id,
            descripcion: 'Membresía actualizada correctamente.',
            datosAntes: $valoresAnteriores,
            datosDespues: $membresiaModel->fresh()?->toArray(),
            sucursalId: (int) ($membresiaModel->sucursal_id ?? 0) ?: null,
        );

        return response()->json([
            'message' => 'Membresía actualizada correctamente.',
            'data' => new MembresiaResource($membresiaModel),
        ]);
    }

    public function renovar(RenovarMembresiaRequest $request, int $membresia): JsonResponse
    {
        $membresiaModel = $this->findMembresiaOrFail($membresia);
        $cliente = $this->findClienteOrFail((int) $membresiaModel->cliente_id);
        $this->assertClienteDisponible($cliente);

        $payload = $request->validated();
        $planId = (int) ($payload['plan_id'] ?? $membresiaModel->plan_id);
        $plan = $this->findPlanOrFail($planId);
        $this->assertPlanActivo($plan);

        $fechaInicio = $payload['fecha_inicio'] ?? $this->resolveFechaInicioRenovacion($membresiaModel);
        $fechaInicioCarbon = Carbon::parse($fechaInicio);

        $this->assertNoRenovacionDuplicada((int) $membresiaModel->cliente_id, $planId, $fechaInicioCarbon);

        $nuevoPayload = [
            'cliente_id' => $membresiaModel->cliente_id,
            'plan_id' => $planId,
        ];

        if (Schema::hasColumn('membresias', 'sucursal_id')) {
            $nuevoPayload['sucursal_id'] = $membresiaModel->sucursal_id;
        }

        if (Schema::hasColumn('membresias', 'fecha_inicio')) {
            $nuevoPayload['fecha_inicio'] = $fechaInicioCarbon->toDateString();
        }

        if (Schema::hasColumn('membresias', 'observaciones') && array_key_exists('observaciones', $payload)) {
            $nuevoPayload['observaciones'] = $payload['observaciones'];
        }

        if (Schema::hasColumn('membresias', 'precio')) {
            $nuevoPayload['precio'] = $payload['precio'] ?? (Schema::hasColumn('planes', 'precio') ? $plan->precio : null);
        }

        $nuevoPayload = $this->setVencimientoAndPrecioDefaults($nuevoPayload, $plan);
        $nuevoPayload = $this->setAccesosDefaults($nuevoPayload, $plan);

        if (Schema::hasColumn('membresias', 'estatus')) {
            $nuevoPayload['estatus'] = 'ACTIVA';
        }

        if (Schema::hasColumn('membresias', 'activo')) {
            $nuevoPayload['activo'] = true;
        }

        $nuevaMembresia = DB::transaction(function () use ($membresiaModel, $nuevoPayload): Membresia {
            if (Schema::hasColumn('membresias', 'estatus')) {
                $membresiaModel->forceFill(['estatus' => 'RENOVADA'])->save();
            }

            if (Schema::hasColumn('membresias', 'activo')) {
                $membresiaModel->forceFill(['activo' => false])->save();
            }

            return Membresia::query()->create($this->sanitizePayload($nuevoPayload));
        });

        $this->loadRelationsForModel($nuevaMembresia);

        $this->auditoriaService->registrar(
            accion: 'RENOVAR_MEMBRESIA',
            entidad: 'Membresia',
            entidadId: $nuevaMembresia->id,
            descripcion: 'Membresía renovada correctamente.',
            datosAntes: $membresiaModel->toArray(),
            datosDespues: $nuevaMembresia->toArray(),
            sucursalId: (int) ($nuevaMembresia->sucursal_id ?? 0) ?: null,
        );

        return response()->json([
            'message' => 'Membresía renovada correctamente.',
            'data' => [
                'membresia_anterior' => [
                    'id' => $membresiaModel->id,
                    'estatus' => Schema::hasColumn('membresias', 'estatus') ? $membresiaModel->estatus : null,
                ],
                'membresia_nueva' => new MembresiaResource($nuevaMembresia),
            ],
        ], 201);
    }

    public function suspender(CambiarEstadoMembresiaRequest $request, int $membresia): JsonResponse
    {
        $membresiaModel = $this->findMembresiaOrFail($membresia);
        $this->assertCanChangeStatus($membresiaModel);

        if (($membresiaModel->estatus ?? null) === 'CANCELADA') {
            throw new ApiException('No se puede suspender una membresía cancelada.', 422);
        }

        if (($membresiaModel->estatus ?? null) === 'SUSPENDIDA') {
            throw new ApiException('La membresía ya está suspendida.', 422);
        }

        $payload = ['estatus' => 'SUSPENDIDA'];
        if (Schema::hasColumn('membresias', 'observaciones') && $request->filled('motivo')) {
            $payload['observaciones'] = trim((string) $request->input('motivo'));
        }

        $valoresAnteriores = $membresiaModel->toArray();
        $membresiaModel->forceFill($this->sanitizePayload($payload))->save();

        $this->auditoriaService->registrar(
            accion: 'CAMBIAR_ESTATUS',
            entidad: 'Membresia',
            entidadId: $membresiaModel->id,
            descripcion: 'Membresía suspendida correctamente.',
            datosAntes: $valoresAnteriores,
            datosDespues: array_merge(
                ['estatus' => $membresiaModel->estatus],
                $request->filled('motivo') ? ['motivo' => trim((string) $request->input('motivo'))] : [],
            ),
            sucursalId: (int) ($membresiaModel->sucursal_id ?? 0) ?: null,
        );

        return response()->json([
            'message' => 'Membresía suspendida correctamente.',
            'data' => new MembresiaResource($membresiaModel),
        ]);
    }

    public function cancelar(CambiarEstadoMembresiaRequest $request, int $membresia): JsonResponse
    {
        $membresiaModel = $this->findMembresiaOrFail($membresia);
        $this->assertCanChangeStatus($membresiaModel);

        if (($membresiaModel->estatus ?? null) === 'CANCELADA') {
            throw new ApiException('La membresía ya está cancelada.', 422);
        }

        $payload = ['estatus' => 'CANCELADA'];

        if (Schema::hasColumn('membresias', 'activo')) {
            $payload['activo'] = false;
        }

        if (Schema::hasColumn('membresias', 'observaciones') && $request->filled('motivo')) {
            $payload['observaciones'] = trim((string) $request->input('motivo'));
        }

        $valoresAnteriores = $membresiaModel->toArray();
        $membresiaModel->forceFill($this->sanitizePayload($payload))->save();

        $this->auditoriaService->registrar(
            accion: 'CAMBIAR_ESTATUS',
            entidad: 'Membresia',
            entidadId: $membresiaModel->id,
            descripcion: 'Membresía cancelada correctamente.',
            datosAntes: $valoresAnteriores,
            datosDespues: array_merge(
                ['estatus' => $membresiaModel->estatus],
                $request->filled('motivo') ? ['motivo' => trim((string) $request->input('motivo'))] : [],
            ),
            sucursalId: (int) ($membresiaModel->sucursal_id ?? 0) ?: null,
        );

        return response()->json([
            'message' => 'Membresía cancelada correctamente.',
            'data' => new MembresiaResource($membresiaModel),
        ]);
    }

    public function reactivar(CambiarEstadoMembresiaRequest $request, int $membresia): JsonResponse
    {
        $membresiaModel = $this->findMembresiaOrFail($membresia);
        $this->assertCanChangeStatus($membresiaModel);

        if (($membresiaModel->estatus ?? null) !== 'SUSPENDIDA') {
            throw new ApiException('Solo se pueden reactivar membresías suspendidas.', 422);
        }

        $cliente = $this->findClienteOrFail((int) $membresiaModel->cliente_id);
        $this->assertClienteDisponible($cliente);

        if (Schema::hasColumn('membresias', 'fecha_vencimiento')
            && filled($membresiaModel->fecha_vencimiento)
            && Carbon::parse($membresiaModel->fecha_vencimiento)->isPast()) {
            throw new ApiException('La membresía ya venció y debe renovarse.', 422);
        }

        $payload = ['estatus' => 'ACTIVA'];
        if (Schema::hasColumn('membresias', 'activo')) {
            $payload['activo'] = true;
        }

        if (Schema::hasColumn('membresias', 'observaciones') && $request->filled('motivo')) {
            $payload['observaciones'] = trim((string) $request->input('motivo'));
        }

        $valoresAnteriores = $membresiaModel->toArray();
        $membresiaModel->forceFill($this->sanitizePayload($payload))->save();

        $this->auditoriaService->registrar(
            accion: 'CAMBIAR_ESTATUS',
            entidad: 'Membresia',
            entidadId: $membresiaModel->id,
            descripcion: 'Membresía reactivada correctamente.',
            datosAntes: $valoresAnteriores,
            datosDespues: array_merge(
                ['estatus' => $membresiaModel->estatus],
                $request->filled('motivo') ? ['motivo' => trim((string) $request->input('motivo'))] : [],
            ),
            sucursalId: (int) ($membresiaModel->sucursal_id ?? 0) ?: null,
        );

        return response()->json([
            'message' => 'Membresía reactivada correctamente.',
            'data' => new MembresiaResource($membresiaModel),
        ]);
    }

    private function findMembresiaOrFail(int $id): Membresia
    {
        $membresia = Membresia::query()->find($id);

        if (! $membresia) {
            throw new ApiException('Membresía no encontrada.', 404);
        }

        return $membresia;
    }

    private function findClienteOrFail(int $id): Cliente
    {
        $cliente = Cliente::query()->find($id);

        if (! $cliente) {
            throw new ApiException('Cliente no encontrado.', 404);
        }

        return $cliente;
    }

    private function findPlanOrFail(int $id): Plan
    {
        $plan = Plan::query()->find($id);

        if (! $plan) {
            throw new ApiException('Plan no encontrado.', 404);
        }

        return $plan;
    }

    private function assertClienteDisponible(Cliente $cliente): void
    {
        if (Schema::hasColumn('clientes', 'activo') && ! $cliente->activo) {
            throw new ApiException('El cliente está inactivo.', 422);
        }

        if (Schema::hasColumn('clientes', 'estatus') && in_array($cliente->estatus, [ClienteEstatus::BLOQUEADO->value, ClienteEstatus::SUSPENDIDO->value], true)) {
            throw new ApiException('El cliente no es elegible para una membresía por su estatus actual.', 422);
        }
    }

    private function assertPlanActivo(Plan $plan): void
    {
        if (Schema::hasColumn('planes', 'activo') && ! $plan->activo) {
            throw new ApiException('El plan seleccionado está inactivo.', 422);
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

    private function assertNoMembresiaActivaVigente(int $clienteId): void
    {
        $query = Membresia::query()->where('cliente_id', $clienteId);

        if (Schema::hasColumn('membresias', 'activo')) {
            $query->where('activo', true);
        }

        if (Schema::hasColumn('membresias', 'estatus')) {
            $query->where('estatus', 'ACTIVA');
        }

        if (Schema::hasColumn('membresias', 'fecha_vencimiento')) {
            $query->where(function ($builder): void {
                $builder->whereNull('fecha_vencimiento')
                    ->orWhereDate('fecha_vencimiento', '>=', Carbon::today()->toDateString());
            });
        }

        if ($query->exists()) {
            throw new ApiException('El cliente ya cuenta con una membresía activa y vigente.', 422);
        }
    }

    private function assertNoRenovacionDuplicada(int $clienteId, int $planId, Carbon $fechaInicio): void
    {
        $query = Membresia::query()
            ->where('cliente_id', $clienteId)
            ->where('plan_id', $planId);

        if (Schema::hasColumn('membresias', 'activo')) {
            $query->where('activo', true);
        }

        if (Schema::hasColumn('membresias', 'estatus')) {
            $query->where('estatus', 'ACTIVA');
        }

        if (Schema::hasColumn('membresias', 'fecha_inicio')) {
            $query->whereDate('fecha_inicio', '>=', $fechaInicio->toDateString());
        }

        if ($query->exists()) {
            throw new ApiException('Ya existe una membresía activa futura para el mismo cliente y plan.', 422);
        }
    }

    private function assertCanChangeStatus(Membresia $membresia): void
    {
        if (! Schema::hasColumn('membresias', 'estatus')) {
            throw new ApiException('La columna estatus no está disponible en membresías.', 422);
        }
    }

    private function resolveFechaInicioRenovacion(Membresia $membresia): string
    {
        if (! Schema::hasColumn('membresias', 'fecha_vencimiento') || blank($membresia->fecha_vencimiento)) {
            return Carbon::today()->toDateString();
        }

        $vencimiento = Carbon::parse($membresia->fecha_vencimiento);

        if ($vencimiento->greaterThanOrEqualTo(Carbon::today())) {
            return $vencimiento->addDay()->toDateString();
        }

        return Carbon::today()->toDateString();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function setVencimientoAndPrecioDefaults(array $payload, Plan $plan): array
    {
        if (Schema::hasColumn('membresias', 'fecha_vencimiento') && ! array_key_exists('fecha_vencimiento', $payload)) {
            if (Schema::hasColumn('planes', 'duracion_dias') && filled($plan->duracion_dias)) {
                $inicio = $payload['fecha_inicio'] ?? Carbon::today()->toDateString();
                $payload['fecha_vencimiento'] = Carbon::parse($inicio)->addDays((int) $plan->duracion_dias)->toDateString();
            }
        }

        if (Schema::hasColumn('membresias', 'precio') && ! array_key_exists('precio', $payload) && Schema::hasColumn('planes', 'precio')) {
            $payload['precio'] = $plan->precio;
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function setAccesosDefaults(array $payload, Plan $plan, bool $override = false): array
    {
        if (! Schema::hasColumn('planes', 'tipo_plan') || ! Schema::hasColumn('planes', 'accesos_incluidos')) {
            return $payload;
        }

        if (! in_array((string) $plan->tipo_plan, ['ACCESOS', 'MIXTO'], true)) {
            return $payload;
        }

        if (Schema::hasColumn('membresias', 'accesos_totales') && ($override || ! array_key_exists('accesos_totales', $payload))) {
            $payload['accesos_totales'] = $plan->accesos_incluidos;
        }

        if (Schema::hasColumn('membresias', 'accesos_usados') && ($override || ! array_key_exists('accesos_usados', $payload))) {
            $payload['accesos_usados'] = 0;
        }

        if (Schema::hasColumn('membresias', 'accesos_disponibles') && ($override || ! array_key_exists('accesos_disponibles', $payload))) {
            $payload['accesos_disponibles'] = $plan->accesos_incluidos;
        }

        return $payload;
    }

    private function loadRelations($query): void
    {
        $clienteColumns = ['id'];
        foreach (['nombre_completo', 'nombre', 'apellido_paterno', 'apellido_materno', 'telefono', 'email', 'estatus'] as $column) {
            if (Schema::hasColumn('clientes', $column)) {
                $clienteColumns[] = $column;
            }
        }

        $planColumns = ['id'];
        foreach (['nombre', 'clave', 'precio'] as $column) {
            if (Schema::hasColumn('planes', $column)) {
                $planColumns[] = $column;
            }
        }

        $with = [
            'cliente:'.implode(',', array_unique($clienteColumns)),
            'plan:'.implode(',', array_unique($planColumns)),
        ];

        if (Schema::hasColumn('membresias', 'sucursal_id')) {
            $with[] = 'sucursal:id,nombre,clave';
        }

        $query->with($with);
    }

    private function loadRelationsForModel(Membresia $membresia): void
    {
        $clienteColumns = ['id'];
        foreach (['nombre_completo', 'nombre', 'apellido_paterno', 'apellido_materno', 'telefono', 'email', 'estatus'] as $column) {
            if (Schema::hasColumn('clientes', $column)) {
                $clienteColumns[] = $column;
            }
        }

        $planColumns = ['id'];
        foreach (['nombre', 'clave', 'precio'] as $column) {
            if (Schema::hasColumn('planes', $column)) {
                $planColumns[] = $column;
            }
        }

        $relations = [
            'cliente:'.implode(',', array_unique($clienteColumns)),
            'plan:'.implode(',', array_unique($planColumns)),
        ];

        if (Schema::hasColumn('membresias', 'sucursal_id')) {
            $relations[] = 'sucursal:id,nombre,clave';
        }

        $membresia->loadMissing($relations);
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
                'plan_id',
                'sucursal_id',
                'fecha_inicio',
                'fecha_fin',
                'fecha_vencimiento',
                'estatus',
                'accesos_totales',
                'accesos_usados',
                'accesos_disponibles',
                'precio',
                'observaciones',
                'activo',
            ], static fn (string $column): bool => Schema::hasColumn('membresias', $column))))
            ->all();
    }
}
