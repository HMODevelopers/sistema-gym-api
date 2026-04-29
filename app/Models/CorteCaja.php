<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorteCaja extends Model
{
    use HasFactory;

    protected $table = 'cortes_caja';

    protected $fillable = [
        'sucursal_id',
        'usuario_id',
        'fecha_desde',
        'fecha_hasta',
        'total_pagos',
        'total_monto',
        'total_cancelados',
        'total_cancelado_monto',
        'efectivo_esperado',
        'efectivo_contado',
        'diferencia_efectivo',
        'detalle_metodos_pago',
        'observaciones',
        'estatus',
        'motivo_cancelacion',
        'cancelado_at',
        'cancelado_por_usuario_id',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_desde' => 'datetime',
            'fecha_hasta' => 'datetime',
            'total_monto' => 'decimal:2',
            'total_cancelado_monto' => 'decimal:2',
            'efectivo_esperado' => 'decimal:2',
            'efectivo_contado' => 'decimal:2',
            'diferencia_efectivo' => 'decimal:2',
            'detalle_metodos_pago' => 'array',
            'cancelado_at' => 'datetime',
            'activo' => 'boolean',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function canceladoPorUsuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cancelado_por_usuario_id');
    }
}
