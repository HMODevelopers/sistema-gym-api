<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Schema;

class ClienteBiometrico extends Model
{
    use HasFactory;

    private const TABLE_CANDIDATES = [
        'biometricos_cliente',
        'referencias_biometricas',
        'biometricos',
        'clientes_biometricos',
    ];

    private static ?string $resolvedTable = null;

    protected $table = 'biometricos_cliente';

    protected $fillable = [
        'cliente_id',
        'dispositivo_id',
        'identificador_biometrico',
        'dedo',
        'es_principal',
        'calidad_lectura',
        'intentos_fallidos',
        'estatus',
        'observaciones',
        'enrolado_at',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'es_principal' => 'boolean',
            'calidad_lectura' => 'integer',
            'intentos_fallidos' => 'integer',
            'enrolado_at' => 'datetime',
            'activo' => 'boolean',
        ];
    }

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->table = self::tableName();
    }

    public static function tableName(): string
    {
        if (self::$resolvedTable !== null) {
            return self::$resolvedTable;
        }

        foreach (self::TABLE_CANDIDATES as $table) {
            if (Schema::hasTable($table)) {
                self::$resolvedTable = $table;

                return $table;
            }
        }

        self::$resolvedTable = self::TABLE_CANDIDATES[0];

        return self::$resolvedTable;
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function dispositivo(): BelongsTo
    {
        return $this->belongsTo(Dispositivo::class, 'dispositivo_id');
    }
}
