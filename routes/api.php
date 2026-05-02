<?php

use App\Http\Controllers\{
    AnexoController,
    ChamadoController,
    FornecedorController,
    GestorController,
    OrcamentarioController,
    PrestacaoContasController,
    SolicitacaoPagamentoController,
};
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/version', function () {
    return config('app.name') . ' version ' . config('app.version');
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')
    ->group(function () {
        Route::prefix('auth')
            ->group(function () {
                Route::post('/logout', [AuthController::class, 'logout']);
                Route::get('/me', [AuthController::class, 'me']);
            });

        // Portal do Fornecedor
        Route::prefix('fornecedor')
            ->group(function () {
                Route::get('/empenhos', [FornecedorController::class, 'empenhos']);
                Route::get('/empenhos/{id}', [FornecedorController::class, 'showEmpenho']);
            });

        // Solicitações de Pagamento
        Route::prefix('empenhos/{empenhoId}/solicitacoes')
            ->group(function () {
                Route::get('/', [SolicitacaoPagamentoController::class, 'index']);
                Route::post('/', [SolicitacaoPagamentoController::class, 'store']);
            });

        Route::prefix('solicitacoes')
            ->group(function () {
                Route::get('/{id}', [SolicitacaoPagamentoController::class, 'show']);
                Route::post('/{id}/cancelar', [SolicitacaoPagamentoController::class, 'cancelar']);
                Route::get('/{id}/tramites', [SolicitacaoPagamentoController::class, 'tramites']);
            });

        // Anexos
        Route::prefix('solicitacoes/{solicitacaoId}/anexos')
            ->group(function () {
                Route::get('/', [AnexoController::class, 'index']);
                Route::post('/enviar-todos', [AnexoController::class, 'enviarTodos']); // ANTES das rotas com {anexoId}
                Route::post('/{anexoId}/upload', [AnexoController::class, 'upload']);
                Route::post('/{anexoId}', [AnexoController::class, 'remover']);
                Route::delete('/{anexoId}', [AnexoController::class, 'remover']);
                Route::get('/{anexoId}/download', [AnexoController::class, 'download']);
            });

        Route::prefix('anexos')
            ->group(function () {
                Route::post('/{anexoId}/aprovar', [AnexoController::class, 'aprovar']);
                Route::post('/{anexoId}/recusar', [AnexoController::class, 'recusar']);
            });

        // Gestor - Aprovação de Anexos
        Route::prefix('gestor')
            ->group(function () {
                Route::get('/solicitacoes-pendentes', [GestorController::class, 'solicitacoesPendentes']);
                Route::get('/solicitacoes/{id}', [GestorController::class, 'solicitacaoDetalhe']);
            });

        // Suporte ao Usuário
        Route::prefix('chamados')
            ->group(function () {
                Route::get('/', [ChamadoController::class, 'index']);
                Route::post('/', [ChamadoController::class, 'store']);
                Route::get('/{id}', [ChamadoController::class, 'show']);
                Route::post('/{id}/responder', [ChamadoController::class, 'responder']);
                Route::post('/{id}/anexos', [ChamadoController::class, 'anexar']);
                Route::post('/{id}/concluir', [ChamadoController::class, 'concluir']);
            });

        // Prestação de Contas
        Route::prefix('prestacao-contas')
            ->group(function () {
                Route::post('/exportar', [PrestacaoContasController::class, 'exportar']);
                Route::get('/exportacoes', [PrestacaoContasController::class, 'exportacoes']);
                Route::get('/exportacoes/{id}/download', [PrestacaoContasController::class, 'download']);
            });

        // Orçamentário
        Route::prefix('orcamentario')
            ->group(function () {
                Route::prefix('leis-atos')
                    ->group(function () {
                        Route::get('/', [OrcamentarioController::class, 'indexLeisAtos']);
                        Route::post('/', [OrcamentarioController::class, 'storeLeiAto']);
                        Route::put('/{id}', [OrcamentarioController::class, 'updateLeiAto']);
                        Route::delete('/{id}', [OrcamentarioController::class, 'destroyLeiAto']);
                    });

                Route::prefix('alteracoes')
                    ->group(function () {
                        Route::get('/', [OrcamentarioController::class, 'indexAlteracoes']);
                        Route::post('/', [OrcamentarioController::class, 'storeAlteracao']);
                        Route::get('/{id}', [OrcamentarioController::class, 'showAlteracao']);
                        Route::post('/{id}/dotacoes', [OrcamentarioController::class, 'adicionarDotacao']);
                        Route::get('/{id}/pdf', [OrcamentarioController::class, 'gerarPdf']);
                    });
            });
    });
