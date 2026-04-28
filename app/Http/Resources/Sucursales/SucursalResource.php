<?php

namespace App\Http\Resources\Sucursales;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Sucursal */
class SucursalResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'clave' => $this->clave,
            'direccion' => $this->direccion,
            'telefono' => $this->telefono,
            'correo' => $this->correo,
            'es_principal' => (bool) $this->es_principal,
            'activo' => (bool) $this->activo,
        ];
    }
}
