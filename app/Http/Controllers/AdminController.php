<?php

namespace App\Http\Controllers;

use App\Models\Empenho;
use App\Models\Fornecedor;
use App\Models\SolicitacaoPagamento;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminController extends Controller
{
    /**
     * Verifica se o usuário tem permissão de admin
     */
    private function verificarPermissaoAdmin(): ?JsonResponse
    {
        $user = Auth::user();
        $perfisPermitidos = ['gestor_suporte', 'operador_pmsjp'];

        if (!in_array($user->perfil, $perfisPermitidos)) {
            return response()->json([
                'message' => 'Acesso negado. Apenas administradores podem acessar esta área.',
            ], 403);
        }

        return null;
    }

    /**
     * Criar usuário
     */
    public function criarUsuario(Request $request): JsonResponse
    {
        if ($erro = $this->verificarPermissaoAdmin()) {
            return $erro;
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'cpf' => 'required|string|unique:users,cpf',
            'password' => 'required|string|min:6',
            'perfil' => 'required|in:responsavel_tecnico,gestor_contrato,gestor_suporte,operador_pmsjp,operador_orcamentario',
            'fornecedor_id' => 'required_if:perfil,responsavel_tecnico|nullable|exists:fornecedores,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'cpf' => $validated['cpf'],
            'password' => Hash::make($validated['password']),
            'perfil' => $validated['perfil'],
            'fornecedor_id' => $validated['fornecedor_id'] ?? null,
            'is_active' => true,
        ]);

        \Log::info('AdminController::criarUsuario', [
            'admin_id' => Auth::id(),
            'usuario_criado_id' => $user->id,
            'perfil' => $user->perfil,
        ]);

        return response()->json([
            'message' => 'Usuário criado com sucesso',
            'usuario' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'cpf' => $user->cpf,
                'perfil' => $user->perfil,
                'fornecedor_id' => $user->fornecedor_id,
            ],
        ], 201);
    }

    /**
     * Criar fornecedor + responsável técnico
     */
    public function criarFornecedor(Request $request): JsonResponse
    {
        if ($erro = $this->verificarPermissaoAdmin()) {
            return $erro;
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'cnpj' => 'required|string|unique:fornecedores,cnpj',
            'responsavel_tecnico_nome' => 'required|string|max:255',
            'responsavel_tecnico_email' => 'required|email|unique:users,email',
            'responsavel_tecnico_cpf' => 'required|string|unique:users,cpf',
            'responsavel_tecnico_password' => 'required|string|min:6',
        ]);

        DB::beginTransaction();

        try {
            // 1. Criar fornecedor
            $fornecedor = Fornecedor::create([
                'nome' => $validated['nome'],
                'cnpj' => $validated['cnpj'],
            ]);

            // 2. Criar responsável técnico
            $responsavelTecnico = User::create([
                'name' => $validated['responsavel_tecnico_nome'],
                'email' => $validated['responsavel_tecnico_email'],
                'cpf' => $validated['responsavel_tecnico_cpf'],
                'password' => Hash::make($validated['responsavel_tecnico_password']),
                'perfil' => 'responsavel_tecnico',
                'fornecedor_id' => $fornecedor->id,
                'is_active' => true,
            ]);

            // 3. Atualizar fornecedor com o ID do responsável
            $fornecedor->update([
                'responsavel_tecnico_id' => $responsavelTecnico->id,
            ]);

            DB::commit();

            \Log::info('AdminController::criarFornecedor', [
                'admin_id' => Auth::id(),
                'fornecedor_id' => $fornecedor->id,
                'responsavel_tecnico_id' => $responsavelTecnico->id,
            ]);

            return response()->json([
                'message' => 'Fornecedor e Responsável Técnico criados com sucesso',
                'fornecedor' => [
                    'id' => $fornecedor->id,
                    'nome' => $fornecedor->nome,
                    'cnpj' => $fornecedor->cnpj,
                    'responsavel_tecnico' => [
                        'id' => $responsavelTecnico->id,
                        'name' => $responsavelTecnico->name,
                        'email' => $responsavelTecnico->email,
                    ],
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('AdminController::criarFornecedor exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Erro ao criar fornecedor',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Criar empenho
     */
    public function criarEmpenho(Request $request): JsonResponse
    {
        if ($erro = $this->verificarPermissaoAdmin()) {
            return $erro;
        }

        $validated = $request->validate([
            'numero' => 'required|string|unique:empenhos,numero',
            'fornecedor_id' => 'required|exists:fornecedores,id',
            'valor' => 'required|numeric|min:0',
            'saldo' => 'required|numeric|min:0',
            'data_emissao' => 'required|date',
            'status' => 'required|in:disponivel,sem_saldo,bloqueado',
        ]);

        // Validar que saldo não excede valor
        if ($validated['saldo'] > $validated['valor']) {
            return response()->json([
                'message' => 'O saldo não pode ser maior que o valor total do empenho',
            ], 422);
        }

        // Criar contrato automaticamente se não existir
        $fornecedor = Fornecedor::find($validated['fornecedor_id']);

        DB::beginTransaction();

        try {
            // Verificar se já existe contrato para este fornecedor
            $contrato = $fornecedor->contratos()->first();

            if (!$contrato) {
                // Criar contrato padrão
                $contrato = $fornecedor->contratos()->create([
                    'numero' => 'Contrato '.date('Y').'-'.$fornecedor->id,
                    'data_inicio' => now(),
                    'data_fim' => now()->addYear(),
                    'objeto' => 'Contrato criado automaticamente via painel administrativo',
                ]);

                \Log::info('AdminController::criarEmpenho - contrato criado', [
                    'contrato_id' => $contrato->id,
                ]);
            }

            // Criar empenho
            $empenho = Empenho::create([
                'numero' => $validated['numero'],
                'contrato_id' => $contrato->id,
                'data_emissao' => $validated['data_emissao'],
                'valor' => $validated['valor'],
                'saldo' => $validated['saldo'],
                'status' => $validated['status'],
            ]);

            DB::commit();

            \Log::info('AdminController::criarEmpenho', [
                'admin_id' => Auth::id(),
                'empenho_id' => $empenho->id,
                'fornecedor_id' => $validated['fornecedor_id'],
                'contrato_id' => $contrato->id,
            ]);

            return response()->json([
                'message' => 'Empenho criado com sucesso',
                'empenho' => [
                    'id' => $empenho->id,
                    'numero' => $empenho->numero,
                    'contrato_id' => $empenho->contrato_id,
                    'fornecedor_id' => $validated['fornecedor_id'],
                    'valor' => $empenho->valor,
                    'saldo' => $empenho->saldo,
                    'data_emissao' => $empenho->data_emissao,
                    'status' => $empenho->status,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('AdminController::criarEmpenho exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Erro ao criar empenho',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Atualizar status de solicitação
     */
    public function atualizarStatusSolicitacao(Request $request, int $id): JsonResponse
    {
        if ($erro = $this->verificarPermissaoAdmin()) {
            return $erro;
        }

        $validated = $request->validate([
            'status' => 'required|in:rascunho,aguardando_aprovacao,anexos,fiscal,gestor,liquidacao,secretario,iss,ordem_pagamento,autorizacao,bordero,remessa,pagamento,pagamento_realizado,cancelado',
            'motivo' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();

        try {
            $solicitacao = SolicitacaoPagamento::findOrFail($id);
            $statusAnterior = $solicitacao->status;
            $user = Auth::user();

            // Validar se pode alterar status
            if ($statusAnterior === 'pagamento_realizado') {
                return response()->json([
                    'message' => 'Não é possível alterar status de solicitação já paga',
                ], 422);
            }

            if ($statusAnterior === 'cancelado' && $validated['status'] !== 'rascunho') {
                return response()->json([
                    'message' => 'Solicitação cancelada só pode voltar para rascunho',
                ], 422);
            }

            // Atualizar status
            $solicitacao->update([
                'status' => $validated['status'],
            ]);

            // Registrar no trâmite
            $observacao = $validated['motivo']
                ? $validated['motivo']
                : "Alteração manual pelo administrador ({$user->name})";

            $solicitacao->registrarTramite(
                $this->getStatusLabel($validated['status']),
                Auth::id(),
                $statusAnterior,
                $validated['status'],
                $observacao
            );

            DB::commit();

            \Log::info('AdminController::atualizarStatusSolicitacao', [
                'admin_id' => Auth::id(),
                'solicitacao_id' => $solicitacao->id,
                'status_anterior' => $statusAnterior,
                'status_novo' => $validated['status'],
            ]);

            return response()->json([
                'message' => 'Status atualizado com sucesso',
                'solicitacao' => [
                    'id' => $solicitacao->id,
                    'numero' => $solicitacao->numero,
                    'status_anterior' => $statusAnterior,
                    'status_atual' => $solicitacao->status,
                    'atualizado_em' => now()->format('d/m/Y H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('AdminController::atualizarStatusSolicitacao exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Erro ao atualizar status',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Retornar label do status
     */
    private function getStatusLabel(string $status): string
    {
        $labels = [
            'rascunho' => 'Rascunho',
            'aguardando_aprovacao' => 'Aguardando Aprovação',
            'anexos' => 'Análise de Anexos',
            'fiscal' => 'Análise Fiscal',
            'gestor' => 'Aprovação do Gestor',
            'liquidacao' => 'Liquidação',
            'secretario' => 'Aprovação do Secretário',
            'iss' => 'Verificação ISS',
            'ordem_pagamento' => 'Ordem de Pagamento',
            'autorizacao' => 'Autorização',
            'bordero' => 'Borderô',
            'remessa' => 'Remessa Bancária',
            'pagamento' => 'Em Pagamento',
            'pagamento_realizado' => 'Pagamento Realizado',
            'cancelado' => 'Cancelado',
        ];

        return $labels[$status] ?? $status;
    }

    /**
     * Listar todos os status disponíveis
     */
    public function listarStatus(): JsonResponse
    {
        if ($erro = $this->verificarPermissaoAdmin()) {
            return $erro;
        }

        $status = [
            ['value' => 'rascunho', 'label' => 'Rascunho', 'color' => 'gray'],
            ['value' => 'aguardando_aprovacao', 'label' => 'Aguardando Aprovação', 'color' => 'yellow'],
            ['value' => 'anexos', 'label' => 'Análise de Anexos', 'color' => 'blue'],
            ['value' => 'fiscal', 'label' => 'Análise Fiscal', 'color' => 'indigo'],
            ['value' => 'gestor', 'label' => 'Aprovação do Gestor', 'color' => 'purple'],
            ['value' => 'liquidacao', 'label' => 'Liquidação', 'color' => 'pink'],
            ['value' => 'secretario', 'label' => 'Aprovação do Secretário', 'color' => 'violet'],
            ['value' => 'iss', 'label' => 'Verificação ISS', 'color' => 'cyan'],
            ['value' => 'ordem_pagamento', 'label' => 'Ordem de Pagamento', 'color' => 'teal'],
            ['value' => 'autorizacao', 'label' => 'Autorização', 'color' => 'emerald'],
            ['value' => 'bordero', 'label' => 'Borderô', 'color' => 'lime'],
            ['value' => 'remessa', 'label' => 'Remessa Bancária', 'color' => 'amber'],
            ['value' => 'pagamento', 'label' => 'Em Pagamento', 'color' => 'orange'],
            ['value' => 'pagamento_realizado', 'label' => 'Pagamento Realizado', 'color' => 'green'],
            ['value' => 'cancelado', 'label' => 'Cancelado', 'color' => 'red'],
        ];

        return response()->json(['status' => $status]);
    }

    /**
     * Listar fornecedores (para dropdowns no frontend)
     */
    public function listarFornecedores(): JsonResponse
    {
        if ($erro = $this->verificarPermissaoAdmin()) {
            return $erro;
        }

        $fornecedores = Fornecedor::with('responsavelTecnico')
            ->orderBy('nome')
            ->get();

        return response()->json([
            'fornecedores' => $fornecedores->map(function ($fornecedor) {
                return [
                    'id' => $fornecedor->id,
                    'nome' => $fornecedor->nome,
                    'cnpj' => $fornecedor->cnpj,
                    'responsavel_tecnico' => $fornecedor->responsavelTecnico ? [
                        'id' => $fornecedor->responsavelTecnico->id,
                        'name' => $fornecedor->responsavelTecnico->name,
                        'email' => $fornecedor->responsavelTecnico->email,
                    ] : null,
                ];
            }),
        ]);
    }
}
