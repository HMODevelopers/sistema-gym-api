<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Biometricos\DesactivarBiometricoRequest;
use App\Http\Requests\Biometricos\EnrolarBiometricoRequest;
use App\Http\Resources\Biometricos\BiometricoResource;
use App\Services\BiometricoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BiometricoController extends Controller
{
    public function __construct(private readonly BiometricoService $biometricoService) {}

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->biometricoService->index($request);

        return response()->json([
            'message' => 'Biométricos obtenidos correctamente.',
            'data' => BiometricoResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(int $biometrico): JsonResponse
    {
        $model = $this->biometricoService->findOrFail($biometrico);

        return response()->json([
            'message' => 'Biométrico obtenido correctamente.',
            'data' => new BiometricoResource($model),
        ]);
    }

    public function porCliente(Request $request, int $cliente): JsonResponse
    {
        $paginator = $this->biometricoService->porCliente($request, $cliente);

        return response()->json([
            'message' => 'Biométricos del cliente obtenidos correctamente.',
            'data' => BiometricoResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function enrolar(EnrolarBiometricoRequest $request): JsonResponse
    {
        $result = $this->biometricoService->enrolar($request->validated());

        return response()->json([
            'message' => $result['quality_low']
                ? 'Biométrico enrolado correctamente, pero la calidad de lectura es baja. Se recomienda registrar una huella secundaria.'
                : 'Biométrico enrolado correctamente.',
            'data' => new BiometricoResource($result['biometrico']),
        ], 201);
    }

    public function marcarPrincipal(int $biometrico): JsonResponse
    {
        $model = $this->biometricoService->marcarPrincipal($biometrico);

        return response()->json([
            'message' => 'Biométrico marcado como principal correctamente.',
            'data' => new BiometricoResource($model),
        ]);
    }

    public function desactivar(DesactivarBiometricoRequest $request, int $biometrico): JsonResponse
    {
        $model = $this->biometricoService->desactivar($biometrico, $request->validated('motivo'));

        return response()->json([
            'message' => 'Biométrico desactivado correctamente.',
            'data' => new BiometricoResource($model),
        ]);
    }

    public function revocar(DesactivarBiometricoRequest $request, int $biometrico): JsonResponse
    {
        $model = $this->biometricoService->revocar($biometrico, $request->validated('motivo'));

        return response()->json([
            'message' => 'Biométrico revocado correctamente.',
            'data' => new BiometricoResource($model),
        ]);
    }
}
