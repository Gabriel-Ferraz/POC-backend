<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exportacoes_prestacao_contas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->integer('ano');
            $table->string('modulo');
            $table->string('tipo_geracao');
            $table->integer('mes')->nullable();
            $table->json('arquivos_selecionados');
            $table->string('arquivo_gerado');
            $table->integer('quantidade_registros');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exportacoes_prestacao_contas');
    }
};
