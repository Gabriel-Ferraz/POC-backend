<?php

declare(strict_types=1);

use App\Http\Middleware\HandleRequestTrace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;

describe('HandleRequestTrace', function () {
    it('adds trace_id to Laravel context', function () {
        $middleware = new HandleRequestTrace();
        $request = Request::create('/test', 'GET');
        $called = false;

        $middleware->handle($request, function () use (&$called) {
            $called = true;

            return response()->noContent();
        });

        expect($called)->toBeTrue()
            ->and(Context::get('trace_id'))->not->toBeNull();
    });

    it('trace_id is a valid UUID v4 format', function () {
        $middleware = new HandleRequestTrace();
        $request = Request::create('/test', 'GET');

        $middleware->handle($request, fn () => response()->noContent());

        $traceId = Context::get('trace_id');

        expect($traceId)->toMatch(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i'
        );
    });

    it('generates a unique trace_id per request', function () {
        $middleware = new HandleRequestTrace();

        $middleware->handle(Request::create('/a'), fn () => response()->noContent());
        $first = Context::get('trace_id');

        $middleware->handle(Request::create('/b'), fn () => response()->noContent());
        $second = Context::get('trace_id');

        expect($first)->not->toBe($second);
    });
});
