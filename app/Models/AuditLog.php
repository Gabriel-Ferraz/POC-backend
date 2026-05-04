<?php

namespace App\Models;

use Illuminate\Database\Eloquent\{Builder, Model};
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\AuditLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(fn (self $model) => $model->created_at ??= now());
    }

    protected $fillable = [
        'user_id',
        'action',
        'entity',
        'entity_id',
        'payload',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    public function scopeByAction(Builder $query, string $action): void
    {
        $query->where('action', $action);
    }

    public function scopeByEntity(Builder $query, string $entity): void
    {
        $query->where('entity', $entity);
    }

    public function scopeByDateRange(Builder $query, ?string $from, ?string $to): void
    {
        $query
            ->when($from, fn ($q) => $q->where('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->where('created_at', '<=', $to));
    }

    public function scopeFilterData(Builder $query, array $filters): void
    {
        $query
            ->when(isset($filters['user_id']), fn ($q) => $q->byUser($filters['user_id']))
            ->when(isset($filters['action']), fn ($q) => $q->byAction($filters['action']))
            ->when(isset($filters['entity']), fn ($q) => $q->byEntity($filters['entity']))
            ->when(
                isset($filters['date_from']) || isset($filters['date_to']),
                fn ($q) => $q->byDateRange($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            );
    }
}
