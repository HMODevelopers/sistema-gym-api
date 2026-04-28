<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Dispositivo extends Model
{
    use HasFactory;

    protected $table = 'dispositivos';

    protected $fillable = [
        'sucursal_id',
        'nombre',
        'clave',
        'identificador',
        'tipo',
        'descripcion',
        'ubicacion',
        'ip',
        'sistema_operativo',
        'estatus',
        'ultimo_uso_at',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'ultimo_uso_at' => 'datetime',
            'activo' => 'boolean',
        ];
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }
}
