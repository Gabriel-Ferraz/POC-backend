<?php

namespace App\Http\Controllers;

use App\Models\AnexoSolicitacao;
use App\Models\SolicitacaoPagamento;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AnexoController extends Controller
{
    /**
     * Listar anexos da solicitação
     */
    public function index(Request $request, int $solicitacaoId): JsonResponse
    {
        $solicitacao = SolicitacaoPagamento::with('anexos.aprovador')->findOrFail($solicitacaoId);

        // Verificar se o documento fiscal foi recusado
        $documentoFiscalRecusado = $solicitacao->anexos
            ->where('tipo_anexo', 'documento_fiscal')
            ->where('status', 'recusado')
            ->isNotEmpty();

        return response()->json([
            'solicitacao' => [
                'id' => $solicitacao->id,
                'numero' => $solicitacao->numero,
                'status' => $solicitacao->status_label ?? ucfirst(str_replace('_', ' ', $solicitacao->status)),
                'documento_fiscal_recusado' => $documentoFiscalRecusado, // FLAG PRINCIPAL
                'anexos' => $solicitacao->anexos->map(function ($anexo) use ($documentoFiscalRecusado) {
                    $isDocumentoFiscal = $anexo->tipo_anexo === 'documento_fiscal';
                    $isRecusado = $anexo->status === 'recusado';

                    // Pode reenviar se:
                    // 1. Está recusado E não é documento fiscal
                    // 2. OU se está pendente/anexo_cadastrado (sempre pode)
                    $podeReenviar = (
                        ($isRecusado && !$isDocumentoFiscal) || // Recusado mas não é doc fiscal
                        in_array($anexo->status, ['pendente', 'anexo_cadastrado']) // Status permite upload
                    );

                    // Se documento fiscal foi recusado, NENHUM anexo pode ser reenviado
                    if ($documentoFiscalRecusado) {
                        $podeReenviar = false;
                    }

                    return [
                        'id' => $anexo->id,
                        'tipo_anexo' => $anexo->tipo_anexo,
                        'tipo_anexo_label' => $anexo->tipo_anexo_label,
                        'arquivo_path' => $anexo->arquivo_path ? '/storage/' . $anexo->arquivo_path : null,
                        'arquivo_nome' => $anexo->arquivo_nome,
                        'status' => $anexo->status_label,
                        'data_envio' => $anexo->data_envio?->format('Y-m-d'),
                        'motivo_recusa' => $anexo->motivo_recusa,
                        'is_documento_fiscal' => $isDocumentoFiscal,
                        'pode_reenviar' => $podeReenviar, // FLAG INDIVIDUAL
                        'pode_remover' => $podeReenviar && $anexo->arquivo_path !== null, // Só pode remover se pode reenviar e tem arquivo
                    ];
                })->values(),
            ],
        ]);
    }

    /**
     * Upload de anexo individual
     */
    public function upload(Request $request, int $solicitacaoId, int $anexoId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'arquivo' => 'required|file|mimes:pdf|max:10240', // 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Os dados fornecidos são inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $solicitacao = SolicitacaoPagamento::findOrFail($solicitacaoId);
        $anexo = $solicitacao->anexos()->findOrFail($anexoId);

        // Verificar permissão - apenas o dono da solicitação
        if ($solicitacao->solicitante_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Você não tem permissão para enviar anexos desta solicitação',
            ], 403);
        }

        // Verificar se já está aprovado
        if ($anexo->status === 'aprovado') {
            return response()->json([
                'message' => 'Não é possível substituir um anexo já aprovado',
            ], 400);
        }

        try {
            // Remover arquivo anterior se existir
            if ($anexo->arquivo_path) {
                Storage::disk('public')->delete($anexo->arquivo_path);
            }

            // Upload do arquivo
            $file = $request->file('arquivo');
            $path = $file->store('anexos', 'public');
            $nome = $file->getClientOriginalName();

            $anexo->update([
                'arquivo_path' => $path,
                'arquivo_nome' => $nome,
                'status' => 'anexo_cadastrado',
                'data_envio' => now()->format('Y-m-d'),
                'motivo_recusa' => null,
            ]);

            return response()->json([
                'message' => 'Anexo enviado com sucesso',
                'anexo' => [
                    'id' => $anexo->id,
                    'tipo_anexo' => $anexo->tipo_anexo_label,
                    'arquivo_path' => '/storage/' . $anexo->arquivo_path,
                    'arquivo_nome' => $anexo->arquivo_nome,
                    'status' => $anexo->status_label,
                    'data_envio' => $anexo->data_envio->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao enviar anexo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remover anexo
     */
    public function remover(Request $request, int $solicitacaoId, int $anexoId): JsonResponse
    {
        $solicitacao = SolicitacaoPagamento::findOrFail($solicitacaoId);
        $anexo = $solicitacao->anexos()->findOrFail($anexoId);

        // Verificar permissão
        if ($solicitacao->solicitante_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Você não tem permissão para remover anexos desta solicitação',
            ], 403);
        }

        // Verificar se já está aprovado
        if ($anexo->status === 'aprovado') {
            return response()->json([
                'message' => 'Não é possível remover um anexo já aprovado',
            ], 400);
        }

        try {
            // Remover arquivo físico
            if ($anexo->arquivo_path) {
                Storage::disk('public')->delete($anexo->arquivo_path);
            }

            $anexo->update([
                'arquivo_path' => null,
                'arquivo_nome' => null,
                'status' => 'pendente',
                'data_envio' => null,
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

    /**
     * Download/visualizar anexo
     */
    public function download(Request $request, int $solicitacaoId, int $anexoId): BinaryFileResponse|JsonResponse
    {
        $solicitacao = SolicitacaoPagamento::findOrFail($solicitacaoId);
        $anexo = $solicitacao->anexos()->findOrFail($anexoId);

        // Verificar permissão: Responsável Técnico (dono) OU Gestor do Contrato
        $isResponsavel = $solicitacao->solicitante_id === $request->user()->id;
        $isGestor = $request->user()->perfil === 'gestor_contrato';

        if (!$isResponsavel && !$isGestor) {
            return response()->json([
                'message' => 'Você não tem permissão para visualizar este anexo',
            ], 403);
        }

        if (!$anexo->arquivo_path) {
            return response()->json([
                'message' => 'Anexo não encontrado',
            ], 404);
        }

        if (!Storage::disk('public')->exists($anexo->arquivo_path)) {
            return response()->json([
                'message' => 'Arquivo não encontrado no servidor',
            ], 404);
        }

        return response()->download(
            Storage::disk('public')->path($anexo->arquivo_path),
            $anexo->arquivo_nome
        );
    }

    /**
     * Enviar todos os anexos para aprovação
     */
    public function enviarTodos(Request $request, int $solicitacaoId): JsonResponse
    {
        $solicitacao = SolicitacaoPagamento::with('anexos')->findOrFail($solicitacaoId);

        // Verificar permissão
        if ($solicitacao->solicitante_id !== $request->user()->id) {
            return response()->json([
                'message' => 'Você não tem permissão para enviar anexos desta solicitação',
            ], 403);
        }

        // Verificar se todos os anexos foram enviados
        $anexosPendentes = $solicitacao->anexos->filter(function ($anexo) {
            return !$anexo->arquivo_path;
        });

        if ($anexosPendentes->count() > 0) {
            return response()->json([
                'message' => 'Existem anexos pendentes de envio',
                'anexos_pendentes' => $anexosPendentes->map(function ($anexo) {
                    return $anexo->tipo_anexo_label;
                })->values(),
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Atualizar status apenas dos anexos que não estão aprovados
            $solicitacao->anexos()
                ->where('status', '!=', 'aprovado')
                ->update([
                    'status' => 'aguardando_aprovacao',
                ]);

            // Atualizar status da solicitação
            $solicitacao->update([
                'status' => 'aguardando_aprovacao_anexos',
            ]);

            // Registrar trâmite
            if ($solicitacao->wasChanged('status')) {
                $mensagem = $solicitacao->status === 'aguardando_aprovacao_anexos' && $solicitacao->getOriginal('status') === 'anexos_recusados'
                    ? 'Anexos corrigidos e reenviados para aprovação do Gestor do Contrato'
                    : 'Todos os anexos foram enviados e aguardam aprovação do Gestor do Contrato';

                $solicitacao->registrarTramite(
                    'Anexos Enviados para Aprovação',
                    $request->user()->id,
                    $mensagem
                );
            }

            DB::commit();

            return response()->json([
                'message' => 'Anexos enviados para aprovação com sucesso',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erro ao enviar anexos para aprovação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Aprovar anexo (Gestor)
     */
    public function aprovar(Request $request, int $anexoId): JsonResponse
    {
        $anexo = AnexoSolicitacao::with('solicitacao.anexos')->findOrFail($anexoId);

        // Verificar se é gestor
        if ($request->user()->perfil !== 'gestor_contrato') {
            return response()->json([
                'message' => 'Apenas gestores de contrato podem aprovar anexos',
            ], 403);
        }

        if ($anexo->status !== 'aguardando_aprovacao') {
            return response()->json([
                'message' => 'Anexo não está aguardando aprovação',
            ], 400);
        }

        DB::beginTransaction();

        try {
            $anexo->update([
                'status' => 'aprovado',
                'aprovado_por' => $request->user()->id,
                'data_aprovacao' => now(),
                'motivo_recusa' => null,
            ]);

            // Verificar se todos os anexos foram aprovados
            $todosAprovados = $anexo->solicitacao->anexos()
                ->where('status', '!=', 'aprovado')
                ->count() === 0;

            if ($todosAprovados) {
                $anexo->solicitacao->update([
                    'status' => 'aguardando_autorizacao_gestor',
                ]);

                $anexo->solicitacao->registrarTramite(
                    'Todos Anexos Aprovados',
                    $request->user()->id,
                    'Todos os anexos foram aprovados. Solicitação prossegue no fluxo interno da PMSJP'
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

    /**
     * Recusar anexo (Gestor)
     */
    public function recusar(Request $request, int $anexoId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'motivo' => 'required|string|min:10|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Os dados fornecidos são inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $anexo = AnexoSolicitacao::with('solicitacao')->findOrFail($anexoId);

        // Verificar se é gestor
        if ($request->user()->perfil !== 'gestor_contrato') {
            return response()->json([
                'message' => 'Apenas gestores de contrato podem recusar anexos',
            ], 403);
        }

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
                'aprovado_por' => $request->user()->id,
                'data_aprovacao' => now(),
            ]);

            // Se for documento fiscal, orientar cancelamento
            $mensagem = 'Anexo ' . $anexo->tipo_anexo_label . ' foi recusado.';
            if ($anexo->tipo_anexo === 'documento_fiscal') {
                $mensagem .= ' ATENÇÃO: Documento Fiscal recusado não pode ser corrigido. Você deve cancelar esta solicitação e criar uma nova com o documento correto.';
            } else {
                $mensagem .= ' Você pode corrigir enviando um novo anexo.';
            }

            $anexo->solicitacao->update([
                'status' => 'anexos_recusados',
            ]);

            $anexo->solicitacao->registrarTramite(
                'Anexo Recusado',
                $request->user()->id,
                $mensagem,
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
}
