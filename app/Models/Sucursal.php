<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sucursal extends Model
{
    use HasFactory;

    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'clave',
        'direccion',
        'telefono',
        'correo',
        'es_principal',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function usuarios(): HasMany
    {
        return $this->hasMany(Usuario::class, 'sucursal_id');
    }
}
