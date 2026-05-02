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
        Schema::table('chamados', function (Blueprint $table) {
            // Renomear colunas para seguir o padrão do documento
            $table->renameColumn('respondido_em', 'data_ultima_resposta');
            $table->renameColumn('concluido_em', 'data_conclusao');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chamados', function (Blueprint $table) {
            $table->renameColumn('data_ultima_resposta', 'respondido_em');
            $table->renameColumn('data_conclusao', 'concluido_em');
        });
    }
};
