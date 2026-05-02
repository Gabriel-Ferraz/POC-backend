<?php

namespace App\Http\Controllers;

use App\Models\ExportacaoPrestacaoContas;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PrestacaoContasController extends Controller
{
    public function exportar(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ano' => 'required|integer|min:2020|max:' . (date('Y') + 1),
            'modulo' => 'required|string',
            'tipo_geracao' => 'required|string',
            'mes' => 'nullable|integer|min:1|max:12',
            'arquivos_selecionados' => 'required|array|min:1',
            'arquivos_selecionados.*' => 'required|string|in:PlanoContabil,MovimentoMensal,Balancete,Receita,Despesa',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dados inválidos',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $conteudo = $this->gerarConteudoArquivo(
                $request->ano,
                $request->modulo,
                $request->tipo_geracao,
                $request->mes,
                $request->arquivos_selecionados
            );

            $nomeArquivo = 'prestacao_contas_' . $request->ano . '_' . $request->modulo . '_' . time() . '.txt';
            $path = 'prestacao-contas/' . $nomeArquivo;

            Storage::disk('public')->put($path, $conteudo);

            $quantidadeRegistros = substr_count($conteudo, "\n");

            $exportacao = ExportacaoPrestacaoContas::create([
                'usuario_id' => $request->user()->id,
                'ano' => $request->ano,
                'modulo' => $request->modulo,
                'tipo_geracao' => $request->tipo_geracao,
                'mes' => $request->mes,
                'arquivos_selecionados' => $request->arquivos_selecionados,
                'arquivo_gerado' => $path,
                'quantidade_registros' => $quantidadeRegistros,
            ]);

            return response()->json([
                'message' => 'Exportação realizada com sucesso',
                'exportacao' => [
                    'id' => $exportacao->id,
                    'arquivo' => $nomeArquivo,
                    'data_exportacao' => $exportacao->created_at->format('d/m/Y H:i'),
                    'quantidade_registros' => $quantidadeRegistros,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao gerar exportação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function exportacoes(Request $request): JsonResponse
    {
        $exportacoes = ExportacaoPrestacaoContas::where('usuario_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'exportacoes' => $exportacoes->map(function ($exp) {
                return [
                    'id' => $exp->id,
                    'ano' => $exp->ano,
                    'modulo' => $exp->modulo,
                    'tipo_geracao' => $exp->tipo_geracao,
                    'mes' => $exp->mes,
                    'arquivos' => $exp->arquivos_selecionados,
                    'arquivo_gerado' => basename($exp->arquivo_gerado),
                    'quantidade_registros' => $exp->quantidade_registros,
                    'data' => $exp->created_at->format('d/m/Y H:i'),
                ];
            }),
        ]);
    }

    public function download(Request $request, int $id): JsonResponse
    {
        $exportacao = ExportacaoPrestacaoContas::where('usuario_id', $request->user()->id)
            ->findOrFail($id);

        if (!Storage::disk('public')->exists($exportacao->arquivo_gerado)) {
            return response()->json([
                'message' => 'Arquivo não encontrado',
            ], 404);
        }

        return response()->download(
            Storage::disk('public')->path($exportacao->arquivo_gerado),
            basename($exportacao->arquivo_gerado)
        );
    }

    private function gerarConteudoArquivo(int $ano, string $modulo, string $tipoGeracao, ?int $mes, array $arquivos): string
    {
        $conteudo = "# PRESTAÇÃO DE CONTAS - SIM-AM\n";
        $conteudo .= "# Ano: {$ano}\n";
        $conteudo .= "# Módulo: {$modulo}\n";
        $conteudo .= "# Tipo de Geração: {$tipoGeracao}\n";

        if ($mes) {
            $conteudo .= "# Mês: {$mes}\n";
        }

        $conteudo .= "# Gerado em: " . date('d/m/Y H:i:s') . "\n";
        $conteudo .= "#" . str_repeat('-', 78) . "\n\n";

        foreach ($arquivos as $tipoArquivo) {
            $conteudo .= $this->gerarDadosPorTipo($tipoArquivo, $ano, $mes);
        }

        return $conteudo;
    }

    private function gerarDadosPorTipo(string $tipo, int $ano, ?int $mes): string
    {
        $dados = "### {$tipo}\n";

        switch ($tipo) {
            case 'PlanoContabil':
                $dados .= "01|1.1.1.1.1.00.00|DISPONIBILIDADES|A|100000.00\n";
                $dados .= "02|1.1.1.2.1.00.00|CRÉDITOS A RECEBER|A|50000.00\n";
                $dados .= "03|2.1.1.1.1.00.00|OBRIGAÇÕES A PAGAR|P|75000.00\n";
                break;

            case 'MovimentoMensal':
                $dados .= "01|{$ano}|" . ($mes ?? 1) . "|1.1.1.1.1.00.00|D|25000.00\n";
                $dados .= "02|{$ano}|" . ($mes ?? 1) . "|1.1.1.2.1.00.00|C|15000.00\n";
                break;

            case 'Balancete':
                $dados .= "01|{$ano}|" . ($mes ?? 12) . "|1.1.1.1.1.00.00|125000.00|25000.00|100000.00\n";
                $dados .= "02|{$ano}|" . ($mes ?? 12) . "|2.1.1.1.1.00.00|75000.00|0.00|75000.00\n";
                break;

            case 'Receita':
                $dados .= "01|{$ano}|1.1.1.1|Receitas Correntes|500000.00|450000.00\n";
                $dados .= "02|{$ano}|1.1.1.2|Receitas de Capital|200000.00|180000.00\n";
                break;

            case 'Despesa':
                $dados .= "01|{$ano}|3.3.90.30|Material de Consumo|100000.00|85000.00\n";
                $dados .= "02|{$ano}|3.1.90.11|Vencimentos e Vantagens|300000.00|280000.00\n";
                break;
        }

        $dados .= "\n";

        return $dados;
    }
}
