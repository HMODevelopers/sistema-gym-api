<?php

namespace Database\Seeders;

use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class SucursalBaseSeeder extends Seeder
{
    public function run(): void
    {
        $sucursalPrincipal = Sucursal::query()
            ->where('activo', true)
            ->where('es_principal', true)
            ->first();

        if ($sucursalPrincipal) {
            return;
        }

        $sucursalActiva = Sucursal::query()
            ->where('activo', true)
            ->orderBy('id')
            ->first();

        if ($sucursalActiva) {
            if (! $sucursalActiva->es_principal) {
                $sucursalActiva->forceFill(['es_principal' => true])->save();
            }

            return;
        }

        Sucursal::updateOrCreate(
            ['clave' => 'MATRIZ'],
            [
                'nombre' => 'Sucursal Matriz',
                'activo' => true,
                'es_principal' => true,
            ],
        );
    }
}
