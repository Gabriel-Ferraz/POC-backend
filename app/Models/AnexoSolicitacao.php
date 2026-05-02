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
        'arquivo_path',
        'arquivo_nome',
        'status',
        'data_envio',
        'motivo_recusa',
        'aprovado_por',
        'data_aprovacao',
        'enviado_por_usuario_id',
        'enviado_em',
    ];

    protected $casts = [
        'data_envio' => 'date',
        'data_aprovacao' => 'datetime',
        'enviado_em' => 'datetime',
    ];

    public function solicitacao(): BelongsTo
    {
        return $this->belongsTo(SolicitacaoPagamento::class, 'solicitacao_id');
    }

    public function aprovador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprovado_por');
    }

    public function enviadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por_usuario_id');
    }

    public function getTipoAnexoLabelAttribute(): string
    {
        $labels = [
            'documento_fiscal' => 'Documento Fiscal (NF, Recibo, Guias, Faturas, etc.)',
            'certidao_negativa_debitos' => 'Certidão Negativa de Débitos',
            'certidao_tributaria' => 'Certidão Tributária',
            'guia_previdencia_social' => 'Guia de Previdência Social',
            'fgts' => 'FGTS',
        ];

        return $labels[$this->tipo_anexo] ?? $this->tipo_anexo;
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'pendente' => 'Pendente',
            'anexo_cadastrado' => 'Anexo Cadastrado',
            'aguardando_aprovacao' => 'Aguardando Aprovação',
            'aprovado' => 'Aprovado',
            'recusado' => 'Recusado',
        ];

        return $labels[$this->status] ?? $this->status;
    }
}
