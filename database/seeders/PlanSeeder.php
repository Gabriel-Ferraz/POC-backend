<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        Plan::firstOrCreate(
            ["slug" => "starter"],
            [
                "name" => "Starter",
                "price" => 0.00,
                "description" => "Plano gratuito para começar",
                "features" => [
                    "Acesso básico à plataforma",
                    "100 minutos de TTS por mês",
                    "1 usuário"
                ],
                "is_active" => true,
            ]
        );

        Plan::firstOrCreate(
            ["slug" => "pro"],
            [
                "name" => "Pro",
                "price" => 0.00,
                "description" => "Para profissionais e times",
                "features" => [
                    "Tudo do Starter",
                    "Minutos ilimitados de TTS",
                    "Até 5 usuários",
                    "Suporte prioritário"
                ],
                "is_active" => false,
            ]
        );

        Plan::firstOrCreate(
            ["slug" => "enterprise"],
            [
                "name" => "Enterprise",
                "price" => 0.00,
                "description" => "Soluções customizadas para empresas",
                "features" => [
                    "Tudo do Pro",
                    "Usuários ilimitados",
                    "SLA garantido",
                    "Suporte dedicado",
                    "Customizações"
                ],
                "is_active" => false,
            ]
        );
    }
}
