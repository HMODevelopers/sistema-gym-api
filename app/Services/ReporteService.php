<?php

namespace App\Services;

use App\Enums\AccesoResultado;
use App\Enums\ClienteEstatus;
use App\Enums\MembresiaEstatus;
use App\Enums\PagoEstatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ReporteService
{
    public function dashboard(?int $sucursalId, Carbon $fecha, string $mes): array
    {
        [$inicioMes, $finMes] = $this->monthRange($mes);
        $vencCol = $this->vencimientoCol();

        $clientes = DB::table('clientes')->when($sucursalId, fn($q)=>$q->where('sucursal_id',$sucursalId))
            ->selectRaw("SUM(CASE WHEN estatus='ACTIVO' THEN 1 ELSE 0 END) activos")
            ->selectRaw("SUM(CASE WHEN estatus='INACTIVO' THEN 1 ELSE 0 END) inactivos")
            ->selectRaw("SUM(CASE WHEN estatus='SUSPENDIDO' THEN 1 ELSE 0 END) suspendidos")
            ->selectRaw("SUM(CASE WHEN estatus='BLOQUEADO' THEN 1 ELSE 0 END) bloqueados")->first();

        $m = DB::table('membresias')->when($sucursalId, fn($q)=>$q->where('sucursal_id',$sucursalId))
            ->selectRaw("SUM(CASE WHEN estatus='VIGENTE' THEN 1 ELSE 0 END) activas")
            ->selectRaw("SUM(CASE WHEN estatus='VENCIDA' THEN 1 ELSE 0 END) vencidas")
            ->selectRaw("SUM(CASE WHEN estatus='SUSPENDIDA' THEN 1 ELSE 0 END) suspendidas")
            ->selectRaw("SUM(CASE WHEN estatus='CANCELADA' THEN 1 ELSE 0 END) canceladas")
            ->selectRaw("SUM(CASE WHEN estatus='VIGENTE' AND {$vencCol} BETWEEN ? AND ? THEN 1 ELSE 0 END) por_vencer",[$fecha->toDateString(),$fecha->copy()->addDays(7)->toDateString()])->first();

        $pDia = DB::table('pagos')->when($sucursalId, fn($q)=>$q->where('sucursal_id',$sucursalId))->whereDate('fecha_pago',$fecha);
        $pMes = DB::table('pagos')->when($sucursalId, fn($q)=>$q->where('sucursal_id',$sucursalId))->whereBetween('fecha_pago',[$inicioMes->startOfDay(),$finMes->endOfDay()]);
        $a = DB::table('accesos')->when($sucursalId, fn($q)=>$q->where('sucursal_id',$sucursalId))->whereDate('fecha_acceso',$fecha)
            ->selectRaw('COUNT(*) total')
            ->selectRaw("SUM(CASE WHEN resultado='PERMITIDO' THEN 1 ELSE 0 END) permitidos")
            ->selectRaw("SUM(CASE WHEN resultado='DENEGADO' THEN 1 ELSE 0 END) denegados")->first();

        return ['filtros'=>['sucursal_id'=>$sucursalId,'fecha'=>$fecha->toDateString(),'mes'=>$mes],
            'clientes'=>['activos'=>(int)($clientes->activos??0),'inactivos'=>(int)($clientes->inactivos??0),'suspendidos'=>(int)($clientes->suspendidos??0),'bloqueados'=>(int)($clientes->bloqueados??0)],
            'membresias'=>['activas'=>(int)($m->activas??0),'vencidas'=>(int)($m->vencidas??0),'suspendidas'=>(int)($m->suspendidas??0),'canceladas'=>(int)($m->canceladas??0),'por_vencer_7_dias'=>(int)($m->por_vencer??0)],
            'ingresos'=>['dia'=>$this->money($pDia->clone()->where('estatus',PagoEstatus::APLICADO->value)->sum('monto')),'mes'=>$this->money($pMes->clone()->where('estatus',PagoEstatus::APLICADO->value)->sum('monto')),'pagos_dia'=>$pDia->clone()->where('estatus',PagoEstatus::APLICADO->value)->count(),'pagos_mes'=>$pMes->clone()->where('estatus',PagoEstatus::APLICADO->value)->count(),'pagos_cancelados_dia'=>$pDia->clone()->where('estatus',PagoEstatus::CANCELADO->value)->count()],
            'accesos'=>['dia'=>(int)($a->total??0),'permitidos_dia'=>(int)($a->permitidos??0),'denegados_dia'=>(int)($a->denegados??0)],
        ];
    }

    public function ingresos(array $f): array { return ['resumen'=>$this->resumenIngresos($f),'detalle'=>$this->detalleIngresos($f),'filtros'=>$f]; }
    private function basePagos(array $f){ return DB::table('pagos')->when($f['sucursal_id'],fn($q)=>$q->where('sucursal_id',$f['sucursal_id']))->when($f['metodo_pago_id'],fn($q)=>$q->where('metodo_pago_id',$f['metodo_pago_id']))->when(($f['concepto']??'all')!=='all',fn($q)=>$q->where('concepto',$f['concepto']))->whereBetween('fecha_pago',[$f['fecha_desde']->copy()->startOfDay(),$f['fecha_hasta']->copy()->endOfDay()]); }
    private function resumenIngresos(array $f): array { $q=$this->basePagos($f); return ['total_monto'=>$this->money($q->clone()->where('estatus',PagoEstatus::APLICADO->value)->sum('monto')),'total_pagos'=>$q->clone()->where('estatus',PagoEstatus::APLICADO->value)->count(),'total_cancelados'=>$q->clone()->where('estatus',PagoEstatus::CANCELADO->value)->count()]; }
    private function detalleIngresos(array $f){ $q=$this->basePagos($f)->where('estatus',PagoEstatus::APLICADO->value); $g=$f['agrupar_por']; if($g==='METODO_PAGO'){return $q->leftJoin('metodos_pago','metodos_pago.id','=','pagos.metodo_pago_id')->selectRaw('pagos.metodo_pago_id, metodos_pago.nombre metodo_pago, metodos_pago.clave, SUM(pagos.monto) total_monto, COUNT(*) total_pagos')->groupBy('pagos.metodo_pago_id','metodos_pago.nombre','metodos_pago.clave')->get()->map(fn($r)=>['metodo_pago_id'=>$r->metodo_pago_id,'metodo_pago'=>$r->metodo_pago,'clave'=>$r->clave,'total_monto'=>$this->money($r->total_monto),'total_pagos'=>(int)$r->total_pagos]); }
        if($g==='SUCURSAL'){return $q->leftJoin('sucursales','sucursales.id','=','pagos.sucursal_id')->selectRaw('pagos.sucursal_id, sucursales.nombre sucursal, SUM(pagos.monto) total_monto, COUNT(*) total_pagos')->groupBy('pagos.sucursal_id','sucursales.nombre')->get();}
        if($g==='CONCEPTO'){return $q->selectRaw('concepto grupo, SUM(monto) total_monto, COUNT(*) total_pagos')->groupBy('concepto')->get()->map(fn($r)=>['grupo'=>$r->grupo,'total_monto'=>$this->money($r->total_monto),'total_pagos'=>(int)$r->total_pagos]);}
        return $q->selectRaw('DATE(fecha_pago) grupo, SUM(monto) total_monto, COUNT(*) total_pagos')->groupByRaw('DATE(fecha_pago)')->orderBy('grupo')->get()->map(fn($r)=>['grupo'=>$r->grupo,'total_monto'=>$this->money($r->total_monto),'total_pagos'=>(int)$r->total_pagos]); }

    public function membresiasPorVencer(array $f){ $v=$this->vencimientoCol(); $today=Carbon::today(); return DB::table('membresias')->join('clientes','clientes.id','=','membresias.cliente_id')->join('planes','planes.id','=','membresias.plan_id')->leftJoin('sucursales','sucursales.id','=','membresias.sucursal_id')->when($f['sucursal_id'],fn($q)=>$q->where('membresias.sucursal_id',$f['sucursal_id']))->where('membresias.estatus',MembresiaEstatus::VIGENTE->value)->whereBetween("membresias.$v",[$today->toDateString(),$today->copy()->addDays($f['dias'])->toDateString()])->selectRaw("membresias.id membresia_id, clientes.id cliente_id, clientes.nombre_completo, clientes.telefono, clientes.email, planes.id plan_id, planes.nombre plan_nombre, planes.clave plan_clave, sucursales.id sucursal_id, sucursales.nombre sucursal_nombre, sucursales.clave sucursal_clave, membresias.$v fecha_vencimiento, membresias.estatus")->orderBy("membresias.$v")->paginate($f['per_page'])->through(fn($r)=>['membresia_id'=>$r->membresia_id,'cliente'=>['id'=>$r->cliente_id,'nombre_completo'=>$r->nombre_completo,'telefono'=>$r->telefono,'email'=>$r->email],'plan'=>['id'=>$r->plan_id,'nombre'=>$r->plan_nombre,'clave'=>$r->plan_clave],'sucursal'=>['id'=>$r->sucursal_id,'nombre'=>$r->sucursal_nombre,'clave'=>$r->sucursal_clave],'fecha_vencimiento'=>$r->fecha_vencimiento,'dias_restantes'=>Carbon::parse($r->fecha_vencimiento)->diffInDays($today,false)*-1,'estatus'=>$r->estatus]); }
    public function ingresosExportacion(array $f){ $q=$this->basePagos($f)->leftJoin('clientes','clientes.id','=','pagos.cliente_id')->leftJoin('metodos_pago','metodos_pago.id','=','pagos.metodo_pago_id')->leftJoin('sucursales','sucursales.id','=','pagos.sucursal_id')->leftJoin('usuarios','usuarios.id','=','pagos.usuario_id')->when(($f['estatus']??'APLICADO')!=='all',fn($x)=>$x->where('pagos.estatus',$f['estatus']))->selectRaw('pagos.id, pagos.fecha_pago, clientes.nombre_completo cliente, clientes.telefono, clientes.email, pagos.concepto, metodos_pago.nombre metodo_pago, pagos.referencia, pagos.monto, sucursales.nombre sucursal, CONCAT_WS(\" \", usuarios.nombre, usuarios.apellido_paterno, usuarios.apellido_materno) usuario, pagos.estatus, pagos.observaciones')->orderBy('pagos.fecha_pago'); return $q->get(); }
    public function accesosExportacion(array $f){ return DB::table('accesos')->leftJoin('clientes','clientes.id','=','accesos.cliente_id')->leftJoin('sucursales','sucursales.id','=','accesos.sucursal_id')->leftJoin('usuarios','usuarios.id','=','accesos.usuario_id')->when($f['sucursal_id'],fn($q)=>$q->where('accesos.sucursal_id',$f['sucursal_id']))->when(($f['metodo']??'all')!=='all',fn($q)=>$q->where('accesos.metodo',$f['metodo']))->when(($f['resultado']??'all')!=='all',fn($q)=>$q->where('accesos.resultado',$f['resultado']))->whereBetween('accesos.fecha_acceso',[$f['fecha_desde']->copy()->startOfDay(),$f['fecha_hasta']->copy()->endOfDay()])->selectRaw('accesos.id, accesos.fecha_acceso, clientes.nombre_completo cliente, clientes.telefono, clientes.email, sucursales.nombre sucursal, accesos.metodo, accesos.resultado, accesos.motivo_rechazo, CONCAT_WS(\" \", usuarios.nombre, usuarios.apellido_paterno, usuarios.apellido_materno) usuario, accesos.observaciones')->orderBy('accesos.fecha_acceso')->get(); }
    public function membresiasPorVencerExportacion(array $f){ return $this->membresiasPorVencer(['dias'=>$f['dias'],'sucursal_id'=>$f['sucursal_id'],'per_page'=>10000])->items(); }
    public function clientesVencidosExportacion(array $f){ $v=$this->vencimientoCol(); $hoy=Carbon::today()->toDateString(); $rows=DB::table('clientes')->leftJoin('sucursales','sucursales.id','=','clientes.sucursal_id')->leftJoin('membresias as m',function($j){$j->on('m.cliente_id','=','clientes.id')->whereRaw('m.id=(SELECT mm.id FROM membresias mm WHERE mm.cliente_id=clientes.id ORDER BY mm.id DESC LIMIT 1)');})->leftJoin('planes','planes.id','=','m.plan_id')->when($f['sucursal_id'],fn($q)=>$q->where('clientes.sucursal_id',$f['sucursal_id']))->selectRaw("clientes.id cliente_id, clientes.nombre_completo cliente, clientes.telefono, clientes.email, sucursales.nombre sucursal, m.id membresia_id, planes.nombre plan, m.$v fecha_vencimiento, m.estatus estatus_membresia")->get(); return $rows->filter(function($r) use ($f,$hoy){ if(!$r->membresia_id){ return (bool) $f['incluir_sin_membresia']; } return $r->fecha_vencimiento < $hoy; })->values(); }

    private function monthRange(string $mes): array { $c=Carbon::createFromFormat('Y-m',$mes); return [$c->copy()->startOfMonth(),$c->copy()->endOfMonth()]; }
    private function vencimientoCol(): string { return Schema::hasColumn('membresias','fecha_vencimiento')?'fecha_vencimiento':'fecha_fin'; }
    private function money($v): string { return number_format((float)$v,2,'.',''); }
}
