<?php

namespace App\Http\Controllers;

use App\Models\AlteracaoOrcamentaria;
use App\Models\LeiAto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OrcamentarioController extends Controller
{
    public function indexLeisAtos(Request $request): JsonResponse
    {
        $leisAtos = LeiAto::orderBy('data_ato', 'desc')->get();

        return response()->json([
            'leis_atos' => $leisAtos->map(function ($lei) {
                return [
                    'id' => $lei->id,
                    'numero' => $lei->numero,
                    'tipo' => $lei->tipo,
                    'data_ato' => $lei->data_ato->format('d/m/Y'),
                    'data_publicacao' => $lei->data_publicacao->format('d/m/Y'),
                    'descricao' => $lei->descricao,
                    'arquivo' => $lei->arquivo,
                ];
            }),
        ]);
    }

    public function storeLeiAto(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'numero' => 'required|string|max:255',
            'tipo' => 'required|in:lei,decreto,resolucao,ato_gestor',
            'data_ato' => 'required|date',
            'data_publicacao' => 'required|date',
            'descricao' => 'nullable|string',
            'arquivo' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $data = $request->only([
                'numero',
                'tipo',
                'data_ato',
                'data_publicacao',
                'descricao',
            ]);

            if ($request->hasFile('arquivo')) {
                $path = $request->file('arquivo')->store('orcamentario/leis-atos', 'public');
                $data['arquivo'] = $path;
            }

            $leiAto = LeiAto::create($data);

            return response()->json([
                'message' => 'Lei/Ato cadastrado com sucesso',
                'lei_ato' => [
                    'id' => $leiAto->id,
                    'numero' => $leiAto->numero,
                    'tipo' => $leiAto->tipo,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao cadastrar lei/ato',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateLeiAto(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'numero' => 'required|string|max:255',
            'tipo' => 'required|in:lei,decreto,resolucao,ato_gestor',
            'data_ato' => 'required|date',
            'data_publicacao' => 'required|date',
            'descricao' => 'nullable|string',
            'arquivo' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $leiAto = LeiAto::findOrFail($id);

        try {
            $data = $request->only([
                'numero',
                'tipo',
                'data_ato',
                'data_publicacao',
                'descricao',
            ]);

            if ($request->hasFile('arquivo')) {
                if ($leiAto->arquivo) {
                    Storage::disk('public')->delete($leiAto->arquivo);
                }
                $path = $request->file('arquivo')->store('orcamentario/leis-atos', 'public');
                $data['arquivo'] = $path;
            }

            $leiAto->update($data);

            return response()->json([
                'message' => 'Lei/Ato atualizado com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao atualizar lei/ato',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyLeiAto(Request $request, int $id): JsonResponse
    {
        $leiAto = LeiAto::findOrFail($id);

        if ($leiAto->alteracoesOrcamentarias()->count() > 0) {
            return response()->json([
                'message' => 'Não é possível excluir lei/ato vinculado a alterações orçamentárias',
            ], 400);
        }

        try {
            if ($leiAto->arquivo) {
                Storage::disk('public')->delete($leiAto->arquivo);
            }

            $leiAto->delete();

            return response()->json([
                'message' => 'Lei/Ato excluído com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao excluir lei/ato',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function indexAlteracoes(Request $request): JsonResponse
    {
        $alteracoes = AlteracaoOrcamentaria::with(['leiAto', 'dotacoes'])
            ->orderBy('data_ato', 'desc')
            ->get();

        return response()->json([
            'alteracoes' => $alteracoes->map(function ($alt) {
                return [
                    'id' => $alt->id,
                    'lei_ato' => $alt->leiAto->numero,
                    'decreto_autorizador' => $alt->decreto_autorizador,
                    'tipo_ato' => $alt->tipo_ato,
                    'tipo_credito' => $alt->tipo_credito,
                    'tipo_recurso' => $alt->tipo_recurso,
                    'valor_credito' => $alt->valor_credito,
                    'data_ato' => $alt->data_ato->format('d/m/Y'),
                    'total_dotacoes' => $alt->dotacoes->count(),
                ];
            }),
        ]);
    }

    public function storeAlteracao(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lei_ato_id' => 'required|exists:leis_atos,id',
            'decreto_autorizador' => 'required|string|max:255',
            'data_ato' => 'required|date',
            'data_publicacao' => 'required|date',
            'tipo_ato' => 'required|in:decreto,resolucao,ato_gestor',
            'tipo_credito' => 'required|in:especial,suplementar,extraordinario',
            'tipo_recurso' => 'required|in:superavit,excesso_arrecadacao',
            'valor_credito' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $alteracao = AlteracaoOrcamentaria::create($request->only([
                'lei_ato_id',
                'decreto_autorizador',
                'data_ato',
                'data_publicacao',
                'tipo_ato',
                'tipo_credito',
                'tipo_recurso',
                'valor_credito',
            ]));

            return response()->json([
                'message' => 'Alteração orçamentária criada com sucesso',
                'alteracao' => [
                    'id' => $alteracao->id,
                    'decreto' => $alteracao->decreto_autorizador,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao criar alteração',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function showAlteracao(Request $request, int $id): JsonResponse
    {
        $alteracao = AlteracaoOrcamentaria::with(['leiAto', 'dotacoes'])
            ->findOrFail($id);

        return response()->json([
            'alteracao' => [
                'id' => $alteracao->id,
                'lei_ato' => [
                    'id' => $alteracao->leiAto->id,
                    'numero' => $alteracao->leiAto->numero,
                    'tipo' => $alteracao->leiAto->tipo,
                ],
                'decreto_autorizador' => $alteracao->decreto_autorizador,
                'data_ato' => $alteracao->data_ato->format('d/m/Y'),
                'data_publicacao' => $alteracao->data_publicacao->format('d/m/Y'),
                'tipo_ato' => $alteracao->tipo_ato,
                'tipo_credito' => $alteracao->tipo_credito,
                'tipo_recurso' => $alteracao->tipo_recurso,
                'valor_credito' => $alteracao->valor_credito,
            ],
            'dotacoes' => $alteracao->dotacoes->map(function ($dot) {
                return [
                    'id' => $dot->id,
                    'dotacao_orcamentaria' => $dot->dotacao_orcamentaria,
                    'conta_receita' => $dot->conta_receita,
                    'valor_suprimido' => $dot->valor_suprimido,
                    'valor_suplementado' => $dot->valor_suplementado,
                    'saldo_atual' => $dot->saldo_atual,
                    'novo_saldo' => $dot->novo_saldo,
                ];
            }),
        ]);
    }

    public function adicionarDotacao(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'dotacao_orcamentaria' => 'required|string|max:255',
            'conta_receita' => 'nullable|string|max:255',
            'valor_suprimido' => 'required|numeric|min:0',
            'valor_suplementado' => 'required|numeric|min:0',
            'saldo_atual' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $alteracao = AlteracaoOrcamentaria::findOrFail($id);

        if ($request->valor_suprimido > $request->saldo_atual) {
            return response()->json([
                'message' => 'Valor suprimido não pode ser maior que o saldo atual',
            ], 400);
        }

        try {
            $novoSaldo = $request->saldo_atual - $request->valor_suprimido + $request->valor_suplementado;

            $dotacao = $alteracao->dotacoes()->create([
                'dotacao_orcamentaria' => $request->dotacao_orcamentaria,
                'conta_receita' => $request->conta_receita,
                'valor_suprimido' => $request->valor_suprimido,
                'valor_suplementado' => $request->valor_suplementado,
                'saldo_atual' => $request->saldo_atual,
                'novo_saldo' => $novoSaldo,
            ]);

            return response()->json([
                'message' => 'Dotação adicionada com sucesso',
                'dotacao' => [
                    'id' => $dotacao->id,
                    'novo_saldo' => $dotacao->novo_saldo,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao adicionar dotação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function gerarPdf(Request $request, int $id): JsonResponse
    {
        $alteracao = AlteracaoOrcamentaria::with(['leiAto', 'dotacoes'])
            ->findOrFail($id);

        $conteudo = [
            'titulo' => 'ALTERAÇÃO ORÇAMENTÁRIA',
            'dados_lei' => [
                'numero' => $alteracao->leiAto->numero,
                'tipo' => strtoupper($alteracao->leiAto->tipo),
                'data_ato' => $alteracao->leiAto->data_ato->format('d/m/Y'),
                'data_publicacao' => $alteracao->leiAto->data_publicacao->format('d/m/Y'),
            ],
            'dados_decreto' => [
                'numero' => $alteracao->decreto_autorizador,
                'tipo_ato' => strtoupper($alteracao->tipo_ato),
                'data_ato' => $alteracao->data_ato->format('d/m/Y'),
                'data_publicacao' => $alteracao->data_publicacao->format('d/m/Y'),
            ],
            'tipo_credito' => strtoupper($alteracao->tipo_credito),
            'tipo_recurso' => strtoupper($alteracao->tipo_recurso),
            'valor_credito' => number_format($alteracao->valor_credito, 2, ',', '.'),
            'dotacoes' => $alteracao->dotacoes->map(function ($dot) {
                return [
                    'dotacao' => $dot->dotacao_orcamentaria,
                    'conta_receita' => $dot->conta_receita,
                    'suprimido' => number_format($dot->valor_suprimido, 2, ',', '.'),
                    'suplementado' => number_format($dot->valor_suplementado, 2, ',', '.'),
                    'saldo_atual' => number_format($dot->saldo_atual, 2, ',', '.'),
                    'novo_saldo' => number_format($dot->novo_saldo, 2, ',', '.'),
                ];
            }),
            'data_geracao' => now()->format('d/m/Y H:i'),
        ];

        return response()->json([
            'message' => 'Dados para geração de PDF',
            'pdf_data' => $conteudo,
        ]);
    }
}
