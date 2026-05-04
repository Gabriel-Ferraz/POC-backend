<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SolicitacaoPagamento extends Model
{
    use HasFactory;

    protected $table = 'solicitacoes_pagamento';

    protected $fillable = [
        'numero',
        'empenho_id',
        'solicitante_id',
        'valor',
        'tipo_documento',
        'numero_documento',
        'serie',
        'data_emissao_documento',
        'observacao_documento',
        'forma_pagamento',
        'banco',
        'agencia',
        'digito_agencia',
        'conta',
        'digito_conta',
        'operacao',
        'cidade_banco',
        'observacao_pagamento',
        'status',
        'cancelada_em',
        'motivo_cancelamento',
        'paga_em',
        'pago_por_id',
        'valor_pago',
        'observacao_pagamento_realizado',
    ];

    protected $casts = [
        'empenho_id' => 'integer',
        'solicitante_id' => 'integer',
        'data_emissao_documento' => 'date',
        'cancelada_em' => 'date',
        'paga_em' => 'datetime',
        'valor' => 'decimal:2',
        'valor_pago' => 'decimal:2',
    ];

    public function empenho(): BelongsTo
    {
        return $this->belongsTo(Empenho::class);
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(AnexoSolicitacao::class, 'solicitacao_id');
    }

    public function tramites(): HasMany
    {
        return $this->hasMany(TramiteSolicitacao::class, 'solicitacao_id');
    }

    public function pagoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pago_por_id');
    }

    public function registrarTramite(
        string $fase,
        ?int $usuarioId = null,
        ?string $origem = null,
        ?string $destino = null,
        ?string $observacao = null,
        ?string $motivo = null
    ): void {
        $this->tramites()->create([
            'fase' => $fase,
            'usuario_id' => $usuarioId,
            'origem' => $origem,
            'destino' => $destino,
            'observacao' => $observacao,
            'motivo' => $motivo,
        ]);
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pendente' => 'Pendente',
            'aguardando_aprovacao_anexos' => 'Aguardando Aprovação dos Anexos',
            'anexos_recusados' => 'Anexos Recusados',
            'aguardando_autorizacao_gestor' => 'Aguardando Autorização do Gestor',
            'em_liquidacao' => 'Em Liquidação',
            'em_ordem_pagamento' => 'Em Ordem de Pagamento',
            'pagamento_em_remessa' => 'Pagamento em Remessa',
            'pagamento_realizado' => 'Pagamento Realizado',
            'cancelada' => 'Cancelada',
            // Admin statuses
            'rascunho' => 'Rascunho',
            'aguardando_aprovacao' => 'Aguardando Aprovação',
            'anexos' => 'Análise de Anexos',
            'fiscal' => 'Análise Fiscal',
            'gestor' => 'Aprovação do Gestor',
            'liquidacao' => 'Liquidação',
            'secretario' => 'Aprovação do Secretário',
            'iss' => 'Verificação ISS',
            'ordem_pagamento' => 'Ordem de Pagamento',
            'autorizacao' => 'Autorização',
            'bordero' => 'Borderô',
            'remessa' => 'Remessa Bancária',
            'pagamento' => 'Em Pagamento',
            'cancelado' => 'Cancelado',
        ];

        return $labels[$this->status] ?? ucfirst(str_replace('_', ' ', $this->status));
    }

    public function getAndamentoAttribute(): array
    {
        $etapas = [
            ['key' => 'solicitacao_pagamento', 'label' => 'Solicitação de Pagamento'],
            ['key' => 'anexos', 'label' => 'Anexos'],
            ['key' => 'fiscal', 'label' => 'Fiscal'],
            ['key' => 'gestor', 'label' => 'Gestor'],
            ['key' => 'liquidacao', 'label' => 'Liquidação'],
            ['key' => 'secretario', 'label' => 'Secretário(a)'],
            ['key' => 'iss', 'label' => 'ISS'],
            ['key' => 'ordem_pagamento', 'label' => 'Ordem de Pagamento'],
            ['key' => 'autorizacao', 'label' => 'Autorização'],
            ['key' => 'bordero', 'label' => 'Borderô'],
            ['key' => 'remessa', 'label' => 'Remessa'],
            ['key' => 'pagamento', 'label' => 'Pagamento'],
            ['key' => 'pagamento_realizado', 'label' => 'Pagamento Realizado'],
        ];

        return [
            'etapas' => array_map(function ($etapa) {
                return [
                    'key' => $etapa['key'],
                    'label' => $etapa['label'],
                    'status' => $this->getStatusEtapa($etapa['key']),
                ];
            }, $etapas),
        ];
    }

    private function getStatusEtapa(string $key): string
    {
        $etapasOrdenadas = [
            'solicitacao_pagamento',
            'anexos',
            'fiscal',
            'gestor',
            'liquidacao',
            'secretario',
            'iss',
            'ordem_pagamento',
            'autorizacao',
            'bordero',
            'remessa',
            'pagamento',
            'pagamento_realizado',
        ];

        $statusParaEtapa = [
            'pendente' => 'anexos',
            'aguardando_aprovacao_anexos' => 'anexos',
            'anexos_recusados' => 'anexos',
            'aguardando_autorizacao_gestor' => 'gestor',
            'em_liquidacao' => 'liquidacao',
            'em_ordem_pagamento' => 'ordem_pagamento',
            'pagamento_em_remessa' => 'pagamento',
            'pagamento_realizado' => 'pagamento_realizado',
            'cancelada' => null,
            // Statuses do admin (simplificados)
            'rascunho' => 'solicitacao_pagamento',
            'aguardando_aprovacao' => 'anexos',
            'anexos' => 'anexos',
            'fiscal' => 'fiscal',
            'gestor' => 'gestor',
            'liquidacao' => 'liquidacao',
            'secretario' => 'secretario',
            'iss' => 'iss',
            'ordem_pagamento' => 'ordem_pagamento',
            'autorizacao' => 'autorizacao',
            'bordero' => 'bordero',
            'remessa' => 'remessa',
            'pagamento' => 'pagamento',
            'cancelado' => null,
        ];

        $statusAtual = $this->status;
        $etapaAtual = $statusParaEtapa[$statusAtual] ?? null;

        if ($etapaAtual === null) {
            return 'pendente';
        }

        $indiceAtual = array_search($etapaAtual, $etapasOrdenadas);
        $indiceKey = array_search($key, $etapasOrdenadas);

        // pagamento_realizado é estado final — tudo concluído
        if ($statusAtual === 'pagamento_realizado') {
            return 'concluido';
        }

        if ($indiceKey < $indiceAtual) {
            return 'concluido';
        } elseif ($indiceKey === $indiceAtual) {
            return 'em_andamento';
        }

        return 'pendente';
    }
}
