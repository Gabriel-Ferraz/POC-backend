<?php

namespace Database\Seeders;

use App\Models\SimamLayout;
use Illuminate\Database\Seeder;

class SimamLayoutsSeeder extends Seeder
{
    public function run(): void
    {
        $layouts = [
            [
                'key' => 'plano_contabil',
                'name' => 'PlanoContabil',
                'module' => 'contabilidade',
                'generation_type' => 'mensal',
                'order_index' => 1,
                'active' => true,
            ],
            [
                'key' => 'movimento_contabil_mensal',
                'name' => 'MovimentoContabilMensal',
                'module' => 'contabilidade',
                'generation_type' => 'mensal',
                'order_index' => 2,
                'active' => true,
            ],
            [
                'key' => 'diario_contabil',
                'name' => 'DiarioContabil',
                'module' => 'contabilidade',
                'generation_type' => 'mensal',
                'order_index' => 3,
                'active' => true,
            ],
            [
                'key' => 'movimento_realizavel',
                'name' => 'MovimentoRealizavel',
                'module' => 'contabilidade',
                'generation_type' => 'mensal',
                'order_index' => 4,
                'active' => true,
            ],
        ];

        foreach ($layouts as $layout) {
            SimamLayout::updateOrCreate(
                ['key' => $layout['key']],
                $layout
            );
        }

        $this->command->info('✅ Layouts SIMAM criados com sucesso!');
    }
}
