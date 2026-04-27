<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ApiException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Usuario;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $payload = $this->authService->login(
            login: $request->string('login')->toString(),
            password: $request->string('password')->toString(),
        );

        return response()->json([
            'message' => 'Login exitoso.',
            'data' => $payload,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $usuario = $this->resolveUsuario($request);
        $payload = $this->authService->me($usuario);

        return response()->json([
            'message' => 'Usuario autenticado.',
            'data' => $payload,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $usuario = $this->resolveUsuario($request);
        $this->authService->logout($usuario);

        return response()->json([
            'message' => 'Logout exitoso.',
        ]);
    }

    private function resolveUsuario(Request $request): Usuario
    {
        $usuario = $request->user();

        if (! $usuario instanceof Usuario) {
            throw new ApiException('No autenticado.', 401);
        }

        return $usuario;
    }
}
