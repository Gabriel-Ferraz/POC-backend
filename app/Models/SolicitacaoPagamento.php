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
        'observacao',
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
        'status',
        'cancelada_em',
        'motivo_cancelamento',
        'paga_em',
    ];

    protected $casts = [
        'data_emissao_documento' => 'date',
        'cancelada_em' => 'datetime',
        'paga_em' => 'datetime',
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

    public function registrarTramite(string $fase, ?int $usuarioId = null, ?string $observacao = null, ?string $motivo = null): void
    {
        $this->tramites()->create([
            'fase' => $fase,
            'usuario_id' => $usuarioId,
            'observacao' => $observacao,
            'motivo' => $motivo,
        ]);
    }
}
