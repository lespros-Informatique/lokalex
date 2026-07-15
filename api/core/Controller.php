<?php

require_once __DIR__ . '/../core/Logger.php';

if (!function_exists('getallheaders')) {
    function getallheaders(): array {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (str_starts_with($name, 'HTTP_')) {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
            }
        }
        return $headers;
    }
}

abstract class Controller
{
    protected function input(string $key, $default = null)
    {
        $data = $this->jsonInput();
        return $data[$key] ?? $default;
    }

    protected function jsonInput(): array
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'GET') {
            return $_GET;
        }

        $contentType = strtolower($_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '');
        if (strpos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true);
            return is_array($data) ? $data : [];
        }

        return $_POST;
    }

    protected function requireAuth(): array
    {
        $headers = getallheaders();
        $token = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        if (!$token && isset($_COOKIE['nafa_user'])) {
            $userData = Auth::verifyCookieData($_COOKIE['nafa_user']);
            if ($userData && isset($userData['telephone_user'])) {
                return $userData;
            }
        }
        if (!$token) {
            Logger::auth('Auth failed: no token provided');
            Response::error('Non autorisé', [], 401);
        }
        $token = preg_replace('/^Bearer\s+/i', '', $token);
        $decoded = Auth::validateToken($token);
        if (!$decoded) {
            Logger::auth('Auth failed: invalid token');
            Response::error('Token invalide ou expiré', [], 401);
        }
        $user = User::findByPhone($decoded['phone']);
        if (!$user) {
            Logger::auth('Auth failed: user not found', ['phone' => $decoded['phone']]);
            Response::error('Utilisateur introuvable', [], 401);
        }
        Logger::auth('Auth success', ['user_code' => $user['code_user']]);
        return $user;
    }

    protected function requireActiveSubscription(): array
    {
        $user = $this->requireAuth();

        if (($user['role_user'] ?? '') === 'developpeur') {
            return $user;
        }

        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        if (!Abonnement::isActive($shop['code_boutique'])) {
            $dejaAbonne = Abonnement::findByBoutique($shop['code_boutique']);
            if ($dejaAbonne) {
                Response::error('Votre période d\'essai est terminée', ['code' => 'SUBSCRIPTION_EXPIRED'], 402);
            }
            Response::error('Abonnement requis pour continuer', ['code' => 'SUBSCRIPTION_REQUIRED'], 402);
        }

        return $user;
    }

    protected function requireCsrf(): void
    {
        $headers = getallheaders();
        $headerToken = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? null;
        $cookieToken = $_COOKIE['XSRF-TOKEN'] ?? $_COOKIE['csrf_token'] ?? null;
        if (!$headerToken || !$cookieToken || !hash_equals($cookieToken, $headerToken)) {
            Logger::warning('CSRF validation failed', [
                'has_header' => !empty($headerToken),
                'has_cookie' => !empty($cookieToken),
            ]);
            Response::error('Requête invalide (CSRF)', [], 403);
        }
    }

    protected function paginationParams(): array
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        return ['page' => $page, 'limit' => $limit, 'offset' => ($page - 1) * $limit];
    }

    protected function paginatedResponse(array $items, int $total, array $extra = []): void
    {
        $params = $this->paginationParams();
        $hasMore = ($params['page'] * $params['limit']) < $total;
        Response::success('', array_merge($extra, [
            'items' => $items,
            'pagination' => [
                'page' => $params['page'],
                'limit' => $params['limit'],
                'total' => $total,
                'has_more' => $hasMore,
            ],
        ]));
    }
}
