<?php

namespace App\Http\Controllers;

use App\Models\Empenho;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FornecedorController extends Controller
{
    public function empenhos(Request $request): JsonResponse
    {
        $user = $request->user()->load('fornecedor');

        if (!$user->fornecedor) {
            return response()->json([
                'message' => 'Usuário não vinculado a nenhum fornecedor',
            ], 404);
        }

        $empenhos = Empenho::whereHas('contrato', function ($query) use ($user) {
            $query->where('fornecedor_id', $user->fornecedor->id);
        })
            ->with(['contrato'])
            ->orderBy('data_emissao', 'desc')
            ->get();

        return response()->json([
            'fornecedor' => [
                'id' => $user->fornecedor->id,
                'nome' => $user->fornecedor->nome,
                'cnpj' => $user->fornecedor->cnpj,
            ],
            'empenhos' => $empenhos->map(function ($empenho) {
                return [
                    'id' => $empenho->id,
                    'numero' => $empenho->numero,
                    'contrato' => $empenho->contrato->numero,
                    'data_emissao' => $empenho->data_emissao->format('d/m/Y'),
                    'valor' => $empenho->valor,
                    'saldo' => $empenho->saldo,
                    'status' => $empenho->status,
                ];
            }),
        ]);
    }

    public function showEmpenho(Request $request, int $id): JsonResponse
    {
        $user = $request->user()->load('fornecedor');

        if (!$user->fornecedor) {
            return response()->json([
                'message' => 'Usuário não vinculado a nenhum fornecedor',
            ], 404);
        }

        $empenho = Empenho::whereHas('contrato', function ($query) use ($user) {
            $query->where('fornecedor_id', $user->fornecedor->id);
        })
            ->with(['contrato', 'solicitacoes'])
            ->findOrFail($id);

        return response()->json([
            'empenho' => [
                'id' => $empenho->id,
                'numero' => $empenho->numero,
                'contrato' => $empenho->contrato->numero,
                'data_emissao' => $empenho->data_emissao->format('d/m/Y'),
                'valor' => $empenho->valor,
                'saldo' => $empenho->saldo,
                'status' => $empenho->status,
                'total_solicitacoes' => $empenho->solicitacoes->count(),
            ],
        ]);
    }
}
