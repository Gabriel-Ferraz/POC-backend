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
    ];

    protected $casts = [
        'empenho_id' => 'integer',
        'solicitante_id' => 'integer',
        'data_emissao_documento' => 'date',
        'cancelada_em' => 'date',
        'paga_em' => 'date',
        'valor' => 'decimal:2',
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
        $mapa = [
            'pendente' => [
                'solicitacao_pagamento' => 'em_andamento',
            ],
            'aguardando_aprovacao_anexos' => [
                'solicitacao_pagamento' => 'concluido',
                'anexos' => 'em_andamento',
            ],
            'anexos_recusados' => [
                'solicitacao_pagamento' => 'concluido',
                'anexos' => 'em_andamento',
            ],
            'aguardando_autorizacao_gestor' => [
                'solicitacao_pagamento' => 'concluido',
                'anexos' => 'concluido',
                'fiscal' => 'concluido',
                'gestor' => 'em_andamento',
            ],
            'em_liquidacao' => [
                'solicitacao_pagamento' => 'concluido',
                'anexos' => 'concluido',
                'fiscal' => 'concluido',
                'gestor' => 'concluido',
                'liquidacao' => 'em_andamento',
            ],
            'em_ordem_pagamento' => [
                'solicitacao_pagamento' => 'concluido',
                'anexos' => 'concluido',
                'fiscal' => 'concluido',
                'gestor' => 'concluido',
                'liquidacao' => 'concluido',
                'secretario' => 'concluido',
                'iss' => 'concluido',
                'ordem_pagamento' => 'em_andamento',
            ],
            'pagamento_em_remessa' => [
                'solicitacao_pagamento' => 'concluido',
                'anexos' => 'concluido',
                'fiscal' => 'concluido',
                'gestor' => 'concluido',
                'liquidacao' => 'concluido',
                'secretario' => 'concluido',
                'iss' => 'concluido',
                'ordem_pagamento' => 'concluido',
                'autorizacao' => 'concluido',
                'bordero' => 'concluido',
                'remessa' => 'concluido',
                'pagamento' => 'em_andamento',
            ],
            'pagamento_realizado' => [
                'solicitacao_pagamento' => 'concluido',
                'anexos' => 'concluido',
                'fiscal' => 'concluido',
                'gestor' => 'concluido',
                'liquidacao' => 'concluido',
                'secretario' => 'concluido',
                'iss' => 'concluido',
                'ordem_pagamento' => 'concluido',
                'autorizacao' => 'concluido',
                'bordero' => 'concluido',
                'remessa' => 'concluido',
                'pagamento' => 'concluido',
                'pagamento_realizado' => 'concluido',
            ],
        ];

        $statusAtual = $this->status;

        if (isset($mapa[$statusAtual][$key])) {
            return $mapa[$statusAtual][$key];
        }

        return 'pendente';
    }
}
