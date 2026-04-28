<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\AuditoriaEvento */
class AuditoriaEventoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sucursal_id' => $this->sucursal_id,
            'sucursal' => $this->whenLoaded('sucursal', function (): ?array {
                if (! $this->sucursal) {
                    return null;
                }

                return [
                    'id' => $this->sucursal->id,
                    'nombre' => $this->sucursal->nombre,
                    'clave' => $this->sucursal->clave,
                ];
            }),
            'usuario_id' => $this->usuario_id,
            'usuario' => $this->whenLoaded('usuario', function (): ?array {
                if (! $this->usuario) {
                    return null;
                }

                $nombreCompleto = trim(collect([
                    $this->usuario->nombre ?? null,
                    $this->usuario->apellido_paterno ?? null,
                    $this->usuario->apellido_materno ?? null,
                ])->filter(static fn ($value): bool => filled($value))->implode(' '));

                return [
                    'id' => $this->usuario->id,
                    'nombre_completo' => $nombreCompleto,
                    'username' => $this->usuario->username,
                ];
            }),
            'entidad' => $this->entidad,
            'entidad_id' => $this->entidad_id,
            'accion' => $this->accion,
            'descripcion' => $this->descripcion,
            'datos_antes' => $this->datos_antes,
            'datos_despues' => $this->datos_despues,
            'ip' => $this->ip,
            'user_agent' => $this->user_agent,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
