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
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Chamado::with(['usuario', 'mensagens']);

        if ($user->perfil !== 'gestor_suporte') {
            $query->where('usuario_id', $user->id);
        }

        if ($request->filled('id')) {
            $query->where('id', $request->id);
        }

        if ($request->filled('data_cadastro_inicio')) {
            $query->whereDate('created_at', '>=', $request->data_cadastro_inicio);
        }

        if ($request->filled('data_cadastro_fim')) {
            $query->whereDate('created_at', '<=', $request->data_cadastro_fim);
        }

        if ($request->filled('modulo')) {
            $query->where('modulo', 'like', '%' . $request->modulo . '%');
        }

        if ($request->filled('usuario_origem') && $user->perfil === 'gestor_suporte') {
            $query->whereHas('usuario', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->usuario_origem . '%');
            });
        }

        if ($request->filled('assunto')) {
            $query->where('assunto', 'like', '%' . $request->assunto . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $chamados = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'chamados' => $chamados->map(function ($chamado) {
                return [
                    'id' => $chamado->id,
                    'modulo' => $chamado->modulo,
                    'assunto' => $chamado->assunto,
                    'usuario' => $chamado->usuario->name,
                    'status' => $chamado->status,
                    'data_cadastro' => $chamado->created_at->format('d/m/Y H:i'),
                    'data_resposta' => $chamado->respondido_em?->format('d/m/Y H:i'),
                    'total_mensagens' => $chamado->mensagens->count() + 1,
                ];
            }),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'modulo' => 'required|string',
            'assunto' => 'required|string|max:255',
            'mensagem' => 'required|string',
            'anexos' => 'nullable|array',
            'anexos.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $chamado = Chamado::create([
                'usuario_id' => $request->user()->id,
                'modulo' => $request->modulo,
                'assunto' => $request->assunto,
                'mensagem' => $request->mensagem,
                'status' => 'aberto',
            ]);

            if ($request->hasFile('anexos')) {
                foreach ($request->file('anexos') as $arquivo) {
                    $path = $arquivo->store('anexos/chamados', 'public');
                    $chamado->anexos()->create([
                        'arquivo' => $path,
                        'nome_original' => $arquivo->getClientOriginalName(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Chamado criado com sucesso',
                'chamado' => [
                    'id' => $chamado->id,
                    'numero' => $chamado->id,
                    'status' => $chamado->status,
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
            'anexos',
        ])->findOrFail($id);

        $user = $request->user();

        if ($user->perfil !== 'gestor_suporte' && $chamado->usuario_id !== $user->id) {
            return response()->json([
                'message' => 'Não autorizado',
            ], 403);
        }

        $timeline = collect([
            [
                'tipo' => 'abertura',
                'usuario' => $chamado->usuario->name,
                'mensagem' => $chamado->mensagem,
                'data' => $chamado->created_at->format('d/m/Y H:i:s'),
                'anexos' => $chamado->anexos->where('mensagem_id', null)->map(function ($anexo) {
                    return [
                        'id' => $anexo->id,
                        'nome' => $anexo->nome_original,
                        'arquivo' => $anexo->arquivo,
                    ];
                }),
            ],
        ]);

        foreach ($chamado->mensagens as $mensagem) {
            $timeline->push([
                'tipo' => 'resposta',
                'usuario' => $mensagem->usuario->name,
                'mensagem' => $mensagem->mensagem,
                'data' => $mensagem->created_at->format('d/m/Y H:i:s'),
                'anexos' => $chamado->anexos->where('mensagem_id', $mensagem->id)->map(function ($anexo) {
                    return [
                        'id' => $anexo->id,
                        'nome' => $anexo->nome_original,
                        'arquivo' => $anexo->arquivo,
                    ];
                }),
            ]);
        }

        return response()->json([
            'chamado' => [
                'id' => $chamado->id,
                'modulo' => $chamado->modulo,
                'assunto' => $chamado->assunto,
                'usuario' => $chamado->usuario->name,
                'status' => $chamado->status,
                'data_cadastro' => $chamado->created_at->format('d/m/Y H:i'),
                'data_resposta' => $chamado->respondido_em?->format('d/m/Y H:i'),
                'data_conclusao' => $chamado->concluido_em?->format('d/m/Y H:i'),
            ],
            'timeline' => $timeline,
        ]);
    }

    public function responder(Request $request, int $id): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'mensagem' => 'required|string',
            'anexos' => 'nullable|array',
            'anexos.*' => 'file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        $chamado = Chamado::findOrFail($id);

        if ($chamado->status === 'concluido') {
            return response()->json([
                'message' => 'Chamado já foi concluído',
            ], 400);
        }

        DB::beginTransaction();

        try {
            $mensagem = $chamado->mensagens()->create([
                'usuario_id' => $request->user()->id,
                'mensagem' => $request->mensagem,
            ]);

            if ($request->hasFile('anexos')) {
                foreach ($request->file('anexos') as $arquivo) {
                    $path = $arquivo->store('anexos/chamados', 'public');
                    $chamado->anexos()->create([
                        'mensagem_id' => $mensagem->id,
                        'arquivo' => $path,
                        'nome_original' => $arquivo->getClientOriginalName(),
                    ]);
                }
            }

            if ($chamado->status === 'aberto') {
                $chamado->update([
                    'status' => 'em_atendimento',
                    'respondido_em' => now(),
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Resposta enviada com sucesso',
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
                'concluido_em' => now(),
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
}
