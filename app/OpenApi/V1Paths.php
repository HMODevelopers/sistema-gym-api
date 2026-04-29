<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\PathItem(
    path: '/api/v1/auth/login',
    post: new OA\Post(
        summary: 'Login',
        description: 'Autentica a un usuario y devuelve token Sanctum, datos del usuario, roles y permisos.',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['login', 'password'],
                properties: [
                    new OA\Property(
                        property: 'login',
                        type: 'string',
                        example: 'admin@sistemagym.local'
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password',
                        example: 'password'
                    ),
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
    )
)]
#[OA\PathItem(
    path: '/api/v1/auth/me',
    get: new OA\Get(
        summary: 'Usuario autenticado',
        description: 'Devuelve los datos del usuario autenticado. Requiere Bearer Token Sanctum.',
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
    )
)]
#[OA\PathItem(
    path: '/api/v1/auth/logout',
    post: new OA\Post(
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
                        new OA\Property(
                            property: 'message',
                            type: 'string',
                            example: 'Sesión cerrada correctamente.'
                        ),
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
    )
)]
#[OA\PathItem(
    path: '/api/v1/sucursales',
    get: new OA\Get(
        summary: 'Listar sucursales',
        description: 'Obtiene el listado paginado de sucursales. Permiso requerido: sucursales.ver.',
        security: [['sanctumBearer' => []]],
        tags: ['Sucursales'],
        parameters: [
            new OA\Parameter(
                name: 'q',
                in: 'query',
                required: false,
                description: 'Búsqueda por nombre o clave',
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'activo',
                in: 'query',
                required: false,
                description: 'Filtro de activo',
                schema: new OA\Schema(
                    type: 'string',
                    enum: ['true', 'false', 'all']
                )
            ),
            new OA\Parameter(
                name: 'page',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'integer',
                    example: 1
                )
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'integer',
                    example: 15
                )
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
    )
)]
class V1Paths
{
}