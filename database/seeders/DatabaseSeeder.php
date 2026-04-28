<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SucursalBaseSeeder::class,
            RolesSeeder::class,
            PermisosSeeder::class,
            RolPermisosSeeder::class,
            MetodosPagoSeeder::class,
            PlanesSeeder::class,
            UsuariosPruebaSeeder::class,
        ]);
    }
}
