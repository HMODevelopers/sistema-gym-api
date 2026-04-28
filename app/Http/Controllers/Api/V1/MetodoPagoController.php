<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\MetodosPago\StoreMetodoPagoRequest;
use App\Http\Requests\MetodosPago\UpdateMetodoPagoRequest;
use App\Http\Resources\MetodosPago\MetodoPagoResource;
use App\Models\MetodoPago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class MetodoPagoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $query = MetodoPago::query();

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('nombre', 'like', "%{$search}%")
                    ->orWhere('clave', 'like', "%{$search}%");

                if (Schema::hasColumn('metodos_pago', 'descripcion')) {
                    $builder->orWhere('descripcion', 'like', "%{$search}%");
                }
            });
        }

        $activo = strtolower(trim((string) $request->query('activo', 'true')));
        if ($activo !== 'all' && Schema::hasColumn('metodos_pago', 'activo')) {
            $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);
        }

        $paginator = $query
            ->orderBy('nombre')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'message' => 'Métodos de pago obtenidos correctamente.',
            'data' => MetodoPagoResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(int $metodoPago): JsonResponse
    {
        $metodoPagoModel = $this->findMetodoPagoOrFail($metodoPago);

        return response()->json([
            'message' => 'Método de pago obtenido correctamente.',
            'data' => new MetodoPagoResource($metodoPagoModel),
        ]);
    }

    public function store(StoreMetodoPagoRequest $request): JsonResponse
    {
        $payload = $this->sanitizePayload($request->validated());

        if (Schema::hasColumn('metodos_pago', 'requiere_referencia')) {
            $payload['requiere_referencia'] = $payload['requiere_referencia'] ?? false;
        }

        if (Schema::hasColumn('metodos_pago', 'activo')) {
            $payload['activo'] = $payload['activo'] ?? true;
        }

        $metodoPago = MetodoPago::query()->create($payload);

        return response()->json([
            'message' => 'Método de pago creado correctamente.',
            'data' => new MetodoPagoResource($metodoPago),
        ], 201);
    }

    public function update(UpdateMetodoPagoRequest $request, int $metodoPago): JsonResponse
    {
        $metodoPagoModel = $this->findMetodoPagoOrFail($metodoPago);
        $metodoPagoModel->fill($this->sanitizePayload($request->validated()));
        $metodoPagoModel->save();

        return response()->json([
            'message' => 'Método de pago actualizado correctamente.',
            'data' => new MetodoPagoResource($metodoPagoModel),
        ]);
    }

    public function desactivar(int $metodoPago): JsonResponse
    {
        $metodoPagoModel = $this->findMetodoPagoOrFail($metodoPago);

        // TODO: validar impacto operativo cuando existan pagos/renovaciones ligados al método de pago.
        $metodoPagoModel->forceFill(['activo' => false])->save();

        return response()->json([
            'message' => 'Método de pago desactivado correctamente.',
            'data' => new MetodoPagoResource($metodoPagoModel),
        ]);
    }

    public function reactivar(int $metodoPago): JsonResponse
    {
        $metodoPagoModel = $this->findMetodoPagoOrFail($metodoPago);
        $metodoPagoModel->forceFill(['activo' => true])->save();

        return response()->json([
            'message' => 'Método de pago reactivado correctamente.',
            'data' => new MetodoPagoResource($metodoPagoModel),
        ]);
    }

    private function findMetodoPagoOrFail(int $id): MetodoPago
    {
        $metodoPago = MetodoPago::query()->find($id);

        if (! $metodoPago) {
            throw new ApiException('Método de pago no encontrado.', 404);
        }

        return $metodoPago;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        $columns = [
            'nombre',
            'clave',
            'descripcion',
            'requiere_referencia',
            'activo',
        ];

        return collect($payload)
            ->only(array_values(array_filter($columns, static fn (string $column): bool => Schema::hasColumn('metodos_pago', $column))))
            ->all();
    }
}
