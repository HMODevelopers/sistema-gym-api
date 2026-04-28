<?php

namespace App\Services;

use App\Models\BitacoraEvento;
use App\Models\Usuario;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuditoriaService
{
    private const SENSITIVE_KEYS = [
        'password',
        'password_hash',
        'remember_token',
        'token',
        'access_token',
        'refresh_token',
        'current_password',
        'password_confirmation',
        'identificador_biometrico',
        'secret',
        'secreto',
        'credential',
        'credencial',
    ];

    public function registrar(
        string $modulo,
        string $accion,
        ?string $entidad = null,
        ?int $entidadId = null,
        ?string $descripcion = null,
        ?array $valoresAnteriores = null,
        ?array $valoresNuevos = null,
        ?int $sucursalId = null,
        ?int $usuarioId = null,
    ): void {
        try {
            if (! Schema::hasTable('bitacora_eventos')) {
                return;
            }

            $request = request();
            $usuario = $request?->user();

            if ($usuarioId === null && $usuario instanceof Usuario) {
                $usuarioId = $usuario->id;
            }

            if ($sucursalId === null && $usuario instanceof Usuario) {
                $sucursalId = $usuario->sucursal_id;
            }

            $requestId = null;
            if ($request) {
                $requestId = $request->headers->get('X-Request-Id')
                    ?? $request->headers->get('X-Correlation-Id');
            }

            BitacoraEvento::query()->create([
                'usuario_id' => $usuarioId,
                'sucursal_id' => $sucursalId,
                'modulo' => mb_strtoupper(trim($modulo)),
                'accion' => mb_strtoupper(trim($accion)),
                'entidad' => $entidad ? trim($entidad) : null,
                'entidad_id' => $entidadId,
                'descripcion' => $descripcion ? trim($descripcion) : null,
                'valores_anteriores' => $this->sanitizePayload($valoresAnteriores),
                'valores_nuevos' => $this->sanitizePayload($valoresNuevos),
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'metodo_http' => $request?->method(),
                'ruta' => $request?->path(),
                'request_id' => $requestId,
            ]);
        } catch (\Throwable $exception) {
            Log::warning('No fue posible registrar evento de auditoría.', [
                'modulo' => $modulo,
                'accion' => $accion,
                'entidad' => $entidad,
                'entidad_id' => $entidadId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function sanitizePayload(?array $payload): ?array
    {
        if ($payload === null) {
            return null;
        }

        $sanitized = [];

        foreach ($payload as $key => $value) {
            $keyString = is_string($key) ? $key : (string) $key;
            $keyNormalized = mb_strtolower($keyString);

            if ($this->isSensitiveKey($keyNormalized)) {
                continue;
            }

            if (is_array($value)) {
                $sanitized[$keyString] = $this->sanitizePayload($value);

                continue;
            }

            $sanitized[$keyString] = $value;
        }

        return Arr::whereNotNull($sanitized);
    }

    private function isSensitiveKey(string $key): bool
    {
        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if (str_contains($key, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }
}
