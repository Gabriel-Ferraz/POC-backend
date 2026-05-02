<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anexos_solicitacao', function (Blueprint $table) {
            $table->id();
            $table->foreignId('solicitacao_id')->constrained('solicitacoes_pagamento')->cascadeOnDelete();
            $table->enum('tipo_anexo', [
                'documento_fiscal',
                'certidao_negativa_debitos',
                'certidao_tributaria',
                'guia_previdencia_social',
                'fgts'
            ]);
            $table->string('arquivo_path')->nullable();
            $table->string('arquivo_nome')->nullable();
            $table->enum('status', [
                'pendente',
                'anexo_cadastrado',
                'aguardando_aprovacao',
                'aprovado',
                'recusado'
            ])->default('pendente');
            $table->date('data_envio')->nullable();
            $table->text('motivo_recusa')->nullable();
            $table->foreignId('aprovado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('data_aprovacao')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anexos_solicitacao');
    }
};
