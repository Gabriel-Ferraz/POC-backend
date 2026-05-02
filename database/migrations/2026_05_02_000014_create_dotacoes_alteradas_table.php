<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dotacoes_alteradas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alteracao_orcamentaria_id')->constrained('alteracoes_orcamentarias')->cascadeOnDelete();
            $table->string('dotacao_orcamentaria');
            $table->string('conta_receita')->nullable();
            $table->decimal('valor_suprimido', 15, 2)->default(0);
            $table->decimal('valor_suplementado', 15, 2)->default(0);
            $table->decimal('saldo_atual', 15, 2)->default(0);
            $table->decimal('novo_saldo', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dotacoes_alteradas');
    }
};
