<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnexoChamado extends Model
{
    use HasFactory;

    protected $table = 'anexos_chamado';

    protected $fillable = [
        'chamado_id',
        'mensagem_id',
        'arquivo',
        'nome_original',
    ];

    public function chamado(): BelongsTo
    {
        return $this->belongsTo(Chamado::class);
    }

    public function mensagem(): BelongsTo
    {
        return $this->belongsTo(MensagemChamado::class);
    }
}
