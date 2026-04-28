<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

class PlanesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->planes() as $planData) {
            $payload = $this->sanitizePayload($planData);

            try {
                Plan::query()->updateOrCreate(
                    ['clave' => $planData['clave']],
                    $payload,
                );
            } catch (QueryException $exception) {
                if (($planData['clave'] ?? null) !== 'DIEZ_ACCESOS' || ! Schema::hasColumn('planes', 'duracion_dias')) {
                    throw $exception;
                }

                $payload['duracion_dias'] = 30;

                Plan::query()->updateOrCreate(
                    ['clave' => $planData['clave']],
                    $payload,
                );
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function planes(): array
    {
        return [
            [
                'nombre' => 'Semanal',
                'clave' => 'SEMANAL',
                'descripcion' => 'Plan semanal de acceso al gimnasio',
                'precio' => 180.00,
                'duracion_dias' => 7,
                'tipo_plan' => 'SEMANAL',
                'accesos_incluidos' => null,
                'activo' => true,
            ],
            [
                'nombre' => 'Quincenal',
                'clave' => 'QUINCENAL',
                'descripcion' => 'Plan quincenal de acceso al gimnasio',
                'precio' => 300.00,
                'duracion_dias' => 15,
                'tipo_plan' => 'QUINCENAL',
                'accesos_incluidos' => null,
                'activo' => true,
            ],
            [
                'nombre' => 'Mensual',
                'clave' => 'MENSUAL',
                'descripcion' => 'Plan mensual de acceso al gimnasio',
                'precio' => 500.00,
                'duracion_dias' => 30,
                'tipo_plan' => 'MENSUAL',
                'accesos_incluidos' => null,
                'activo' => true,
            ],
            [
                'nombre' => 'Trimestral',
                'clave' => 'TRIMESTRAL',
                'descripcion' => 'Plan trimestral de acceso al gimnasio',
                'precio' => 1350.00,
                'duracion_dias' => 90,
                'tipo_plan' => 'TRIMESTRAL',
                'accesos_incluidos' => null,
                'activo' => true,
            ],
            [
                'nombre' => 'Anual',
                'clave' => 'ANUAL',
                'descripcion' => 'Plan anual de acceso al gimnasio',
                'precio' => 4800.00,
                'duracion_dias' => 365,
                'tipo_plan' => 'ANUAL',
                'accesos_incluidos' => null,
                'activo' => true,
            ],
            [
                'nombre' => '10 Accesos',
                'clave' => 'DIEZ_ACCESOS',
                'descripcion' => 'Plan por paquete de 10 accesos',
                'precio' => 350.00,
                'duracion_dias' => null,
                'tipo_plan' => 'POR_ACCESOS',
                'accesos_incluidos' => 10,
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
        $columns = [
            'nombre',
            'descripcion',
            'precio',
            'duracion_dias',
            'tipo_plan',
            'accesos_incluidos',
            'activo',
        ];

        $availableColumns = array_values(array_filter($columns, static fn (string $column): bool => Schema::hasColumn('planes', $column)));

        $data = collect($payload)->only($availableColumns)->all();

        return $data;
    }
}
