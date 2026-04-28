<?php

namespace App\Services;

use App\Enums\UsuarioEstatus;
use App\Exceptions\ApiException;
use App\Http\Resources\Auth\UsuarioAuthResource;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function __construct(private readonly AuditoriaService $auditoriaService) {}

    public function login(string $login, string $password): array
    {
        $usuario = Usuario::query()
            ->where('username', $login)
            ->orWhere('email', $login)
            ->with('roles.permisos')
            ->first();

        if (! $usuario || ! Hash::check($password, $usuario->getAuthPassword())) {
            $this->auditoriaService->registrar(
                modulo: 'AUTH',
                accion: 'LOGIN_FALLIDO',
                entidad: 'Usuario',
                descripcion: 'Intento de inicio de sesión fallido.',
                valoresNuevos: ['login' => $login],
            );
            throw new ApiException('Credenciales inválidas.', 401);
        }

        $this->validarEstatus($usuario);

        $usuario->forceFill([
            'ultimo_acceso_at' => now(),
        ])->save();

        $token = $usuario->createToken('auth_token')->plainTextToken;

        $this->auditoriaService->registrar(
            modulo: 'AUTH',
            accion: 'LOGIN_EXITOSO',
            entidad: 'Usuario',
            entidadId: $usuario->id,
            descripcion: 'Inicio de sesión exitoso.',
            sucursalId: $usuario->sucursal_id,
        );

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
        $this->auditoriaService->registrar(
            modulo: 'AUTH',
            accion: 'LOGOUT',
            entidad: 'Usuario',
            entidadId: $usuario->id,
            descripcion: 'Cierre de sesión.',
            sucursalId: $usuario->sucursal_id,
        );

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
            'usuario' => new UsuarioAuthResource($usuario),
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
