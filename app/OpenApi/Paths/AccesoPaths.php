<?php
namespace App\OpenApi\Paths;
use OpenApi\Attributes as OA;
class AccesoPaths {
#[OA\Get(path:'/api/v1/accesos',summary:'index',description:'Permiso requerido: accesos.ver.',security:[['sanctumBearer'=>[]]],tags:['Accesos'],responses:[new OA\Response(response:200,description:'OK'),new OA\Response(response:401,description:'No autenticado'),new OA\Response(response:403,description:'Sin permiso')])] public function index():void{}
#[OA\Post(path:'/api/v1/accesos/validar',summary:'validar',description:'Permiso requerido: accesos.validar.',security:[['sanctumBearer'=>[]]],tags:['Accesos'], requestBody:new OA\RequestBody(required:true,content:new OA\JsonContent(type:'object')),responses:[new OA\Response(response:200,description:'OK'),new OA\Response(response:401,description:'No autenticado'),new OA\Response(response:403,description:'Sin permiso')])] public function validar():void{}
#[OA\Get(path:'/api/v1/accesos/{acceso}',summary:'show',description:'Permiso requerido: accesos.ver.',security:[['sanctumBearer'=>[]]],tags:['Accesos'], parameters:[new OA\Parameter(name:'acceso',in:'path',required:true,schema:new OA\Schema(type:'integer'))],responses:[new OA\Response(response:200,description:'OK'),new OA\Response(response:401,description:'No autenticado'),new OA\Response(response:403,description:'Sin permiso')])] public function show():void{}
}