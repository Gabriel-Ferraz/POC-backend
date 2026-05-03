<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Remove a constraint antiga
        DB::statement('ALTER TABLE solicitacoes_pagamento DROP CONSTRAINT IF EXISTS solicitacoes_pagamento_status_check');

        // Mapeia os status antigos para os novos
        DB::statement("
            UPDATE solicitacoes_pagamento
            SET status = CASE
                WHEN status = 'pendente' THEN 'rascunho'
                WHEN status = 'aguardando_aprovacao_anexos' THEN 'aguardando_aprovacao'
                WHEN status = 'anexos_recusados' THEN 'anexos'
                WHEN status = 'aguardando_autorizacao_gestor' THEN 'gestor'
                WHEN status = 'em_liquidacao' THEN 'liquidacao'
                WHEN status = 'em_ordem_pagamento' THEN 'ordem_pagamento'
                WHEN status = 'pagamento_em_remessa' THEN 'remessa'
                WHEN status = 'pagamento_realizado' THEN 'pagamento_realizado'
                WHEN status = 'cancelada' THEN 'cancelado'
                ELSE status
            END
        ");

        // Adiciona a nova constraint com todos os status do fluxo
        DB::statement("
            ALTER TABLE solicitacoes_pagamento
            ADD CONSTRAINT solicitacoes_pagamento_status_check
            CHECK (status IN (
                'rascunho',
                'aguardando_aprovacao',
                'anexos',
                'fiscal',
                'gestor',
                'liquidacao',
                'secretario',
                'iss',
                'ordem_pagamento',
                'autorizacao',
                'bordero',
                'remessa',
                'pagamento',
                'pagamento_realizado',
                'cancelado'
            ))
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove a constraint nova
        DB::statement('ALTER TABLE solicitacoes_pagamento DROP CONSTRAINT IF EXISTS solicitacoes_pagamento_status_check');

        // Restaura a constraint antiga
        DB::statement("
            ALTER TABLE solicitacoes_pagamento
            ADD CONSTRAINT solicitacoes_pagamento_status_check
            CHECK (status IN (
                'pendente',
                'aguardando_aprovacao_anexos',
                'anexos_recusados',
                'aguardando_autorizacao_gestor',
                'em_liquidacao',
                'em_ordem_pagamento',
                'pagamento_em_remessa',
                'pagamento_realizado',
                'cancelada'
            ))
        ");
    }
};
