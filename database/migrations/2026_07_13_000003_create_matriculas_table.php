<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Matrículas vindas de GET /api/v2/academico/matriculas
 * Chave primária = id_matricula (upsert idempotente por período letivo).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriculas', function (Blueprint $table) {
            $table->unsignedBigInteger('id_matricula')->primary();

            $table->unsignedBigInteger('id_turma')->nullable();
            $table->unsignedBigInteger('id_periodo_letivo')->nullable()->index();
            $table->unsignedBigInteger('id_aluno_curso_ingresso')->nullable();
            $table->unsignedBigInteger('id_aluno')->nullable()->index();
            $table->unsignedBigInteger('id_perfil_aluno')->nullable();

            $table->string('aluno')->nullable();
            $table->string('ra')->nullable()->index();
            $table->string('ra_estadual')->nullable();
            $table->string('periodo_letivo')->nullable();

            $table->unsignedBigInteger('id_curso_base')->nullable();
            $table->string('curso')->nullable();
            $table->string('turma')->nullable();
            $table->string('status')->nullable()->index();

            $table->unsignedBigInteger('id_curso_matriz')->nullable();
            $table->string('matriz')->nullable();

            $table->string('aluno_email')->nullable();
            $table->string('aluno_email_institucional')->nullable();

            $table->date('data_matricula')->nullable();
            $table->date('data_ativacao')->nullable();
            $table->date('data_trancamento')->nullable();
            $table->date('data_cadastro')->nullable();

            $table->unsignedBigInteger('id_unidade_fisica')->nullable();
            $table->string('unidade_fisica')->nullable();

            $table->unsignedBigInteger('id_org')->nullable();
            $table->string('organizacao')->nullable();

            $table->timestamp('data_criacao_api')->nullable();
            $table->timestamp('data_alteracao_api')->nullable();

            $table->jsonb('raw')->nullable()->comment('Payload original da API');
            $table->timestamp('sincronizado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriculas');
    }
};
