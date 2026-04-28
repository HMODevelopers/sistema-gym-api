<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\AuditoriaEventoResource;
use App\Models\AuditoriaEvento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $query = AuditoriaEvento::query()->with([
            'usuario:id,nombre,apellido_paterno,apellido_materno,username',
            'sucursal:id,nombre,clave',
        ]);

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('entidad', 'like', "%{$search}%")
                    ->orWhere('accion', 'like', "%{$search}%")
                    ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        foreach (['sucursal_id', 'usuario_id', 'entidad_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, (int) $request->query($filter));
            }
        }

        foreach (['entidad', 'accion'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, trim((string) $request->query($filter)));
            }
        }

        if ($request->filled('fecha_desde')) {
            $query->whereDate('created_at', '>=', (string) $request->query('fecha_desde'));
        }

        if ($request->filled('fecha_hasta')) {
            $query->whereDate('created_at', '<=', (string) $request->query('fecha_hasta'));
        }

        $paginator = $query
            ->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->query());

        return response()->json([
            'message' => 'Eventos de auditoría obtenidos correctamente.',
            'data' => AuditoriaEventoResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(int $evento): JsonResponse
    {
        $registro = AuditoriaEvento::query()
            ->with([
                'usuario:id,nombre,apellido_paterno,apellido_materno,username',
                'sucursal:id,nombre,clave',
            ])
            ->find($evento);

        if (! $registro) {
            throw new ApiException('Evento de auditoría no encontrado.', 404);
        }

        return response()->json([
            'message' => 'Evento de auditoría obtenido correctamente.',
            'data' => new AuditoriaEventoResource($registro),
        ]);
    }
}
