<?php

require_once __DIR__ . '/../core/Controller.php';

class AuthController extends Controller
{
    public function login(): void
    {
        $phone = trim($this->input('phone', $this->input('telephone', '')));
        if (!$phone) {
            Response::error('Numéro de téléphone requis');
        }

        $user = User::findByPhone($phone);
        if (!$user) {
            Logger::auth('Login failed: user not found', ['phone' => $phone]);
            Response::error('Utilisateur introuvable', [], 401);
        }

        $shop = Shop::findByUserCode($user['code_user']);

        $token = Auth::generateToken($user['telephone_user']);
        $csrfToken = Auth::generateCsrfToken();
        setcookie('nafa_token', $token, time() + 86400 * 30, '/', '', false, false);
        setcookie('nafa_user', Auth::signCookieData($user), time() + 86400 * 30, '/', '', false, false);
        setcookie('XSRF-TOKEN', $csrfToken, time() + 86400 * 30, '/', '', false, false);

        Logger::auth('Login success', ['user_code' => $user['code_user'], 'phone' => $phone]);

        Response::success('Connexion réussie', [
            'user' => $user,
            'shop' => $shop,
            'csrf_token' => $csrfToken,
            'token' => $token,
        ]);
    }

    public function me(): void
    {
        $user = $this->requireAuth();
        $shop = Shop::findByUserCode($user['code_user']);
        Response::success('Utilisateur connecté', [
            'user' => $user,
            'shop' => $shop,
        ]);
    }

    public function logout(): void
    {
        setcookie('nafa_token', '', time() - 3600, '/');
        setcookie('nafa_user', '', time() - 3600, '/');
        setcookie('XSRF-TOKEN', '', time() - 3600, '/');
        Response::success('Déconnexion réussie');
    }
}
