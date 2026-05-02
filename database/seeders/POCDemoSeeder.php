<?php

namespace Database\Seeders;

use App\Models\{
    AlteracaoOrcamentaria,
    AnexoSolicitacao,
    Chamado,
    Contrato,
    Empenho,
    Fornecedor,
    LeiAto,
    SolicitacaoPagamento,
    User,
};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class POCDemoSeeder extends Seeder
{
    public function run(): void
    {
        // Criar usuários com diferentes perfis
        $responsavelTecnico = User::create([
            'name' => 'João Silva (Responsável Técnico)',
            'email' => 'responsavel@fornecedor.com',
            'cpf' => '12345678900',
            'password' => Hash::make('senha123'),
            'perfil' => 'responsavel_tecnico',
            'is_active' => true,
        ]);

        $gestorContrato = User::create([
            'name' => 'Maria Santos (Gestor Contrato)',
            'email' => 'gestor.contrato@pmsjp.pr.gov.br',
            'cpf' => '98765432100',
            'password' => Hash::make('senha123'),
            'perfil' => 'gestor_contrato',
            'is_active' => true,
        ]);

        $operadorPmsjp = User::create([
            'name' => 'Carlos Oliveira (Operador PMSJP)',
            'email' => 'operador@pmsjp.pr.gov.br',
            'cpf' => '11122233344',
            'password' => Hash::make('senha123'),
            'perfil' => 'operador_pmsjp',
            'is_active' => true,
        ]);

        $gestorSuporte = User::create([
            'name' => 'Ana Paula (Gestor Suporte)',
            'email' => 'suporte@pmsjp.pr.gov.br',
            'cpf' => '55566677788',
            'password' => Hash::make('senha123'),
            'perfil' => 'gestor_suporte',
            'is_active' => true,
        ]);

        $operadorOrcamentario = User::create([
            'name' => 'Pedro Costa (Operador Orçamentário)',
            'email' => 'orcamento@pmsjp.pr.gov.br',
            'cpf' => '99988877766',
            'password' => Hash::make('senha123'),
            'perfil' => 'operador_orcamentario',
            'is_active' => true,
        ]);

        // Criar fornecedor
        $fornecedor = Fornecedor::create([
            'nome' => 'Fornecedor Demonstração LTDA',
            'cnpj' => '12.345.678/0001-90',
            'responsavel_tecnico_id' => $responsavelTecnico->id,
        ]);

        // Criar contrato
        $contrato = Contrato::create([
            'numero' => 'Contrato 154/2023',
            'fornecedor_id' => $fornecedor->id,
            'data_inicio' => '2023-01-15',
            'data_fim' => '2025-01-15',
            'objeto' => 'Prestação de serviços de manutenção e suporte técnico',
        ]);

        // Criar empenhos
        $empenho1 = Empenho::create([
            'numero' => '934/2023',
            'contrato_id' => $contrato->id,
            'data_emissao' => '2023-02-01',
            'valor' => 150000.00,
            'saldo' => 45000.00,
            'status' => 'disponivel',
        ]);

        $empenho2 = Empenho::create([
            'numero' => '1205/2023',
            'contrato_id' => $contrato->id,
            'data_emissao' => '2023-06-15',
            'valor' => 80000.00,
            'saldo' => 0.00,
            'status' => 'sem_saldo',
        ]);

        // Criar solicitações de pagamento
        $solicitacao1 = SolicitacaoPagamento::create([
            'numero' => 'SP-2024-000001',
            'empenho_id' => $empenho1->id,
            'solicitante_id' => $responsavelTecnico->id,
            'valor' => 12000.00,
            'observacao' => 'Pagamento referente aos serviços de janeiro/2024',
            'tipo_documento' => 'Nota Fiscal',
            'numero_documento' => '12345',
            'serie' => '001',
            'data_emissao_documento' => '2024-01-25',
            'observacao_documento' => 'NF-e de serviços',
            'forma_pagamento' => 'conta_bancaria',
            'banco' => 'Banco do Brasil',
            'agencia' => '1234',
            'digito_agencia' => '5',
            'conta' => '567890',
            'digito_conta' => '1',
            'operacao' => '001',
            'cidade_banco' => 'São José dos Pinhais',
            'status' => 'aguardando_aprovacao_anexos',
        ]);

        $solicitacao1->registrarTramite(
            'Solicitação Criada',
            $responsavelTecnico->id,
            'Solicitação de pagamento criada'
        );

        $solicitacao1->registrarTramite(
            'Anexos Enviados para Aprovação',
            $responsavelTecnico->id,
            'Todos os anexos foram enviados'
        );

        // Criar anexos para solicitação 1
        $tiposAnexo = [
            'documento_fiscal' => 'aguardando_aprovacao',
            'certidao_negativa_debitos' => 'aguardando_aprovacao',
            'certidao_tributaria' => 'aguardando_aprovacao',
            'guia_previdencia_social' => 'aguardando_aprovacao',
            'fgts' => 'aguardando_aprovacao',
        ];

        foreach ($tiposAnexo as $tipo => $status) {
            AnexoSolicitacao::create([
                'solicitacao_id' => $solicitacao1->id,
                'tipo_anexo' => $tipo,
                'arquivo' => 'anexos/demo/' . $tipo . '.pdf',
                'status' => $status,
            ]);
        }

        // Segunda solicitação (já paga)
        $solicitacao2 = SolicitacaoPagamento::create([
            'numero' => 'SP-2023-000125',
            'empenho_id' => $empenho1->id,
            'solicitante_id' => $responsavelTecnico->id,
            'valor' => 15000.00,
            'tipo_documento' => 'Nota Fiscal',
            'numero_documento' => '11223',
            'serie' => '001',
            'data_emissao_documento' => '2023-12-20',
            'forma_pagamento' => 'conta_bancaria',
            'banco' => 'Banco do Brasil',
            'agencia' => '1234',
            'digito_agencia' => '5',
            'conta' => '567890',
            'digito_conta' => '1',
            'status' => 'pagamento_realizado',
            'paga_em' => '2023-12-28 14:30:00',
        ]);

        $solicitacao2->registrarTramite('Solicitação Criada', $responsavelTecnico->id);
        $solicitacao2->registrarTramite('Anexos Aprovados', $gestorContrato->id);
        $solicitacao2->registrarTramite('Pagamento Realizado', $operadorPmsjp->id);

        // Terceira solicitação (cancelada)
        $solicitacao3 = SolicitacaoPagamento::create([
            'numero' => 'SP-2024-000002',
            'empenho_id' => $empenho1->id,
            'solicitante_id' => $responsavelTecnico->id,
            'valor' => 8000.00,
            'tipo_documento' => 'Recibo',
            'numero_documento' => '555',
            'data_emissao_documento' => '2024-02-10',
            'forma_pagamento' => 'conta_bancaria',
            'banco' => 'Caixa Econômica Federal',
            'agencia' => '9876',
            'conta' => '123456',
            'digito_conta' => '7',
            'status' => 'cancelada',
            'cancelada_em' => '2024-02-15 10:20:00',
            'motivo_cancelamento' => 'Documento fiscal com dados incorretos. Nova solicitação será criada.',
        ]);

        $solicitacao3->registrarTramite('Solicitação Criada', $responsavelTecnico->id);
        $solicitacao3->registrarTramite(
            'Solicitação Cancelada',
            $responsavelTecnico->id,
            null,
            'Documento fiscal com dados incorretos'
        );

        // Criar chamados
        $chamado1 = Chamado::create([
            'usuario_id' => $responsavelTecnico->id,
            'modulo' => 'Portal do Fornecedor',
            'assunto' => 'Dúvida sobre anexação de documentos',
            'mensagem' => 'Gostaria de saber quais formatos de arquivo são aceitos para os anexos de solicitação de pagamento.',
            'status' => 'concluido',
            'respondido_em' => now()->subDays(2),
            'concluido_em' => now()->subDays(1),
        ]);

        $chamado1->mensagens()->create([
            'usuario_id' => $gestorSuporte->id,
            'mensagem' => 'Os formatos aceitos são: PDF, JPG, JPEG e PNG. O tamanho máximo por arquivo é de 10MB.',
        ]);

        $chamado2 = Chamado::create([
            'usuario_id' => $responsavelTecnico->id,
            'modulo' => 'Solicitação de Pagamento',
            'assunto' => 'Erro ao enviar anexo',
            'mensagem' => 'Estou tentando enviar o anexo da certidão negativa mas o sistema retorna erro.',
            'status' => 'em_atendimento',
            'respondido_em' => now()->subHours(3),
        ]);

        $chamado2->mensagens()->create([
            'usuario_id' => $gestorSuporte->id,
            'mensagem' => 'Pode me informar qual mensagem de erro aparece? E qual o tamanho do arquivo?',
        ]);

        // Criar Leis e Atos
        $lei1 = LeiAto::create([
            'numero' => 'Lei 2.345/2024',
            'tipo' => 'lei',
            'data_ato' => '2024-01-10',
            'data_publicacao' => '2024-01-12',
            'descricao' => 'Autoriza abertura de crédito suplementar no orçamento vigente',
        ]);

        $lei2 = LeiAto::create([
            'numero' => 'Decreto 1.234/2024',
            'tipo' => 'decreto',
            'data_ato' => '2024-02-05',
            'data_publicacao' => '2024-02-06',
            'descricao' => 'Regulamenta a aplicação de crédito especial',
        ]);

        // Criar Alterações Orçamentárias
        $alteracao1 = AlteracaoOrcamentaria::create([
            'lei_ato_id' => $lei1->id,
            'decreto_autorizador' => 'Decreto 100/2024',
            'data_ato' => '2024-01-15',
            'data_publicacao' => '2024-01-16',
            'tipo_ato' => 'decreto',
            'tipo_credito' => 'suplementar',
            'tipo_recurso' => 'superavit',
            'valor_credito' => 500000.00,
        ]);

        $alteracao1->dotacoes()->create([
            'dotacao_orcamentaria' => '10.101.10.122.0001.2.001',
            'conta_receita' => null,
            'valor_suprimido' => 0.00,
            'valor_suplementado' => 250000.00,
            'saldo_atual' => 1000000.00,
            'novo_saldo' => 1250000.00,
        ]);

        $alteracao1->dotacoes()->create([
            'dotacao_orcamentaria' => '15.452.12.365.0002.2.050',
            'conta_receita' => null,
            'valor_suprimido' => 0.00,
            'valor_suplementado' => 250000.00,
            'saldo_atual' => 750000.00,
            'novo_saldo' => 1000000.00,
        ]);

        $this->command->info('✅ Dados de demonstração criados com sucesso!');
        $this->command->info('');
        $this->command->info('👤 Usuários criados:');
        $this->command->info('   - Responsável Técnico: CPF 12345678900 | Senha: senha123');
        $this->command->info('   - Gestor Contrato: CPF 98765432100 | Senha: senha123');
        $this->command->info('   - Operador PMSJP: CPF 11122233344 | Senha: senha123');
        $this->command->info('   - Gestor Suporte: CPF 55566677788 | Senha: senha123');
        $this->command->info('   - Operador Orçamentário: CPF 99988877766 | Senha: senha123');
    }
}
