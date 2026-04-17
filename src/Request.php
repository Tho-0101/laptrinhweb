<?php

declare(strict_types=1);

namespace App;

final class Request
{
    public static function method(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public static function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        return rtrim($path ?: '/', '/') ?: '/';
    }

    public static function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    public static function bearerToken(): ?string
    {
        $possibleHeaders = [
            $_SERVER['HTTP_AUTHORIZATION'] ?? null,
            $_SERVER['Authorization'] ?? null,
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null,
        ];

        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            if (is_array($headers)) {
                $possibleHeaders[] = $headers['Authorization'] ?? null;
                $possibleHeaders[] = $headers['authorization'] ?? null;
            }
        }

        foreach ($possibleHeaders as $header) {
            if (!is_string($header) || trim($header) === '') {
                continue;
            }

            if (preg_match('/Bearer\s+(.+)/i', $header, $matches) === 1) {
                return trim($matches[1]);
            }
        }

        return null;
    }
}
