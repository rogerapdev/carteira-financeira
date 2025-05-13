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
        Schema::create('auditoria', function (Blueprint $table) {
            $table->id();
            $table->string('acao');
            $table->string('recurso');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->string('request_id')->nullable();
            $table->string('ip')->nullable();
            $table->string('method')->nullable();
            $table->text('url')->nullable();
            $table->text('user_agent')->nullable();
            $table->json('detalhes')->nullable();
            $table->string('nivel')->default('info');
            $table->timestamps();

            $table->foreign('usuario_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            $table->index(['acao', 'recurso']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('auditoria');
    }
};