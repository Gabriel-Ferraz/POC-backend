<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $exception, $request) {
            if ($exception instanceof \Illuminate\Http\Exceptions\ThrottleRequestsException) {
                return response()->json([
                    'message' => 'Too many requests',
                    'errors' => null
                ], 429);
            }

            if ($exception instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => 'Invalid data',
                    'errors' => $exception->errors()
                ], 422);
            }

            if (
                $exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ||
                $exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
            ) {
                return response()->json([
                    'message' => 'Resource not found',
                    'errors' => null
                ], 404);
            }

            return response()->json([
                'message' => 'Internal server error',
                'errors' => null
            ], 500);
        });
    })->create();
