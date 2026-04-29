<?php

namespace App\Services;

use App\Models\Acceso;
use App\Models\Cliente;
use App\Models\ClienteBiometrico;
use App\Models\Membresia;
use App\Models\Pago;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class RecepcionService
{
    /**
     * @return Collection<int, Cliente>
     */
    public function buscarClientes(string $q, ?int $sucursalId, ?string $estatus, string $activo, int $limit): Collection
    {
        $query = Cliente::query()->with('sucursal:id,nombre,clave');

        $query->where(function ($builder) use ($q): void {
            foreach (['nombre', 'apellido_paterno', 'apellido_materno', 'nombre_completo', 'telefono', 'email'] as $column) {
                if (Schema::hasColumn('clientes', $column)) {
                    $builder->orWhere($column, 'like', "%{$q}%");
                }
            }

            foreach (['codigo', 'codigo_cliente', 'clave'] as $column) {
                if (Schema::hasColumn('clientes', $column)) {
                    $builder->orWhere($column, 'like', "%{$q}%");
                }
            }
        });

        if (Schema::hasColumn('clientes', 'sucursal_id') && $sucursalId) {
            $query->where('sucursal_id', $sucursalId);
        }

        if (Schema::hasColumn('clientes', 'estatus') && $estatus && $estatus !== 'ALL') {
            $query->where('estatus', $estatus);
        }

        if (Schema::hasColumn('clientes', 'activo') && $activo !== 'all') {
            $query->where('activo', filter_var($activo, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true);
        }

        if (Schema::hasColumn('clientes', 'nombre_completo')) {
            $query->orderByRaw('CASE WHEN nombre_completo LIKE ? THEN 0 ELSE 1 END', ["{$q}%"])
                ->orderBy('nombre_completo');
        } elseif (Schema::hasColumn('clientes', 'nombre')) {
            $query->orderBy('nombre');
        }

        $clientes = $query->limit($limit)->get();

        $clientes->each(function (Cliente $cliente): void {
            $cliente->setRelation('membresia_recepcion', $this->obtenerMembresiaActual($cliente->id));
        });

        return $clientes;
    }

    public function obtenerResumenCliente(Cliente $cliente): array
    {
        $cliente->loadMissing('sucursal:id,nombre,clave');

        $membresia = $this->obtenerMembresiaActual($cliente->id);
        $ultimoPago = $this->obtenerUltimoPago($cliente->id);
        $ultimoAcceso = $this->obtenerUltimoAcceso($cliente->id);
        $biometricos = $this->obtenerResumenBiometricos($cliente->id);

        [$puedeIngresar, $motivo] = $this->evaluarIngreso($cliente, $membresia);

        return [
            'cliente' => $cliente,
            'membresia_actual' => $this->normalizarMembresia($membresia),
            'puede_ingresar' => $puedeIngresar,
            'motivo_no_ingreso' => $motivo,
            'ultimo_pago' => $this->normalizarPago($ultimoPago),
            'ultimo_acceso' => $this->normalizarAcceso($ultimoAcceso),
            'biometricos' => $biometricos,
            'alertas' => $this->generarAlertas($cliente, $membresia, $ultimoPago, $ultimoAcceso, $biometricos),
        ];
    }

    public function obtenerMembresiaActual(int $clienteId): ?Membresia
    {
        $fechaCol = Schema::hasColumn('membresias', 'fecha_vencimiento') ? 'fecha_vencimiento' : 'fecha_fin';

        $queryVigente = Membresia::query()
            ->with('plan:id,nombre,clave')
            ->where('cliente_id', $clienteId)
            ->where('activo', true)
            ->whereIn('estatus', ['ACTIVA', 'VIGENTE']);

        if (Schema::hasColumn('membresias', $fechaCol)) {
            $queryVigente->whereDate($fechaCol, '>=', Carbon::today()->toDateString());
        }

        $vigente = $queryVigente->orderByDesc($fechaCol)->orderByDesc('id')->first();

        if ($vigente) {
            return $vigente;
        }

        return Membresia::query()
            ->with('plan:id,nombre,clave')
            ->where('cliente_id', $clienteId)
            ->orderByDesc('id')
            ->first();
    }

    private function obtenerUltimoPago(int $clienteId): ?Pago
    {
        return Pago::query()->with('metodoPago:id,nombre,clave')
            ->where('cliente_id', $clienteId)
            ->orderByDesc('fecha_pago')
            ->orderByDesc('id')
            ->first();
    }

    private function obtenerUltimoAcceso(int $clienteId): ?Acceso
    {
        return Acceso::query()->where('cliente_id', $clienteId)
            ->orderByDesc('fecha_acceso')
            ->orderByDesc('id')
            ->first();
    }

    private function obtenerResumenBiometricos(int $clienteId): array
    {
        $table = ClienteBiometrico::tableName();
        $query = ClienteBiometrico::query()->where('cliente_id', $clienteId);

        if (Schema::hasColumn($table, 'activo')) {
            $query->where('activo', true);
        }

        $total = (clone $query)->count();

        $principal = (clone $query)
            ->orderByDesc('es_principal')
            ->orderByDesc('id')
            ->first();

        return [
            'tiene_huella' => $total > 0,
            'total' => $total,
            'principal' => $principal ? [
                'id' => $principal->id,
                'dedo' => $principal->dedo,
                'calidad_lectura' => $principal->calidad_lectura,
            ] : null,
        ];
    }

    private function evaluarIngreso(Cliente $cliente, ?Membresia $membresia): array
    {
        if (Schema::hasColumn('clientes', 'activo') && ! $cliente->activo) return [false, 'CLIENTE_INACTIVO'];
        if (Schema::hasColumn('clientes', 'estatus') && $cliente->estatus === 'SUSPENDIDO') return [false, 'CLIENTE_SUSPENDIDO'];
        if (Schema::hasColumn('clientes', 'estatus') && $cliente->estatus === 'BLOQUEADO') return [false, 'CLIENTE_BLOQUEADO'];
        if (! $membresia) return [false, 'SIN_MEMBRESIA'];

        $fecha = $membresia->fecha_vencimiento ?? $membresia->fecha_fin;
        if (in_array($membresia->estatus, ['SUSPENDIDA'], true)) return [false, 'MEMBRESIA_SUSPENDIDA'];
        if (in_array($membresia->estatus, ['CANCELADA'], true)) return [false, 'MEMBRESIA_CANCELADA'];
        if ($fecha && Carbon::parse($fecha)->lt(Carbon::today())) return [false, 'MEMBRESIA_VENCIDA'];
        if (! in_array($membresia->estatus, ['ACTIVA', 'VIGENTE'], true)) return [false, 'MEMBRESIA_VENCIDA'];

        if (! is_null($membresia->accesos_disponibles) && $membresia->accesos_disponibles <= 0) {
            return [false, 'SIN_ACCESOS_DISPONIBLES'];
        }

        return [true, null];
    }

    private function normalizarMembresia(?Membresia $m): ?array { if (! $m) return null; $fv=$m->fecha_vencimiento ?? $m->fecha_fin; $d=$fv?max(Carbon::today()->diffInDays(Carbon::parse($fv), false),0):null; return ['id'=>$m->id,'plan'=>$m->relationLoaded('plan')&&$m->plan?['id'=>$m->plan->id,'nombre'=>$m->plan->nombre,'clave'=>$m->plan->clave]:null,'fecha_inicio'=>$m->fecha_inicio,'fecha_vencimiento'=>$fv,'estatus'=>$m->estatus,'esta_vencida'=>$fv?Carbon::parse($fv)->lt(Carbon::today()):false,'dias_restantes'=>$d,'accesos_totales'=>$m->accesos_totales,'accesos_usados'=>$m->accesos_usados,'accesos_disponibles'=>$m->accesos_disponibles]; }
    private function normalizarPago(?Pago $p): ?array { if(!$p) return null; return ['id'=>$p->id,'monto'=>$p->monto,'fecha_pago'=>$p->fecha_pago,'metodo_pago'=>$p->relationLoaded('metodoPago')&&$p->metodoPago?['id'=>$p->metodoPago->id,'nombre'=>$p->metodoPago->nombre,'clave'=>$p->metodoPago->clave]:null,'concepto'=>$p->concepto,'estatus'=>$p->estatus]; }
    private function normalizarAcceso(?Acceso $a): ?array { if(!$a) return null; return ['id'=>$a->id,'fecha_acceso'=>$a->fecha_acceso,'resultado'=>$a->resultado,'metodo'=>$a->metodo,'motivo_rechazo'=>$a->motivo_rechazo]; }

    private function generarAlertas(Cliente $cliente, ?Membresia $membresia, ?Pago $pago, ?Acceso $acceso, array $biometricos): array
    {
        $alertas = [];
        [$puede, $motivo] = $this->evaluarIngreso($cliente, $membresia);
        if (! $puede && $motivo) $alertas[] = ['tipo' => 'ERROR', 'codigo' => $motivo, 'mensaje' => $this->mensajeMotivo($motivo)];
        if ($membresia) {
            $fv = $membresia->fecha_vencimiento ?? $membresia->fecha_fin;
            if ($fv && Carbon::parse($fv)->betweenIncluded(Carbon::today(), Carbon::today()->copy()->addDays(3))) {
                $alertas[] = ['tipo' => 'WARNING', 'codigo' => 'MEMBRESIA_POR_VENCER', 'mensaje' => 'La membresía del cliente está por vencer pronto.'];
            }
        }
        if (! $biometricos['tiene_huella']) $alertas[] = ['tipo' => 'WARNING', 'codigo' => 'SIN_HUELLA', 'mensaje' => 'El cliente no tiene huella registrada.'];
        if ($pago && $pago->estatus === 'CANCELADO') $alertas[] = ['tipo' => 'WARNING', 'codigo' => 'ULTIMO_PAGO_CANCELADO', 'mensaje' => 'El último pago del cliente está cancelado.'];
        if (! $acceso) $alertas[] = ['tipo' => 'INFO', 'codigo' => 'SIN_ACCESOS_RECIENTES', 'mensaje' => 'El cliente no tiene accesos recientes.'];
        return $alertas;
    }

    private function mensajeMotivo(string $motivo): string
    {
        return match ($motivo) {
            'CLIENTE_INACTIVO' => 'El cliente está inactivo.',
            'CLIENTE_SUSPENDIDO' => 'El cliente está suspendido.',
            'CLIENTE_BLOQUEADO' => 'El cliente está bloqueado.',
            'SIN_MEMBRESIA' => 'El cliente no tiene membresía activa.',
            'MEMBRESIA_VENCIDA' => 'La membresía del cliente está vencida.',
            'MEMBRESIA_SUSPENDIDA' => 'La membresía del cliente está suspendida.',
            'MEMBRESIA_CANCELADA' => 'La membresía del cliente está cancelada.',
            'SIN_ACCESOS_DISPONIBLES' => 'La membresía del cliente no tiene accesos disponibles.',
            default => 'No cumple reglas de acceso.',
        };
    }
}
