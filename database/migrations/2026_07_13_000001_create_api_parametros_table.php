<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabela de parâmetros das APIs (JACAD e futuras integrações).
 * Fonte de verdade em runtime para base_url, usuário e token de acesso.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_parametros', function (Blueprint $table) {
            $table->id();
            $table->string('nome')->unique()->comment('Identificador lógico da integração, ex.: jacad');
            $table->string('descricao')->nullable();
            $table->string('base_url');
            $table->string('usuario')->nullable();
            $table->text('token')->nullable()->comment('Chave de API / Token de Acesso');
            $table->unsignedInteger('page_size')->default(200);
            $table->boolean('ativo')->default(true);
            $table->jsonb('extra')->nullable()->comment('Configurações adicionais (grupos, headers, etc.)');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_parametros');
    }
};
