<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Endereço do aluno (cidade/bairro), usado no painel de Demografia.
 *
 * As matrículas não trazem endereço: ele vive no perfil da pessoa
 * (GET /api/v1/basicos/perfis), ligado por matriculas.id_perfil_aluno,
 * e a cidade é uma referência (GET /api/v1/basicos/locais/cidades).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cidades', function (Blueprint $table) {
            $table->unsignedBigInteger('id_cidade')->primary();

            $table->string('descricao')->nullable()->index();
            $table->unsignedBigInteger('id_estado')->nullable();
            $table->string('uf', 5)->nullable()->index();
            $table->string('estado')->nullable();
            $table->string('codigo_ibge')->nullable();
            $table->unsignedBigInteger('id_pais')->nullable();

            $table->timestamp('sincronizado_em')->nullable();
            $table->timestamps();
        });

        Schema::create('perfis', function (Blueprint $table) {
            $table->unsignedBigInteger('id_perfil')->primary();

            $table->string('nome')->nullable();
            $table->string('cpf')->nullable();

            $table->unsignedBigInteger('id_cidade')->nullable()->index();
            $table->string('bairro')->nullable()->index();
            $table->string('logradouro')->nullable();
            $table->string('numero')->nullable();
            $table->string('complemento')->nullable();
            $table->string('cep', 20)->nullable();

            $table->timestamp('sincronizado_em')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perfis');
        Schema::dropIfExists('cidades');
    }
};
