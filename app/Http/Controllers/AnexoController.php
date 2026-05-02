<?php

namespace App\Http\Controllers;

use App\Models\AnexoSolicitacao;
use App\Models\SolicitacaoPagamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AnexoController extends Controller
{
    public function index(Request $request, int $solicitacaoId): JsonResponse
    {
        $solicitacao = SolicitacaoPagamento::with('anexos.avaliador')->findOrFail($solicitacaoId);

        return response()->json([
            'solicitacao_id' => $solicitacao->id,
            'solicitacao_numero' => $solicitacao->numero,
            'solicitacao_status' => $solicitacao->status,
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
        ]);
    }

    public function upload(Request $request, int $solicitacaoId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'anexo_id' => 'required|exists:anexos_solicitacao,id',
            'arquivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $anexo = AnexoSolicitacao::where('solicitacao_id', $solicitacaoId)
            ->where('id', $request->anexo_id)
            ->firstOrFail();

        if (!in_array($anexo->status, ['pendente', 'recusado', 'anexo_cadastrado'])) {
            return response()->json([
                'message' => 'Não é possível alterar anexo com status: ' . $anexo->status,
            ], 400);
        }

        try {
            if ($anexo->arquivo) {
                Storage::disk('public')->delete($anexo->arquivo);
            }

            $path = $request->file('arquivo')->store('anexos/solicitacoes', 'public');

            $anexo->update([
                'arquivo' => $path,
                'status' => 'anexo_cadastrado',
                'motivo_recusa' => null,
            ]);

            return response()->json([
                'message' => 'Anexo enviado com sucesso',
                'anexo' => [
                    'id' => $anexo->id,
                    'tipo_anexo' => $anexo->tipo_anexo,
                    'arquivo' => $anexo->arquivo,
                    'status' => $anexo->status,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao enviar anexo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function enviarTodos(Request $request, int $solicitacaoId): JsonResponse
    {
        $solicitacao = SolicitacaoPagamento::with('anexos')->findOrFail($solicitacaoId);

        $anexosPendentes = $solicitacao->anexos->filter(function ($anexo) {
            return !$anexo->arquivo || in_array($anexo->status, ['pendente', 'recusado']);
        });

        if ($anexosPendentes->count() > 0) {
            return response()->json([
                'message' => 'Existem anexos pendentes de envio',
                'anexos_pendentes' => $anexosPendentes->pluck('tipo_anexo'),
            ], 400);
        }

        DB::beginTransaction();

        try {
            $solicitacao->anexos()->update([
                'status' => 'aguardando_aprovacao',
            ]);

            $solicitacao->update([
                'status' => 'aguardando_aprovacao_anexos',
            ]);

            $solicitacao->registrarTramite(
                'Anexos Enviados para Aprovação',
                $request->user()->id,
                'Todos os anexos foram enviados e aguardam aprovação do gestor'
            );

            DB::commit();

            return response()->json([
                'message' => 'Anexos enviados para aprovação com sucesso',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erro ao enviar anexos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function aprovar(Request $request, int $anexoId): JsonResponse
    {
        $anexo = AnexoSolicitacao::with('solicitacao')->findOrFail($anexoId);

        if ($anexo->status !== 'aguardando_aprovacao') {
            return response()->json([
                'message' => 'Anexo não está aguardando aprovação',
            ], 400);
        }

        DB::beginTransaction();

        try {
            $anexo->update([
                'status' => 'aprovado',
                'avaliado_por' => $request->user()->id,
                'avaliado_em' => now(),
                'motivo_recusa' => null,
            ]);

            $todosAprovados = $anexo->solicitacao->anexos()
                ->where('id', '!=', $anexo->id)
                ->where('status', '!=', 'aprovado')
                ->count() === 0;

            if ($todosAprovados) {
                $anexo->solicitacao->update([
                    'status' => 'aguardando_autorizacao_gestor',
                ]);

                $anexo->solicitacao->registrarTramite(
                    'Todos Anexos Aprovados',
                    $request->user()->id,
                    'Todos os anexos foram aprovados. Aguardando autorização do gestor'
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'Anexo aprovado com sucesso',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erro ao aprovar anexo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function recusar(Request $request, int $anexoId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'motivo' => 'required|string|min:10',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Motivo da recusa é obrigatório',
                'errors' => $validator->errors(),
            ], 422);
        }

        $anexo = AnexoSolicitacao::with('solicitacao')->findOrFail($anexoId);

        if ($anexo->status !== 'aguardando_aprovacao') {
            return response()->json([
                'message' => 'Anexo não está aguardando aprovação',
            ], 400);
        }

        DB::beginTransaction();

        try {
            $anexo->update([
                'status' => 'recusado',
                'motivo_recusa' => $request->motivo,
                'avaliado_por' => $request->user()->id,
                'avaliado_em' => now(),
            ]);

            $anexo->solicitacao->update([
                'status' => 'anexos_recusados',
            ]);

            $anexo->solicitacao->registrarTramite(
                'Anexo Recusado',
                $request->user()->id,
                'Anexo ' . $anexo->tipo_anexo . ' foi recusado',
                $request->motivo
            );

            DB::commit();

            return response()->json([
                'message' => 'Anexo recusado com sucesso',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erro ao recusar anexo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(Request $request, int $anexoId): JsonResponse
    {
        $anexo = AnexoSolicitacao::findOrFail($anexoId);

        if (!in_array($anexo->status, ['pendente', 'anexo_cadastrado', 'recusado'])) {
            return response()->json([
                'message' => 'Não é possível excluir anexo com status: ' . $anexo->status,
            ], 400);
        }

        try {
            if ($anexo->arquivo) {
                Storage::disk('public')->delete($anexo->arquivo);
            }

            $anexo->update([
                'arquivo' => null,
                'status' => 'pendente',
                'motivo_recusa' => null,
            ]);

            return response()->json([
                'message' => 'Anexo removido com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao remover anexo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function download(Request $request, int $anexoId): JsonResponse
    {
        $anexo = AnexoSolicitacao::findOrFail($anexoId);

        if (!$anexo->arquivo) {
            return response()->json([
                'message' => 'Anexo não possui arquivo',
            ], 404);
        }

        if (!Storage::disk('public')->exists($anexo->arquivo)) {
            return response()->json([
                'message' => 'Arquivo não encontrado',
            ], 404);
        }

        return response()->download(
            Storage::disk('public')->path($anexo->arquivo)
        );
    }
}
