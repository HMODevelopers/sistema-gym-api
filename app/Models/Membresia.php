<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Membresia extends Model
{
    use HasFactory;

    protected $table = 'membresias';

    protected $fillable = [
        'cliente_id',
        'plan_id',
        'sucursal_id',
        'fecha_inicio',
        'fecha_fin',
        'fecha_vencimiento',
        'estatus',
        'accesos_totales',
        'accesos_usados',
        'accesos_disponibles',
        'precio',
        'observaciones',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
            'fecha_vencimiento' => 'date',
            'accesos_totales' => 'integer',
            'accesos_usados' => 'integer',
            'accesos_disponibles' => 'integer',
            'precio' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }
}
