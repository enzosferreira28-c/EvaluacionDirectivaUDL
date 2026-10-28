<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Servicio centralizado de registro de eventos (Logging).
 * Escribe bitácoras organizadas por fecha en storage/logs/.
 */
class Logger
{
    private static string $logDir = '';

    private static function init(): void
    {
        if (self::$logDir === '') {
            self::$logDir = (defined('APP_ROOT') ? APP_ROOT : dirname(__DIR__, 2)) . '/storage/logs';
            if (!is_dir(self::$logDir)) {
                @mkdir(self::$logDir, 0755, true);
            }
        }
    }

    /**
     * Escribe un registro en el archivo de log correspondiente.
     */
    public static function log(string $level, string $message, array $context = [], string $channel = 'app'): void
    {
        self::init();

        $date = date('Y-m-d');
        $time = date('Y-m-d H:i:s');
        $logFile = self::$logDir . "/{$channel}_{$date}.log";

        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : '';
        $formattedMessage = "[{$time}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        @file_put_contents($logFile, $formattedMessage, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = [], string $channel = 'app'): void
    {
        self::log('INFO', $message, $context, $channel);
    }

    public static function warning(string $message, array $context = [], string $channel = 'app'): void
    {
        self::log('WARNING', $message, $context, $channel);
    }

    public static function error(string $message, array $context = [], string $channel = 'app'): void
    {
        self::log('ERROR', $message, $context, $channel);
    }

    public static function debug(string $message, array $context = [], string $channel = 'app'): void
    {
        if (defined('APP_DEBUG') && APP_DEBUG) {
            self::log('DEBUG', $message, $context, $channel);
        }
    }
}
