<?php

require_once __DIR__ . '/../core/Controller.php';

class SubscriptionController extends Controller
{
    public function listForfaits(): void
    {
        $this->requireAuth();
        $forfaits = Forfait::getActifs();
        Response::success('Forfaits disponibles', ['forfaits' => $forfaits]);
    }

    public function subscribe(): void
    {
        $user = $this->requireAuth();

        if (($user['role_user'] ?? '') === 'developpeur') {
            Response::error('Action réservée aux vendeurs');
        }

        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $forfaitCode = trim($this->input('forfait_code', ''));
        if (!$forfaitCode) {
            Response::error('Forfait requis');
        }

        $forfait = Forfait::findByCode($forfaitCode);
        if (!$forfait || ($forfait['statut_forfait'] ?? '') !== 'actif') {
            Response::error('Forfait invalide', [], 404);
        }

        if (Abonnement::isActive($shop['code_boutique'])) {
            Response::error('Vous avez déjà un abonnement actif', [], 409);
        }

        $debut = date('Y-m-d');
        $fin = date('Y-m-d', strtotime('+' . (int) $forfait['duree_forfait'] . ' days'));

        $abonnement = Abonnement::create([
            'code_abonnement' => 'ABO' . time() . mt_rand(100, 999),
            'boutique_code' => $shop['code_boutique'],
            'forfait_code' => $forfait['code_forfait'],
            'date_debut_abonnement' => $debut,
            'date_fin_abonnement' => $fin,
            'montant_abonnement' => $forfait['prix_forfait'],
            'statut_abonnement' => 'actif',
        ]);

        Response::success('Abonnement activé', ['abonnement' => $abonnement]);
    }
}
