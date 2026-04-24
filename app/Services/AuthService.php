<?php

namespace App\Services;

use App\Enums\UsuarioEstatus;
use App\Exceptions\ApiException;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function login(string $login, string $password): array
    {
        $usuario = Usuario::query()
            ->where('username', $login)
            ->orWhere('email', $login)
            ->with('roles.permisos')
            ->first();

        if (! $usuario || ! Hash::check($password, $usuario->getAuthPassword())) {
            throw new ApiException('Credenciales inválidas.', 401);
        }

        $this->validarEstatus($usuario);

        $usuario->forceFill([
            'ultimo_acceso_at' => now(),
        ])->save();

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return $this->buildAuthPayload($usuario->fresh('roles.permisos'), $token);
    }

    public function me(Usuario $usuario): array
    {
        $usuario->loadMissing('roles.permisos');
        $this->validarEstatus($usuario);

        return $this->buildAuthPayload($usuario);
    }

    public function logout(Usuario $usuario): void
    {
        $tokenActual = $usuario->currentAccessToken();

        if ($tokenActual) {
            $tokenActual->delete();
        }
    }

    private function validarEstatus(Usuario $usuario): void
    {
        if (! $usuario->activo) {
            throw new ApiException('Tu usuario está inactivo.', 403);
        }

        if ($usuario->estatus === UsuarioEstatus::BLOQUEADO) {
            throw new ApiException('Tu usuario está bloqueado.', 403);
        }

        if ($usuario->estatus !== UsuarioEstatus::ACTIVO) {
            throw new ApiException('Tu usuario no tiene permitido iniciar sesión.', 403);
        }
    }

    private function buildAuthPayload(Usuario $usuario, ?string $token = null): array
    {
        $payload = [
            'usuario' => $usuario,
            'auth' => [
                'roles' => $usuario->clavesRoles(),
                'permisos' => $usuario->permisosEfectivos(),
            ],
        ];

        if ($token !== null) {
            $payload['token'] = $token;
        }

        return $payload;
    }
}
