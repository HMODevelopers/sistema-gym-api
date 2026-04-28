<?php

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolPermisosSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Rol::query()->whereIn('clave', [
            'ADMIN',
            'RECEPCIONISTA',
            'CAJERO',
            'ENCARGADO',
            'SUPERVISOR',
        ])->get()->keyBy('clave');

        if ($roles->isEmpty()) {
            return;
        }

        $activosByClave = Permiso::query()
            ->where('activo', true)
            ->pluck('id', 'clave');

        /** @var Rol|null $admin */
        $admin = $roles->get('ADMIN');
        if ($admin) {
            $admin->permisos()->sync($activosByClave->values()->all());
        }

        foreach ($this->permisosPorRol() as $rolClave => $permisosClaves) {
            if ($rolClave === 'ADMIN') {
                continue;
            }

            /** @var Rol|null $rol */
            $rol = $roles->get($rolClave);

            if (! $rol) {
                continue;
            }

            $ids = collect($permisosClaves)
                ->map(static fn (string $clave) => $activosByClave->get($clave))
                ->filter()
                ->values()
                ->all();

            $rol->permisos()->sync($ids);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function permisosPorRol(): array
    {
        return [
            'RECEPCIONISTA' => [
                'clientes.ver',
                'clientes.crear',
                'clientes.editar',
                'clientes.cambiar_estatus',
                'membresias.ver',
                'membresias.crear',
                'membresias.renovar',
                'pagos.ver',
                'pagos.registrar',
                'accesos.ver',
                'accesos.validar',
                'biometricos.ver',
                'biometricos.enrolar',
                'planes.ver',
                'metodos_pago.ver',
                'sucursales.ver',
                'dispositivos.ver',
            ],
            'CAJERO' => [
                'clientes.ver',
                'membresias.ver',
                'pagos.ver',
                'pagos.registrar',
                'planes.ver',
                'metodos_pago.ver',
                'sucursales.ver',
            ],
            'ENCARGADO' => [
                'clientes.ver',
                'clientes.crear',
                'clientes.editar',
                'clientes.desactivar',
                'clientes.cambiar_estatus',
                'membresias.ver',
                'membresias.crear',
                'membresias.editar',
                'membresias.renovar',
                'membresias.suspender',
                'membresias.cancelar',
                'pagos.ver',
                'pagos.registrar',
                'pagos.cancelar',
                'accesos.ver',
                'accesos.validar',
                'biometricos.ver',
                'biometricos.enrolar',
                'biometricos.eliminar',
                'planes.ver',
                'metodos_pago.ver',
                'sucursales.ver',
                'dispositivos.ver',
                'dispositivos.crear',
                'dispositivos.editar',
                'dispositivos.desactivar',
                'reportes.ver',
                'reportes.exportar',
                'auditoria.ver',
            ],
            'SUPERVISOR' => [
                'clientes.ver',
                'membresias.ver',
                'pagos.ver',
                'accesos.ver',
                'biometricos.ver',
                'planes.ver',
                'metodos_pago.ver',
                'sucursales.ver',
                'dispositivos.ver',
                'reportes.ver',
                'reportes.exportar',
                'auditoria.ver',
                'usuarios.ver',
                'roles.ver',
            ],
        ];
    }
}
