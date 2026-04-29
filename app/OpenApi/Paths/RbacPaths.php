<?php
namespace App\OpenApi\Paths;
use OpenApi\Attributes as OA;
class RbacPaths { #[OA\Get(path: '/api/v1/rbac/check-admin', summary: 'Verificar admin', description: 'Permiso requerido: roles.asignar_permisos.', security: [['sanctumBearer' => []]], tags: ['RBAC'], responses: [new OA\Response(response: 200, description: 'Autorizado correctamente'), new OA\Response(response: 401, description: 'No autenticado'), new OA\Response(response: 403, description: 'Sin permiso')])] public function checkAdmin(): void {} }
