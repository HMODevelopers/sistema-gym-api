<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\DispositivoEstatus;
use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dispositivos\CambiarEstatusDispositivoRequest;
use App\Http\Requests\Dispositivos\StoreDispositivoRequest;
use App\Http\Requests\Dispositivos\UpdateDispositivoRequest;
use App\Http\Resources\Dispositivos\DispositivoResource;
use App\Models\Dispositivo;
use App\Services\AuditoriaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DispositivoController extends Controller
{
    public function __construct(private readonly AuditoriaService $auditoriaService) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $query = Dispositivo::query();

        if (Schema::hasColumn('dispositivos', 'sucursal_id')) {
            $query->with('sucursal:id,nombre,clave');
        }

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                foreach (['nombre', 'clave', 'identificador', 'descripcion', 'ubicacion'] as $column) {
                    if (Schema::hasColumn('dispositivos', $column)) {
                        $builder->orWhere($column, 'like', "%{$search}%");
                    }
                }
            });
        }

        if (Schema::hasColumn('dispositivos', 'sucursal_id') && $request->filled('sucursal_id')) {
            $query->where('sucursal_id', (int) $request->query('sucursal_id'));
        }

        if (Schema::hasColumn('dispositivos', 'tipo')) {
            $tipo = mb_strtoupper(trim((string) $request->query('tipo', '')));
            if ($tipo !== '' && $tipo !== 'ALL') {
                $query->where('tipo', $tipo);
            }
        }

        if (Schema::hasColumn('dispositivos', 'estatus')) {
            $estatus = mb_strtoupper(trim((string) $request->query('estatus', '')));
            if ($estatus !== '' && $estatus !== 'ALL') {
                $query->where('estatus', $estatus);
            }
        }

        $activo = strtolower(trim((string) $request->query('activo', 'true')));
        if ($activo !== 'all' && Schema::hasColumn('dispositivos', 'activo')) {
            $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);
        }

        if (Schema::hasColumn('dispositivos', 'nombre')) {
            $query->orderBy('nombre');
        } else {
            $query->orderByDesc('id');
        }

        $paginator = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'message' => 'Dispositivos obtenidos correctamente.',
            'data' => DispositivoResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(int $dispositivo): JsonResponse
    {
        $dispositivoModel = $this->findDispositivoOrFail($dispositivo);

        if (Schema::hasColumn('dispositivos', 'sucursal_id')) {
            $dispositivoModel->loadMissing('sucursal:id,nombre,clave');
        }

        return response()->json([
            'message' => 'Dispositivo obtenido correctamente.',
            'data' => new DispositivoResource($dispositivoModel),
        ]);
    }

    public function store(StoreDispositivoRequest $request): JsonResponse
    {
        $payload = $this->sanitizePayload($request->validated());

        if (Schema::hasColumn('dispositivos', 'estatus')) {
            $payload['estatus'] = $payload['estatus'] ?? DispositivoEstatus::ACTIVO->value;
        }

        if (Schema::hasColumn('dispositivos', 'activo')) {
            $payload['activo'] = $payload['activo'] ?? true;
        }

        $dispositivo = Dispositivo::query()->create($payload);

        if (Schema::hasColumn('dispositivos', 'sucursal_id')) {
            $dispositivo->loadMissing('sucursal:id,nombre,clave');
        }

        $this->auditoriaService->registrar(
            accion: 'CREAR',
            entidad: 'Dispositivo',
            entidadId: $dispositivo->id,
            descripcion: 'Dispositivo creado correctamente.',
            datosDespues: $dispositivo->toArray(),
            sucursalId: (int) ($dispositivo->sucursal_id ?? 0) ?: null,
        );

        return response()->json([
            'message' => 'Dispositivo creado correctamente.',
            'data' => new DispositivoResource($dispositivo),
        ], 201);
    }

    public function update(UpdateDispositivoRequest $request, int $dispositivo): JsonResponse
    {
        $dispositivoModel = $this->findDispositivoOrFail($dispositivo);
        $valoresAnteriores = $dispositivoModel->toArray();
        $dispositivoModel->fill($this->sanitizePayload($request->validated()));
        $dispositivoModel->save();

        if (Schema::hasColumn('dispositivos', 'sucursal_id')) {
            $dispositivoModel->loadMissing('sucursal:id,nombre,clave');
        }

        $this->auditoriaService->registrar(
            accion: 'EDITAR',
            entidad: 'Dispositivo',
            entidadId: $dispositivoModel->id,
            descripcion: 'Dispositivo actualizado correctamente.',
            datosAntes: $valoresAnteriores,
            datosDespues: $dispositivoModel->fresh()?->toArray(),
            sucursalId: (int) ($dispositivoModel->sucursal_id ?? 0) ?: null,
        );

        return response()->json([
            'message' => 'Dispositivo actualizado correctamente.',
            'data' => new DispositivoResource($dispositivoModel),
        ]);
    }

    public function cambiarEstatus(CambiarEstatusDispositivoRequest $request, int $dispositivo): JsonResponse
    {
        $dispositivoModel = $this->findDispositivoOrFail($dispositivo);

        if (! Schema::hasColumn('dispositivos', 'estatus')) {
            throw new ApiException('La columna estatus no está disponible en dispositivos.', 422);
        }

        $valoresAnteriores = $dispositivoModel->toArray();
        $dispositivoModel->forceFill([
            'estatus' => $request->validated('estatus'),
        ])->save();

        $this->auditoriaService->registrar(
            accion: 'CAMBIAR_ESTATUS',
            entidad: 'Dispositivo',
            entidadId: $dispositivoModel->id,
            descripcion: 'Estatus del dispositivo actualizado correctamente.',
            datosAntes: $valoresAnteriores,
            datosDespues: ['estatus' => $dispositivoModel->estatus],
            sucursalId: (int) ($dispositivoModel->sucursal_id ?? 0) ?: null,
        );

        return response()->json([
            'message' => 'Estatus del dispositivo actualizado correctamente.',
            'data' => new DispositivoResource($dispositivoModel),
        ]);
    }

    public function desactivar(int $dispositivo): JsonResponse
    {
        $dispositivoModel = $this->findDispositivoOrFail($dispositivo);

        $attributes = [];
        if (Schema::hasColumn('dispositivos', 'activo')) {
            $attributes['activo'] = false;
        }

        if (Schema::hasColumn('dispositivos', 'estatus')) {
            $attributes['estatus'] = DispositivoEstatus::INACTIVO->value;
        }

        if ($attributes === []) {
            throw new ApiException('No hay columnas para desactivar el dispositivo.', 422);
        }

        $valoresAnteriores = $dispositivoModel->toArray();
        $dispositivoModel->forceFill($attributes)->save();

        $this->auditoriaService->registrar(
            accion: 'ELIMINAR_LOGICO',
            entidad: 'Dispositivo',
            entidadId: $dispositivoModel->id,
            descripcion: 'Dispositivo desactivado correctamente.',
            datosAntes: $valoresAnteriores,
            datosDespues: $dispositivoModel->fresh()?->toArray(),
            sucursalId: (int) ($dispositivoModel->sucursal_id ?? 0) ?: null,
        );

        return response()->json([
            'message' => 'Dispositivo desactivado correctamente.',
            'data' => new DispositivoResource($dispositivoModel),
        ]);
    }

    public function reactivar(int $dispositivo): JsonResponse
    {
        $dispositivoModel = $this->findDispositivoOrFail($dispositivo);

        $attributes = [];
        if (Schema::hasColumn('dispositivos', 'activo')) {
            $attributes['activo'] = true;
        }

        if (Schema::hasColumn('dispositivos', 'estatus')) {
            $attributes['estatus'] = DispositivoEstatus::ACTIVO->value;
        }

        if ($attributes === []) {
            throw new ApiException('No hay columnas para reactivar el dispositivo.', 422);
        }

        $valoresAnteriores = $dispositivoModel->toArray();
        $dispositivoModel->forceFill($attributes)->save();

        $this->auditoriaService->registrar(
            accion: 'CAMBIAR_ESTATUS',
            entidad: 'Dispositivo',
            entidadId: $dispositivoModel->id,
            descripcion: 'Dispositivo reactivado correctamente.',
            datosAntes: $valoresAnteriores,
            datosDespues: $dispositivoModel->fresh()?->toArray(),
            sucursalId: (int) ($dispositivoModel->sucursal_id ?? 0) ?: null,
        );

        return response()->json([
            'message' => 'Dispositivo reactivado correctamente.',
            'data' => new DispositivoResource($dispositivoModel),
        ]);
    }

    private function findDispositivoOrFail(int $id): Dispositivo
    {
        $dispositivo = Dispositivo::query()->find($id);

        if (! $dispositivo) {
            throw new ApiException('Dispositivo no encontrado.', 404);
        }

        return $dispositivo;
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
                'clave',
                'identificador',
                'tipo',
                'descripcion',
                'ubicacion',
                'ip',
                'sistema_operativo',
                'estatus',
                'activo',
            ], static fn (string $column): bool => Schema::hasColumn('dispositivos', $column))))
            ->all();
    }
}
