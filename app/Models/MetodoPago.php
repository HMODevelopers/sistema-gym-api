<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MetodoPago extends Model
{
    use HasFactory;

    protected $table = 'metodos_pago';

    protected $fillable = [
        'nombre',
        'clave',
        'descripcion',
        'requiere_referencia',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'requiere_referencia' => 'boolean',
            'activo' => 'boolean',
        ];
    }
}
