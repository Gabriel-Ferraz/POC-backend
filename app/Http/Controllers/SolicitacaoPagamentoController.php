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
            'valor' => 'required|numeric|min:0.01',
            'observacao' => 'nullable|string',
            'tipo_documento' => 'required|string',
            'numero_documento' => 'required|string',
            'serie' => 'nullable|string',
            'data_emissao_documento' => 'required|date',
            'observacao_documento' => 'nullable|string',
            'forma_pagamento' => 'required|in:conta_bancaria,documento_fatura',
            'banco' => 'required_if:forma_pagamento,conta_bancaria',
            'agencia' => 'required_if:forma_pagamento,conta_bancaria',
            'digito_agencia' => 'nullable|string',
            'conta' => 'required_if:forma_pagamento,conta_bancaria',
            'digito_conta' => 'nullable|string',
            'operacao' => 'nullable|string',
            'cidade_banco' => 'nullable|string',
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
                'observacao' => $request->observacao,
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
                'status' => 'pendente',
            ]);

            $empenho->bloquearSaldo($request->valor);

            $solicitacao->registrarTramite(
                'Solicitação Criada',
                $request->user()->id,
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
            'anexos.avaliador',
            'tramites.usuario',
        ])->findOrFail($id);

        return response()->json([
            'solicitacao' => [
                'id' => $solicitacao->id,
                'numero' => $solicitacao->numero,
                'valor' => $solicitacao->valor,
                'status' => $solicitacao->status,
                'data' => $solicitacao->created_at->format('d/m/Y H:i'),
                'observacao' => $solicitacao->observacao,
                'solicitante' => $solicitacao->solicitante->name,
                'fornecedor' => $solicitacao->empenho->contrato->fornecedor->nome,
                'empenho' => $solicitacao->empenho->numero,
                'contrato' => $solicitacao->empenho->contrato->numero,
                'documento_fiscal' => [
                    'tipo' => $solicitacao->tipo_documento,
                    'numero' => $solicitacao->numero_documento,
                    'serie' => $solicitacao->serie,
                    'data_emissao' => $solicitacao->data_emissao_documento->format('d/m/Y'),
                    'observacao' => $solicitacao->observacao_documento,
                ],
                'forma_pagamento' => [
                    'tipo' => $solicitacao->forma_pagamento,
                    'banco' => $solicitacao->banco,
                    'agencia' => $solicitacao->agencia . ($solicitacao->digito_agencia ? '-' . $solicitacao->digito_agencia : ''),
                    'conta' => $solicitacao->conta . ($solicitacao->digito_conta ? '-' . $solicitacao->digito_conta : ''),
                    'operacao' => $solicitacao->operacao,
                    'cidade' => $solicitacao->cidade_banco,
                ],
                'cancelamento' => $solicitacao->cancelada_em ? [
                    'data' => $solicitacao->cancelada_em->format('d/m/Y H:i'),
                    'motivo' => $solicitacao->motivo_cancelamento,
                ] : null,
                'pagamento' => $solicitacao->paga_em ? [
                    'data' => $solicitacao->paga_em->format('d/m/Y H:i'),
                ] : null,
            ],
            'anexos' => $solicitacao->anexos->map(function ($anexo) {
                return [
                    'id' => $anexo->id,
                    'tipo_anexo' => $anexo->tipo_anexo,
                    'arquivo' => $anexo->arquivo,
                    'status' => $anexo->status,
                    'motivo_recusa' => $anexo->motivo_recusa,
                    'avaliado_por' => $anexo->avaliador?->name,
                    'avaliado_em' => $anexo->avaliado_em?->format('d/m/Y H:i'),
                    'data_envio' => $anexo->created_at->format('d/m/Y H:i'),
                ];
            }),
            'tramites' => $solicitacao->tramites->map(function ($tramite) {
                return [
                    'id' => $tramite->id,
                    'fase' => $tramite->fase,
                    'usuario' => $tramite->usuario?->name,
                    'observacao' => $tramite->observacao,
                    'motivo' => $tramite->motivo,
                    'data' => $tramite->created_at->format('d/m/Y H:i'),
                ];
            }),
        ]);
    }

    public function cancelar(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'motivo' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Motivo do cancelamento é obrigatório',
                'errors' => $validator->errors(),
            ], 422);
        }

        $solicitacao = SolicitacaoPagamento::with('empenho')->findOrFail($id);

        if ($solicitacao->status === 'cancelada') {
            return response()->json([
                'message' => 'Solicitação já está cancelada',
            ], 400);
        }

        if ($solicitacao->status === 'pagamento_realizado') {
            return response()->json([
                'message' => 'Não é possível cancelar uma solicitação já paga',
            ], 400);
        }

        DB::beginTransaction();

        try {
            $solicitacao->update([
                'status' => 'cancelada',
                'cancelada_em' => now(),
                'motivo_cancelamento' => $request->motivo,
            ]);

            $solicitacao->empenho->liberarSaldo($solicitacao->valor);

            $solicitacao->registrarTramite(
                'Solicitação Cancelada',
                $request->user()->id,
                null,
                $request->motivo
            );

            DB::commit();

            return response()->json([
                'message' => 'Solicitação cancelada com sucesso',
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
                    'observacao' => $tramite->observacao,
                    'motivo' => $tramite->motivo,
                    'data' => $tramite->created_at->format('d/m/Y H:i:s'),
                ];
            }),
        ]);
    }
}
