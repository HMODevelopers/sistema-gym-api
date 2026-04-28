<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Sucursales\StoreSucursalRequest;
use App\Http\Requests\Sucursales\UpdateSucursalRequest;
use App\Http\Resources\Sucursales\SucursalResource;
use App\Models\Sucursal;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SucursalController extends Controller
{
    public function __construct(private readonly AuditoriaService $auditoriaService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $query = Sucursal::query();

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('nombre', 'like', "%{$search}%")
                    ->orWhere('clave', 'like', "%{$search}%");
            });
        }

        $activo = strtolower(trim((string) $request->query('activo', 'true')));
        if ($activo !== 'all') {
            $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);
        }

        $paginator = $query
            ->orderBy('nombre')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'message' => 'Sucursales obtenidas correctamente.',
            'data' => SucursalResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(int $sucursal): JsonResponse
    {
        $sucursalModel = $this->findSucursalOrFail($sucursal);

        return response()->json([
            'message' => 'Sucursal obtenida correctamente.',
            'data' => new SucursalResource($sucursalModel),
        ]);
    }

    public function store(StoreSucursalRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['activo'] = $payload['activo'] ?? true;

        $sucursal = Sucursal::query()->create($payload);
        $this->auditoriaService->registrar(
            accion: 'CREAR',
            entidad: 'Sucursal',
            entidadId: $sucursal->id,
            descripcion: 'Sucursal creada correctamente.',
            datosDespues: $sucursal->toArray(),
        );

        return response()->json([
            'message' => 'Sucursal creada correctamente.',
            'data' => new SucursalResource($sucursal),
        ], 201);
    }

    public function update(UpdateSucursalRequest $request, int $sucursal): JsonResponse
    {
        $sucursalModel = $this->findSucursalOrFail($sucursal);
        $valoresAnteriores = $sucursalModel->toArray();
        $sucursalModel->fill($request->validated());
        $sucursalModel->save();
        $this->auditoriaService->registrar(
            accion: 'EDITAR',
            entidad: 'Sucursal',
            entidadId: $sucursalModel->id,
            descripcion: 'Sucursal actualizada correctamente.',
            datosAntes: $valoresAnteriores,
            datosDespues: $sucursalModel->fresh()?->toArray(),
        );

        return response()->json([
            'message' => 'Sucursal actualizada correctamente.',
            'data' => new SucursalResource($sucursalModel),
        ]);
    }

    public function desactivar(int $sucursal): JsonResponse
    {
        $sucursalModel = $this->findSucursalOrFail($sucursal);

        if ($sucursalModel->activo) {
            $activeCount = Sucursal::query()->where('activo', true)->count();
            if ($activeCount <= 1) {
                throw new ApiException('No se puede desactivar la última sucursal activa del sistema.', 422);
            }
        }

        // TODO: validar restricciones de negocio cuando se vincule operación de usuarios activos por sucursal.
        $valoresAnteriores = $sucursalModel->toArray();
        $sucursalModel->forceFill(['activo' => false])->save();
        $this->auditoriaService->registrar(
            accion: 'ELIMINAR_LOGICO',
            entidad: 'Sucursal',
            entidadId: $sucursalModel->id,
            descripcion: 'Sucursal desactivada correctamente.',
            datosAntes: $valoresAnteriores,
            datosDespues: $sucursalModel->fresh()?->toArray(),
        );

        return response()->json([
            'message' => 'Sucursal desactivada correctamente.',
            'data' => new SucursalResource($sucursalModel),
        ]);
    }

    public function reactivar(int $sucursal): JsonResponse
    {
        $sucursalModel = $this->findSucursalOrFail($sucursal);
        $valoresAnteriores = $sucursalModel->toArray();
        $sucursalModel->forceFill(['activo' => true])->save();
        $this->auditoriaService->registrar(
            accion: 'CAMBIAR_ESTATUS',
            entidad: 'Sucursal',
            entidadId: $sucursalModel->id,
            descripcion: 'Sucursal reactivada correctamente.',
            datosAntes: $valoresAnteriores,
            datosDespues: $sucursalModel->fresh()?->toArray(),
        );

        return response()->json([
            'message' => 'Sucursal reactivada correctamente.',
            'data' => new SucursalResource($sucursalModel),
        ]);
    }

    private function findSucursalOrFail(int $id): Sucursal
    {
        $sucursal = Sucursal::query()->find($id);

        if (! $sucursal) {
            throw new ApiException('Sucursal no encontrada.', 404);
        }

        return $sucursal;
    }
}
