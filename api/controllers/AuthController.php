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
            Response::error('Utilisateur introuvable', [], 401);
        }

        $shop = Shop::findByUserCode($user['code_user']);

        $token = base64_encode($user['telephone_user'] . ':' . time());
        setcookie('nafa_token', $token, time() + 86400 * 30, '/', '', false, true);
        setcookie('nafa_user', base64_encode(json_encode($user)), time() + 86400 * 30, '/', '', false, true);

        Response::success('Connexion réussie', [
            'user' => $user,
            'shop' => $shop,
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
        Response::success('Déconnexion réussie');
    }
}
