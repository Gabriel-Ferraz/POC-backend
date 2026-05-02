<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitacoes_pagamento', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('empenho_id')->constrained('empenhos')->cascadeOnDelete();
            $table->foreignId('solicitante_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('valor', 15, 2);
            $table->text('observacao')->nullable();

            // Documento fiscal
            $table->string('tipo_documento');
            $table->string('numero_documento');
            $table->string('serie')->nullable();
            $table->date('data_emissao_documento');
            $table->text('observacao_documento')->nullable();

            // Forma de pagamento
            $table->enum('forma_pagamento', ['conta_bancaria', 'documento'])->default('conta_bancaria');
            $table->string('banco')->nullable();
            $table->string('agencia')->nullable();
            $table->string('digito_agencia')->nullable();
            $table->string('conta')->nullable();
            $table->string('digito_conta')->nullable();
            $table->string('operacao')->nullable();
            $table->string('cidade_banco')->nullable();
            $table->text('observacao_pagamento')->nullable();

            $table->enum('status', [
                'pendente',
                'aguardando_aprovacao_anexos',
                'anexos_recusados',
                'aguardando_autorizacao_gestor',
                'em_liquidacao',
                'em_ordem_pagamento',
                'pagamento_em_remessa',
                'pagamento_realizado',
                'cancelada'
            ])->default('pendente');

            $table->timestamp('cancelada_em')->nullable();
            $table->text('motivo_cancelamento')->nullable();
            $table->timestamp('paga_em')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitacoes_pagamento');
    }
};
