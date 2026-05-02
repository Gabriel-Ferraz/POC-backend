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
        'status',
        'data_ultima_resposta',
        'data_conclusao',
        'navegador',
        'sistema_operacional',
        'ip_origem',
        'user_agent',
    ];

    protected $casts = [
        'data_ultima_resposta' => 'datetime',
        'data_conclusao' => 'datetime',
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
