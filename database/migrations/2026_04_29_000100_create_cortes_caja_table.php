<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cortes_caja')) return;
        Schema::create('cortes_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales');
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios');
            $table->dateTime('fecha_desde');
            $table->dateTime('fecha_hasta');
            $table->unsignedInteger('total_pagos')->default(0);
            $table->decimal('total_monto', 12, 2)->default(0);
            $table->unsignedInteger('total_cancelados')->default(0);
            $table->decimal('total_cancelado_monto', 12, 2)->default(0);
            $table->decimal('efectivo_esperado', 12, 2)->default(0);
            $table->decimal('efectivo_contado', 12, 2)->default(0);
            $table->decimal('diferencia_efectivo', 12, 2)->default(0);
            $table->json('detalle_metodos_pago')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('estatus', 20)->default('CERRADO');
            $table->string('motivo_cancelacion', 255)->nullable();
            $table->dateTime('cancelado_at')->nullable();
            $table->foreignId('cancelado_por_usuario_id')->nullable()->constrained('usuarios');
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('cortes_caja'); }
};
