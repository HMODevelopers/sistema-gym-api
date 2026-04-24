<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Usuario extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;

    public const ESTATUS_ACTIVO = 'ACTIVO';
    public const ESTATUS_BLOQUEADO = 'BLOQUEADO';

    protected $table = 'usuarios';

    protected $fillable = [
        'sucursal_id',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'email',
        'telefono',
        'username',
        'password_hash',
        'foto_url',
        'estatus',
        'ultimo_acceso_at',
        'activo',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'ultimo_acceso_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Rol::class, 'usuario_roles', 'usuario_id', 'rol_id')
            ->withTimestamps();
    }

    public function permisosEfectivos(): array
    {
        $this->loadMissing('roles.permisos');

        return $this->roles
            ->flatMap(fn (Rol $rol) => $rol->permisos->pluck('clave'))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function clavesRoles(): array
    {
        $this->loadMissing('roles');

        return $this->roles
            ->pluck('clave')
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
