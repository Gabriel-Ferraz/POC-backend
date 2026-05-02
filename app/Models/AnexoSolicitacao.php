<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnexoSolicitacao extends Model
{
    use HasFactory;

    protected $table = 'anexos_solicitacao';

    protected $fillable = [
        'solicitacao_id',
        'tipo_anexo',
        'arquivo',
        'status',
        'motivo_recusa',
        'avaliado_por',
        'avaliado_em',
    ];

    protected $casts = [
        'avaliado_em' => 'datetime',
    ];

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoPagamento::class, 'solicitacao_id');
    }

    public function avaliador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'avaliado_por');
    }
}
