<?php

namespace App\Http\Resources\Dispositivos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;

/** @mixin \App\Models\Dispositivo */
class DispositivoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
        ];

        foreach ([
            'sucursal_id',
            'nombre',
            'clave',
            'identificador',
            'tipo',
            'descripcion',
            'ubicacion',
            'ip',
            'sistema_operativo',
            'estatus',
            'ultimo_uso_at',
            'activo',
        ] as $column) {
            if (! Schema::hasColumn('dispositivos', $column)) {
                continue;
            }

            $data[$column] = $column === 'activo'
                ? (bool) $this->{$column}
                : $this->{$column};
        }

        if ($this->relationLoaded('sucursal') && $this->sucursal) {
            $data['sucursal'] = [
                'id' => $this->sucursal->id,
                'nombre' => $this->sucursal->nombre,
                'clave' => $this->sucursal->clave,
            ];
        }

        return $data;
    }
}
