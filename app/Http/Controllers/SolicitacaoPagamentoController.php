<?php

namespace App\Http\Controllers;

use App\Models\Empenho;
use App\Models\SolicitacaoPagamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SolicitacaoPagamentoController extends Controller
{
    public function index(Request $request, int $empenhoId): JsonResponse
    {
        $empenho = Empenho::with(['solicitacoes.solicitante'])->findOrFail($empenhoId);

        $solicitacoes = $empenho->solicitacoes->map(function ($sol) {
            return [
                'id' => $sol->id,
                'numero' => $sol->numero,
                'data' => $sol->created_at->format('d/m/Y'),
                'valor' => $sol->valor,
                'solicitante' => $sol->solicitante->name,
                'status' => $sol->status,
                'documento_fiscal' => $sol->tipo_documento . ' ' . $sol->numero_documento,
            ];
        });

        return response()->json([
            'empenho' => [
                'id' => $empenho->id,
                'numero' => $empenho->numero,
                'saldo' => $empenho->saldo,
            ],
            'solicitacoes' => $solicitacoes,
        ]);
    }

    public function store(Request $request, int $empenhoId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            // Bloco 1: Valor
            'valor' => 'required|numeric|min:0.01',

            // Bloco 2: Documento Fiscal
            'tipo_documento' => 'required|string',
            'numero_documento' => 'required|string',
            'serie' => 'nullable|string',
            'data_emissao_documento' => 'required|date',
            'observacao_documento' => 'nullable|string',

            // Bloco 3: Forma de Pagamento
            'forma_pagamento' => 'required|in:conta_bancaria,documento',
            'banco' => 'required_if:forma_pagamento,conta_bancaria|nullable|string',
            'agencia' => 'required_if:forma_pagamento,conta_bancaria|nullable|string',
            'digito_agencia' => 'nullable|string',
            'conta' => 'required_if:forma_pagamento,conta_bancaria|nullable|string',
            'digito_conta' => 'nullable|string',
            'operacao' => 'nullable|string',
            'cidade_banco' => 'nullable|string',

            // Bloco 4: Observação do Pagamento
            'observacao_pagamento' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $empenho = Empenho::findOrFail($empenhoId);

        if ($empenho->saldo < $request->valor) {
            return response()->json([
                'message' => 'Saldo insuficiente no empenho',
            ], 400);
        }

        DB::beginTransaction();

        try {
            $numero = 'SP-' . date('Y') . '-' . str_pad(SolicitacaoPagamento::count() + 1, 6, '0', STR_PAD_LEFT);

            $solicitacao = SolicitacaoPagamento::create([
                'numero' => $numero,
                'empenho_id' => $empenhoId,
                'solicitante_id' => $request->user()->id,
                'valor' => $request->valor,
                'tipo_documento' => $request->tipo_documento,
                'numero_documento' => $request->numero_documento,
                'serie' => $request->serie,
                'data_emissao_documento' => $request->data_emissao_documento,
                'observacao_documento' => $request->observacao_documento,
                'forma_pagamento' => $request->forma_pagamento,
                'banco' => $request->banco,
                'agencia' => $request->agencia,
                'digito_agencia' => $request->digito_agencia,
                'conta' => $request->conta,
                'digito_conta' => $request->digito_conta,
                'operacao' => $request->operacao,
                'cidade_banco' => $request->cidade_banco,
                'observacao_pagamento' => $request->observacao_pagamento,
                'status' => 'pendente',
            ]);

            $empenho->bloquearSaldo($request->valor);

            $solicitacao->registrarTramite(
                'Solicitação de Pagamento',
                $request->user()->id,
                null,
                'Anexar Documentos',
                'Solicitação de pagamento criada pelo fornecedor'
            );

            $tiposAnexo = [
                'documento_fiscal',
                'certidao_negativa_debitos',
                'certidao_tributaria',
                'guia_previdencia_social',
                'fgts',
            ];

            foreach ($tiposAnexo as $tipo) {
                $solicitacao->anexos()->create([
                    'tipo_anexo' => $tipo,
                    'status' => 'pendente',
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Solicitação criada com sucesso',
                'solicitacao' => [
                    'id' => $solicitacao->id,
                    'numero' => $solicitacao->numero,
                    'valor' => $solicitacao->valor,
                    'status' => $solicitacao->status,
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erro ao criar solicitação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $solicitacao = SolicitacaoPagamento::with([
            'empenho.contrato.fornecedor',
            'solicitante',
            'anexos.aprovador',
            'anexos.enviadoPor',
            'tramites.usuario',
        ])->findOrFail($id);

        return response()->json([
            'solicitacao' => [
                'id' => $solicitacao->id,
                'numero' => $solicitacao->numero,
                'status' => $solicitacao->status,
                'status_label' => $solicitacao->status_label,
                'valor' => $solicitacao->valor,
                'created_at' => $solicitacao->created_at->format('d/m/Y'),

                // DADOS GERAIS
                'solicitante' => [
                    'id' => $solicitacao->solicitante->id,
                    'name' => $solicitacao->solicitante->name,
                    'cpf' => $solicitacao->solicitante->cpf,
                ],
                'empenho' => [
                    'id' => $solicitacao->empenho->id,
                    'numero' => $solicitacao->empenho->numero,
                ],
                'fornecedor' => [
                    'id' => $solicitacao->empenho->contrato->fornecedor->id,
                    'cnpj' => $solicitacao->empenho->contrato->fornecedor->cnpj,
                    'razao_social' => $solicitacao->empenho->contrato->fornecedor->nome,
                ],
                'contrato' => [
                    'id' => $solicitacao->empenho->contrato->id,
                    'numero' => $solicitacao->empenho->contrato->numero,
                ],

                // DOCUMENTO FISCAL
                'documento_fiscal_tipo' => $solicitacao->tipo_documento,
                'documento_fiscal_numero' => $solicitacao->numero_documento,
                'documento_fiscal_serie' => $solicitacao->serie,
                'documento_fiscal_data_emissao' => $solicitacao->data_emissao_documento->format('d/m/Y'),
                'documento_fiscal_observacao' => $solicitacao->observacao_documento,

                // FORMA DE PAGAMENTO
                'forma_pagamento_tipo' => $solicitacao->forma_pagamento,
                'banco' => $solicitacao->banco,
                'agencia' => $solicitacao->agencia,
                'agencia_digito' => $solicitacao->digito_agencia,
                'conta' => $solicitacao->conta,
                'conta_digito' => $solicitacao->digito_conta,
                'operacao' => $solicitacao->operacao,
                'cidade_banco' => $solicitacao->cidade_banco,
                'observacao_pagamento' => $solicitacao->observacao_pagamento,

                // ANDAMENTO
                'andamento' => $solicitacao->andamento,

                // TRÂMITES
                'tramites' => $solicitacao->tramites->map(function ($tramite) {
                    return [
                        'id' => $tramite->id,
                        'fase' => $tramite->fase,
                        'created_at' => $tramite->created_at->format('d/m/Y H:i'),
                        'usuario' => $tramite->usuario ? [
                            'id' => $tramite->usuario->id,
                            'name' => $tramite->usuario->name,
                        ] : null,
                        'origem' => $tramite->origem,
                        'destino' => $tramite->destino,
                        'motivo' => $tramite->motivo,
                        'observacao' => $tramite->observacao,
                    ];
                }),

                // ANEXOS PAGAMENTO
                'anexos' => $solicitacao->anexos->map(function ($anexo) {
                    return [
                        'id' => $anexo->id,
                        'tipo_anexo' => $anexo->tipo_anexo,
                        'tipo_anexo_label' => $anexo->tipo_anexo_label,
                        'arquivo_nome' => $anexo->arquivo_nome,
                        'arquivo_path' => $anexo->arquivo_path ? '/storage/' . $anexo->arquivo_path : null,
                        'status' => $anexo->status_label,
                        'data_envio' => $anexo->data_envio?->format('d/m/Y'),
                        'avaliado_por' => $anexo->aprovador?->name,
                        'motivo_recusa' => $anexo->motivo_recusa,
                        'enviado_por' => $anexo->enviadoPor?->name,
                        'enviado_em' => $anexo->enviado_em?->format('d/m/Y H:i'),
                    ];
                }),

                // PAGAMENTO REALIZADO
                'pagamento_realizado' => $solicitacao->paga_em ? [
                    'data_hora' => $solicitacao->paga_em->format('d/m/Y H:i'),
                    'valor' => $solicitacao->valor,
                ] : null,

                // CANCELAMENTO
                'cancelamento' => $solicitacao->cancelada_em ? [
                    'data' => $solicitacao->cancelada_em->format('d/m/Y'),
                    'motivo' => $solicitacao->motivo_cancelamento,
                ] : null,
            ],
        ]);
    }

    public function cancelar(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'data_cancelamento' => 'required|date|before_or_equal:today',
            'motivo' => 'required|string|min:10|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Os dados fornecidos são inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $solicitacao = SolicitacaoPagamento::with(['empenho', 'solicitante'])->findOrFail($id);

        // Verificar se o usuário é o dono da solicitação
        if ($solicitacao->solicitante_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Você não tem permissão para cancelar esta solicitação',
            ], 403);
        }

        // Verificar se já está cancelada
        if ($solicitacao->status === 'cancelada') {
            return response()->json([
                'message' => 'Solicitação já está cancelada',
            ], 400);
        }

        // Verificar se já foi paga
        if ($solicitacao->status === 'pagamento_realizado') {
            return response()->json([
                'message' => 'Não é possível cancelar uma solicitação já paga',
            ], 400);
        }

        // Verificar se status é pendente
        if ($solicitacao->status !== 'pendente') {
            return response()->json([
                'message' => 'Não é possível cancelar uma solicitação com status diferente de Pendente',
            ], 400);
        }

        DB::beginTransaction();

        try {
            $solicitacao->update([
                'status' => 'cancelada',
                'cancelada_em' => $request->data_cancelamento,
                'motivo_cancelamento' => $request->motivo,
            ]);

            $solicitacao->empenho->liberarSaldo($solicitacao->valor);

            $solicitacao->registrarTramite(
                'Cancelamento',
                $request->user()->id,
                $solicitacao->status, // De qual status estava
                'Cancelada',
                'Solicitação cancelada pelo responsável técnico',
                $request->motivo
            );

            DB::commit();

            return response()->json([
                'message' => 'Solicitação cancelada com sucesso',
                'solicitacao' => [
                    'id' => $solicitacao->id,
                    'numero' => $solicitacao->numero,
                    'status' => 'Cancelada',
                    'data_cancelamento' => $solicitacao->cancelada_em->format('Y-m-d'),
                    'motivo_cancelamento' => $solicitacao->motivo_cancelamento,
                    'updated_at' => $solicitacao->updated_at->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erro ao cancelar solicitação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function tramites(Request $request, int $id): JsonResponse
    {
        $solicitacao = SolicitacaoPagamento::with('tramites.usuario')->findOrFail($id);

        return response()->json([
            'tramites' => $solicitacao->tramites->map(function ($tramite) {
                return [
                    'id' => $tramite->id,
                    'fase' => $tramite->fase,
                    'usuario' => $tramite->usuario?->name,
                    'origem' => $tramite->origem,
                    'destino' => $tramite->destino,
                    'motivo' => $tramite->motivo,
                    'observacao' => $tramite->observacao,
                    'data' => $tramite->created_at->format('d/m/Y H:i:s'),
                ];
            }),
        ]);
    }
}
