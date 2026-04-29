<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

class V1Paths
{
    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'Iniciar sesión',
        description: 'Autentica a un usuario y devuelve token Sanctum, datos del usuario, roles y permisos.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['login', 'password'],
                properties: [
                    new OA\Property(property: 'login', type: 'string', example: 'admin@sistemagym.local'),
                    new OA\Property(property: 'password', type: 'string', example: 'password'),
                ],
                type: 'object'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login exitoso',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Error de validación',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationError422')
            ),
        ]
    )]
    public function login(): void
    {
    }

    #[OA\Get(
        path: '/api/v1/auth/me',
        summary: 'Obtener usuario autenticado',
        description: 'Devuelve los datos del usuario autenticado, sus roles y permisos.',
        security: [['sanctumBearer' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Usuario autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/AuthResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/Error401')
            ),
        ]
    )]
    public function me(): void
    {
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: 'Cerrar sesión',
        description: 'Revoca el token actual del usuario autenticado.',
        security: [['sanctumBearer' => []]],
        tags: ['Auth'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sesión cerrada correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Sesión cerrada correctamente.'),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/Error401')
            ),
        ]
    )]
    public function logout(): void
    {
    }

    #[OA\Get(
        path: '/api/v1/sucursales',
        summary: 'Listar sucursales',
        description: 'Obtiene el listado paginado de sucursales. Permiso requerido: sucursales.ver.',
        security: [['sanctumBearer' => []]],
        tags: ['Sucursales'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                description: 'Búsqueda por nombre o clave'
            ),
            new OA\Parameter(
                name: 'activo',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['true', 'false', 'all']),
                description: 'Filtro de activo'
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 1)
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', example: 15)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucursales obtenidas correctamente'
            ),
            new OA\Response(
                response: 401,
                description: 'No autenticado',
                content: new OA\JsonContent(ref: '#/components/schemas/Error401')
            ),
            new OA\Response(
                response: 403,
                description: 'Sin permiso',
                content: new OA\JsonContent(ref: '#/components/schemas/Error403')
            ),
        ]
    )]
    public function sucursalesIndex(): void
    {
    }
}