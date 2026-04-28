<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bitacora_eventos')) {
            return;
        }

        Schema::create('bitacora_eventos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('usuarios')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->nullOnDelete();
            $table->string('modulo', 80);
            $table->string('accion', 80);
            $table->string('entidad', 120)->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('metodo_http', 10)->nullable();
            $table->string('ruta', 255)->nullable();
            $table->string('request_id', 100)->nullable();
            $table->timestamps();

            $table->index(['modulo', 'accion']);
            $table->index(['usuario_id', 'created_at']);
            $table->index(['sucursal_id', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bitacora_eventos');
    }
};
