<?php

namespace App\Http\Resources\Membresias;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;

/** @mixin \App\Models\Membresia */
class MembresiaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'plan_id' => $this->plan_id,
        ];

        if (Schema::hasColumn('membresias', 'sucursal_id')) {
            $data['sucursal_id'] = $this->sucursal_id;
        }

        foreach ([
            'fecha_inicio',
            'fecha_fin',
            'fecha_vencimiento',
            'estatus',
            'accesos_totales',
            'accesos_usados',
            'accesos_disponibles',
            'precio',
            'observaciones',
            'activo',
        ] as $column) {
            if (! Schema::hasColumn('membresias', $column)) {
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

        if ($this->relationLoaded('plan') && $this->plan) {
            $plan = [
                'id' => $this->plan->id,
                'nombre' => $this->plan->nombre,
                'clave' => $this->plan->clave,
            ];

            if (Schema::hasColumn('planes', 'precio')) {
                $plan['precio'] = $this->plan->precio;
            }

            $data['plan'] = $plan;
        }

        if (Schema::hasColumn('membresias', 'sucursal_id') && $this->relationLoaded('sucursal') && $this->sucursal) {
            $data['sucursal'] = [
                'id' => $this->sucursal->id,
                'nombre' => $this->sucursal->nombre,
                'clave' => $this->sucursal->clave,
            ];
        }

        if (Schema::hasColumn('membresias', 'estatus') && Schema::hasColumn('membresias', 'fecha_vencimiento')) {
            $data['esta_vencida'] = $this->estatus === 'ACTIVA'
                && $this->fecha_vencimiento
                && Carbon::parse($this->fecha_vencimiento)->isPast();
        }

        return $data;
    }
}
