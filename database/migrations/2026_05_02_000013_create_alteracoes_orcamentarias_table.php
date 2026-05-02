<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alteracoes_orcamentarias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lei_ato_id')->constrained('leis_atos')->cascadeOnDelete();
            $table->string('decreto_autorizador');
            $table->date('data_ato');
            $table->date('data_publicacao');
            $table->enum('tipo_ato', ['decreto', 'resolucao', 'ato_gestor']);
            $table->enum('tipo_credito', ['especial', 'suplementar', 'extraordinario']);
            $table->enum('tipo_recurso', ['superavit', 'excesso_arrecadacao']);
            $table->decimal('valor_credito', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alteracoes_orcamentarias');
    }
};
