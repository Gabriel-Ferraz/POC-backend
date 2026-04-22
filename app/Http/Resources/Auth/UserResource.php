<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Use already loaded relationship if available (prevents N+1)
        // If not loaded, will lazy load (fallback for single user requests)
        $activeSubscription = $this->whenLoaded('activeSubscription',
            fn() => $this->activeSubscription,
            fn() => $this->activeSubscription()->with('plan')->first()
        );

        $hasSubscription = $activeSubscription && $activeSubscription->isActive();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at,
            'roles' => $this->getRoleNames(),
            'permissions' => $this->getAllPermissions()->pluck('name'),
            'has_active_subscription' => $hasSubscription,
            'subscription' => $activeSubscription ? [
                'id' => $activeSubscription->id,
                'status' => $activeSubscription->status,
                'started_at' => $activeSubscription->started_at,
                'expires_at' => $activeSubscription->expires_at,
                'plan' => $activeSubscription->plan ? [
                    'id' => $activeSubscription->plan->id,
                    'name' => $activeSubscription->plan->name,
                    'slug' => $activeSubscription->plan->slug,
                ] : null,
            ] : null,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
