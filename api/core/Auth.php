<?php

class Auth
{
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
        $parts = explode(':', base64_decode($token));
        if (count($parts) !== 2) return null;
        [$phone, $password] = $parts;
        return ['phone' => $phone, 'password' => $password];
    }
}
