<?php

namespace App\OpenApi;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Sistema Gym API",
 *     description="API REST para administración de gimnasio, control de clientes, membresías, pagos, accesos, biométricos, reportes y auditoría."
 * )
 * @OA\Server(url="http://127.0.0.1:8000", description="Servidor local")
 * @OA\Tag(name="Auth")
 * @OA\Tag(name="RBAC")
 * @OA\Tag(name="Sucursales")
 * @OA\Tag(name="Planes")
 * @OA\Tag(name="Métodos de Pago")
 * @OA\Tag(name="Clientes")
 * @OA\Tag(name="Membresías")
 * @OA\Tag(name="Pagos")
 * @OA\Tag(name="Accesos")
 * @OA\Tag(name="Dispositivos")
 * @OA\Tag(name="Biométricos")
 * @OA\Tag(name="Auditoría")
 * @OA\Tag(name="Recepción")
 * @OA\Tag(name="Reportes")
 * @OA\Tag(name="Cortes de Caja")
 * @OA\Tag(name="Exportaciones")
 *
 * @OA\SecurityScheme(
 *   securityScheme="sanctumBearer",
 *   type="http",
 *   scheme="bearer",
 *   bearerFormat="Token"
 * )
 *
 * @OA\Schema(schema="Error401", type="object", @OA\Property(property="message", type="string", example="No autenticado."))
 * @OA\Schema(schema="Error403", type="object", @OA\Property(property="message", type="string", example="No tienes permisos para realizar esta acción."))
 * @OA\Schema(schema="Error404", type="object", @OA\Property(property="message", type="string", example="Recurso no encontrado."))
 * @OA\Schema(
 *   schema="ValidationError422",
 *   type="object",
 *   @OA\Property(property="message", type="string", example="Los datos proporcionados no son válidos."),
 *   @OA\Property(property="errors", type="object", additionalProperties=@OA\Schema(type="array", @OA\Items(type="string")))
 * )
 * @OA\Schema(schema="PaginationMeta", type="object", @OA\Property(property="current_page", type="integer"), @OA\Property(property="per_page", type="integer"), @OA\Property(property="total", type="integer"))
 * @OA\Schema(schema="AuthUser", type="object", @OA\Property(property="id", type="integer"), @OA\Property(property="sucursal_id", type="integer", nullable=true), @OA\Property(property="nombre", type="string"), @OA\Property(property="apellido_paterno", type="string"), @OA\Property(property="apellido_materno", type="string", nullable=true), @OA\Property(property="nombre_completo", type="string"), @OA\Property(property="email", type="string"), @OA\Property(property="telefono", type="string", nullable=true), @OA\Property(property="username", type="string", nullable=true), @OA\Property(property="foto_url", type="string", nullable=true), @OA\Property(property="estatus", type="string"), @OA\Property(property="ultimo_acceso_at", type="string", format="date-time", nullable=true), @OA\Property(property="activo", type="boolean"))
 * @OA\Schema(schema="AuthResponse", type="object", @OA\Property(property="message", type="string", example="Login exitoso."), @OA\Property(property="data", type="object", @OA\Property(property="usuario", ref="#/components/schemas/AuthUser"), @OA\Property(property="auth", type="object", @OA\Property(property="roles", type="array", @OA\Items(type="string")), @OA\Property(property="permisos", type="array", @OA\Items(type="string"))), @OA\Property(property="token", type="string")))
 *
 * @OA\Schema(schema="Sucursal", type="object")
 * @OA\Schema(schema="Plan", type="object")
 * @OA\Schema(schema="MetodoPago", type="object")
 * @OA\Schema(schema="Cliente", type="object")
 * @OA\Schema(schema="Membresia", type="object")
 * @OA\Schema(schema="Pago", type="object")
 * @OA\Schema(schema="Acceso", type="object")
 * @OA\Schema(schema="Dispositivo", type="object")
 * @OA\Schema(schema="Biometrico", type="object")
 * @OA\Schema(schema="AuditoriaEvento", type="object", @OA\Property(property="sucursal_id", type="integer", nullable=true), @OA\Property(property="usuario_id", type="integer", nullable=true), @OA\Property(property="entidad", type="string"), @OA\Property(property="entidad_id", type="string"), @OA\Property(property="accion", type="string"), @OA\Property(property="descripcion", type="string"), @OA\Property(property="datos_antes", type="object", nullable=true), @OA\Property(property="datos_despues", type="object", nullable=true), @OA\Property(property="ip", type="string", nullable=true), @OA\Property(property="user_agent", type="string", nullable=true), @OA\Property(property="created_at", type="string", format="date-time"))
 * @OA\Schema(schema="CorteCaja", type="object")
 */
class V1Spec {}
