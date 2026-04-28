<?php

namespace Database\Seeders;

use App\Models\MetodoPago;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class MetodosPagoSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->metodosPago() as $metodoPago) {
            MetodoPago::query()->updateOrCreate(
                ['clave' => $metodoPago['clave']],
                $this->sanitizePayload($metodoPago),
            );
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function metodosPago(): array
    {
        return [
            [
                'nombre' => 'Efectivo',
                'clave' => 'EFECTIVO',
                'descripcion' => 'Pago en efectivo',
                'requiere_referencia' => false,
                'activo' => true,
            ],
            [
                'nombre' => 'Tarjeta de débito',
                'clave' => 'TARJETA_DEBITO',
                'descripcion' => 'Pago con tarjeta de débito',
                'requiere_referencia' => true,
                'activo' => true,
            ],
            [
                'nombre' => 'Tarjeta de crédito',
                'clave' => 'TARJETA_CREDITO',
                'descripcion' => 'Pago con tarjeta de crédito',
                'requiere_referencia' => true,
                'activo' => true,
            ],
            [
                'nombre' => 'Transferencia',
                'clave' => 'TRANSFERENCIA',
                'descripcion' => 'Pago mediante transferencia bancaria',
                'requiere_referencia' => true,
                'activo' => true,
            ],
            [
                'nombre' => 'Depósito',
                'clave' => 'DEPOSITO',
                'descripcion' => 'Pago mediante depósito bancario',
                'requiere_referencia' => true,
                'activo' => true,
            ],
            [
                'nombre' => 'Cortesía',
                'clave' => 'CORTESIA',
                'descripcion' => 'Movimiento sin cobro autorizado',
                'requiere_referencia' => false,
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
        $columns = ['nombre', 'descripcion', 'requiere_referencia', 'activo'];

        $availableColumns = array_values(array_filter($columns, static fn (string $column): bool => Schema::hasColumn('metodos_pago', $column)));

        return collect($payload)->only($availableColumns)->all();
    }
}
