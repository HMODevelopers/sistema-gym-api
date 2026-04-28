<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use HasFactory;

    protected $table = 'pagos';

    protected $fillable = [
        'cliente_id',
        'membresia_id',
        'sucursal_id',
        'metodo_pago_id',
        'usuario_id',
        'concepto',
        'monto',
        'fecha_pago',
        'referencia',
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
            'monto' => 'decimal:2',
            'fecha_pago' => 'datetime',
            'cancelado_at' => 'datetime',
            'activo' => 'boolean',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function membresia(): BelongsTo
    {
        return $this->belongsTo(Membresia::class, 'membresia_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function metodoPago(): BelongsTo
    {
        return $this->belongsTo(MetodoPago::class, 'metodo_pago_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function usuarioCancelo(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'cancelado_por_usuario_id');
    }
}
