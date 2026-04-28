<?php

namespace App\Http\Resources\Pagos;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;

/** @mixin \App\Models\Pago */
class PagoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'cliente_id' => $this->cliente_id,
        ];

        foreach ([
            'membresia_id',
            'sucursal_id',
            'metodo_pago_id',
            'usuario_id',
            'concepto',
            'monto',
            'fecha_pago',
            'referencia',
            'observaciones',
            'estatus',
            'motivo_cancelacion',
            'cancelado_at',
            'cancelado_por_usuario_id',
            'activo',
        ] as $column) {
            if (! Schema::hasColumn('pagos', $column)) {
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

            foreach (['telefono', 'email'] as $column) {
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

        if ($this->relationLoaded('metodoPago') && $this->metodoPago) {
            $data['metodo_pago'] = [
                'id' => $this->metodoPago->id,
                'nombre' => $this->metodoPago->nombre,
                'clave' => $this->metodoPago->clave,
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
