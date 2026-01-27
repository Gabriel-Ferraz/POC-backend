<?php

namespace App\Logging\Formatters;

use Monolog\Formatter\JsonFormatter;
use Monolog\LogRecord;

class CleanJsonFormatter extends JsonFormatter
{
    /**
     * Format the log record.
     */
    public function format(LogRecord $record): string
    {
        // Remove level prefix (INFO, ERROR, WARNING, etc.) from message
        $message = $record->message;
        $message = preg_replace('/^(DEBUG|INFO|NOTICE|WARNING|ERROR|CRITICAL|ALERT|EMERGENCY):\s*/i', '', $message);

        // Create a new record with the cleaned message
        $cleanedRecord = $record->with(message: $message);

        return parent::format($cleanedRecord);
    }
}
