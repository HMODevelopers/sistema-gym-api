<?php

namespace App\Models;

use App\Enums\ClienteEstatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'sucursal_id',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'nombre_completo',
        'telefono',
        'email',
        'fecha_nacimiento',
        'contacto_emergencia_nombre',
        'contacto_emergencia_telefono',
        'foto_url',
        'fecha_inscripcion',
        'estatus',
        'notas',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_inscripcion' => 'date',
            'activo' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $cliente): void {
            if (! Schema::hasColumn('clientes', 'nombre_completo')) {
                return;
            }

            $cliente->nombre_completo = $cliente->buildNombreCompleto();

            if (Schema::hasColumn('clientes', 'estatus') && blank($cliente->estatus)) {
                $cliente->estatus = ClienteEstatus::ACTIVO->value;
            }
        });
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function buildNombreCompleto(): string
    {
        return trim(collect([
            $this->nombre,
            $this->apellido_paterno,
            $this->apellido_materno,
        ])->filter(static fn ($value): bool => filled($value))->implode(' '));
    }
}
