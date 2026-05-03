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

    public function listarResponsaveis(Request $request): JsonResponse
    {
        // Responsável = qualquer usuário que enviou mensagem num chamado,
        // exceto o próprio autor do chamado
        $query = \App\Models\User::whereIn(
                'id',
                \App\Models\MensagemChamado::select('mensagens_chamado.usuario_id')
                    ->join('chamados', 'chamados.id', '=', 'mensagens_chamado.chamado_id')
                    ->whereColumn('mensagens_chamado.usuario_id', '!=', 'chamados.usuario_id')
                    ->distinct()
            )
            ->select('id', 'name', 'perfil');

        if ($request->filled('busca')) {
            $query->where('name', 'ILIKE', '%'.$request->busca.'%');
        }

        $usuarios = $query->orderBy('name')->limit(20)->get();

        return response()->json([
            'usuarios' => $usuarios->map(fn ($u) => [
                'id'          => $u->id,
                'name'        => $u->name,
                'perfil'      => $u->perfil,
                'perfil_label' => $this->getPerfilLabel($u->perfil),
            ])->values(),
        ]);
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

        // Filtro por Responsável (qualquer usuário que respondeu, exceto o autor)
        if ($request->filled('responsavel_id')) {
            $query->whereHas('mensagens', function ($q) use ($request) {
                $q->where('mensagens_chamado.usuario_id', $request->responsavel_id)
                  ->whereColumn('mensagens_chamado.usuario_id', '!=', 'chamados.usuario_id');
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
                // Determinar quem enviou a última mensagem
                $ultimaMensagem = $chamado->mensagens()->orderBy('created_at', 'desc')->first();
                $ultimaMensagemPor = null;
                $temRespostaPendente = false;

                $responsavel = null;
                $responsavelId = null;

                if ($ultimaMensagem) {
                    $perfisGestores = ['gestor_suporte', 'gestor_contrato'];
                    $mensagemDeGestor = in_array($ultimaMensagem->usuario->perfil, $perfisGestores);

                    $ultimaMensagemPor = $mensagemDeGestor ? 'gestor' : 'usuario';

                    if ($mensagemDeGestor) {
                        $responsavel = $ultimaMensagem->usuario->name;
                        $responsavelId = $ultimaMensagem->usuario->id;
                    }

                    // Se a última mensagem foi de um gestor e o chamado não está concluído
                    // e o usuário logado é o criador do chamado
                    if ($mensagemDeGestor &&
                        $chamado->status !== 'concluido' &&
                        $chamado->usuario_id === $user->id) {
                        $temRespostaPendente = true;
                    }
                }

                return [
                    'id' => $chamado->id,
                    'protocolo' => '#'.$chamado->id,
                    'modulo' => $chamado->modulo,
                    'assunto' => $chamado->assunto,
                    'usuario' => $chamado->usuario->name,
                    'usuario_id' => $chamado->usuario_id,
                    'responsavel' => $responsavel,
                    'responsavel_id' => $responsavelId,
                    'status' => $chamado->status,
                    'status_label' => $this->getStatusLabel($chamado->status),
                    'data_abertura' => $chamado->created_at->format('d/m/Y'),
                    'data_cadastro' => $chamado->created_at->format('d/m/Y H:i'),
                    'data_ultima_resposta' => $chamado->data_ultima_resposta?->format('d/m/Y H:i'),
                    'data_conclusao' => $chamado->data_conclusao?->format('d/m/Y H:i'),
                    'total_mensagens' => $chamado->mensagens()->count(),
                    'total_anexos' => $chamado->anexos()->count(),

                    // NOVOS CAMPOS para controle de cores dos ícones
                    'ultima_mensagem_por' => $ultimaMensagemPor,
                    'tem_resposta_pendente' => $temRespostaPendente,
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
        \Log::info('ChamadoController::store iniciado', [
            'modulo' => $request->input('modulo'),
            'assunto' => substr($request->input('assunto'), 0, 50),
            'has_anexos' => $request->hasFile('anexos'),
        ]);

        $validator = Validator::make($request->all(), [
            'modulo' => 'required|string|max:255',
            'assunto' => 'required|string',
            'anexos' => 'nullable|array',
            'anexos.*' => 'file|max:10240|mimes:pdf,doc,docx,xls,xlsx,png,jpg,jpeg,gif',
        ]);

        if ($validator->fails()) {
            \Log::warning('ChamadoController::store validação falhou', [
                'errors' => $validator->errors()->toArray(),
            ]);
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $user = $request->user();
            \Log::info('ChamadoController::store user', ['user_id' => $user->id]);

            // Capturar informações do sistema
            $userAgent = $request->header('User-Agent');
            $navegador = $this->detectarNavegador($userAgent);
            $sistemaOperacional = $this->detectarSO($userAgent);
            $ip = $request->ip();

            // Criar chamado (assunto é TEXT grande, não tem mensagem separada)
            $chamado = Chamado::create([
                'usuario_id' => $user->id,
                'modulo' => $request->modulo,
                'assunto' => $request->assunto,
                'status' => 'aberto',
                'navegador' => $navegador,
                'sistema_operacional' => $sistemaOperacional,
                'ip_origem' => $ip,
                'user_agent' => $userAgent,
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

            \Log::info('ChamadoController::store sucesso', ['chamado_id' => $chamado->id]);

            return response()->json([
                'message' => 'Chamado criado com sucesso',
                'chamado' => [
                    'id' => $chamado->id,
                    'protocolo' => '#'.$chamado->id,
                    'modulo' => $chamado->modulo,
                    'assunto' => $chamado->assunto,
                    'usuario' => $user->name,
                    'status' => $chamado->status,
                    'data_cadastro' => $chamado->created_at->format('d/m/Y H:i'),
                    'data_abertura' => $chamado->created_at->format('d/m/Y'),
                    'anexos_count' => $chamado->anexos()->count(),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('ChamadoController::store exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Erro ao criar chamado',
                'error' => config('app.debug') ? $e->getMessage() : null,
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
            // NOVO: Log do sistema
            'log_sistema' => [
                'navegador' => $chamado->navegador ?? 'Não disponível',
                'sistema_operacional' => $chamado->sistema_operacional ?? 'Não disponível',
                'ip' => $chamado->ip_origem ?? 'Não disponível',
                'data_hora_acesso' => $chamado->created_at->format('d/m/Y H:i:s'),
                'user_agent' => $chamado->user_agent ?? 'Não disponível',
            ],
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
        try {
            \Log::info('downloadAnexo iniciado', ['anexo_id' => $id]);

            $anexo = \App\Models\AnexoChamado::findOrFail($id);
            \Log::info('Anexo encontrado', ['caminho' => $anexo->caminho]);

            $user = $request->user();
            \Log::info('Usuario', ['user_id' => $user->id, 'perfil' => $user->perfil]);

            $chamado = $anexo->chamado;
            \Log::info('Chamado', ['chamado_id' => $chamado->id, 'usuario_id' => $chamado->usuario_id]);

            // Verificar permissão: Criador do chamado OU Gestor
            $perfisGestores = ['gestor_suporte', 'gestor_contrato'];
            $podeVisualizar = $chamado->usuario_id === $user->id || in_array($user->perfil, $perfisGestores);

            if (!$podeVisualizar) {
                \Log::warning('Sem permissao');
                return response()->json([
                    'message' => 'Você não tem permissão para visualizar este anexo',
                ], 403);
            }

            if (!$anexo->caminho) {
                \Log::warning('Caminho vazio');
                return response()->json([
                    'message' => 'Anexo não encontrado',
                ], 404);
            }

            if (!Storage::disk('public')->exists($anexo->caminho)) {
                \Log::warning('Arquivo nao existe no storage');
                return response()->json([
                    'message' => 'Arquivo não encontrado no servidor',
                ], 404);
            }

            $path = Storage::disk('public')->path($anexo->caminho);
            \Log::info('Iniciando download', ['path' => $path, 'nome' => $anexo->nome_original]);

            return response()->download($path, $anexo->nome_original);
        } catch (\Exception $e) {
            \Log::error('Erro no downloadAnexo', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'message' => 'Erro ao baixar anexo',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Detectar navegador a partir do User-Agent
     */
    private function detectarNavegador(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Desconhecido';
        }

        if (str_contains($userAgent, 'Edg')) {
            return 'Microsoft Edge';
        }
        if (str_contains($userAgent, 'Chrome')) {
            return 'Google Chrome';
        }
        if (str_contains($userAgent, 'Firefox')) {
            return 'Mozilla Firefox';
        }
        if (str_contains($userAgent, 'Safari') && !str_contains($userAgent, 'Chrome')) {
            return 'Safari';
        }
        if (str_contains($userAgent, 'Opera') || str_contains($userAgent, 'OPR')) {
            return 'Opera';
        }

        return 'Outro';
    }

    /**
     * Detectar sistema operacional a partir do User-Agent
     */
    private function detectarSO(?string $userAgent): string
    {
        if (!$userAgent) {
            return 'Desconhecido';
        }

        if (str_contains($userAgent, 'Windows NT 10.0')) {
            return 'Windows 10/11';
        }
        if (str_contains($userAgent, 'Windows NT 6.3')) {
            return 'Windows 8.1';
        }
        if (str_contains($userAgent, 'Windows NT 6.2')) {
            return 'Windows 8';
        }
        if (str_contains($userAgent, 'Windows NT 6.1')) {
            return 'Windows 7';
        }
        if (str_contains($userAgent, 'Windows')) {
            return 'Windows';
        }
        if (str_contains($userAgent, 'Mac OS X')) {
            return 'macOS';
        }
        if (str_contains($userAgent, 'Linux')) {
            return 'Linux';
        }
        if (str_contains($userAgent, 'Android')) {
            return 'Android';
        }
        if (str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad')) {
            return 'iOS';
        }

        return 'Outro';
    }
}
