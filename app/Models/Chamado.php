<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chamado extends Model
{
    use HasFactory;

    protected $fillable = [
        'usuario_id',
        'modulo',
        'assunto',
        'mensagem',
        'status',
        'respondido_em',
        'concluido_em',
    ];

    protected $casts = [
        'respondido_em' => 'datetime',
        'concluido_em' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mensagens(): HasMany
    {
        return $this->hasMany(MensagemChamado::class);
    }

    public function anexos(): HasMany
    {
        return $this->hasMany(AnexoChamado::class);
    }
}
