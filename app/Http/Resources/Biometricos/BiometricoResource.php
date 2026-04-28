<?php

namespace App\Http\Resources\Biometricos;

use App\Models\ClienteBiometrico;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Schema;

/** @mixin \App\Models\ClienteBiometrico */
class BiometricoResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $table = ClienteBiometrico::tableName();

        $data = [
            'id' => $this->id,
        ];

        foreach ([
            'cliente_id',
            'dispositivo_id',
            'identificador_biometrico',
            'dedo',
            'es_principal',
            'calidad_lectura',
            'intentos_fallidos',
            'estatus',
            'observaciones',
            'enrolado_at',
            'activo',
        ] as $column) {
            if (! Schema::hasColumn($table, $column)) {
                continue;
            }

            $data[$column] = in_array($column, ['activo', 'es_principal'], true)
                ? (bool) $this->{$column}
                : $this->{$column};
        }

        if ($this->relationLoaded('cliente') && $this->cliente) {
            $data['cliente'] = [
                'id' => $this->cliente->id,
                'nombre_completo' => $this->cliente->nombre_completo,
                'telefono' => $this->cliente->telefono,
                'email' => $this->cliente->email,
                'estatus' => $this->cliente->estatus,
            ];
        }

        if ($this->relationLoaded('dispositivo') && $this->dispositivo) {
            $data['dispositivo'] = [
                'id' => $this->dispositivo->id,
                'nombre' => $this->dispositivo->nombre,
                'clave' => $this->dispositivo->clave,
                'identificador' => $this->dispositivo->identificador,
            ];
        }

        return $data;
    }
}
