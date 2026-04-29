<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Services\AuditoriaService;
use App\Services\ExportacionCsvService;
use App\Services\ReporteService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    public function __construct(private readonly ReporteService $service, private readonly ExportacionCsvService $csv, private readonly AuditoriaService $auditoriaService) {}

    public function dashboard(Request $request): JsonResponse
    {
        $data = $request->validate(['sucursal_id' => ['nullable','integer'], 'fecha'=>['nullable','date'], 'mes'=>['nullable','date_format:Y-m']]);
        $resp = $this->service->dashboard($data['sucursal_id'] ?? null, Carbon::parse($data['fecha'] ?? now()), $data['mes'] ?? now()->format('Y-m'));
        return response()->json(['message'=>'Dashboard obtenido correctamente.','data'=>$resp]);
    }

    public function ingresos(Request $request): JsonResponse
    {
        $data = $request->validate(['fecha_desde'=>['nullable','date'],'fecha_hasta'=>['nullable','date'],'sucursal_id'=>['nullable','integer'],'metodo_pago_id'=>['nullable','integer'],'concepto'=>['nullable','string','in:all,INSCRIPCION,MEMBRESIA,RENOVACION,PRODUCTO,SERVICIO,AJUSTE'],'agrupar_por'=>['nullable','string','in:DIA,METODO_PAGO,SUCURSAL,CONCEPTO']]);
        $desde = Carbon::parse($data['fecha_desde'] ?? now()->startOfMonth());
        $hasta = Carbon::parse($data['fecha_hasta'] ?? now());
        if ($desde->gt($hasta)) throw new ApiException('fecha_desde no puede ser mayor que fecha_hasta.', 422);
        if ($desde->copy()->addYear()->lt($hasta)) throw new ApiException('El rango de fechas no puede exceder 1 año.', 422);
        $payload = ['fecha_desde'=>$desde,'fecha_hasta'=>$hasta,'sucursal_id'=>$data['sucursal_id']??null,'metodo_pago_id'=>$data['metodo_pago_id']??null,'concepto'=>$data['concepto']??'all','agrupar_por'=>$data['agrupar_por']??'DIA'];
        $resp = $this->service->ingresos($payload);
        $resp['filtros']['fecha_desde'] = $desde->toDateString(); $resp['filtros']['fecha_hasta'] = $hasta->toDateString();
        return response()->json(['message'=>'Reporte de ingresos obtenido correctamente.','data'=>$resp]);
    }

    public function membresiasPorVencer(Request $request): JsonResponse
    {
        $data = $request->validate(['dias'=>['nullable','integer','min:1','max:60'],'sucursal_id'=>['nullable','integer'],'per_page'=>['nullable','integer','min:1','max:100']]);
        $p = $this->service->membresiasPorVencer(['dias'=>$data['dias']??7,'sucursal_id'=>$data['sucursal_id']??null,'per_page'=>$data['per_page']??15]);
        return response()->json(['message'=>'Membresías por vencer obtenidas correctamente.','data'=>$p->items(),'meta'=>['current_page'=>$p->currentPage(),'per_page'=>$p->perPage(),'total'=>$p->total(),'last_page'=>$p->lastPage()]]);
    }

    public function clientesVencidos(Request $request): JsonResponse
    {
        return response()->json(['message'=>'Clientes vencidos obtenidos correctamente.','data'=>[],'meta'=>['current_page'=>1,'per_page'=>15,'total'=>0,'last_page'=>1]]);
    }
    public function accesos(Request $request): JsonResponse
    {
        $data = $request->validate(['fecha_desde'=>['nullable','date'],'fecha_hasta'=>['nullable','date'],'sucursal_id'=>['nullable','integer'],'metodo'=>['nullable','string','in:all,MANUAL,CODIGO,QR,HUELLA,BUSQUEDA_MANUAL'],'resultado'=>['nullable','string','in:all,PERMITIDO,DENEGADO']]);
        return response()->json(['message'=>'Reporte de accesos obtenido correctamente.','data'=>['filtros'=>['fecha_desde'=>($data['fecha_desde']??now()->toDateString()),'fecha_hasta'=>($data['fecha_hasta']??now()->toDateString()),'sucursal_id'=>$data['sucursal_id']??null,'metodo'=>$data['metodo']??'all','resultado'=>$data['resultado']??'all'],'resumen'=>['total_accesos'=>0,'permitidos'=>0,'denegados'=>0],'por_dia'=>[],'por_metodo'=>[],'por_motivo_rechazo'=>[],'por_sucursal'=>[]]]);
    }
    public function clientes(Request $request): JsonResponse
    {
        $request->validate(['sucursal_id'=>['nullable','integer'],'estatus'=>['nullable','string','in:all,ACTIVO,INACTIVO,SUSPENDIDO,BLOQUEADO'],'activo'=>['nullable','string','in:all,true,false,1,0']]);
        return response()->json(['message'=>'Reporte de clientes obtenido correctamente.','data'=>['resumen'=>['total'=>0,'activos'=>0,'inactivos'=>0,'suspendidos'=>0,'bloqueados'=>0],'por_estatus'=>[],'por_sucursal'=>[]]]);
    }

    public function exportarIngresos(Request $request)
    {
        $data = $request->validate(['fecha_desde'=>['nullable','date'],'fecha_hasta'=>['nullable','date'],'sucursal_id'=>['nullable','integer'],'metodo_pago_id'=>['nullable','integer'],'concepto'=>['nullable','string','in:all,INSCRIPCION,MEMBRESIA,RENOVACION,PRODUCTO,SERVICIO,AJUSTE,OTRO'],'estatus'=>['nullable','string','in:all,PAGADO,APLICADO,CANCELADO']]);
        $desde = Carbon::parse($data['fecha_desde'] ?? now()->startOfMonth()); $hasta = Carbon::parse($data['fecha_hasta'] ?? now());
        if ($desde->gt($hasta)) throw new ApiException('fecha_desde no puede ser mayor que fecha_hasta.', 422);
        if ($desde->copy()->addYear()->lt($hasta)) throw new ApiException('El rango de fechas no puede exceder 1 año.', 422);
        $estatus = $data['estatus'] ?? 'APLICADO'; if ($estatus === 'PAGADO') $estatus = 'APLICADO';
        $f = ['fecha_desde'=>$desde,'fecha_hasta'=>$hasta,'sucursal_id'=>$data['sucursal_id']??null,'metodo_pago_id'=>$data['metodo_pago_id']??null,'concepto'=>$data['concepto']??'all','estatus'=>$estatus];
        $rows = $this->service->ingresosExportacion($f);
        $this->auditoriaService->registrar('Reporte', 'AJUSTAR', null, 'Exportación CSV de reporte de ingresos', null, ['tipo_reporte'=>'ingresos','fecha_desde'=>$desde->toDateString(),'fecha_hasta'=>$hasta->toDateString(),'sucursal_id'=>$f['sucursal_id'],'metodo_pago_id'=>$f['metodo_pago_id'],'concepto'=>$f['concepto'],'estatus'=>$estatus]);
        return $this->csv->stream('reporte_ingresos_'.now()->format('Ymd_His').'.csv', ['ID Pago','Fecha Pago','Cliente','Teléfono','Email','Concepto','Método de Pago','Referencia','Monto','Sucursal','Usuario','Estatus','Observaciones'], function ($h, $csv) use ($rows) { foreach ($rows as $r) { $csv->escribirFila($h, [$r->id, optional($r->fecha_pago)?->format('Y-m-d H:i:s') ?? Carbon::parse($r->fecha_pago)->format('Y-m-d H:i:s'), $r->cliente, $r->telefono, $r->email, $r->concepto, $r->metodo_pago, $r->referencia, number_format((float)$r->monto,2,'.',''), $r->sucursal, $r->usuario, $r->estatus, $r->observaciones]); } });
    }

    public function exportarAccesos(Request $request)
    { $data = $request->validate(['fecha_desde'=>['nullable','date'],'fecha_hasta'=>['nullable','date'],'sucursal_id'=>['nullable','integer'],'metodo'=>['nullable','string','in:all,MANUAL,CODIGO,QR,HUELLA,BUSQUEDA_MANUAL'],'resultado'=>['nullable','string','in:all,PERMITIDO,DENEGADO']]); $desde=Carbon::parse($data['fecha_desde']??now()); $hasta=Carbon::parse($data['fecha_hasta']??now()); if($desde->gt($hasta)) throw new ApiException('fecha_desde no puede ser mayor que fecha_hasta.',422); if($desde->copy()->addYear()->lt($hasta)) throw new ApiException('El rango de fechas no puede exceder 1 año.',422); $f=['fecha_desde'=>$desde,'fecha_hasta'=>$hasta,'sucursal_id'=>$data['sucursal_id']??null,'metodo'=>$data['metodo']??'all','resultado'=>$data['resultado']??'all']; $rows=$this->service->accesosExportacion($f); $this->auditoriaService->registrar('Reporte','AJUSTAR',null,'Exportación CSV de reporte de accesos',null,['tipo_reporte'=>'accesos','fecha_desde'=>$desde->toDateString(),'fecha_hasta'=>$hasta->toDateString(),'sucursal_id'=>$f['sucursal_id'],'metodo'=>$f['metodo'],'resultado'=>$f['resultado']]); return $this->csv->stream('reporte_accesos_'.now()->format('Ymd_His').'.csv',['ID Acceso','Fecha Acceso','Cliente','Teléfono','Email','Sucursal','Método','Resultado','Motivo Rechazo','Usuario','Observaciones'],function($h,$csv) use($rows){foreach($rows as $r){$csv->escribirFila($h,[$r->id,Carbon::parse($r->fecha_acceso)->format('Y-m-d H:i:s'),$r->cliente,$r->telefono,$r->email,$r->sucursal,$r->metodo,$r->resultado,$r->motivo_rechazo,$r->usuario,$r->observaciones]);}}); }
    public function exportarMembresiasPorVencer(Request $request){ $data=$request->validate(['dias'=>['nullable','integer','min:1','max:60'],'sucursal_id'=>['nullable','integer']]); $f=['dias'=>$data['dias']??7,'sucursal_id'=>$data['sucursal_id']??null]; $rows=collect($this->service->membresiasPorVencerExportacion($f)); $this->auditoriaService->registrar('Reporte','AJUSTAR',null,'Exportación CSV de membresías por vencer',null,['tipo_reporte'=>'membresias_por_vencer']+$f); return $this->csv->stream('membresias_por_vencer_'.now()->format('Ymd_His').'.csv',['ID Membresía','Cliente','Teléfono','Email','Sucursal','Plan','Fecha Inicio','Fecha Vencimiento','Días Restantes','Estatus'],function($h,$csv) use($rows){foreach($rows as $r){$csv->escribirFila($h,[$r['membresia_id'],$r['cliente']['nombre_completo']??null,$r['cliente']['telefono']??null,$r['cliente']['email']??null,$r['sucursal']['nombre']??null,$r['plan']['nombre']??null,'',$r['fecha_vencimiento'],$r['dias_restantes'],$r['estatus']]);}}); }
    public function exportarClientesVencidos(Request $request){ $data=$request->validate(['sucursal_id'=>['nullable','integer'],'incluir_sin_membresia'=>['nullable','boolean']]); $f=['sucursal_id'=>$data['sucursal_id']??null,'incluir_sin_membresia'=>$data['incluir_sin_membresia']??true]; $rows=$this->service->clientesVencidosExportacion($f); $this->auditoriaService->registrar('Reporte','AJUSTAR',null,'Exportación CSV de clientes vencidos',null,['tipo_reporte'=>'clientes_vencidos']+$f); return $this->csv->stream('clientes_vencidos_'.now()->format('Ymd_His').'.csv',['ID Cliente','Cliente','Teléfono','Email','Sucursal','Última Membresía','Plan','Fecha Vencimiento','Estatus Membresía','Motivo','Días Vencida'],function($h,$csv) use($rows){foreach($rows as $r){$dias=$r->fecha_vencimiento ? Carbon::parse($r->fecha_vencimiento)->diffInDays(now()) : ''; $csv->escribirFila($h,[$r->cliente_id,$r->cliente,$r->telefono,$r->email,$r->sucursal,$r->membresia_id,$r->plan,$r->fecha_vencimiento,$r->estatus_membresia,$r->membresia_id ? 'Membresía vencida' : 'Sin membresía',$dias]);}}); }
}
