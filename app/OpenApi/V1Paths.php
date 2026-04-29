<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class V1Paths
{
    #[OA\Post(
        path: '/api/v1/auth/login',
        operationId: 'authLogin',
        summary: 'Iniciar sesión',
        description: 'Autentica a un usuario y devuelve token Sanctum, datos del usuario, roles y permisos.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['login', 'password'],
                properties: [
                    new OA\Property(property: 'login', type: 'string', example: 'admin@sistemagym.local'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'password'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login exitoso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Login exitoso'),
                        new OA\Property(property: 'token', type: 'string', example: '1|abc123token'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'The given data was invalid.'),
                    ],
                    type: 'object'
                )
            ),
        ]
    )]
    public function login(): void
    {
    }
}
