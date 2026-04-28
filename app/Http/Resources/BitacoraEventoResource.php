<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\BitacoraEvento */
class BitacoraEventoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
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
            'modulo' => $this->modulo,
            'accion' => $this->accion,
            'entidad' => $this->entidad,
            'entidad_id' => $this->entidad_id,
            'descripcion' => $this->descripcion,
            'valores_anteriores' => $this->valores_anteriores,
            'valores_nuevos' => $this->valores_nuevos,
            'ip' => $this->ip,
            'user_agent' => $this->user_agent,
            'metodo_http' => $this->metodo_http,
            'ruta' => $this->ruta,
            'request_id' => $this->request_id,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
