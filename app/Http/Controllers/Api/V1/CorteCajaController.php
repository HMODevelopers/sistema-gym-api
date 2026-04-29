<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CortesCaja\CalcularCorteCajaRequest;
use App\Http\Requests\CortesCaja\CancelarCorteCajaRequest;
use App\Http\Requests\CortesCaja\StoreCorteCajaRequest;
use App\Http\Resources\CorteCajaResource;
use App\Models\CorteCaja;
use App\Services\AuditoriaService;
use App\Services\CorteCajaService;
use App\Services\ExportacionCsvService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CorteCajaController extends Controller
{
    public function __construct(private readonly CorteCajaService $service, private readonly ExportacionCsvService $csv, private readonly AuditoriaService $auditoriaService) {}

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

    public function exportar(CorteCaja $corte)
    {
        $data = $this->service->obtenerDatosExportacion($corte);
        $c = $data['corte'];
        $this->auditoriaService->registrar('CorteCaja', 'AJUSTAR', $c->id, 'Exportación CSV de corte de caja', null, ['tipo_reporte' => 'corte_caja', 'corte_id' => $c->id, 'sucursal_id' => $c->sucursal_id, 'usuario_id' => $c->usuario_id, 'fecha_desde' => optional($c->fecha_desde)?->format('Y-m-d H:i:s'), 'fecha_hasta' => optional($c->fecha_hasta)?->format('Y-m-d H:i:s')]);
        return $this->csv->stream('corte_caja_'.$c->id.'_'.now()->format('Ymd_His').'.csv', ['Campo', 'Valor'], function ($h, $csv) use ($data, $c): void {
            $csv->escribirFila($h, ['ID Corte', $c->id]);
            $csv->escribirFila($h, ['Sucursal', $c->sucursal?->nombre]);
            $csv->escribirFila($h, ['Usuario', trim(($c->usuario?->nombre ?? '').' '.($c->usuario?->apellido_paterno ?? '').' '.($c->usuario?->apellido_materno ?? ''))]);
            $csv->escribirFila($h, ['Fecha Desde', optional($c->fecha_desde)?->format('Y-m-d H:i:s')]);
            $csv->escribirFila($h, ['Fecha Hasta', optional($c->fecha_hasta)?->format('Y-m-d H:i:s')]);
            $csv->escribirFila($h, ['Total Pagos', $c->total_pagos]);
            $csv->escribirFila($h, ['Total Monto', number_format((float) $c->total_monto, 2, '.', '')]);
            $csv->escribirFila($h, ['Total Cancelados', $c->total_cancelados]);
            $csv->escribirFila($h, ['Total Cancelado Monto', number_format((float) $c->total_cancelado_monto, 2, '.', '')]);
            $csv->escribirFila($h, ['Efectivo Esperado', number_format((float) $c->efectivo_esperado, 2, '.', '')]);
            $csv->escribirFila($h, ['Efectivo Contado', number_format((float) $c->efectivo_contado, 2, '.', '')]);
            $csv->escribirFila($h, ['Diferencia Efectivo', number_format((float) $c->diferencia_efectivo, 2, '.', '')]);
            $csv->escribirFila($h, ['Estatus', $c->estatus]);
            $csv->escribirFila($h, ['Observaciones', $c->observaciones]);
            $csv->escribirFila($h, []);
            $csv->escribirFila($h, ['Método de Pago', 'Clave', 'Total Pagos', 'Total Monto']);
            foreach ($data['detalle_metodos_pago'] as $d) {
                $csv->escribirFila($h, [$d['nombre'] ?? null, $d['clave'] ?? null, $d['total_pagos'] ?? 0, number_format((float) ($d['total_monto'] ?? 0), 2, '.', '')]);
            }
        });
    }
}
