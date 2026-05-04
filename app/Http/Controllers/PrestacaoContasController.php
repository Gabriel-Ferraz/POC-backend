<?php

namespace App\Http\Controllers;

use App\Models\SimamExport;
use App\Models\SimamGeneratedFile;
use App\Models\SimamLayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class PrestacaoContasController extends Controller
{
    /**
     * Listar layouts disponíveis
     */
    public function listarLayouts(Request $request): JsonResponse
    {
        $module = $request->query('module');
        $generationType = $request->query('generation_type');

        $query = SimamLayout::where('active', true);

        if ($module) {
            $query->where('module', $module);
        }

        if ($generationType) {
            $query->where('generation_type', $generationType);
        }

        $layouts = $query->orderBy('order_index')->get();

        return response()->json([
            'layouts' => $layouts->map(function ($layout) {
                return [
                    'id' => $layout->id,
                    'key' => $layout->key,
                    'nome' => $layout->name,
                    'modulo' => $layout->module,
                    'ordem' => $layout->order_index,
                    'ativo' => $layout->active,
                ];
            }),
        ]);
    }

    /**
     * Exportar prestação de contas
     */
    public function exportar(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:'.(date('Y') + 1),
            'module' => 'required|string|in:contabilidade',
            'generationType' => 'required|string|in:abertura,diario,fechamento,mensal',
            'month' => 'required_if:generationType,mensal|nullable|integer|min:1|max:12',
            'onlyActive' => 'nullable|boolean',
            'files' => 'required|array|min:1',
        ]);

        // Processar files: pode ser array de strings ou array de objetos com key
        $fileKeys = collect($validated['files'])->map(function ($file) {
            if (is_string($file)) {
                return $file;
            }
            if (is_array($file) && isset($file['key'])) {
                return $file['key'];
            }
            if (is_object($file) && isset($file->key)) {
                return $file->key;
            }
            return null;
        })->filter()->toArray();

        if (empty($fileKeys)) {
            return response()->json([
                'message' => 'Nenhum arquivo válido selecionado',
            ], 422);
        }

        DB::beginTransaction();

        try {
            // Criar registro de exportação
            $export = SimamExport::create([
                'year' => $validated['year'],
                'month' => $validated['month'] ?? null,
                'module' => $validated['module'],
                'generation_type' => $validated['generationType'],
                'only_active' => $validated['onlyActive'] ?? true,
                'status' => 'processando',
                'created_by' => Auth::id(),
            ]);

            // Buscar layouts selecionados
            $layouts = SimamLayout::whereIn('key', $fileKeys)
                ->where('active', true)
                ->orderBy('order_index')
                ->get();

            if ($layouts->isEmpty()) {
                throw new \Exception('Nenhum layout válido selecionado');
            }

            $generatedFiles = [];
            $filePaths = [];

            // Gerar cada arquivo
            $filesDir = storage_path('app/prestacao-contas/files/'.$export->id);
            if (!file_exists($filesDir)) {
                mkdir($filesDir, 0755, true);
            }

            foreach ($layouts as $layout) {
                $fileName = $layout->key.'.txt';
                $content = $this->generateFileContent($layout, $validated);
                $recordsCount = substr_count($content, "\n") - 1; // -1 para não contar o cabeçalho

                $filePath = $filesDir.'/'.$fileName;
                file_put_contents($filePath, $content);
                $filePaths[] = $filePath;

                // Registrar arquivo gerado
                $generatedFile = SimamGeneratedFile::create([
                    'export_id' => $export->id,
                    'layout_key' => $layout->key,
                    'file_name' => $fileName,
                    'status' => 'gerado',
                    'records_count' => $recordsCount,
                    'file_path' => $filePath,
                ]);

                $generatedFiles[] = $generatedFile;
            }

            // Criar ZIP
            $zipName = sprintf(
                '%s_%s_%s_%s.zip',
                $export->id,
                $validated['module'],
                $validated['generationType'],
                $validated['year']
            );

            $zipPath = storage_path('app/prestacao-contas/'.$zipName);
            if (!file_exists(dirname($zipPath))) {
                mkdir(dirname($zipPath), 0755, true);
            }

            $zip = new ZipArchive;
            if ($zip->open($zipPath, ZipArchive::CREATE) === true) {
                foreach ($filePaths as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();
            }

            // Atualizar exportação
            $export->update([
                'zip_name' => $zipName,
                'zip_path' => $zipPath,
                'status' => 'sucesso',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Exportação realizada com sucesso',
                'exportacao' => [
                    'id' => $export->id,
                    'zipName' => $zipName,
                    'status' => 'sucesso',
                    'arquivos' => array_map(function ($file) {
                        return [
                            'id' => $file->id,
                            'nome' => $file->file_name,
                            'status' => $file->status,
                            'quantidadeRegistros' => $file->records_count,
                            'geradoEm' => $file->created_at->format('d/m/Y H:i:s'),
                        ];
                    }, $generatedFiles),
                    'createdAt' => $export->created_at->format('d/m/Y H:i:s'),
                ],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($export)) {
                $export->update([
                    'status' => 'erro',
                    'error_message' => $e->getMessage(),
                ]);
            }

            return response()->json([
                'message' => 'Erro ao gerar exportação',
                'error' => config('app.debug') ? $e->getMessage() : 'Erro interno do servidor',
            ], 500);
        }
    }

    /**
     * Listar exportações
     */
    public function listarExportacoes(Request $request): JsonResponse
    {
        $exportacoes = SimamExport::with('generatedFiles')
            ->where('created_by', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return response()->json([
            'exportacoes' => $exportacoes->map(function ($export) {
                return [
                    'id' => $export->id,
                    'year' => $export->year,
                    'month' => $export->month,
                    'module' => $export->module,
                    'generationType' => $export->generation_type,
                    'zipName' => $export->zip_name,
                    'status' => $export->status,
                    'arquivos' => $export->generatedFiles->map(function ($file) {
                        return [
                            'id' => $file->id,
                            'nome' => $file->file_name,
                            'status' => $file->status,
                            'quantidadeRegistros' => $file->records_count,
                        ];
                    }),
                    'createdAt' => $export->created_at->format('d/m/Y H:i:s'),
                ];
            }),
        ]);
    }

    /**
     * Download do ZIP
     */
    public function downloadZip(Request $request, int $id): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        // Autenticar via token na query string se fornecido
        $user = $this->authenticateFromToken($request);

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $export = SimamExport::where('created_by', $user->id)->findOrFail($id);

        if (!file_exists($export->zip_path)) {
            return response()->json(['message' => 'Arquivo não encontrado'], 404);
        }

        return response()->download($export->zip_path, $export->zip_name);
    }

    /**
     * Download de arquivo individual
     */
    public function downloadArquivo(Request $request, int $exportId, int $arquivoId): \Symfony\Component\HttpFoundation\BinaryFileResponse|JsonResponse
    {
        // Autenticar via token na query string se fornecido
        $user = $this->authenticateFromToken($request);

        if (!$user) {
            return response()->json(['message' => 'Não autenticado'], 401);
        }

        $export = SimamExport::where('created_by', $user->id)->findOrFail($exportId);
        $file = $export->generatedFiles()->findOrFail($arquivoId);

        if (!file_exists($file->file_path)) {
            return response()->json(['message' => 'Arquivo não encontrado'], 404);
        }

        return response()->download($file->file_path, $file->file_name);
    }

    /**
     * Reordenar layouts
     */
    public function reordenarLayouts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'layoutIds' => 'required|array|min:1',
            'layoutIds.*' => 'required|integer|exists:simam_layouts,id',
        ]);

        DB::beginTransaction();

        try {
            foreach ($validated['layoutIds'] as $index => $layoutId) {
                SimamLayout::where('id', $layoutId)->update(['order_index' => $index]);
            }

            DB::commit();

            return response()->json(['message' => 'Ordem atualizada com sucesso']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erro ao reordenar layouts',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Autenticar usuário via token (header ou query string)
     */
    private function authenticateFromToken(Request $request): ?\App\Models\User
    {
        // Tentar pegar token do header primeiro
        $token = $request->bearerToken();

        // Se não houver no header, tentar pegar da query string
        if (!$token) {
            $token = $request->query('token');
        }

        if (!$token) {
            return null;
        }

        // Buscar token no banco
        $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);

        if (!$accessToken) {
            return null;
        }

        return $accessToken->tokenable;
    }

    /**
     * Gerar conteúdo do arquivo baseado no layout
     */
    private function generateFileContent(SimamLayout $layout, array $params): string
    {
        $content = "# {$layout->name}\n";
        $content .= "# Gerado em: ".date('d/m/Y H:i:s')."\n";
        $content .= "# Ano: {$params['year']}\n";

        if (isset($params['month'])) {
            $content .= "# Mês: {$params['month']}\n";
        }

        $content .= "#".str_repeat('-', 78)."\n";

        // Gerar dados mockados baseado no tipo de layout
        $rows = $this->generateMockData($layout->key, $params);

        foreach ($rows as $row) {
            $content .= implode('|', $row)."\n";
        }

        return $content;
    }

    /**
     * Gerar dados mockados para POC
     */
    private function generateMockData(string $layoutKey, array $params): array
    {
        $year = $params['year'];
        $month = $params['month'] ?? 1;

        switch ($layoutKey) {
            case 'plano_contabil':
                return [
                    ['01', '1.1.1.0.00.00', 'Caixa e Equivalentes de Caixa', 'ANALITICA', '100000.00'],
                    ['02', '1.1.2.0.00.00', 'Créditos a Receber', 'ANALITICA', '50000.00'],
                    ['03', '2.1.1.0.00.00', 'Obrigações a Pagar', 'ANALITICA', '75000.00'],
                ];

            case 'movimento_contabil_mensal':
                return [
                    ['01', $year, $month, '1.1.1.0.00.00', 'D', '25000.00'],
                    ['02', $year, $month, '1.1.2.0.00.00', 'C', '15000.00'],
                    ['03', $year, $month, '2.1.1.0.00.00', 'D', '10000.00'],
                ];

            case 'diario_contabil':
                return [
                    ['01', $year, $month, '01', 'Lançamento de abertura', '1.1.1.0.00.00', 'D', '100000.00'],
                    ['02', $year, $month, '01', 'Lançamento de abertura', '3.1.1.0.00.00', 'C', '100000.00'],
                ];

            case 'movimento_realizavel':
                return [
                    ['01', $year, $month, '1000', 'Receitas Correntes', '500000.00', '450000.00'],
                    ['02', $year, $month, '2000', 'Receitas de Capital', '200000.00', '180000.00'],
                ];

            default:
                return [
                    ['01', $year, $month, 'Dado genérico 1', '1000.00'],
                    ['02', $year, $month, 'Dado genérico 2', '2000.00'],
                ];
        }
    }
}
