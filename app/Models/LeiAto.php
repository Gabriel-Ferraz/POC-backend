<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeiAto extends Model
{
    use HasFactory;

    protected $table = 'leis_atos';

    protected $fillable = [
        'numero',
        'tipo',
        'data_ato',
        'data_publicacao',
        'descricao',
        'arquivo',
    ];

    protected $casts = [
        'data_ato' => 'date',
        'data_publicacao' => 'date',
    ];

    public function alteracoesOrcamentarias(): HasMany
    {
        return $this->hasMany(AlteracaoOrcamentaria::class);
    }
}
