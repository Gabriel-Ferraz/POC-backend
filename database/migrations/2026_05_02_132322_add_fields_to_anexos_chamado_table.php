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
        Schema::table('anexos_chamado', function (Blueprint $table) {
            // Renomear coluna arquivo para caminho
            $table->renameColumn('arquivo', 'caminho');

            // Adicionar novos campos
            $table->string('nome_salvo')->after('nome_original');
            $table->bigInteger('tamanho')->after('caminho');
            $table->string('tipo', 100)->after('tamanho');
            $table->foreignId('enviado_por_usuario_id')->after('tipo')->constrained('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('anexos_chamado', function (Blueprint $table) {
            $table->renameColumn('caminho', 'arquivo');
            $table->dropForeign(['enviado_por_usuario_id']);
            $table->dropColumn(['nome_salvo', 'tamanho', 'tipo', 'enviado_por_usuario_id']);
        });
    }
};
