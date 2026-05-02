<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fornecedor extends Model
{
    use HasFactory;

    protected $table = 'fornecedores';

    protected $fillable = [
        'nome',
        'cnpj',
        'responsavel_tecnico_id',
    ];

    public function responsavelTecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsavel_tecnico_id');
    }

    public function contratos(): HasMany
    {
        return $this->hasMany(Contrato::class);
    }
}
