<?php

use App\Http\Controllers\Admin\{AuditLogController, PermissionController, RoleController, UserController};
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/version', function () {
    return config('app.name') . ' version ' . config('app.version');
});

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
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
