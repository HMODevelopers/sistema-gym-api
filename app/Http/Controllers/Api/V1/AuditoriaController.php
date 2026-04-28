<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\BitacoraEventoResource;
use App\Models\BitacoraEvento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditoriaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $query = BitacoraEvento::query()->with([
            'usuario:id,nombre,apellido_paterno,apellido_materno,username',
            'sucursal:id,nombre,clave',
        ]);

        $search = trim((string) $request->query('q', ''));
        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('descripcion', 'like', "%{$search}%")
                    ->orWhere('modulo', 'like', "%{$search}%")
                    ->orWhere('accion', 'like', "%{$search}%")
                    ->orWhere('entidad', 'like', "%{$search}%")
                    ->orWhere('ruta', 'like', "%{$search}%");
            });
        }

        foreach (['usuario_id', 'sucursal_id', 'entidad_id'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, (int) $request->query($filter));
            }
        }

        foreach (['modulo', 'accion'] as $filter) {
            if ($request->filled($filter)) {
                $query->where($filter, mb_strtoupper(trim((string) $request->query($filter))));
            }
        }

        if ($request->filled('entidad')) {
            $query->where('entidad', trim((string) $request->query('entidad')));
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
            'data' => BitacoraEventoResource::collection($paginator->items()),
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
        $registro = BitacoraEvento::query()
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
            'data' => new BitacoraEventoResource($registro),
        ]);
    }
}
