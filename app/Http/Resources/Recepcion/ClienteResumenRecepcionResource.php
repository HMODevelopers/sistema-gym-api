<?php

namespace App\Http\Resources\Recepcion;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClienteResumenRecepcionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $cliente = $this['cliente'];

        return [
            'cliente' => [
                'id' => $cliente->id,
                'sucursal_id' => $cliente->sucursal_id,
                'sucursal' => $cliente->sucursal ? [
                    'id' => $cliente->sucursal->id,
                    'nombre' => $cliente->sucursal->nombre,
                    'clave' => $cliente->sucursal->clave,
                ] : null,
                'nombre_completo' => $cliente->nombre_completo,
                'telefono' => $cliente->telefono,
                'email' => $cliente->email,
                'estatus' => $cliente->estatus,
                'activo' => (bool) $cliente->activo,
            ],
            'membresia_actual' => $this['membresia_actual'],
            'puede_ingresar' => $this['puede_ingresar'],
            'motivo_no_ingreso' => $this['motivo_no_ingreso'],
            'ultimo_pago' => $this['ultimo_pago'],
            'ultimo_acceso' => $this['ultimo_acceso'],
            'biometricos' => $this['biometricos'],
            'alertas' => $this['alertas'],
        ];
    }
}
