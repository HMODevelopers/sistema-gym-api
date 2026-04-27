<?php

namespace App\Http\Middleware;

use App\Exceptions\ApiException;
use App\Models\Usuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, ?string $permission = null): Response
    {
        $usuario = $request->user();

        if (! $usuario instanceof Usuario) {
            throw new ApiException('No autenticado.', 401);
        }

        if ($permission === null || trim($permission) === '') {
            throw new ApiException('Permiso no configurado para esta ruta.', 403);
        }

        if (! $usuario->hasPermission($permission)) {
            throw new ApiException('No tienes permisos para realizar esta acción.', 403);
        }

        return $next($request);
    }
}
