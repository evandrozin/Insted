<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turmas, de GET /api/v1/academico/turmas
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turmas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_turma')->primary();
            $table->string('nome')->nullable();
            $table->string('nome_reduzido')->nullable();
            $table->unsignedBigInteger('id_curso')->nullable()->index();
            $table->string('curso')->nullable();
            $table->unsignedBigInteger('id_matriz')->nullable();
            $table->string('matriz')->nullable();
            $table->unsignedBigInteger('id_periodo_letivo')->nullable()->index();
            $table->string('periodo_letivo')->nullable();
            $table->unsignedBigInteger('id_unidade_fisica')->nullable();
            $table->string('unidade_fisica')->nullable();
            $table->string('turno')->nullable();
            $table->string('status')->nullable()->index();
            $table->unsignedBigInteger('id_org')->nullable();
            $table->string('org_descricao')->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->integer('qtde_disciplina')->nullable();
            $table->jsonb('raw')->nullable();
            $table->timestamp('sincronizado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turmas');
    }
};
