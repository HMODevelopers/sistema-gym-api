<?php

use App\Http\Controllers\Api\V1\AuthController;
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

Route::prefix('sucursales')->middleware('auth:sanctum')->group(function (): void {
        Route::get('/', [SucursalController::class, 'index'])->middleware('permission:sucursales.ver');
        Route::get('{sucursal}', [SucursalController::class, 'show'])->middleware('permission:sucursales.ver');
        Route::post('/', [SucursalController::class, 'store'])->middleware('permission:sucursales.crear');
        Route::match(['put', 'patch'], '{sucursal}', [SucursalController::class, 'update'])->middleware('permission:sucursales.editar');
        Route::patch('{sucursal}/desactivar', [SucursalController::class, 'desactivar'])->middleware('permission:sucursales.desactivar');
        Route::patch('{sucursal}/reactivar', [SucursalController::class, 'reactivar'])->middleware('permission:sucursales.editar');
});

