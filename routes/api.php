<?php

use App\Http\Controllers\Api\V1\AccesoController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuditoriaController;
use App\Http\Controllers\Api\V1\BiometricoController;
use App\Http\Controllers\Api\V1\ClienteController;
use App\Http\Controllers\Api\V1\CorteCajaController;
use App\Http\Controllers\Api\V1\DispositivoController;
use App\Http\Controllers\Api\V1\MembresiaController;
use App\Http\Controllers\Api\V1\MetodoPagoController;
use App\Http\Controllers\Api\V1\PagoController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\RecepcionController;
use App\Http\Controllers\Api\V1\ReporteController;
use App\Http\Controllers\Api\V1\SucursalController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/auth')->group(function (): void {
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('v1/rbac')
    ->middleware(['auth:sanctum', 'permission:roles.asignar_permisos'])
    ->group(function (): void {
        Route::get('check-admin', function () {
            return response()->json([
                'message' => 'Autorizado correctamente.',
            ]);
        });
    });

Route::prefix('v1')->group(function (): void {

    Route::prefix('planes')->middleware('auth:sanctum')->group(function (): void {
        Route::get('/', [PlanController::class, 'index'])->middleware('permission:planes.ver');
        Route::get('{plan}', [PlanController::class, 'show'])->middleware('permission:planes.ver');
        Route::post('/', [PlanController::class, 'store'])->middleware('permission:planes.crear');
        Route::match(['put', 'patch'], '{plan}', [PlanController::class, 'update'])->middleware('permission:planes.editar');
        Route::patch('{plan}/desactivar', [PlanController::class, 'desactivar'])->middleware('permission:planes.desactivar');
        Route::patch('{plan}/reactivar', [PlanController::class, 'reactivar'])->middleware('permission:planes.editar');
    });

    Route::prefix('sucursales')->middleware('auth:sanctum')->group(function (): void {
        Route::get('/', [SucursalController::class, 'index'])->middleware('permission:sucursales.ver');
        Route::get('{sucursal}', [SucursalController::class, 'show'])->middleware('permission:sucursales.ver');
        Route::post('/', [SucursalController::class, 'store'])->middleware('permission:sucursales.crear');
        Route::match(['put', 'patch'], '{sucursal}', [SucursalController::class, 'update'])->middleware('permission:sucursales.editar');
        Route::patch('{sucursal}/desactivar', [SucursalController::class, 'desactivar'])->middleware('permission:sucursales.desactivar');
        Route::patch('{sucursal}/reactivar', [SucursalController::class, 'reactivar'])->middleware('permission:sucursales.editar');
    });


    Route::prefix('clientes')->middleware('auth:sanctum')->group(function (): void {
        Route::get('/', [ClienteController::class, 'index'])->middleware('permission:clientes.ver');
        Route::get('{cliente}', [ClienteController::class, 'show'])->middleware('permission:clientes.ver');
        Route::get('{cliente}/membresias', [MembresiaController::class, 'porCliente'])->middleware('permission:membresias.ver');
        Route::get('{cliente}/pagos', [PagoController::class, 'porCliente'])->middleware('permission:pagos.ver');
        Route::get('{cliente}/accesos', [AccesoController::class, 'porCliente'])->middleware('permission:accesos.ver');
        Route::get('{cliente}/biometricos', [BiometricoController::class, 'porCliente'])->middleware('permission:biometricos.ver');
        Route::post('/', [ClienteController::class, 'store'])->middleware('permission:clientes.crear');
        Route::match(['put', 'patch'], '{cliente}', [ClienteController::class, 'update'])->middleware('permission:clientes.editar');
        Route::patch('{cliente}/cambiar-estatus', [ClienteController::class, 'cambiarEstatus'])->middleware('permission:clientes.cambiar_estatus');
        Route::patch('{cliente}/desactivar', [ClienteController::class, 'desactivar'])->middleware('permission:clientes.desactivar');
        Route::patch('{cliente}/reactivar', [ClienteController::class, 'reactivar'])->middleware('permission:clientes.cambiar_estatus');
    });

    Route::prefix('membresias')->middleware('auth:sanctum')->group(function (): void {
        Route::get('/', [MembresiaController::class, 'index'])->middleware('permission:membresias.ver');
        Route::get('{membresia}', [MembresiaController::class, 'show'])->middleware('permission:membresias.ver');
        Route::post('/', [MembresiaController::class, 'store'])->middleware('permission:membresias.crear');
        Route::match(['put', 'patch'], '{membresia}', [MembresiaController::class, 'update'])->middleware('permission:membresias.editar');
        Route::patch('{membresia}/renovar', [MembresiaController::class, 'renovar'])->middleware('permission:membresias.renovar');
        Route::patch('{membresia}/suspender', [MembresiaController::class, 'suspender'])->middleware('permission:membresias.suspender');
        Route::patch('{membresia}/cancelar', [MembresiaController::class, 'cancelar'])->middleware('permission:membresias.cancelar');
        Route::patch('{membresia}/reactivar', [MembresiaController::class, 'reactivar'])->middleware('permission:membresias.editar');
    });

    Route::prefix('metodos-pago')->middleware('auth:sanctum')->group(function (): void {
        Route::get('/', [MetodoPagoController::class, 'index'])->middleware('permission:metodos_pago.ver');
        Route::get('{metodoPago}', [MetodoPagoController::class, 'show'])->middleware('permission:metodos_pago.ver');
        Route::post('/', [MetodoPagoController::class, 'store'])->middleware('permission:metodos_pago.crear');
        Route::match(['put', 'patch'], '{metodoPago}', [MetodoPagoController::class, 'update'])->middleware('permission:metodos_pago.editar');
        Route::patch('{metodoPago}/desactivar', [MetodoPagoController::class, 'desactivar'])->middleware('permission:metodos_pago.desactivar');
        Route::patch('{metodoPago}/reactivar', [MetodoPagoController::class, 'reactivar'])->middleware('permission:metodos_pago.editar');
    });

    Route::prefix('pagos')->middleware('auth:sanctum')->group(function (): void {
        Route::get('/', [PagoController::class, 'index'])->middleware('permission:pagos.ver');
        Route::post('/', [PagoController::class, 'store'])->middleware('permission:pagos.registrar');
        Route::get('{pago}', [PagoController::class, 'show'])->middleware('permission:pagos.ver');
        Route::patch('{pago}/cancelar', [PagoController::class, 'cancelar'])->middleware('permission:pagos.cancelar');
    });



    Route::prefix('cortes-caja')->middleware('auth:sanctum')->group(function (): void {
        Route::get('/', [CorteCajaController::class, 'index'])->middleware('permission:pagos.ver');
        Route::post('calcular', [CorteCajaController::class, 'calcular'])->middleware('permission:pagos.ver');
        Route::post('/', [CorteCajaController::class, 'store'])->middleware('permission:pagos.registrar');
        Route::get('{corte}/exportar', [CorteCajaController::class, 'exportar'])->middleware('permission:reportes.exportar');
        Route::get('{corte}', [CorteCajaController::class, 'show'])->middleware('permission:pagos.ver');
        Route::patch('{corte}/cancelar', [CorteCajaController::class, 'cancelar'])->middleware('permission:pagos.cancelar');
    });

    Route::prefix('dispositivos')->middleware('auth:sanctum')->group(function (): void {
        Route::get('/', [DispositivoController::class, 'index'])->middleware('permission:dispositivos.ver');
        Route::get('{dispositivo}', [DispositivoController::class, 'show'])->middleware('permission:dispositivos.ver');
        Route::post('/', [DispositivoController::class, 'store'])->middleware('permission:dispositivos.crear');
        Route::match(['put', 'patch'], '{dispositivo}', [DispositivoController::class, 'update'])->middleware('permission:dispositivos.editar');
        Route::patch('{dispositivo}/cambiar-estatus', [DispositivoController::class, 'cambiarEstatus'])->middleware('permission:dispositivos.editar');
        Route::patch('{dispositivo}/desactivar', [DispositivoController::class, 'desactivar'])->middleware('permission:dispositivos.desactivar');
        Route::patch('{dispositivo}/reactivar', [DispositivoController::class, 'reactivar'])->middleware('permission:dispositivos.editar');
    });


    Route::prefix('biometricos')->middleware('auth:sanctum')->group(function (): void {
        Route::get('/', [BiometricoController::class, 'index'])->middleware('permission:biometricos.ver');
        Route::get('{biometrico}', [BiometricoController::class, 'show'])->middleware('permission:biometricos.ver');
        Route::post('enrolar', [BiometricoController::class, 'enrolar'])->middleware('permission:biometricos.enrolar');
        Route::patch('{biometrico}/marcar-principal', [BiometricoController::class, 'marcarPrincipal'])->middleware('permission:biometricos.enrolar');
        Route::patch('{biometrico}/desactivar', [BiometricoController::class, 'desactivar'])->middleware('permission:biometricos.eliminar');
        Route::patch('{biometrico}/revocar', [BiometricoController::class, 'revocar'])->middleware('permission:biometricos.eliminar');
    });

    Route::prefix('accesos')->middleware('auth:sanctum')->group(function (): void {
        Route::get('/', [AccesoController::class, 'index'])->middleware('permission:accesos.ver');
        Route::get('{acceso}', [AccesoController::class, 'show'])->middleware('permission:accesos.ver');
        Route::post('validar', [AccesoController::class, 'validar'])->middleware('permission:accesos.validar');
    });


    Route::prefix('recepcion')->middleware('auth:sanctum')->group(function (): void {
        Route::get('clientes/buscar', [RecepcionController::class, 'buscarClientes'])->middleware('permission:clientes.ver');
        Route::get('clientes/{cliente}/resumen', [RecepcionController::class, 'resumenCliente'])->middleware('permission:clientes.ver');
    });


    Route::prefix('reportes')->middleware('auth:sanctum')->group(function (): void {
        Route::get('ingresos/exportar', [ReporteController::class, 'exportarIngresos'])->middleware('permission:reportes.exportar');
        Route::get('accesos/exportar', [ReporteController::class, 'exportarAccesos'])->middleware('permission:reportes.exportar');
        Route::get('membresias-por-vencer/exportar', [ReporteController::class, 'exportarMembresiasPorVencer'])->middleware('permission:reportes.exportar');
        Route::get('clientes-vencidos/exportar', [ReporteController::class, 'exportarClientesVencidos'])->middleware('permission:reportes.exportar');
        Route::get('dashboard', [ReporteController::class, 'dashboard'])->middleware('permission:reportes.ver');
        Route::get('ingresos', [ReporteController::class, 'ingresos'])->middleware('permission:reportes.ver');
        Route::get('membresias-por-vencer', [ReporteController::class, 'membresiasPorVencer'])->middleware('permission:reportes.ver');
        Route::get('clientes-vencidos', [ReporteController::class, 'clientesVencidos'])->middleware('permission:reportes.ver');
        Route::get('accesos', [ReporteController::class, 'accesos'])->middleware('permission:reportes.ver');
        Route::get('clientes', [ReporteController::class, 'clientes'])->middleware('permission:reportes.ver');
    });

    Route::prefix('auditoria')->middleware('auth:sanctum')->group(function (): void {
        Route::get('/', [AuditoriaController::class, 'index'])->middleware('permission:auditoria.ver');
        Route::get('{evento}', [AuditoriaController::class, 'show'])->middleware('permission:auditoria.ver');
    });
});
