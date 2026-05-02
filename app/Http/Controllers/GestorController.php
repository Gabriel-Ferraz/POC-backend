<?php

namespace App\Http\Controllers;

use App\Models\SolicitacaoPagamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GestorController extends Controller
{
    /**
     * Lista solicitações pendentes de aprovação de anexos (para Gestor).
     */
    public function solicitacoesPendentes(Request $request): JsonResponse
    {
        $user = $request->user();

        // Verificar se é gestor
        if ($user->perfil !== 'gestor_contrato') {
            return response()->json([
                'message' => 'Acesso negado. Apenas gestores de contrato podem acessar.',
            ], 403);
        }

        // Buscar solicitações aguardando aprovação de anexos
        $solicitacoes = SolicitacaoPagamento::whereIn('status', [
            'aguardando_aprovacao_anexos',
            'anexos_recusados', // Para reenvios
        ])
            ->with([
                'empenho.contrato.fornecedor',
                'solicitante',
                'anexos' => function ($query) {
                    $query->whereIn('status', ['aguardando_aprovacao', 'recusado']);
                },
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'solicitacoes' => $solicitacoes->map(function ($sol) {
                $totalAnexos = $sol->anexos->count();
                $anexosAprovados = $sol->anexos->where('status', 'aprovado')->count();
                $anexosPendentes = $sol->anexos->where('status', 'aguardando_aprovacao')->count();
                $anexosRecusados = $sol->anexos->where('status', 'recusado')->count();

                return [
                    'id' => $sol->id,
                    'numero' => $sol->numero,
                    'valor' => $sol->valor,
                    'status' => $sol->status,
                    'data' => $sol->created_at->format('d/m/Y H:i'),
                    'solicitante' => $sol->solicitante->name,
                    'fornecedor' => $sol->empenho->contrato->fornecedor->nome,
                    'empenho' => $sol->empenho->numero,
                    'contrato' => $sol->empenho->contrato->numero,
                    'documento_fiscal' => $sol->tipo_documento . ' ' . $sol->numero_documento,
                    'total_anexos' => $totalAnexos,
                    'anexos_aprovados' => $anexosAprovados,
                    'anexos_pendentes' => $anexosPendentes,
                    'anexos_recusados' => $anexosRecusados,
                ];
            }),
        ]);
    }

    /**
     * Detalhe de uma solicitação para aprovação (com anexos).
     */
    public function solicitacaoDetalhe(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        // Verificar se é gestor
        if ($user->perfil !== 'gestor_contrato') {
            return response()->json([
                'message' => 'Acesso negado. Apenas gestores de contrato podem acessar.',
            ], 403);
        }

        $solicitacao = SolicitacaoPagamento::with([
            'empenho.contrato.fornecedor',
            'solicitante',
            'anexos.avaliador',
        ])->findOrFail($id);

        return response()->json([
            'solicitacao' => [
                'id' => $solicitacao->id,
                'numero' => $solicitacao->numero,
                'valor' => $solicitacao->valor,
                'status' => $solicitacao->status,
                'data' => $solicitacao->created_at->format('d/m/Y H:i'),
                'solicitante' => $solicitacao->solicitante->name,
                'fornecedor' => $solicitacao->empenho->contrato->fornecedor->nome,
                'cnpj' => $solicitacao->empenho->contrato->fornecedor->cnpj,
                'empenho' => $solicitacao->empenho->numero,
                'contrato' => $solicitacao->empenho->contrato->numero,
                'documento_fiscal' => [
                    'tipo' => $solicitacao->tipo_documento,
                    'numero' => $solicitacao->numero_documento,
                    'serie' => $solicitacao->serie,
                    'data_emissao' => $solicitacao->data_emissao_documento->format('d/m/Y'),
                ],
            ],
            'anexos' => $solicitacao->anexos->map(function ($anexo) {
                return [
                    'id' => $anexo->id,
                    'tipo_anexo' => $anexo->tipo_anexo,
                    'tipo_anexo_label' => $this->getLabelTipoAnexo($anexo->tipo_anexo),
                    'arquivo' => $anexo->arquivo,
                    'status' => $anexo->status,
                    'motivo_recusa' => $anexo->motivo_recusa,
                    'avaliado_por' => $anexo->avaliador?->name,
                    'avaliado_em' => $anexo->avaliado_em?->format('d/m/Y H:i'),
                    'data_envio' => $anexo->created_at->format('d/m/Y H:i'),
                ];
            }),
        ]);
    }

    private function getLabelTipoAnexo(string $tipo): string
    {
        $labels = [
            'documento_fiscal' => 'Documento Fiscal / Recibo',
            'certidao_negativa_debitos' => 'Certidão Negativa de Débitos',
            'certidao_tributaria' => 'Certidão Tributária',
            'guia_previdencia_social' => 'Guia de Previdência Social (GPS)',
            'fgts' => 'FGTS',
        ];

        return $labels[$tipo] ?? $tipo;
    }
}
