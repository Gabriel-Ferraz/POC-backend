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
            'cpf' => $this->cpf,
            'perfil' => $this->perfil,
            'is_active' => $this->is_active,
            'last_login_at' => $this->last_login_at,
            'fornecedor' => $this->whenLoaded('fornecedor', function () {
                return $this->fornecedor ? [
                    'id' => $this->fornecedor->id,
                    'nome' => $this->fornecedor->nome,
                    'cnpj' => $this->fornecedor->cnpj,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
