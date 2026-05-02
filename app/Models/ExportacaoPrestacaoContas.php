<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExportacaoPrestacaoContas extends Model
{
    use HasFactory;

    protected $table = 'exportacoes_prestacao_contas';

    protected $fillable = [
        'usuario_id',
        'ano',
        'modulo',
        'tipo_geracao',
        'mes',
        'arquivos_selecionados',
        'arquivo_gerado',
        'quantidade_registros',
    ];

    protected $casts = [
        'arquivos_selecionados' => 'array',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
