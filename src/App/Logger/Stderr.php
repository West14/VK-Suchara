<?php
/**
 * Created by PhpStorm.
 * User: Andriy
 * Date: 08.08.2021
 * Time: 21:22
 * Made with <3 by West from Bubuni Team
 */

namespace App\Logger;

class Stderr extends AbstractLogger
{
    public function setup(): bool
    {
        return true;
    }

    public function log($level, \Stringable|string $message, array $context = []): void
    {
        error_log("[{$level}] {$message}");
    }
}