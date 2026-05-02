<?php

namespace App\Http\Controllers;

use App\Models\Chamado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ChamadoController extends Controller
{
    /**
     * Lista usuários para o filtro (GESTORES)
     * Suporta busca por nome (autocomplete)
     */
    public function listarUsuarios(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return response()->json([
                    'message' => 'Usuário não autenticado',
                ], 401);
            }

            // Perfis que podem visualizar todos os chamados e filtrar por usuário
            $perfisGestores = ['gestor_suporte', 'gestor_contrato'];

            // Apenas gestores podem listar outros usuários
            if (!in_array($user->perfil, $perfisGestores)) {
                return response()->json([
                    'usuario_atual' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'perfil' => $user->perfil,
                        'perfil_label' => $this->getPerfilLabel($user->perfil),
                    ],
                ]);
            }

            // Buscar usuários que já criaram chamados
            $query = \App\Models\User::whereHas('chamados')
                ->select('id', 'name', 'perfil');

            // Filtro por nome (para autocomplete) - case-insensitive
            if ($request->filled('busca')) {
                $query->where('name', 'ILIKE', '%'.$request->busca.'%');
            }

            $usuarios = $query->orderBy('name')
                ->limit(20) // Limita a 20 resultados para autocomplete
                ->get();

            return response()->json([
                'usuarios' => $usuarios->map(function ($usuario) {
                    return [
                        'id' => $usuario->id,
                        'name' => $usuario->name,
                        'perfil' => $usuario->perfil,
                        'perfil_label' => $this->getPerfilLabel($usuario->perfil),
                    ];
                })->values(), // Garante que retorna array indexado
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao listar usuários',
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => basename($e->getFile()),
            ], 500);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Chamado::with(['usuario', 'mensagens']);

        // REGRA DE PERMISSÃO:
        // - Gestores (gestor_suporte, gestor_contrato): vê todos os chamados
        // - Usuários Comuns: vê APENAS seus próprios chamados (filtro automático)
        $perfisGestores = ['gestor_suporte', 'gestor_contrato'];

        if (!in_array($user->perfil, $perfisGestores)) {
            $query->where('usuario_id', $user->id);
        }

        // Filtro por ID/Protocolo
        if ($request->filled('protocolo')) {
            $protocolo = str_replace('#', '', $request->protocolo);
            $query->where('id', $protocolo);
        }

        // Filtro por Data de Cadastro (Início)
        if ($request->filled('data_cadastro_inicio')) {
            $query->whereDate('created_at', '>=', $request->data_cadastro_inicio);
        }

        // Filtro por Data de Cadastro (Fim)
        if ($request->filled('data_cadastro_fim')) {
            $query->whereDate('created_at', '<=', $request->data_cadastro_fim);
        }

        // Filtro por Data de Resposta (Início)
        if ($request->filled('data_resposta_inicio')) {
            $query->whereDate('respondido_em', '>=', $request->data_resposta_inicio);
        }

        // Filtro por Data de Resposta (Fim)
        if ($request->filled('data_resposta_fim')) {
            $query->whereDate('respondido_em', '<=', $request->data_resposta_fim);
        }

        // Filtro por Módulo
        if ($request->filled('modulo')) {
            $query->where('modulo', 'like', '%' . $request->modulo . '%');
        }

        // Filtro por Usuário (ID ou Nome)
        // APENAS GESTORES podem filtrar por outros usuários
        $perfisGestores = ['gestor_suporte', 'gestor_contrato'];

        if ($request->filled('usuario_id') && in_array($user->perfil, $perfisGestores)) {
            $query->where('usuario_id', $request->usuario_id);
        } elseif ($request->filled('usuario_origem') && in_array($user->perfil, $perfisGestores)) {
            // Mantém compatibilidade com filtro antigo por nome
            $query->whereHas('usuario', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->usuario_origem . '%');
            });
        }

        // Filtro por Assunto
        if ($request->filled('assunto')) {
            $query->where('assunto', 'like', '%' . $request->assunto . '%');
        }

        // Filtro por Status (aceita múltiplos valores)
        if ($request->filled('status')) {
            $status = $request->status;

            // Se for string separada por vírgula, converte para array
            if (is_string($status)) {
                $status = explode(',', $status);
            }

            // Remove espaços em branco
            $status = array_map('trim', (array) $status);

            // Filtra valores vazios
            $status = array_filter($status);

            if (!empty($status)) {
                $query->whereIn('status', $status);
            }
        }

        $chamados = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'chamados' => $chamados->map(function ($chamado) use ($user) {
                return [
                    'id' => $chamado->id,
                    'protocolo' => '#'.$chamado->id,
                    'modulo' => $chamado->modulo,
                    'assunto' => $chamado->assunto,
                    'usuario' => $chamado->usuario->name.' ('.$this->getPerfilLabel($chamado->usuario->perfil).')',
                    'status' => $chamado->status,
                    'status_label' => $this->getStatusLabel($chamado->status),
                    'data_abertura' => $chamado->created_at->format('d/m/Y'),
                    'data_cadastro' => $chamado->created_at->format('d/m/Y H:i'),
                    'data_ultima_resposta' => $chamado->data_ultima_resposta?->format('d/m/Y H:i'),
                    'total_mensagens' => $chamado->mensagens()->count(),
                    'total_anexos' => $chamado->anexos()->count(),
                ];
            }),
        ]);
    }

    /**
     * Helper para obter label do perfil
     */
    private function getPerfilLabel(string $perfil): string
    {
        $perfis = [
            'responsavel_tecnico' => 'Responsável Técnico',
            'gestor_contrato' => 'Gestor do Contrato',
            'operador_pmsjp' => 'Operador PMSJP',
            'gestor_suporte' => 'Gestor de Suporte',
            'operador_orcamentario' => 'Operador Orçamentário',
        ];

        return $perfis[$perfil] ?? ucfirst(str_replace('_', ' ', $perfil));
    }

    /**
     * Helper para obter label do status
     */
    private function getStatusLabel(string $status): string
    {
        $labels = [
            'aberto' => 'Aberto',
            'em_atendimento' => 'Em Atendimento',
            'concluido' => 'Concluído',
        ];

        return $labels[$status] ?? ucfirst(str_replace('_', ' ', $status));
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'modulo' => 'required|string|max:255',
            'assunto' => 'required|string',
            'anexos' => 'nullable|array',
            'anexos.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = $request->user();

            // Criar chamado (assunto é TEXT grande, não tem mensagem separada)
            $chamado = Chamado::create([
                'usuario_id' => $user->id,
                'modulo' => $request->modulo,
                'assunto' => $request->assunto,
                'status' => 'aberto',
            ]);

            // Criar mensagem de abertura na timeline (com o texto do assunto)
            $mensagem = $chamado->mensagens()->create([
                'usuario_id' => $user->id,
                'tipo' => 'abertura',
                'mensagem' => $request->assunto,
            ]);

            // Salvar anexos
            if ($request->hasFile('anexos')) {
                foreach ($request->file('anexos') as $arquivo) {
                    $nomeOriginal = $arquivo->getClientOriginalName();
                    $nomeSalvo = time().'_'.\Illuminate\Support\Str::slug(pathinfo($nomeOriginal, PATHINFO_FILENAME)).'.'.$arquivo->extension();
                    $caminho = $arquivo->storeAs('chamados/'.$chamado->id, $nomeSalvo, 'public');

                    $chamado->anexos()->create([
                        'mensagem_id' => $mensagem->id,
                        'nome_original' => $nomeOriginal,
                        'nome_salvo' => $nomeSalvo,
                        'caminho' => $caminho,
                        'tamanho' => $arquivo->getSize(),
                        'tipo' => $arquivo->getMimeType(),
                        'enviado_por_usuario_id' => $user->id,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Chamado criado com sucesso',
                'chamado' => [
                    'id' => $chamado->id,
                    'protocolo' => '#'.$chamado->id,
                    'modulo' => $chamado->modulo,
                    'assunto' => $chamado->assunto,
                    'usuario' => $user->name.' ('.$this->getPerfilLabel($user->perfil).')',
                    'status' => $chamado->status,
                    'data_cadastro' => $chamado->created_at->format('d/m/Y H:i'),
                    'data_abertura' => $chamado->created_at->format('d/m/Y'),
                    'anexos_count' => $chamado->anexos()->count(),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erro ao criar chamado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $chamado = Chamado::with([
            'usuario',
            'mensagens.usuario',
            'mensagens.anexos',
        ])->findOrFail($id);

        $user = $request->user();

        // Gestores podem ver qualquer chamado, usuários comuns apenas os próprios
        $perfisGestores = ['gestor_suporte', 'gestor_contrato'];

        if (!in_array($user->perfil, $perfisGestores) && $chamado->usuario_id !== $user->id) {
            return response()->json([
                'message' => 'Você não tem permissão para visualizar este chamado.',
            ], 403);
        }

        // Montar timeline a partir das mensagens
        $timeline = $chamado->mensagens->map(function ($msg) {
            return [
                'id' => $msg->id,
                'tipo' => $msg->tipo,
                'usuario' => $msg->usuario->name,
                'mensagem' => $msg->mensagem,
                'data' => $msg->created_at->format('d/m/Y H:i'),
                'anexos' => $msg->anexos->map(function ($anexo) {
                    return [
                        'id' => $anexo->id,
                        'nome' => $anexo->nome_original,
                        'tamanho' => number_format($anexo->tamanho / 1024, 1).' KB',
                        'arquivo_path' => '/api/chamados/anexos/'.$anexo->id.'/download',
                    ];
                }),
            ];
        });

        return response()->json([
            'chamado' => [
                'id' => $chamado->id,
                'protocolo' => '#'.$chamado->id,
                'modulo' => $chamado->modulo,
                'assunto' => $chamado->assunto,
                'usuario' => $chamado->usuario->name,
                'status' => $chamado->status,
                'data_abertura' => $chamado->created_at->format('d/m/Y'),
                'data_cadastro' => $chamado->created_at->format('d/m/Y H:i'),
                'data_ultima_resposta' => $chamado->data_ultima_resposta?->format('d/m/Y H:i'),
                'data_conclusao' => $chamado->data_conclusao?->format('d/m/Y H:i'),
                'mensagem_inicial' => $chamado->assunto,
            ],
            'timeline' => $timeline,
        ]);
    }

    public function responder(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mensagem' => 'required|string',
            'anexos' => 'nullable|array',
            'anexos.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $chamado = Chamado::findOrFail($id);

        // Verificar permissão: criador do chamado OU gestor
        $perfisGestores = ['gestor_suporte', 'gestor_contrato'];
        $podeResponder = $chamado->usuario_id === $user->id || in_array($user->perfil, $perfisGestores);

        if (!$podeResponder) {
            return response()->json([
                'message' => 'Você não tem permissão para responder este chamado.',
            ], 403);
        }

        if ($chamado->status === 'concluido') {
            return response()->json([
                'message' => 'Chamado já foi concluído',
            ], 400);
        }

        DB::beginTransaction();

        try {
            // Criar mensagem de resposta
            $mensagem = $chamado->mensagens()->create([
                'usuario_id' => $user->id,
                'tipo' => 'resposta',
                'mensagem' => $request->mensagem,
            ]);

            // Atualizar data da última resposta e status (apenas se estiver aberto)
            $chamado->update([
                'data_ultima_resposta' => now(),
                'status' => $chamado->status === 'aberto' ? 'em_atendimento' : $chamado->status,
            ]);

            // Salvar anexos
            if ($request->hasFile('anexos')) {
                foreach ($request->file('anexos') as $arquivo) {
                    $nomeOriginal = $arquivo->getClientOriginalName();
                    $nomeSalvo = time().'_'.\Illuminate\Support\Str::slug(pathinfo($nomeOriginal, PATHINFO_FILENAME)).'.'.$arquivo->extension();
                    $caminho = $arquivo->storeAs('chamados/'.$chamado->id, $nomeSalvo, 'public');

                    $chamado->anexos()->create([
                        'mensagem_id' => $mensagem->id,
                        'nome_original' => $nomeOriginal,
                        'nome_salvo' => $nomeSalvo,
                        'caminho' => $caminho,
                        'tamanho' => $arquivo->getSize(),
                        'tipo' => $arquivo->getMimeType(),
                        'enviado_por_usuario_id' => $user->id,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Resposta adicionada com sucesso',
                'mensagem' => [
                    'id' => $mensagem->id,
                    'tipo' => $mensagem->tipo,
                    'usuario' => $user->name,
                    'mensagem' => $mensagem->mensagem,
                    'data' => $mensagem->created_at->format('d/m/Y H:i'),
                    'anexos_count' => $mensagem->anexos()->count(),
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erro ao enviar resposta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function anexar(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'arquivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Arquivo inválido',
                'errors' => $validator->errors(),
            ], 422);
        }

        $chamado = Chamado::findOrFail($id);

        if ($chamado->status === 'concluido') {
            return response()->json([
                'message' => 'Não é possível anexar arquivos em chamado concluído',
            ], 400);
        }

        try {
            $path = $request->file('arquivo')->store('anexos/chamados', 'public');

            $chamado->anexos()->create([
                'arquivo' => $path,
                'nome_original' => $request->file('arquivo')->getClientOriginalName(),
            ]);

            return response()->json([
                'message' => 'Anexo adicionado com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao anexar arquivo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function concluir(Request $request, int $id): JsonResponse
    {
        $chamado = Chamado::findOrFail($id);

        if ($chamado->status === 'concluido') {
            return response()->json([
                'message' => 'Chamado já está concluído',
            ], 400);
        }

        try {
            $chamado->update([
                'status' => 'concluido',
                'data_conclusao' => now(),
            ]);

            return response()->json([
                'message' => 'Chamado concluído com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao concluir chamado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function downloadAnexo(Request $request, int $id)
    {
        $anexo = \App\Models\AnexoChamado::findOrFail($id);
        $user = $request->user();
        $chamado = $anexo->chamado;

        // Verificar permissão: Criador do chamado OU Gestor
        $perfisGestores = ['gestor_suporte', 'gestor_contrato'];
        $podeVisualizar = $chamado->usuario_id === $user->id || in_array($user->perfil, $perfisGestores);

        if (!$podeVisualizar) {
            return response()->json([
                'message' => 'Você não tem permissão para visualizar este anexo',
            ], 403);
        }

        if (!$anexo->caminho) {
            return response()->json([
                'message' => 'Anexo não encontrado',
            ], 404);
        }

        if (!Storage::disk('public')->exists($anexo->caminho)) {
            return response()->json([
                'message' => 'Arquivo não encontrado no servidor',
            ], 404);
        }

        return response()->download(
            Storage::disk('public')->path($anexo->caminho),
            $anexo->nome_original
        );
    }
}
