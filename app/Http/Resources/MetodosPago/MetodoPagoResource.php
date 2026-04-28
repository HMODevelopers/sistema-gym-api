<?php

namespace App\Http\Resources\MetodosPago;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;

/** @mixin \App\Models\MetodoPago */
class MetodoPagoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'clave' => $this->clave,
        ];

        if (Schema::hasColumn('metodos_pago', 'descripcion')) {
            $data['descripcion'] = $this->descripcion;
        }

        if (Schema::hasColumn('metodos_pago', 'requiere_referencia')) {
            $data['requiere_referencia'] = (bool) $this->requiere_referencia;
        }

        if (Schema::hasColumn('metodos_pago', 'activo')) {
            $data['activo'] = (bool) $this->activo;
        }

        return $data;
    }
}
