<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('solicitacoes_pagamento', function (Blueprint $table) {
            // Troca date por datetime para registrar hora exata
            $table->dateTime('paga_em')->nullable()->change();

            // Quem registrou o pagamento e valor efetivamente pago
            $table->foreignId('pago_por_id')->nullable()->constrained('users')->nullOnDelete()->after('paga_em');
            $table->decimal('valor_pago', 15, 2)->nullable()->after('pago_por_id');
            $table->text('observacao_pagamento_realizado')->nullable()->after('valor_pago');
        });
    }

    public function down(): void
    {
        Schema::table('solicitacoes_pagamento', function (Blueprint $table) {
            $table->date('paga_em')->nullable()->change();
            $table->dropForeign(['pago_por_id']);
            $table->dropColumn(['pago_por_id', 'valor_pago', 'observacao_pagamento_realizado']);
        });
    }
};
