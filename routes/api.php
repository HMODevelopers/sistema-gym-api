<?php

use App\Http\Controllers\Api\V1\AuthController;
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
                'message' => 'Acceso permitido.',
            ]);
        });
    });
