<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empenho extends Model
{
    use HasFactory;

    protected $fillable = [
        'numero',
        'contrato_id',
        'data_emissao',
        'valor',
        'saldo',
        'status',
    ];

    protected $casts = [
        'data_emissao' => 'date',
        'valor' => 'decimal:2',
        'saldo' => 'decimal:2',
    ];

    public function contrato(): BelongsTo
    {
        return $this->belongsTo(Contrato::class);
    }

    public function solicitacoes(): HasMany
    {
        return $this->hasMany(SolicitacaoPagamento::class);
    }

    public function bloquearSaldo(float $valor): void
    {
        $this->saldo -= $valor;
        $this->status = $this->saldo <= 0 ? 'sem_saldo' : 'bloqueado';
        $this->save();
    }

    public function liberarSaldo(float $valor): void
    {
        $this->saldo += $valor;
        $this->status = $this->saldo > 0 ? 'disponivel' : 'sem_saldo';
        $this->save();
    }
}
