<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ClienteEstatus;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Clientes\CambiarEstatusClienteRequest;
use App\Http\Requests\Clientes\StoreClienteRequest;
use App\Http\Requests\Clientes\UpdateClienteRequest;
use App\Http\Resources\Clientes\ClienteResource;
use App\Models\Cliente;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ClienteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $query = Cliente::query();

        if (Schema::hasColumn('clientes', 'sucursal_id')) {
            $query->with('sucursal:id,nombre,clave');
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                foreach (['nombre', 'apellido_paterno', 'apellido_materno', 'nombre_completo', 'email', 'telefono'] as $column) {
                    if (Schema::hasColumn('clientes', $column)) {
                        $builder->orWhere($column, 'like', "%{$search}%");
                    }
                }
            });
        }

        if (Schema::hasColumn('clientes', 'sucursal_id') && $request->filled('sucursal_id')) {
            $query->where('sucursal_id', (int) $request->query('sucursal_id'));
        }

        if (Schema::hasColumn('clientes', 'estatus')) {
            $estatus = mb_strtoupper(trim((string) $request->query('estatus', '')));
            if ($estatus !== '' && $estatus !== 'ALL') {
                $query->where('estatus', $estatus);
            }
        }

        $activo = strtolower(trim((string) $request->query('activo', 'true')));
        if ($activo !== 'all' && Schema::hasColumn('clientes', 'activo')) {
            $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);
        }

        if (Schema::hasColumn('clientes', 'nombre')) {
            $query->orderBy('nombre');
        } else {
            $query->orderByDesc('id');
        }

        $paginator = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'message' => 'Clientes obtenidos correctamente.',
            'data' => ClienteResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(int $cliente): JsonResponse
    {
        $clienteModel = $this->findClienteOrFail($cliente);

        if (Schema::hasColumn('clientes', 'sucursal_id')) {
            $clienteModel->loadMissing('sucursal:id,nombre,clave');
        }

        return response()->json([
            'message' => 'Cliente obtenido correctamente.',
            'data' => new ClienteResource($clienteModel),
        ]);
    }

    public function store(StoreClienteRequest $request): JsonResponse
    {
        $payload = $this->sanitizePayload($request->validated());

        if (Schema::hasColumn('clientes', 'fecha_inscripcion')) {
            $payload['fecha_inscripcion'] = $payload['fecha_inscripcion'] ?? Carbon::today()->toDateString();
        }

        if (Schema::hasColumn('clientes', 'estatus')) {
            $payload['estatus'] = $payload['estatus'] ?? ClienteEstatus::ACTIVO->value;
        }

        if (Schema::hasColumn('clientes', 'activo')) {
            $payload['activo'] = $payload['activo'] ?? true;
        }

        $cliente = Cliente::query()->create($payload);

        if (Schema::hasColumn('clientes', 'sucursal_id')) {
            $cliente->loadMissing('sucursal:id,nombre,clave');
        }

        // TODO: Registrar en bitácora/auditoría cuando el módulo esté disponible.

        return response()->json([
            'message' => 'Cliente creado correctamente.',
            'data' => new ClienteResource($cliente),
        ], 201);
    }

    public function update(UpdateClienteRequest $request, int $cliente): JsonResponse
    {
        $clienteModel = $this->findClienteOrFail($cliente);
        $clienteModel->fill($this->sanitizePayload($request->validated()));
        $clienteModel->save();

        if (Schema::hasColumn('clientes', 'sucursal_id')) {
            $clienteModel->loadMissing('sucursal:id,nombre,clave');
        }

        // TODO: Registrar en bitácora/auditoría cuando el módulo esté disponible.

        return response()->json([
            'message' => 'Cliente actualizado correctamente.',
            'data' => new ClienteResource($clienteModel),
        ]);
    }

    public function cambiarEstatus(CambiarEstatusClienteRequest $request, int $cliente): JsonResponse
    {
        $clienteModel = $this->findClienteOrFail($cliente);

        if (! Schema::hasColumn('clientes', 'estatus')) {
            throw new ApiException('La columna estatus no está disponible en clientes.', 422);
        }

        $clienteModel->forceFill([
            'estatus' => $request->validated('estatus'),
        ])->save();

        // TODO: Registrar motivo y cambio de estatus en bitácora/auditoría cuando el módulo esté disponible.

        return response()->json([
            'message' => 'Estatus del cliente actualizado correctamente.',
            'data' => new ClienteResource($clienteModel),
        ]);
    }

    public function desactivar(int $cliente): JsonResponse
    {
        $clienteModel = $this->findClienteOrFail($cliente);

        $attributes = [];
        if (Schema::hasColumn('clientes', 'activo')) {
            $attributes['activo'] = false;
        }

        if (Schema::hasColumn('clientes', 'estatus')) {
            $attributes['estatus'] = ClienteEstatus::INACTIVO->value;
        }

        if ($attributes === []) {
            throw new ApiException('No hay columnas para desactivar al cliente.', 422);
        }

        $clienteModel->forceFill($attributes)->save();

        // TODO: Registrar desactivación en bitácora/auditoría cuando el módulo esté disponible.

        return response()->json([
            'message' => 'Cliente desactivado correctamente.',
            'data' => new ClienteResource($clienteModel),
        ]);
    }

    public function reactivar(int $cliente): JsonResponse
    {
        $clienteModel = $this->findClienteOrFail($cliente);

        $attributes = [];
        if (Schema::hasColumn('clientes', 'activo')) {
            $attributes['activo'] = true;
        }

        if (Schema::hasColumn('clientes', 'estatus')) {
            $attributes['estatus'] = ClienteEstatus::ACTIVO->value;
        }

        if ($attributes === []) {
            throw new ApiException('No hay columnas para reactivar al cliente.', 422);
        }

        $clienteModel->forceFill($attributes)->save();

        // TODO: Registrar reactivación en bitácora/auditoría cuando el módulo esté disponible.

        return response()->json([
            'message' => 'Cliente reactivado correctamente.',
            'data' => new ClienteResource($clienteModel),
        ]);
    }

    private function findClienteOrFail(int $id): Cliente
    {
        $cliente = Cliente::query()->find($id);

        if (! $cliente) {
            throw new ApiException('Cliente no encontrado.', 404);
        }

        return $cliente;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        return collect($payload)
            ->only(array_values(array_filter([
                'sucursal_id',
                'nombre',
                'apellido_paterno',
                'apellido_materno',
                'telefono',
                'email',
                'fecha_nacimiento',
                'contacto_emergencia_nombre',
                'contacto_emergencia_telefono',
                'foto_url',
                'fecha_inscripcion',
                'estatus',
                'notas',
                'activo',
            ], static fn (string $column): bool => Schema::hasColumn('clientes', $column))))
            ->all();
    }
}
