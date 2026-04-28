<?php

namespace App\Http\Resources\Clientes;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;

/** @mixin \App\Models\Cliente */
class ClienteResource extends JsonResource
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
            'apellido_paterno',
            'apellido_materno',
            'nombre_completo',
            'telefono',
            'email',
            'fecha_nacimiento',
            'contacto_emergencia_nombre',
            'contacto_emergencia_telefono',
            'foto_url',
            'fecha_inscripcion',
            'estatus',
            'notas',
            'activo',
        ] as $column) {
            if (Schema::hasColumn('clientes', $column)) {
                $data[$column] = $column === 'activo'
                    ? (bool) $this->{$column}
                    : $this->{$column};
            }
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
