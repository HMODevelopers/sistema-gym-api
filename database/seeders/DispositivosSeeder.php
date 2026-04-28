<?php

namespace Database\Seeders;

use App\Enums\DispositivoEstatus;
use App\Models\Dispositivo;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DispositivosSeeder extends Seeder
{
    public function run(): void
    {
        $sucursal = Sucursal::query()
            ->when(Schema::hasColumn('sucursales', 'activo'), fn ($query) => $query->where('activo', true))
            ->orderByRaw("CASE WHEN UPPER(clave) = 'MATRIZ' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        if (! $sucursal) {
            return;
        }

        foreach ($this->dispositivos((int) $sucursal->id) as $dispositivo) {
            Dispositivo::query()->updateOrCreate(
                ['clave' => $dispositivo['clave']],
                $this->sanitizePayload($dispositivo),
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function dispositivos(int $sucursalId): array
    {
        return [
            [
                'sucursal_id' => $sucursalId,
                'nombre' => 'PC Recepción Matriz',
                'clave' => 'PC_RECEPCION_MATRIZ',
                'identificador' => 'DEVICE-MATRIZ-PC-001',
                'tipo' => 'PC_RECEPCION',
                'descripcion' => 'Equipo principal de recepción de la sucursal matriz',
                'ubicacion' => 'Recepción',
                'ip' => null,
                'sistema_operativo' => 'Windows',
                'estatus' => DispositivoEstatus::ACTIVO->value,
                'activo' => true,
            ],
            [
                'sucursal_id' => $sucursalId,
                'nombre' => 'Lector Huella Matriz',
                'clave' => 'LECTOR_HUELLA_MATRIZ',
                'identificador' => 'DEVICE-MATRIZ-FINGER-001',
                'tipo' => 'LECTOR_HUELLA',
                'descripcion' => 'Lector biométrico principal de recepción',
                'ubicacion' => 'Recepción',
                'ip' => null,
                'sistema_operativo' => null,
                'estatus' => DispositivoEstatus::ACTIVO->value,
                'activo' => true,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        return collect($payload)
            ->only(array_values(array_filter([
                'sucursal_id',
                'nombre',
                'identificador',
                'tipo',
                'descripcion',
                'ubicacion',
                'ip',
                'sistema_operativo',
                'estatus',
                'activo',
            ], static fn (string $column): bool => Schema::hasColumn('dispositivos', $column))))
            ->all();
    }
}
