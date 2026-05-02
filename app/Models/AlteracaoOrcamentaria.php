<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AlteracaoOrcamentaria extends Model
{
    use HasFactory;

    protected $table = 'alteracoes_orcamentarias';

    protected $fillable = [
        'lei_ato_id',
        'decreto_autorizador',
        'data_ato',
        'data_publicacao',
        'tipo_ato',
        'tipo_credito',
        'tipo_recurso',
        'valor_credito',
    ];

    protected $casts = [
        'data_ato' => 'date',
        'data_publicacao' => 'date',
        'valor_credito' => 'decimal:2',
    ];

    public function leiAto(): BelongsTo
    {
        return $this->belongsTo(LeiAto::class);
    }

    public function dotacoes(): HasMany
    {
        return $this->hasMany(DotacaoAlterada::class);
    }
}
