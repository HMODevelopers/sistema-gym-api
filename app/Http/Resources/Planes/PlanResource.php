<?php

namespace App\Http\Resources\Planes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;

/** @mixin \App\Models\Plan */
class PlanResource extends JsonResource
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

        foreach (['descripcion', 'precio', 'duracion_dias', 'tipo_plan', 'accesos_incluidos'] as $column) {
            if (Schema::hasColumn('planes', $column)) {
                $data[$column] = $this->{$column};
            }
        }

        $data['activo'] = (bool) $this->activo;

        return $data;
    }
}
