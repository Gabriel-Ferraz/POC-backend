<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\Trait\{Auditable, Logger};
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\{ForgotPasswordRequest, LoginRequest, RegisterRequest, ResetPasswordRequest};
use App\Http\Resources\Auth\UserResource;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\{Hash, Password};
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    use Auditable;
    use Logger;

    /**
     * Authenticate user and return token (login by CPF or email).
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $identifier = $request->email ?? $request->cpf;
        $this->writeInfo('Login attempt', ['identifier' => $identifier]);

        $user = User::where('email', $identifier)
            ->orWhere('cpf', $identifier)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            $this->writeWarning('Login failed: invalid credentials', ['identifier' => $identifier]);

            return response()->json([
                'message' => 'CPF/Email ou senha inválidos',
            ], Response::HTTP_UNAUTHORIZED);
        }

        if (!$user->is_active) {
            $this->writeWarning('Login failed: inactive user', ['identifier' => $identifier]);

            return response()->json([
                'message' => 'Usuário inativo',
            ], Response::HTTP_FORBIDDEN);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken('auth-token')->plainTextToken;

        $this->writeInfo('Login successful', ['user_id' => $user->id]);
        $this->audit('login', 'User', $user->id, null, $user->id);

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user->load('fornecedor')),
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
     * Return authenticated user data with fornecedor.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('fornecedor');

        return response()->json([
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Register a new user.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $this->writeInfo('Registration attempt', ['email' => $request->email]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        $this->writeInfo('Registration successful', ['user_id' => $user->id]);
        $this->audit('register', 'User', $user->id, null, $user->id);

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user->load('roles', 'permissions')),
        ], Response::HTTP_CREATED);
    }

    /**
     * Send password reset link to user email.
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->writeInfo('Password reset requested', ['email' => $request->email]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            $this->writeInfo('Password reset link sent', ['email' => $request->email]);

            return response()->json([
                'message' => 'Link de redefinição de senha enviado para seu email',
            ]);
        }

        $this->writeWarning('Password reset link failed', ['email' => $request->email, 'status' => $status]);

        return response()->json([
            'message' => 'Não foi possível enviar o link de redefinição',
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * Reset user password with token.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $this->writeInfo('Password reset attempt', ['email' => $request->email]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                event(new PasswordReset($user));

                $this->writeInfo('Password reset successful', ['user_id' => $user->id]);
                $this->audit('password_reset', 'User', $user->id);
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'message' => 'Senha redefinida com sucesso',
            ]);
        }

        $this->writeWarning('Password reset failed', ['email' => $request->email, 'status' => $status]);

        return response()->json([
            'message' => 'Token inválido ou expirado',
        ], Response::HTTP_UNPROCESSABLE_ENTITY);
    }
}
