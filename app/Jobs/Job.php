<?php namespace App\Jobs;

abstract class Job
{
    protected function log(string $message, array $context = [])
    {
        \Log::info(static::class . ' ' . $message, $context);

        if (isProduction()) {
            $context = json_stringify($context);
            $log = '[' . date('Y-m-d H:i:s') . ' ' . class_basename(static::class) . "] {$message} {$context}\n";
            echo $log;
        }
    }
}
