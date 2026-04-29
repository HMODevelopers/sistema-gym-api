<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class AuthPaths
{
    #[OA\Post(path: '/api/v1/auth/login', summary: 'Iniciar sesión', description: 'Sin permiso requerido.', tags: ['Auth'], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(type: 'object')), responses: [new OA\Response(response: 200, description: 'Login exitoso'), new OA\Response(response: 401, description: 'Credenciales inválidas'), new OA\Response(response: 422, description: 'Datos inválidos')])]
    public function login(): void {}

    #[OA\Get(path: '/api/v1/auth/me', summary: 'Usuario autenticado', description: 'Permiso requerido: autenticado.', security: [['sanctumBearer' => []]], tags: ['Auth'], responses: [new OA\Response(response: 200, description: 'Usuario obtenido correctamente'), new OA\Response(response: 401, description: 'No autenticado')])]
    public function me(): void {}

    #[OA\Post(path: '/api/v1/auth/logout', summary: 'Cerrar sesión', description: 'Permiso requerido: autenticado.', security: [['sanctumBearer' => []]], tags: ['Auth'], responses: [new OA\Response(response: 200, description: 'Logout exitoso'), new OA\Response(response: 401, description: 'No autenticado')])]
    public function logout(): void {}
}
