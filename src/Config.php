<?php
declare(strict_types=1);
namespace App;
final class Config
{
    private static bool $loaded = false;
    public static function load(string $basePath): void
    {
        if (self::$loaded) {
            return;
        }
        $envPath = $basePath . DIRECTORY_SEPARATOR . '.env';
        if (!is_file($envPath)) {
            self::$loaded = true;
            return;
        }
        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            self::$loaded = true;
            return;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $key = trim($parts[0]);
            $value = trim($parts[1]);
            $value = trim($value, "\"'");

            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
        }
        self::$loaded = true;
    }
    public static function get(string $key, ?string $default = null): ?string
    {
        $fromEnv = $_ENV[$key] ?? getenv($key);
        if ($fromEnv === false || $fromEnv === null || $fromEnv === '') {
            return $default;
        }
        return (string) $fromEnv;
    }
}
