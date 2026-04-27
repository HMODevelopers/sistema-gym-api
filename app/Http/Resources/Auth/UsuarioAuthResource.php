<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Usuario */
class UsuarioAuthResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $nombreCompleto = collect([
            $this->nombre,
            $this->apellido_paterno,
            $this->apellido_materno,
        ])->filter()->implode(' ');

        return [
            'id' => $this->id,
            'sucursal_id' => $this->sucursal_id,
            'nombre' => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'nombre_completo' => $nombreCompleto,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'username' => $this->username,
            'foto_url' => $this->foto_url,
            'estatus' => $this->estatus?->value ?? (string) $this->estatus,
            'ultimo_acceso_at' => $this->ultimo_acceso_at?->utc()->format('Y-m-d\\TH:i:s.u\\Z'),
            'activo' => (bool) $this->activo,
        ];
    }
}
