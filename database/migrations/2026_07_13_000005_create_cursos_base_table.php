<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cursos base, de GET /api/v1/academico/cursos-base/
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cursos_base', function (Blueprint $table) {
            $table->unsignedBigInteger('id_curso_base')->primary();
            $table->string('nome_impressao')->nullable();
            $table->string('nome_reduzido')->nullable();
            $table->string('codigo_curso')->nullable();
            $table->string('modalidade')->nullable();
            $table->string('status')->nullable()->index();
            $table->string('grau_academico')->nullable();
            $table->string('org')->nullable();
            $table->jsonb('raw')->nullable();
            $table->timestamp('sincronizado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cursos_base');
    }
};
