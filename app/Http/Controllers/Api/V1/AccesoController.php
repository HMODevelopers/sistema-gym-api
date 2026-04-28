<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accesos\ValidarAccesoRequest;
use App\Http\Resources\Accesos\AccesoResource;
use App\Services\AccesoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccesoController extends Controller
{
    public function __construct(private readonly AccesoService $accesoService) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->accesoService->index($request);

        return response()->json([
            'message' => 'Accesos obtenidos correctamente.',
            'data' => AccesoResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(int $acceso): JsonResponse
    {
        $accesoModel = $this->accesoService->findOrFail($acceso);

        return response()->json([
            'message' => 'Acceso obtenido correctamente.',
            'data' => new AccesoResource($accesoModel),
        ]);
    }

    public function porCliente(Request $request, int $cliente): JsonResponse
    {
        $paginator = $this->accesoService->porCliente($request, $cliente);

        return response()->json([
            'message' => 'Accesos del cliente obtenidos correctamente.',
            'data' => AccesoResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function validar(ValidarAccesoRequest $request): JsonResponse
    {
        $resultado = $this->accesoService->validar($request->validated(), $request->user());

        return response()->json([
            'message' => $resultado['http_message'],
            'data' => [
                'resultado' => $resultado['resultado'],
                'motivo_rechazo' => $resultado['motivo_rechazo'],
                'cliente' => $resultado['cliente'] ? [
                    'id' => $resultado['cliente']->id,
                    'nombre_completo' => $resultado['cliente']->nombre_completo,
                    'estatus' => $resultado['cliente']->estatus,
                ] : null,
                'membresia' => $resultado['membresia'] ? [
                    'id' => $resultado['membresia']->id,
                    'estatus' => $resultado['membresia']->estatus,
                    'fecha_vencimiento' => $resultado['membresia']->fecha_vencimiento,
                ] : null,
                'acceso' => [
                    'id' => $resultado['acceso']->id,
                    'fecha_acceso' => $resultado['acceso']->fecha_acceso,
                    'metodo' => $resultado['acceso']->metodo,
                    'resultado' => $resultado['acceso']->resultado,
                    'motivo_rechazo' => $resultado['acceso']->motivo_rechazo,
                ],
            ],
        ]);
    }
}
