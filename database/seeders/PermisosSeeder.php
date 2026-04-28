<?php

namespace Database\Seeders;

use App\Models\Permiso;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermisosSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->catalogoPermisos() as $permiso) {
            Permiso::updateOrCreate(
                ['clave' => $permiso['clave']],
                [
                    'modulo' => $permiso['modulo'],
                    'nombre' => $permiso['nombre'],
                    'descripcion' => $permiso['descripcion'],
                    'activo' => true,
                ],
            );
        }
    }

    /**
     * @return array<int, array{modulo:string, clave:string, nombre:string, descripcion:string}>
     */
    private function catalogoPermisos(): array
    {
        $claves = [
            'accesos.validar',
            'accesos.ver',
            'auditoria.ver',
            'biometricos.eliminar',
            'biometricos.enrolar',
            'biometricos.ver',
            'clientes.cambiar_estatus',
            'clientes.crear',
            'clientes.desactivar',
            'clientes.editar',
            'clientes.ver',
            'membresias.cancelar',
            'membresias.crear',
            'membresias.editar',
            'membresias.renovar',
            'membresias.suspender',
            'membresias.ver',
            'metodos_pago.crear',
            'metodos_pago.desactivar',
            'metodos_pago.editar',
            'metodos_pago.ver',
            'pagos.cancelar',
            'pagos.registrar',
            'pagos.ver',
            'planes.crear',
            'planes.desactivar',
            'planes.editar',
            'planes.ver',
            'reportes.exportar',
            'reportes.ver',
            'roles.asignar_permisos',
            'roles.crear',
            'roles.desactivar',
            'roles.editar',
            'roles.ver',
            'sucursales.crear',
            'sucursales.desactivar',
            'sucursales.editar',
            'sucursales.ver',
            'usuarios.asignar_roles',
            'usuarios.cambiar_password',
            'usuarios.crear',
            'usuarios.desactivar',
            'usuarios.editar',
            'usuarios.ver',
        ];

        return collect($claves)
            ->map(function (string $clave): array {
                [$modulo, $accion] = explode('.', $clave, 2);

                return [
                    'modulo' => $modulo,
                    'clave' => $clave,
                    'nombre' => sprintf('%s %s', Str::headline($accion), Str::headline($modulo)),
                    'descripcion' => sprintf('Permite %s en el módulo de %s.', str_replace('_', ' ', $accion), str_replace('_', ' ', $modulo)),
                ];
            })
            ->values()
            ->all();
    }
}
