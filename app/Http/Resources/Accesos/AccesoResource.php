<?php

namespace App\Http\Resources\Accesos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;

/** @mixin \App\Models\Acceso */
class AccesoResource extends JsonResource
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
            'cliente_id',
            'membresia_id',
            'sucursal_id',
            'usuario_id',
            'dispositivo_id',
            'metodo',
            'resultado',
            'motivo_rechazo',
            'fecha_acceso',
            'observaciones',
            'activo',
        ] as $column) {
            if (! Schema::hasColumn('accesos', $column)) {
                continue;
            }

            $data[$column] = $column === 'activo'
                ? (bool) $this->{$column}
                : $this->{$column};
        }

        if ($this->relationLoaded('cliente') && $this->cliente) {
            $nombreCompleto = $this->cliente->nombre_completo
                ?? trim(collect([
                    $this->cliente->nombre ?? null,
                    $this->cliente->apellido_paterno ?? null,
                    $this->cliente->apellido_materno ?? null,
                ])->filter(static fn ($value): bool => filled($value))->implode(' '));

            $cliente = [
                'id' => $this->cliente->id,
                'nombre_completo' => $nombreCompleto,
            ];

            foreach (['telefono', 'email', 'estatus'] as $column) {
                if (Schema::hasColumn('clientes', $column)) {
                    $cliente[$column] = $this->cliente->{$column};
                }
            }

            $data['cliente'] = $cliente;
        }

        if ($this->relationLoaded('membresia') && $this->membresia) {
            $membresia = [
                'id' => $this->membresia->id,
            ];

            foreach (['fecha_inicio', 'fecha_vencimiento', 'estatus'] as $column) {
                if (Schema::hasColumn('membresias', $column)) {
                    $membresia[$column] = $this->membresia->{$column};
                }
            }

            if ($this->membresia->relationLoaded('plan') && $this->membresia->plan) {
                $membresia['plan'] = [
                    'id' => $this->membresia->plan->id,
                    'nombre' => $this->membresia->plan->nombre,
                    'clave' => $this->membresia->plan->clave,
                ];
            }

            $data['membresia'] = $membresia;
        }

        if ($this->relationLoaded('sucursal') && $this->sucursal) {
            $data['sucursal'] = [
                'id' => $this->sucursal->id,
                'nombre' => $this->sucursal->nombre,
                'clave' => $this->sucursal->clave,
            ];
        }

        if ($this->relationLoaded('usuario') && $this->usuario) {
            $nombreCompleto = trim(collect([
                $this->usuario->nombre ?? null,
                $this->usuario->apellido_paterno ?? null,
                $this->usuario->apellido_materno ?? null,
            ])->filter(static fn ($value): bool => filled($value))->implode(' '));

            $data['usuario'] = [
                'id' => $this->usuario->id,
                'nombre_completo' => $nombreCompleto,
                'username' => $this->usuario->username,
            ];
        }

        return $data;
    }
}
