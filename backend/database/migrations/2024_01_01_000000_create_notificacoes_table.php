<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notificacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->string('tipo');
            $table->string('titulo');
            $table->text('mensagem');
            $table->json('dados')->nullable();
            $table->boolean('lida')->default(false);
            $table->boolean('enviada')->default(false);
            $table->string('canal')->default('email');
            $table->timestamp('data_enviada')->nullable();
            $table->string('recurso_tipo')->nullable(); // 'transacao', 'conta', etc.
            $table->string('recurso_id')->nullable(); // public_id do recurso
            $table->timestamps();
            
            // Índices para melhorar performance de consultas
            $table->index('usuario_id');
            $table->index('tipo');
            $table->index('lida');
            $table->index('enviada');
            $table->index(['recurso_tipo', 'recurso_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificacoes');
    }
}; 