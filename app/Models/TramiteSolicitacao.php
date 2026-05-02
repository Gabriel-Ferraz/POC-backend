<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TramiteSolicitacao extends Model
{
    use HasFactory;

    protected $table = 'tramites_solicitacao';

    protected $fillable = [
        'solicitacao_id',
        'fase',
        'origem',
        'destino',
        'usuario_id',
        'observacao',
        'motivo',
    ];

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoPagamento::class, 'solicitacao_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
