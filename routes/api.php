<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\MetodoPagoController;
use App\Http\Controllers\Api\V1\PlanController;
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

    Route::prefix('metodos-pago')->middleware('auth:sanctum')->group(function (): void {
        Route::get('/', [MetodoPagoController::class, 'index'])->middleware('permission:metodos_pago.ver');
        Route::get('{metodoPago}', [MetodoPagoController::class, 'show'])->middleware('permission:metodos_pago.ver');
        Route::post('/', [MetodoPagoController::class, 'store'])->middleware('permission:metodos_pago.crear');
        Route::match(['put', 'patch'], '{metodoPago}', [MetodoPagoController::class, 'update'])->middleware('permission:metodos_pago.editar');
        Route::patch('{metodoPago}/desactivar', [MetodoPagoController::class, 'desactivar'])->middleware('permission:metodos_pago.desactivar');
        Route::patch('{metodoPago}/reactivar', [MetodoPagoController::class, 'reactivar'])->middleware('permission:metodos_pago.editar');
    });
});
