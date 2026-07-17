<?php

require_once __DIR__ . '/../config/database.php';

class Auth
{
    public static function isSecureContext(): bool
    {
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }
        return (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);
    }

    public static function setCookie(string $name, string $value, int $lifetime, bool $httpOnly = false): void
    {
        $secure = self::isSecureContext();
        setcookie($name, $value, [
            'expires' => time() + $lifetime,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => $httpOnly,
            'samesite' => 'Lax',
        ]);
    }

    public static function clearCookie(string $name): void
    {
        self::setCookie($name, '', -3600);
    }

    public static function getPhoneFromHeader(): ?string
    {
        $headers = getallheaders();
        $phone = $headers['X-Phone'] ?? $headers['x-phone'] ?? null;
        return $phone ? trim($phone) : null;
    }

    public static function getUserFromToken(): ?array
    {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if (!$token) return null;
        $token = preg_replace('/^Bearer\s+/i', '', $token);
        return self::validateToken($token);
    }

    public static function getTokenTtl(): int
    {
        return 86400 * 30;
    }

    public static function generateToken(string $phone): string
    {
        $config = require __DIR__ . '/../config/database.php';
        $secret = $config['jwt_secret'] ?? 'LOKALEX_SECRET';
        $exp = time() + self::getTokenTtl();
        $payload = $phone . ':' . $exp;
        $sig = hash_hmac('sha256', $payload, $secret);
        $data = $payload . ':' . $sig;
        return bin2hex($data);
    }

    public static function refreshToken(string $oldToken): ?string
    {
        $decoded = self::validateToken($oldToken, true);
        if (!$decoded) {
            return null;
        }
        return self::generateToken($decoded['phone']);
    }

    public static function validateToken(string $token, bool $allowExpired = false): ?array
    {
        $config = require __DIR__ . '/../config/database.php';
        $secret = $config['jwt_secret'] ?? 'LOKALEX_SECRET';
        $decoded = hex2bin($token);
        if ($decoded === false) return null;
        $parts = explode(':', $decoded);
        if (count($parts) !== 3) return null;
        [$phone, $exp, $sig] = $parts;
        $expectedSig = hash_hmac('sha256', $phone . ':' . $exp, $secret);
        if (!hash_equals($expectedSig, $sig)) return null;
        if (!$allowExpired && time() > (int) $exp) return null;
        return ['phone' => $phone, 'exp' => (int) $exp, 'password' => ''];
    }

    public static function signCookieData(array $data): string
    {
        $config = require __DIR__ . '/../config/database.php';
        $secret = $config['jwt_secret'] ?? 'LOKALEX_SECRET';
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        $sig = hash_hmac('sha256', $json, $secret);
        return base64_encode($json . ':' . $sig);
    }

    public static function verifyCookieData(string $signed): ?array
    {
        $config = require __DIR__ . '/../config/database.php';
        $secret = $config['jwt_secret'] ?? 'LOKALEX_SECRET';
        $decoded = base64_decode($signed, true);
        if ($decoded === false) return null;
        $parts = explode(':', $decoded, 2);
        if (count($parts) !== 2) return null;
        [$json, $sig] = $parts;
        $expectedSig = hash_hmac('sha256', $json, $secret);
        if (!hash_equals($expectedSig, $sig)) return null;
        $data = json_decode($json, true);
        return is_array($data) ? $data : null;
    }

    public static function generateCsrfToken(): string
    {
        $config = require __DIR__ . '/../config/database.php';
        $secret = $config['csrf_secret'] ?? 'LOKALEX_CSRF';
        $random = bin2hex(random_bytes(32));
        $sig = hash_hmac('sha256', $random, $secret);
        return base64_encode($random . ':' . $sig);
    }

    public static function validateCsrfToken(string $token): bool
    {
        $config = require __DIR__ . '/../config/database.php';
        $secret = $config['csrf_secret'] ?? 'LOKALEX_CSRF';
        $decoded = base64_decode($token, true);
        if ($decoded === false) return false;
        $parts = explode(':', $decoded);
        if (count($parts) !== 2) return false;
        [$random, $sig] = $parts;
        $expectedSig = hash_hmac('sha256', $random, $secret);
        return hash_equals($expectedSig, $sig);
    }
}
