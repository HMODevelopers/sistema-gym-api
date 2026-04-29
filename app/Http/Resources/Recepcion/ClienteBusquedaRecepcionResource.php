<?php

namespace App\Http\Resources\Recepcion;

use App\Models\Membresia;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Cliente */
class ClienteBusquedaRecepcionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Membresia|null $membresia */
        $membresia = $this->getRelation('membresia_recepcion');
        $fecha = $membresia?->fecha_vencimiento ?? $membresia?->fecha_fin;

        return [
            'id' => $this->id,
            'sucursal_id' => $this->sucursal_id,
            'sucursal' => $this->sucursal ? [
                'id' => $this->sucursal->id,
                'nombre' => $this->sucursal->nombre,
                'clave' => $this->sucursal->clave,
            ] : null,
            'nombre_completo' => $this->nombre_completo,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'estatus' => $this->estatus,
            'activo' => (bool) $this->activo,
            'membresia_resumen' => [
                'tiene_membresia' => (bool) $membresia,
                'estatus' => $membresia?->estatus,
                'fecha_vencimiento' => $fecha,
                'esta_vencida' => $fecha ? Carbon::parse($fecha)->lt(Carbon::today()) : false,
            ],
        ];
    }
}
