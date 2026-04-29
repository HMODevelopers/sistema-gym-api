<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Sistema Gym API',
    description: 'API REST para administración de gimnasio.'
)]
#[OA\Server(
    url: 'http://127.0.0.1:8000',
    description: 'Servidor local'
)]

#[OA\Tag(name: 'Auth')]
#[OA\Tag(name: 'RBAC')]
#[OA\Tag(name: 'Sucursales')]
#[OA\Tag(name: 'Planes')]
#[OA\Tag(name: 'Métodos de Pago')]
#[OA\Tag(name: 'Clientes')]
#[OA\Tag(name: 'Membresías')]
#[OA\Tag(name: 'Pagos')]
#[OA\Tag(name: 'Accesos')]
#[OA\Tag(name: 'Dispositivos')]
#[OA\Tag(name: 'Biométricos')]
#[OA\Tag(name: 'Auditoría')]
#[OA\Tag(name: 'Recepción')]
#[OA\Tag(name: 'Reportes')]
#[OA\Tag(name: 'Cortes de Caja')]
#[OA\Tag(name: 'Exportaciones')]
#[OA\SecurityScheme(
    securityScheme: 'sanctumBearer',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Token',
    description: 'Token Bearer emitido por Sanctum'
)]
class V1Spec
{
}
