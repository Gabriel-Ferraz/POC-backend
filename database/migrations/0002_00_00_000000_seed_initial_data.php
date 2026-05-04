<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure solicitacoes_pagamento use the current status slugs
        DB::statement("
            UPDATE solicitacoes_pagamento
            SET status = CASE
                WHEN status = 'pendente'                    THEN 'rascunho'
                WHEN status = 'aguardando_aprovacao_anexos' THEN 'aguardando_aprovacao'
                WHEN status = 'anexos_recusados'            THEN 'anexos'
                WHEN status = 'aguardando_autorizacao_gestor' THEN 'gestor'
                WHEN status = 'em_liquidacao'               THEN 'liquidacao'
                WHEN status = 'em_ordem_pagamento'          THEN 'ordem_pagamento'
                WHEN status = 'pagamento_em_remessa'        THEN 'remessa'
                WHEN status = 'cancelada'                   THEN 'cancelado'
                ELSE status
            END
        ");
    }

    public function down(): void
    {
        // Status migration is not reversible
    }
};
