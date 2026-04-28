<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $table = 'planes';

    protected $fillable = [
        'nombre',
        'clave',
        'descripcion',
        'precio',
        'duracion_dias',
        'tipo_plan',
        'accesos_incluidos',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'precio' => 'decimal:2',
            'duracion_dias' => 'integer',
            'accesos_incluidos' => 'integer',
            'activo' => 'boolean',
        ];
    }
}
