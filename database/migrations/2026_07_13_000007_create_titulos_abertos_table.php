<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Títulos (receitas) em aberto e vencidos, de
 * GET /api/v1/financeiro/consolidacao/receitas?situacao=ABERTO
 * Usados para determinar inadimplência por aluno/matrícula.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('titulos_abertos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_transacao')->primary();
            $table->unsignedBigInteger('id_aluno')->nullable()->index();
            $table->unsignedBigInteger('id_matricula')->nullable()->index();
            $table->unsignedBigInteger('id_periodo_letivo')->nullable()->index();
            $table->unsignedBigInteger('id_curso')->nullable();
            $table->string('curso')->nullable();
            $table->unsignedBigInteger('id_turma')->nullable();
            $table->string('turma')->nullable();
            $table->string('pagador_nome')->nullable();
            $table->string('pagador_cpf')->nullable();
            $table->string('situacao')->nullable();
            $table->string('origem')->nullable();
            $table->date('data_vencimento')->nullable()->index();
            $table->decimal('valor', 12, 2)->nullable();
            $table->string('matricula_status')->nullable();
            $table->unsignedBigInteger('id_org')->nullable();
            $table->timestamp('sincronizado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('titulos_abertos');
    }
};
