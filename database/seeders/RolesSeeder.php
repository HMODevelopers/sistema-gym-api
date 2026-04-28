<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'clave' => 'ADMIN',
                'nombre' => 'Administrador',
                'descripcion' => 'Acceso total al sistema',
            ],
            [
                'clave' => 'RECEPCIONISTA',
                'nombre' => 'Recepcionista',
                'descripcion' => 'Operación diaria de recepción, clientes, membresías, pagos básicos, accesos y enrolamiento biométrico',
            ],
            [
                'clave' => 'CAJERO',
                'nombre' => 'Cajero',
                'descripcion' => 'Registro y consulta de pagos, consulta básica de clientes y membresías',
            ],
            [
                'clave' => 'ENCARGADO',
                'nombre' => 'Encargado',
                'descripcion' => 'Supervisión operativa de sucursal, reportes, clientes, membresías, pagos y accesos',
            ],
            [
                'clave' => 'SUPERVISOR',
                'nombre' => 'Supervisor',
                'descripcion' => 'Consulta, seguimiento, auditoría y reportes operativos',
            ],
        ];

        foreach ($roles as $rol) {
            Rol::updateOrCreate(
                ['clave' => $rol['clave']],
                [
                    'nombre' => $rol['nombre'],
                    'descripcion' => $rol['descripcion'],
                    'activo' => true,
                ],
            );
        }
    }
}
