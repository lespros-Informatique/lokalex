<?php

class Request
{
    public static function input(string $key, mixed $default = null): mixed
    {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        return $input[$key] ?? $default;
    }

    public static function all(): array
    {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }
}
