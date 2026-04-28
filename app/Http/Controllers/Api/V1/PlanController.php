<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Planes\StorePlanRequest;
use App\Http\Requests\Planes\UpdatePlanRequest;
use App\Http\Resources\Planes\PlanResource;
use App\Models\Plan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class PlanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $query = Plan::query();

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('nombre', 'like', "%{$search}%");

                if (Schema::hasColumn('planes', 'clave')) {
                    $builder->orWhere('clave', 'like', "%{$search}%");
                }

                if (Schema::hasColumn('planes', 'descripcion')) {
                    $builder->orWhere('descripcion', 'like', "%{$search}%");
                }
            });
        }

        if (Schema::hasColumn('planes', 'tipo_plan') && $request->filled('tipo_plan')) {
            $query->where('tipo_plan', mb_strtoupper(trim((string) $request->query('tipo_plan'))));
        }

        $activo = strtolower(trim((string) $request->query('activo', 'true')));
        if ($activo !== 'all' && Schema::hasColumn('planes', 'activo')) {
            $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);
        }

        $paginator = $query
            ->orderBy('nombre')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'message' => 'Planes obtenidos correctamente.',
            'data' => PlanResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(int $plan): JsonResponse
    {
        $planModel = $this->findPlanOrFail($plan);

        return response()->json([
            'message' => 'Plan obtenido correctamente.',
            'data' => new PlanResource($planModel),
        ]);
    }

    public function store(StorePlanRequest $request): JsonResponse
    {
        $payload = $this->sanitizePayload($request->validated());
        $payload['activo'] = $payload['activo'] ?? true;

        $plan = Plan::query()->create($payload);

        return response()->json([
            'message' => 'Plan creado correctamente.',
            'data' => new PlanResource($plan),
        ], 201);
    }

    public function update(UpdatePlanRequest $request, int $plan): JsonResponse
    {
        $planModel = $this->findPlanOrFail($plan);
        $planModel->fill($this->sanitizePayload($request->validated()));
        $planModel->save();

        return response()->json([
            'message' => 'Plan actualizado correctamente.',
            'data' => new PlanResource($planModel),
        ]);
    }

    public function desactivar(int $plan): JsonResponse
    {
        $planModel = $this->findPlanOrFail($plan);

        // TODO: Validar impacto operativo cuando existan membresías/pagos ligados a planes activos.
        $planModel->forceFill(['activo' => false])->save();

        return response()->json([
            'message' => 'Plan desactivado correctamente.',
            'data' => new PlanResource($planModel),
        ]);
    }

    public function reactivar(int $plan): JsonResponse
    {
        $planModel = $this->findPlanOrFail($plan);
        $planModel->forceFill(['activo' => true])->save();

        return response()->json([
            'message' => 'Plan reactivado correctamente.',
            'data' => new PlanResource($planModel),
        ]);
    }

    private function findPlanOrFail(int $id): Plan
    {
        $plan = Plan::query()->find($id);

        if (! $plan) {
            throw new ApiException('Plan no encontrado.', 404);
        }

        return $plan;
    }

    private function sanitizePayload(array $payload): array
    {
        $columns = [
            'nombre',
            'clave',
            'descripcion',
            'precio',
            'duracion_dias',
            'tipo_plan',
            'accesos_incluidos',
            'activo',
        ];

        return collect($payload)
            ->only(array_values(array_filter($columns, static fn (string $column): bool => Schema::hasColumn('planes', $column))))
            ->all();
    }
}
