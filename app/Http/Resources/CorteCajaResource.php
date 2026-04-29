<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CorteCajaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sucursal' => $this->whenLoaded('sucursal', fn () => [
                'id' => $this->sucursal?->id,
                'nombre' => $this->sucursal?->nombre,
                'clave' => $this->sucursal?->clave,
            ]),
            'usuario' => $this->whenLoaded('usuario', fn () => [
                'id' => $this->usuario?->id,
                'nombre_completo' => trim(collect([$this->usuario?->nombre, $this->usuario?->apellido_paterno, $this->usuario?->apellido_materno])->filter()->implode(' ')),
                'username' => $this->usuario?->username,
            ]),
            'sucursal_id' => $this->sucursal_id,
            'usuario_id' => $this->usuario_id,
            'fecha_desde' => optional($this->fecha_desde)?->format('Y-m-d H:i:s'),
            'fecha_hasta' => optional($this->fecha_hasta)?->format('Y-m-d H:i:s'),
            'total_pagos' => (int) $this->total_pagos,
            'total_monto' => (string) $this->total_monto,
            'total_cancelados' => (int) $this->total_cancelados,
            'total_cancelado_monto' => (string) $this->total_cancelado_monto,
            'efectivo_esperado' => (string) $this->efectivo_esperado,
            'efectivo_contado' => (string) $this->efectivo_contado,
            'diferencia_efectivo' => (string) $this->diferencia_efectivo,
            'detalle_metodos_pago' => $this->detalle_metodos_pago,
            'observaciones' => $this->observaciones,
            'estatus' => $this->estatus,
            'motivo_cancelacion' => $this->motivo_cancelacion,
            'cancelado_at' => optional($this->cancelado_at)?->format('Y-m-d H:i:s'),
            'cancelado_por_usuario_id' => $this->cancelado_por_usuario_id,
            'activo' => (bool) $this->activo,
        ];
    }
}
