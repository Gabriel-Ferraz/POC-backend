<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\{Exceptions, Middleware};
use Illuminate\Http\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies('*');

        $middleware->append([
            \App\Http\Middleware\HandleRequestTrace::class,
        ]);

        $middleware->api([
            'throttle:api'
        ]);

        $middleware->alias([
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $exception, $request) {
            if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json([
                    'message' => 'Unauthenticated',
                    'errors' => null
                ], \Illuminate\Http\Response::HTTP_UNAUTHORIZED);
            }

            if ($exception instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
                return response()->json([
                    'message' => 'Too many requests',
                    'errors' => null
                ], Response::HTTP_TOO_MANY_REQUESTS);
            }

            if ($exception instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => 'Invalid data',
                    'errors' => $exception->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (
                $exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ||
                $exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
            ) {
                return response()->json([
                    'message' => 'Resource not found',
                    'errors' => null
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'message' => 'Internal server error',
                'errors' => null
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        });
    })->create();
