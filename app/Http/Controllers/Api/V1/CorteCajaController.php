<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CortesCaja\CalcularCorteCajaRequest;
use App\Http\Requests\CortesCaja\CancelarCorteCajaRequest;
use App\Http\Requests\CortesCaja\StoreCorteCajaRequest;
use App\Http\Resources\CorteCajaResource;
use App\Models\CorteCaja;
use App\Services\CorteCajaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CorteCajaController extends Controller
{
    public function __construct(private readonly CorteCajaService $service) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $p = $this->service->index($request->all(), $perPage)->appends($request->query());
        return response()->json(['message' => 'Cortes de caja obtenidos correctamente.', 'data' => CorteCajaResource::collection($p->items()), 'meta' => ['current_page' => $p->currentPage(), 'per_page' => $p->perPage(), 'total' => $p->total(), 'last_page' => $p->lastPage()]]);
    }

    public function show(CorteCaja $corte): JsonResponse
    {
        $corte->load(['sucursal:id,nombre,clave', 'usuario:id,nombre,apellido_paterno,apellido_materno,username']);
        return response()->json(['message' => 'Corte de caja obtenido correctamente.', 'data' => new CorteCajaResource($corte)]);
    }

    public function calcular(CalcularCorteCajaRequest $request): JsonResponse
    {
        return response()->json(['message' => 'Corte de caja calculado correctamente.', 'data' => $this->service->calcular($request->validated())]);
    }

    public function store(StoreCorteCajaRequest $request): JsonResponse
    {
        $corte = $this->service->store($request->validated(), (int) $request->user()->id);
        return response()->json(['message' => 'Corte de caja generado correctamente.', 'data' => new CorteCajaResource($corte->load(['sucursal:id,nombre,clave', 'usuario:id,nombre,apellido_paterno,apellido_materno,username']))], 201);
    }

    public function cancelar(CancelarCorteCajaRequest $request, CorteCaja $corte): JsonResponse
    {
        $corte = $this->service->cancelar($corte, (string) $request->validated('motivo'), (int) $request->user()->id);
        return response()->json(['message' => 'Corte de caja cancelado correctamente.', 'data' => ['id' => $corte->id, 'estatus' => $corte->estatus, 'motivo_cancelacion' => $corte->motivo_cancelacion, 'cancelado_at' => optional($corte->cancelado_at)?->format('Y-m-d H:i:s'), 'activo' => (bool) $corte->activo]]);
    }
}
