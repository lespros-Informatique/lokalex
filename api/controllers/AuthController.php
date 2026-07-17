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
        Auth::setCookie('lokalex_token', $token, Auth::getTokenTtl(), true);
        Auth::setCookie('lokalex_user', Auth::signCookieData($user), Auth::getTokenTtl(), true);
        Auth::setCookie('XSRF-TOKEN', $csrfToken, Auth::getTokenTtl(), false);

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
        $response = [
            'user' => $user,
            'shop' => $shop,
        ];
        if (isset($user['_new_token'])) {
            $response['token'] = $user['_new_token'];
        }
        Response::success('Utilisateur connecté', $response);
    }

    public function logout(): void
    {
        $this->requireCsrf();
        Auth::clearCookie('lokalex_token');
        Auth::clearCookie('lokalex_user');
        Auth::clearCookie('XSRF-TOKEN');
        Response::success('Déconnexion réussie');
    }
}
