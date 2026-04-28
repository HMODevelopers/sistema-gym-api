<?php

namespace Database\Seeders;

use App\Enums\UsuarioEstatus;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuariosPruebaSeeder extends Seeder
{
    public function run(): void
    {
        $sucursal = $this->resolveSucursal();

        $roles = Rol::query()
            ->whereIn('clave', ['ADMIN', 'RECEPCIONISTA', 'CAJERO', 'ENCARGADO', 'SUPERVISOR'])
            ->pluck('id', 'clave');

        foreach ($this->usuariosBase() as $usuarioBase) {
            $usuario = Usuario::firstOrCreate(
                ['email' => $usuarioBase['email']],
                [
                    'sucursal_id' => $sucursal->id,
                    'nombre' => $usuarioBase['nombre'],
                    'apellido_paterno' => $usuarioBase['apellido_paterno'],
                    'apellido_materno' => $usuarioBase['apellido_materno'],
                    'username' => $usuarioBase['username'],
                    'password_hash' => Hash::make($usuarioBase['password']),
                    'estatus' => UsuarioEstatus::ACTIVO->value,
                    'activo' => true,
                ],
            );

            $usuario->forceFill([
                'sucursal_id' => $usuario->sucursal_id ?: $sucursal->id,
                'nombre' => $usuario->nombre ?: $usuarioBase['nombre'],
                'apellido_paterno' => $usuario->apellido_paterno ?: $usuarioBase['apellido_paterno'],
                'apellido_materno' => $usuario->apellido_materno ?: $usuarioBase['apellido_materno'],
                'username' => $usuario->username ?: $usuarioBase['username'],
                'estatus' => $usuario->estatus ?: UsuarioEstatus::ACTIVO->value,
                'activo' => $usuario->activo ?? true,
            ])->save();

            $rolId = $roles->get($usuarioBase['rol']);
            if ($rolId) {
                $usuario->roles()->syncWithoutDetaching([$rolId]);
            }
        }
    }

    private function resolveSucursal(): Sucursal
    {
        $sucursal = Sucursal::query()
            ->where('activo', true)
            ->where('es_principal', true)
            ->first();

        if ($sucursal) {
            return $sucursal;
        }

        $sucursalActiva = Sucursal::query()
            ->where('activo', true)
            ->orderBy('id')
            ->first();

        if ($sucursalActiva) {
            if (! $sucursalActiva->es_principal) {
                $sucursalActiva->forceFill(['es_principal' => true])->save();
            }

            return $sucursalActiva;
        }

        return Sucursal::updateOrCreate(
            ['clave' => 'MATRIZ'],
            [
                'nombre' => 'Sucursal Matriz',
                'activo' => true,
                'es_principal' => true,
            ],
        );
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function usuariosBase(): array
    {
        return [
            [
                'nombre' => 'Carlos',
                'apellido_paterno' => 'Admin',
                'apellido_materno' => 'Sistema',
                'email' => 'admin@sistemagym.local',
                'username' => 'admin',
                'password' => 'password',
                'rol' => 'ADMIN',
            ],
            [
                'nombre' => 'Rosa',
                'apellido_paterno' => 'Recepcionista',
                'apellido_materno' => 'Sistema',
                'email' => 'recepcionista@sistemagym.local',
                'username' => 'recepcionista',
                'password' => 'password',
                'rol' => 'RECEPCIONISTA',
            ],
            [
                'nombre' => 'Carlos',
                'apellido_paterno' => 'Cajero',
                'apellido_materno' => 'Sistema',
                'email' => 'cajero@sistemagym.local',
                'username' => 'cajero',
                'password' => 'password',
                'rol' => 'CAJERO',
            ],
            [
                'nombre' => 'Elena',
                'apellido_paterno' => 'Encargada',
                'apellido_materno' => 'Sistema',
                'email' => 'encargado@sistemagym.local',
                'username' => 'encargado',
                'password' => 'password',
                'rol' => 'ENCARGADO',
            ],
            [
                'nombre' => 'Sergio',
                'apellido_paterno' => 'Supervisor',
                'apellido_materno' => 'Sistema',
                'email' => 'supervisor@sistemagym.local',
                'username' => 'supervisor',
                'password' => 'password',
                'rol' => 'SUPERVISOR',
            ],
        ];
    }
}
