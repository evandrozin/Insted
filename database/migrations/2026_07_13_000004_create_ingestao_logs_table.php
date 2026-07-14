<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Histórico de execuções de ingestão (sincronização com o JACAD).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingestao_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tipo')->default('matriculas')->comment('matriculas, periodos_letivos, ...');
            $table->unsignedBigInteger('id_periodo_letivo')->nullable();
            $table->string('referencia')->nullable()->comment('Ex.: ano/período processado');
            $table->string('status')->default('executando')->comment('executando, concluido, erro');
            $table->unsignedInteger('total_registros')->default(0);
            $table->unsignedInteger('total_paginas')->default(0);
            $table->text('mensagem')->nullable();
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('finalizado_em')->nullable();
            $table->timestamps();

            $table->index(['tipo', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingestao_logs');
    }
};
