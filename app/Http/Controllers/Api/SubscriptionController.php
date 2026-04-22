<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Trait\{Auditable, Logger};
use App\Http\Controllers\Controller;
use App\Models\{Plan, UserSubscription};
use Illuminate\Http\{JsonResponse, Request};
use Symfony\Component\HttpFoundation\Response;

class SubscriptionController extends Controller
{
    use Auditable;
    use Logger;

    /**
     * Get all available plans
     */
    public function index(): JsonResponse
    {
        $plans = Plan::orderBy("price")->get();

        return response()->json([
            "data" => $plans,
        ]);
    }

    /**
     * Get current user active subscription
     */
    public function current(Request $request): JsonResponse
    {
        $subscription = $request->user()
            ->activeSubscription()
            ->with("plan")
            ->first();

        if (!$subscription) {
            return response()->json([
                "message" => "No active subscription",
                "data" => null,
            ], Response::HTTP_NOT_FOUND);
        }

        return response()->json([
            "data" => $subscription,
        ]);
    }

    /**
     * Activate starter plan (free)
     */
    public function activateStarter(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user already has an active subscription
        if ($user->hasActiveSubscription()) {
            return response()->json([
                "message" => "Você já possui um plano ativo",
            ], Response::HTTP_BAD_REQUEST);
        }

        // Get starter plan
        $starterPlan = Plan::where("slug", "starter")->first();

        if (!$starterPlan) {
            return response()->json([
                "message" => "Starter plan not found",
            ], Response::HTTP_NOT_FOUND);
        }

        // Create subscription
        $subscription = UserSubscription::create([
            "user_id" => $user->id,
            "plan_id" => $starterPlan->id,
            "status" => "active",
            "started_at" => now(),
            "expires_at" => null, // Free plan never expires
        ]);

        $this->writeInfo("Starter plan activated", ["user_id" => $user->id]);
        $this->audit("activate_plan", "UserSubscription", $subscription->id, null, $user->id);

        return response()->json([
            "message" => "Plano Starter ativado com sucesso!",
            "data" => $subscription->load("plan"),
        ], Response::HTTP_CREATED);
    }
}
