<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('turmas', function (Blueprint $table) {
            $table->string('periodo_item')->nullable()->after('turno');
            $table->integer('periodo_numero')->nullable()->after('periodo_item');
        });
    }

    public function down(): void
    {
        Schema::table('turmas', function (Blueprint $table) {
            $table->dropColumn(['periodo_item', 'periodo_numero']);
        });
    }
};
