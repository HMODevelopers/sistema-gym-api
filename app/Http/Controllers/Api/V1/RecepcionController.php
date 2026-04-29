<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Resources\Recepcion\ClienteBusquedaRecepcionResource;
use App\Http\Resources\Recepcion\ClienteResumenRecepcionResource;
use App\Models\Cliente;
use App\Services\RecepcionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RecepcionController extends Controller
{
    public function __construct(private readonly RecepcionService $recepcionService) {}

    public function buscarClientes(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            throw new ApiException('El término de búsqueda debe tener al menos 2 caracteres.', 422);
        }

        $limit = min(max((int) $request->integer('limit', 10), 1), 20);
        $clientes = $this->recepcionService->buscarClientes(
            q: $q,
            sucursalId: $request->filled('sucursal_id') ? (int) $request->query('sucursal_id') : null,
            estatus: $request->filled('estatus') ? mb_strtoupper((string) $request->query('estatus')) : null,
            activo: strtolower((string) $request->query('activo', 'true')),
            limit: $limit,
        );

        return response()->json([
            'message' => 'Clientes encontrados correctamente.',
            'data' => ClienteBusquedaRecepcionResource::collection($clientes),
        ]);
    }

    public function resumenCliente(int $cliente): JsonResponse
    {
        $clienteModel = Cliente::query()->find($cliente);
        if (! $clienteModel) {
            throw new ApiException('Cliente no encontrado.', 404);
        }

        return response()->json([
            'message' => 'Resumen de cliente obtenido correctamente.',
            'data' => new ClienteResumenRecepcionResource($this->recepcionService->obtenerResumenCliente($clienteModel)),
        ]);
    }
}
