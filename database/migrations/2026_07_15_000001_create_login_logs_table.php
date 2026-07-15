<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Registro de acessos (login/logout) dos usuários do painel.
 * Cada login gera uma linha; o logout carimba logged_out_at e um
 * middleware de atividade mantém last_activity_at (heartbeat) para
 * detectar sessões ainda ativas ou com conexão perdida.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->string('email')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('session_id')->nullable()->index();
            $table->timestamp('logged_in_at')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('logged_out_at')->nullable();
            $table->string('logout_type', 20)->nullable()->comment('manual = clicou em sair');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_logs');
    }
};
