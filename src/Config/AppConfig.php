<?php

declare(strict_types=1);

namespace App\Config;

final class AppConfig
{
    /**
     * @var string
     */
    private static string $basePath;

    /**
     * This is where the bootstrapping happens.
     *
     * @return void
     */
    public static function bootstrap(string $rootPath): void
    {
        self::$basePath = rtrim($rootPath, '/\\');

        $envFile = self::$basePath . '/.env';
        if (is_readable($envFile)) {
            foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
                    continue;
                }
                [$key, $value] = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    }

    /**
     * Resolve a path relative to the project root.
     *
     * @return string
     */
    public static function basePath(string $path = ''): string
    {
        return self::$basePath . ($path ? '/' . ltrim($path, '/\\') : '');
    }

    /**
     * Resolve a path inside the storage dir.
     *
     * @return string
     */
    public static function storagePath(string $path = ''): string
    {
        return self::basePath('storage' . ($path ? '/' . ltrim($path, '/\\') : ''));
    }
}

