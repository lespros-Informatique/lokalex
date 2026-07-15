<?php

require_once __DIR__ . '/../config/database.php';

class Logger
{
    private static ?string $logDir = null;

    private static function getLogDir(): string
    {
        if (self::$logDir === null) {
            self::$logDir = __DIR__ . '/../logs';
            if (!is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0755, true);
            }
        }
        return self::$logDir;
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function auth(string $message, array $context = []): void
    {
        self::write('AUTH', $message, $context);
    }

    private static function write(string $level, string $message, array $context = []): void
    {
        $date = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'cli';
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'] ?? '';
        $contextStr = $context ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $line = "[{$date}] [{$level}] [{$method} {$uri}] [{$ip}] {$message}{$contextStr}" . PHP_EOL;
        $file = self::getLogDir() . '/' . date('Y-m-d') . '.log';
        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }
}
