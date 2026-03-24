<?php

namespace App\Helpers\Trait;

use Illuminate\Support\Facades\Log;

trait Logger
{
    private function getDefaultMessage(): string
    {
        $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[2];

        return "{$stack['class']}::{$stack['function']}";
    }

    public function writeInfo(string $message, array $context = [])
    {
        Log::info("{$this->getDefaultMessage()} => {$message}", $context);
    }

    public function writeError(string $message, array $context = [])
    {
        Log::error("{$this->getDefaultMessage()} => {$message}", $context);
    }

    public function writeDebug(string $message, array $context = [])
    {
        Log::debug("{$this->getDefaultMessage()} => {$message}", $context);
    }

    public function writeWarning(string $message, array $context = [])
    {
        Log::warning("{$this->getDefaultMessage()} => {$message}", $context);
    }
}
