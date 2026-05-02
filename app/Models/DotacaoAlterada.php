<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DotacaoAlterada extends Model
{
    use HasFactory;

    protected $table = 'dotacoes_alteradas';

    protected $fillable = [
        'alteracao_orcamentaria_id',
        'dotacao_orcamentaria',
        'conta_receita',
        'valor_suprimido',
        'valor_suplementado',
        'saldo_atual',
        'novo_saldo',
    ];

    protected $casts = [
        'valor_suprimido' => 'decimal:2',
        'valor_suplementado' => 'decimal:2',
        'saldo_atual' => 'decimal:2',
        'novo_saldo' => 'decimal:2',
    ];

    public function alteracaoOrcamentaria(): BelongsTo
    {
        return $this->belongsTo(AlteracaoOrcamentaria::class);
    }
}
