<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Períodos letivos (os "anos disponíveis") vindos de
 * GET /api/v1/academico/periodos-letivos/
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodos_letivos', function (Blueprint $table) {
            $table->unsignedBigInteger('id_periodo_letivo')->primary();
            $table->unsignedBigInteger('id_org')->nullable();
            $table->string('org_descricao')->nullable();
            $table->string('descricao')->nullable();
            $table->string('descricao_especial')->nullable();
            $table->date('data_inicio')->nullable();
            $table->date('data_termino')->nullable();
            $table->string('situacao')->nullable();
            $table->integer('ano')->nullable()->index();
            $table->integer('semestre')->nullable();
            $table->integer('periodo_atual')->nullable();
            $table->string('tipo')->nullable();
            $table->integer('consolidado')->nullable();
            $table->jsonb('raw')->nullable()->comment('Payload original da API');
            $table->timestamp('sincronizado_em')->nullable();
            $table->timestamps();

            $table->index(['ano', 'semestre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodos_letivos');
    }
};
