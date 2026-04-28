<?php

namespace App\Services;

use App\Enums\AuditoriaAccion;
use App\Models\AuditoriaEvento;
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
        'api_key',
        'private_key',
    ];

    public function registrar(
        string $entidad,
        string $accion,
        ?int $entidadId = null,
        ?string $descripcion = null,
        ?array $datosAntes = null,
        ?array $datosDespues = null,
        ?int $sucursalId = null,
        ?int $usuarioId = null,
    ): void {
        try {
            if (! Schema::hasTable('auditoria_eventos')) {
                return;
            }

            if (! in_array($accion, array_column(AuditoriaAccion::cases(), 'value'), true)) {
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

            AuditoriaEvento::query()->create([
                'usuario_id' => $usuarioId,
                'sucursal_id' => $sucursalId,
                'entidad' => trim($entidad),
                'entidad_id' => $entidadId,
                'accion' => mb_strtoupper(trim($accion)),
                'descripcion' => $descripcion ? trim($descripcion) : null,
                'datos_antes' => $this->sanitizePayload($datosAntes),
                'datos_despues' => $this->sanitizePayload($datosDespues),
                'ip' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ]);
        } catch (\Throwable $exception) {
            Log::warning('No fue posible registrar evento de auditoría.', [
                'entidad' => $entidad,
                'accion' => $accion,
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
