<?php

namespace App\Http\Controllers;

use App\Models\AlteracaoOrcamentaria;
use App\Models\DotacaoAlterada;
use App\Models\LeiAto;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class OrcamentarioController extends Controller
{
    // ============================
    // LEIS E ATOS
    // ============================

    public function indexLeisAtos(Request $request): JsonResponse
    {
        $leisAtos = LeiAto::orderBy('data_ato', 'desc')->get();

        return response()->json([
            'leis_atos' => $leisAtos->map(function ($lei) {
                return [
                    'id' => $lei->id,
                    'numero' => $lei->numero,
                    'tipo' => $lei->tipo,
                    'data_ato' => $lei->data_ato->format('Y-m-d'),
                    'data_publicacao' => $lei->data_publicacao->format('Y-m-d'),
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
            $data = $request->only(['numero', 'tipo', 'data_ato', 'data_publicacao', 'descricao']);

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
            return response()->json(['message' => 'Erro ao cadastrar lei/ato', 'error' => $e->getMessage()], 500);
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
            return response()->json(['message' => 'Dados inválidos', 'errors' => $validator->errors()], 422);
        }

        $leiAto = LeiAto::findOrFail($id);

        try {
            $data = $request->only(['numero', 'tipo', 'data_ato', 'data_publicacao', 'descricao']);

            if ($request->hasFile('arquivo')) {
                if ($leiAto->arquivo) {
                    Storage::disk('public')->delete($leiAto->arquivo);
                }
                $path = $request->file('arquivo')->store('orcamentario/leis-atos', 'public');
                $data['arquivo'] = $path;
            }

            $leiAto->update($data);

            return response()->json(['message' => 'Lei/Ato atualizado com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao atualizar lei/ato', 'error' => $e->getMessage()], 500);
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

            return response()->json(['message' => 'Lei/Ato excluído com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao excluir lei/ato', 'error' => $e->getMessage()], 500);
        }
    }

    // ============================
    // ALTERAÇÕES ORÇAMENTÁRIAS
    // ============================

    public function indexAlteracoes(Request $request): JsonResponse
    {
        $query = AlteracaoOrcamentaria::with(['leiAto', 'dotacoes']);

        if ($request->filled('decreto')) {
            $query->whereRaw('UPPER(decreto_autorizador) LIKE UPPER(?)', ['%'.$request->decreto.'%']);
        }
        if ($request->filled('tipo_ato')) {
            $query->where('tipo_ato', $request->tipo_ato);
        }
        if ($request->filled('tipo_credito')) {
            $query->where('tipo_credito', $request->tipo_credito);
        }
        if ($request->filled('tipo_recurso')) {
            $query->where('tipo_recurso', $request->tipo_recurso);
        }
        if ($request->filled('data_ato_de')) {
            $query->whereDate('data_ato', '>=', $request->data_ato_de);
        }
        if ($request->filled('data_ato_ate')) {
            $query->whereDate('data_ato', '<=', $request->data_ato_ate);
        }
        if ($request->filled('data_publicacao_de')) {
            $query->whereDate('data_publicacao', '>=', $request->data_publicacao_de);
        }
        if ($request->filled('data_publicacao_ate')) {
            $query->whereDate('data_publicacao', '<=', $request->data_publicacao_ate);
        }

        $alteracoes = $query->orderBy('data_ato', 'desc')->get();

        return response()->json([
            'alteracoes' => $alteracoes->map(fn ($alt) => $this->formatAlteracao($alt)),
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
            'tipo_recurso' => 'required|in:superavit,excesso_arrecadacao,valor_credito',
            'valor_credito' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dados inválidos', 'errors' => $validator->errors()], 422);
        }

        try {
            $alteracao = AlteracaoOrcamentaria::create($request->only([
                'lei_ato_id', 'decreto_autorizador', 'data_ato', 'data_publicacao',
                'tipo_ato', 'tipo_credito', 'tipo_recurso', 'valor_credito',
            ]));

            $alteracao->load('leiAto', 'dotacoes');

            return response()->json([
                'message' => 'Alteração orçamentária criada com sucesso',
                'alteracao' => $this->formatAlteracao($alteracao),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao criar alteração', 'error' => $e->getMessage()], 500);
        }
    }

    public function updateAlteracao(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'lei_ato_id' => 'required|exists:leis_atos,id',
            'decreto_autorizador' => 'required|string|max:255',
            'data_ato' => 'required|date',
            'data_publicacao' => 'required|date',
            'tipo_ato' => 'required|in:decreto,resolucao,ato_gestor',
            'tipo_credito' => 'required|in:especial,suplementar,extraordinario',
            'tipo_recurso' => 'required|in:superavit,excesso_arrecadacao,valor_credito',
            'valor_credito' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Dados inválidos', 'errors' => $validator->errors()], 422);
        }

        $alteracao = AlteracaoOrcamentaria::findOrFail($id);

        try {
            $alteracao->update($request->only([
                'lei_ato_id', 'decreto_autorizador', 'data_ato', 'data_publicacao',
                'tipo_ato', 'tipo_credito', 'tipo_recurso', 'valor_credito',
            ]));

            $alteracao->load('leiAto', 'dotacoes');

            return response()->json([
                'message' => 'Alteração orçamentária atualizada com sucesso',
                'alteracao' => $this->formatAlteracao($alteracao),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao atualizar alteração', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroyAlteracao(Request $request, int $id): JsonResponse
    {
        $alteracao = AlteracaoOrcamentaria::findOrFail($id);

        try {
            $alteracao->delete();

            return response()->json(['message' => 'Alteração orçamentária excluída com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao excluir alteração', 'error' => $e->getMessage()], 500);
        }
    }

    public function showAlteracao(Request $request, int $id): JsonResponse
    {
        $alteracao = AlteracaoOrcamentaria::with(['leiAto', 'dotacoes'])->findOrFail($id);

        return response()->json([
            'alteracao' => [
                'id' => $alteracao->id,
                'lei_ato_id' => $alteracao->lei_ato_id,
                'lei_ato' => [
                    'id' => $alteracao->leiAto->id,
                    'numero' => $alteracao->leiAto->numero,
                    'tipo' => $alteracao->leiAto->tipo,
                ],
                'decreto_autorizador' => $alteracao->decreto_autorizador,
                'data_ato' => $alteracao->data_ato->format('Y-m-d'),
                'data_publicacao' => $alteracao->data_publicacao->format('Y-m-d'),
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

    // ============================
    // DOTAÇÕES
    // ============================

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
            return response()->json(['message' => 'Dados inválidos', 'errors' => $validator->errors()], 422);
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
            return response()->json(['message' => 'Erro ao adicionar dotação', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroyDotacao(Request $request, int $alteracaoId, int $dotacaoId): JsonResponse
    {
        $alteracao = AlteracaoOrcamentaria::findOrFail($alteracaoId);
        $dotacao = $alteracao->dotacoes()->findOrFail($dotacaoId);

        try {
            $dotacao->delete();

            return response()->json(['message' => 'Dotação excluída com sucesso']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao excluir dotação', 'error' => $e->getMessage()], 500);
        }
    }

    // ============================
    // PDF
    // ============================

    public function gerarPdf(Request $request, int $id)
    {
        $user = $this->authenticateFromToken($request);

        if (! $user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $alteracao = AlteracaoOrcamentaria::with(['leiAto', 'dotacoes'])->findOrFail($id);

        $tipoAtoLabels = [
            'decreto' => 'Decreto',
            'resolucao' => 'Resolução',
            'ato_gestor' => 'Ato do Gestor',
        ];
        $tipoCreditoLabels = [
            'especial' => 'Especial',
            'suplementar' => 'Suplementar',
            'extraordinario' => 'Extraordinário',
        ];
        $tipoRecursoLabels = [
            'superavit' => 'Superávit',
            'excesso_arrecadacao' => 'Excesso de Arrecadação',
            'valor_credito' => 'Valor do Crédito',
        ];

        $data = [
            'alteracao' => $alteracao,
            'tipoAtoLabel' => $tipoAtoLabels[$alteracao->tipo_ato] ?? $alteracao->tipo_ato,
            'tipoCreditoLabel' => $tipoCreditoLabels[$alteracao->tipo_credito] ?? $alteracao->tipo_credito,
            'tipoRecursoLabel' => $tipoRecursoLabels[$alteracao->tipo_recurso] ?? $alteracao->tipo_recurso,
            'totalSuprimido' => $alteracao->dotacoes->sum('valor_suprimido'),
            'totalSuplementado' => $alteracao->dotacoes->sum('valor_suplementado'),
            'dataGeracao' => now()->format('d/m/Y H:i'),
        ];

        $pdf = Pdf::loadView('pdf.alteracao-orcamentaria', $data);
        $pdf->setPaper('A4', 'landscape');

        $filename = 'alteracao_orcamentaria_'.$alteracao->id.'_'.now()->format('Ymd_His').'.pdf';

        return $pdf->download($filename);
    }

    // ============================
    // HELPERS
    // ============================

    private function formatAlteracao(AlteracaoOrcamentaria $alt): array
    {
        return [
            'id' => $alt->id,
            'lei_ato_id' => $alt->lei_ato_id,
            'lei_ato' => $alt->leiAto->numero,
            'decreto_autorizador' => $alt->decreto_autorizador,
            'tipo_ato' => $alt->tipo_ato,
            'tipo_credito' => $alt->tipo_credito,
            'tipo_recurso' => $alt->tipo_recurso,
            'valor_credito' => $alt->valor_credito,
            'data_ato' => $alt->data_ato->format('Y-m-d'),
            'data_publicacao' => $alt->data_publicacao->format('Y-m-d'),
            'total_dotacoes' => $alt->dotacoes->count(),
        ];
    }

    private function authenticateFromToken(Request $request): ?\App\Models\User
    {
        $token = $request->bearerToken() ?? $request->query('token');

        if (! $token) {
            return null;
        }

        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        return $accessToken?->tokenable;
    }
}
