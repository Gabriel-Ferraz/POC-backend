<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "slug",
        "price",
        "description",
        "features",
        "is_active",
    ];

    protected function casts(): array
    {
        return [
            "price" => "decimal:2",
            "features" => "array",
            "is_active" => "boolean",
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(UserSubscription::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, "user_subscriptions")
            ->withPivot("status", "started_at", "expires_at")
            ->withTimestamps();
    }
}
