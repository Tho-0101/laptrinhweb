<?php

declare(strict_types=1);

namespace App;

final class Auth
{
    public static function makeToken(int $userId): string
    {
        $header = self::base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT']));
        $payload = self::base64UrlEncode(json_encode([
            'sub' => $userId,
            'iat' => time(),
            'exp' => time() + 60 * 60 * 24 * 7,
        ]));

        $secret = Config::get('JWT_SECRET', 'secret');
        $signature = hash_hmac('sha256', $header . '.' . $payload, (string) $secret, true);
        return $header . '.' . $payload . '.' . self::base64UrlEncode($signature);
    }

    public static function userIdFromToken(?string $token): ?int
    {
        if (!$token) {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$header, $payload, $sig] = $parts;
        $secret = Config::get('JWT_SECRET', 'secret');
        $expected = self::base64UrlEncode(hash_hmac('sha256', $header . '.' . $payload, (string) $secret, true));
        if (!hash_equals($expected, $sig)) {
            return null;
        }

        $decoded = json_decode((string) self::base64UrlDecode($payload), true);
        if (!is_array($decoded) || !isset($decoded['sub'], $decoded['exp'])) {
            return null;
        }

        if ((int) $decoded['exp'] < time()) {
            return null;
        }

        return (int) $decoded['sub'];
    }

    private static function base64UrlEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $input): string|false
    {
        $remainder = strlen($input) % 4;
        if ($remainder > 0) {
            $input .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }
}
