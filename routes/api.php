<?php

use App\Http\Controllers\Admin\{AuditLogController, PermissionController, RoleController, UserController};
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Voice\{BiometriaController, ChatController, TTSController};
use Illuminate\Support\Facades\Route;

Route::get('/version', function () {
    return config('app.name') . ' version ' . config('app.version');
});

// Voice Services Routes (Public)
Route::prefix('tts')
    ->group(function () {
        Route::post('/synthesize', [TTSController::class, 'synthesize']);
        Route::get('/stream/{sessionId}', [TTSController::class, 'stream']);
        Route::post('/stream-cleanup/{sessionId}', [TTSController::class, 'streamCleanup']);
        Route::get('/download/{fileId}', [TTSController::class, 'download']);
        Route::post('/voice-clone', [TTSController::class, 'voiceClone']);
        Route::get('/demo-text/{voiceId}', [TTSController::class, 'getDemoText']);
    });

Route::prefix('chat')
    ->group(function () {
        Route::post('/send', [ChatController::class, 'sendMessage']);
    });

Route::prefix('biometria')
    ->group(function () {
        Route::post('/add-user', [BiometriaController::class, 'addUser']);
        Route::post('/identification', [BiometriaController::class, 'identification']);
        Route::post('/verification', [BiometriaController::class, 'verification']);
        Route::delete('/delete-user', [BiometriaController::class, 'deleteUser']);
        Route::post('/check-deepfake', [BiometriaController::class, 'checkDeepfake']);
    });

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

Route::middleware('auth:sanctum')
    ->group(function () {
        Route::prefix('auth')
            ->group(function () {
                Route::post('/logout', [AuthController::class, 'logout']);
                Route::get('/me', [AuthController::class, 'me']);
            });

        Route::prefix('subscriptions')
            ->group(function () {
                Route::get('/plans', [SubscriptionController::class, 'index']);
                Route::get('/current', [SubscriptionController::class, 'current']);
                Route::post('/activate-starter', [SubscriptionController::class, 'activateStarter']);
            });

        Route::prefix('admin')
            ->group(function () {
                Route::prefix('users')
                    ->group(function () {
                        Route::get('/', [UserController::class, 'index']);
                        Route::get('/{user}', [UserController::class, 'show']);
                        Route::post('/', [UserController::class, 'store'])->middleware('permission:admin.users.store');
                        Route::put('{user}', [UserController::class, 'update'])->middleware('permission:admin.users.update');
                        Route::put('/{user}/roles', [UserController::class, 'syncRoles'])->middleware('permission:admin.users.update');
                        Route::put('/{user}/permissions', [UserController::class, 'syncPermissions'])->middleware('permission:admin.users.update');
                        Route::delete('/{user}', [UserController::class, 'destroy'])->middleware('permission:admin.users.destroy');
                    });

                Route::prefix('roles')
                    ->group(function () {
                        Route::get('/', [RoleController::class, 'index']);
                        Route::get('/{role}', [RoleController::class, 'show']);
                        Route::post('/', [RoleController::class, 'store'])->middleware('permission:admin.roles.store');
                        Route::put('/{role}', [RoleController::class, 'update'])->middleware('permission:admin.roles.update');
                        Route::put('/{role}/permissions', [RoleController::class, 'syncPermissions'])->middleware('permission:admin.roles.update');
                        Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('permission:admin.roles.destroy');
                    });

                Route::prefix('permissions')
                    ->group(function () {
                        Route::get('/', [PermissionController::class, 'index']);
                        Route::get('/{permission}', [PermissionController::class, 'show']);
                    });

                Route::prefix('audit-logs')
                    ->group(function () {
                        Route::get('/', [AuditLogController::class, 'index']);
                        Route::get('/{auditLog}', [AuditLogController::class, 'show']);
                    });
            });
    });
