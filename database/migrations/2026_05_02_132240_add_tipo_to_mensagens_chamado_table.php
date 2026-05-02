<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('mensagens_chamado', function (Blueprint $table) {
            $table->enum('tipo', ['abertura', 'resposta', 'conclusao'])->default('resposta')->after('usuario_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mensagens_chamado', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
