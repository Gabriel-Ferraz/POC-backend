<?php

declare(strict_types=1);

use App\Helpers\Trait\Logger;
use Illuminate\Support\Facades\Log;

$newLogger = fn (): object => new class () {
    use Logger;

    public function doInfo(string $msg, array $ctx = []): void
    {
        $this->writeInfo($msg, $ctx);
    }

    public function doError(string $msg, array $ctx = []): void
    {
        $this->writeError($msg, $ctx);
    }

    public function doDebug(string $msg, array $ctx = []): void
    {
        $this->writeDebug($msg, $ctx);
    }

    public function doWarning(string $msg, array $ctx = []): void
    {
        $this->writeWarning($msg, $ctx);
    }
};

describe('Logger trait', function () use ($newLogger) {
    it('writeInfo calls Log::info', function () use ($newLogger) {
        Log::shouldReceive('info')->once()->withArgs(
            fn (string $msg) => str_contains($msg, 'test info message')
        );

        $newLogger()->doInfo('test info message');
    });

    it('writeError calls Log::error', function () use ($newLogger) {
        Log::shouldReceive('error')->once()->withArgs(
            fn (string $msg) => str_contains($msg, 'test error message')
        );

        $newLogger()->doError('test error message');
    });

    it('writeDebug calls Log::debug', function () use ($newLogger) {
        Log::shouldReceive('debug')->once()->withArgs(
            fn (string $msg) => str_contains($msg, 'test debug message')
        );

        $newLogger()->doDebug('test debug message');
    });

    it('writeWarning calls Log::warning', function () use ($newLogger) {
        Log::shouldReceive('warning')->once()->withArgs(
            fn (string $msg) => str_contains($msg, 'test warning message')
        );

        $newLogger()->doWarning('test warning message');
    });

    it('log message includes context array', function () use ($newLogger) {
        Log::shouldReceive('info')->once()->with(
            \Mockery::type('string'),
            ['key' => 'value']
        );

        $newLogger()->doInfo('message', ['key' => 'value']);
    });
});
