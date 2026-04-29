<?php
namespace App\OpenApi\Paths;
use OpenApi\Attributes as OA;
class AuditoriaPaths {
#[OA\Get(path:'/api/v1/auditoria',summary:'index',description:'Permiso requerido: auditoria.ver.',security:[['sanctumBearer'=>[]]],tags:['Auditoría'],responses:[new OA\Response(response:200,description:'OK'),new OA\Response(response:401,description:'No autenticado'),new OA\Response(response:403,description:'Sin permiso')])] public function index():void{}
#[OA\Get(path:'/api/v1/auditoria/{evento}',summary:'show',description:'Permiso requerido: auditoria.ver.',security:[['sanctumBearer'=>[]]],tags:['Auditoría'], parameters:[new OA\Parameter(name:'evento',in:'path',required:true,schema:new OA\Schema(type:'integer'))],responses:[new OA\Response(response:200,description:'OK'),new OA\Response(response:401,description:'No autenticado'),new OA\Response(response:403,description:'Sin permiso')])] public function show():void{}
}