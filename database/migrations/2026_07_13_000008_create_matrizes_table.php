<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Matrizes curriculares, de GET /api/v1/academico/matrizes/
 * Guarda a duração para determinar o último semestre (possíveis formandos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matrizes', function (Blueprint $table) {
            $table->unsignedBigInteger('id_curso_matriz')->primary();
            $table->unsignedBigInteger('id_curso_base')->nullable()->index();
            $table->string('nome')->nullable();
            $table->integer('prazo_conclusao')->nullable();
            $table->string('prazo_em')->nullable();
            $table->string('periodicidade')->nullable();
            $table->integer('total_semestres')->nullable();
            $table->string('status')->nullable();
            $table->jsonb('raw')->nullable();
            $table->timestamp('sincronizado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matrizes');
    }
};
