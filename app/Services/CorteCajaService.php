<?php

namespace App\Services;

use App\Enums\PagoEstatus;
use App\Exceptions\ApiException;
use App\Models\CorteCaja;
use App\Models\Pago;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CorteCajaService
{
    public function __construct(private readonly AuditoriaService $auditoriaService) {}

    public function calcular(array $data): array
    {
        $query = Pago::query()->where('sucursal_id', $data['sucursal_id'])
            ->whereBetween('fecha_pago', [$data['fecha_desde'], $data['fecha_hasta']]);

        if (! empty($data['usuario_id'])) {
            $query->where('usuario_id', $data['usuario_id']);
        }

        $pagado = $query->clone()->where('estatus', PagoEstatus::APLICADO->value);
        $cancelado = $query->clone()->where('estatus', PagoEstatus::CANCELADO->value);

        $metodos = $pagado->clone()->join('metodos_pago', 'metodos_pago.id', '=', 'pagos.metodo_pago_id')
            ->selectRaw('pagos.metodo_pago_id, metodos_pago.nombre, metodos_pago.clave, COUNT(*) total_pagos, SUM(pagos.monto) total_monto')
            ->groupBy('pagos.metodo_pago_id', 'metodos_pago.nombre', 'metodos_pago.clave')
            ->get()
            ->map(fn ($row) => [
                'metodo_pago_id' => (int) $row->metodo_pago_id,
                'nombre' => $row->nombre,
                'clave' => $row->clave,
                'total_pagos' => (int) $row->total_pagos,
                'total_monto' => number_format((float) $row->total_monto, 2, '.', ''),
            ])->values()->all();

        $efectivo = collect($metodos)->firstWhere('clave', 'EFECTIVO');

        return [
            'sucursal' => DB::table('sucursales')->where('id', $data['sucursal_id'])->first(['id', 'nombre', 'clave']),
            'usuario' => ! empty($data['usuario_id']) ? DB::table('usuarios')->where('id', $data['usuario_id'])->first(['id', 'nombre', 'apellido_paterno', 'apellido_materno', 'username']) : null,
            'rango' => ['fecha_desde' => $data['fecha_desde'], 'fecha_hasta' => $data['fecha_hasta']],
            'resumen' => [
                'total_pagos' => $pagado->count(),
                'total_monto' => number_format((float) $pagado->sum('monto'), 2, '.', ''),
                'total_cancelados' => $cancelado->count(),
                'total_cancelado_monto' => number_format((float) $cancelado->sum('monto'), 2, '.', ''),
                'efectivo_esperado' => number_format((float) ($efectivo['total_monto'] ?? 0), 2, '.', ''),
            ],
            'metodos_pago' => $metodos,
        ];
    }

    public function store(array $data, int $authUserId): CorteCaja
    {
        $calc = $this->calcular($data);
        $exists = CorteCaja::query()->where('sucursal_id', $data['sucursal_id'])
            ->where('fecha_desde', $data['fecha_desde'])->where('fecha_hasta', $data['fecha_hasta'])
            ->where('estatus', '!=', 'CANCELADO')
            ->when(array_key_exists('usuario_id', $data), fn (Builder $q) => $q->where('usuario_id', $data['usuario_id']))
            ->exists();
        if ($exists) throw new ApiException('Ya existe un corte de caja para el mismo rango/sucursal/usuario.', 422);

        return DB::transaction(function () use ($data, $calc) {
            $efectivoContado = (float) ($data['efectivo_contado'] ?? 0);
            $efectivoEsperado = (float) $calc['resumen']['efectivo_esperado'];
            $corte = CorteCaja::query()->create([
                'sucursal_id' => $data['sucursal_id'],
                'usuario_id' => $data['usuario_id'] ?? null,
                'fecha_desde' => $data['fecha_desde'],
                'fecha_hasta' => $data['fecha_hasta'],
                'total_pagos' => $calc['resumen']['total_pagos'],
                'total_monto' => $calc['resumen']['total_monto'],
                'total_cancelados' => $calc['resumen']['total_cancelados'],
                'total_cancelado_monto' => $calc['resumen']['total_cancelado_monto'],
                'efectivo_esperado' => $efectivoEsperado,
                'efectivo_contado' => $efectivoContado,
                'diferencia_efectivo' => $efectivoContado - $efectivoEsperado,
                'detalle_metodos_pago' => $calc['metodos_pago'],
                'observaciones' => $data['observaciones'] ?? null,
                'estatus' => 'CERRADO',
                'activo' => true,
            ]);
            $this->auditoriaService->registrar('CorteCaja', 'AJUSTAR', $corte->id, 'Corte de caja generado correctamente', null, $corte->toArray(), $corte->sucursal_id, $corte->usuario_id);
            return $corte;
        });
    }

    public function index(array $filters, int $perPage): LengthAwarePaginator
    {
        return CorteCaja::query()->with(['sucursal:id,nombre,clave', 'usuario:id,nombre,apellido_paterno,apellido_materno,username'])
            ->when(! empty($filters['sucursal_id']), fn ($q) => $q->where('sucursal_id', $filters['sucursal_id']))
            ->when(! empty($filters['usuario_id']), fn ($q) => $q->where('usuario_id', $filters['usuario_id']))
            ->when(($filters['estatus'] ?? 'all') !== 'all', fn ($q) => $q->where('estatus', $filters['estatus']))
            ->when(($filters['activo'] ?? 'true') !== 'all', fn ($q) => $q->where('activo', filter_var($filters['activo'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true))
            ->when(! empty($filters['fecha_desde']), fn ($q) => $q->whereDate('fecha_desde', '>=', $filters['fecha_desde']))
            ->when(! empty($filters['fecha_hasta']), fn ($q) => $q->whereDate('fecha_hasta', '<=', $filters['fecha_hasta']))
            ->orderByDesc('id')->paginate($perPage);
    }

    public function cancelar(CorteCaja $corte, string $motivo, int $authUserId): CorteCaja
    {
        if ($corte->estatus === 'CANCELADO') throw new ApiException('El corte de caja ya está cancelado.', 422);
        $antes = $corte->toArray();
        return DB::transaction(function () use ($corte, $motivo, $authUserId, $antes) {
            $corte->update(['estatus' => 'CANCELADO', 'activo' => false, 'motivo_cancelacion' => $motivo, 'cancelado_at' => Carbon::now(), 'cancelado_por_usuario_id' => $authUserId]);
            $this->auditoriaService->registrar('CorteCaja', 'ELIMINAR_LOGICO', $corte->id, 'Corte de caja cancelado correctamente', $antes, $corte->fresh()->toArray(), $corte->sucursal_id, $authUserId);
            return $corte->fresh();
        });
    }
}
