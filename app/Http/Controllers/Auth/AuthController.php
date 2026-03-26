<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\Trait\{Auditable, Logger};
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\{LoginRequest};
use App\Http\Resources\Auth\UserResource;
use App\Models\User;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Hash};
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    use Auditable;
    use Logger;

    /**
     * Authenticate user and return token.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $this->writeInfo('Login attempt', ['email' => $request->email]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            $this->writeWarning('Login failed: invalid credentials', ['email' => $request->email]);

            return response()->json([
                'message' => 'Invalid credentials',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->is_active) {
            $this->writeWarning('Login failed: inactive user', ['email' => $request->email]);

            return response()->json([
                'message' => 'User account is disabled',
            ], Response::HTTP_FORBIDDEN);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth-token')->plainTextToken;

        $this->writeInfo('Login successful', ['user_id' => $user->id]);
        $this->audit('login', 'User', $user->id, null, $user->id);

        return response()->json([
            'token' => $token,
        ]);
    }

    /**
     * Revoke current token and logout.
     */
    public function logout(Request $request): Response
    {
        $this->writeInfo('Logout', ['user_id' => $request->user()->id]);
        $this->audit('logout', 'User', $request->user()->id);

        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    /**
     * Return authenticated user data with roles and permissions.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles', 'permissions');

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }
}
