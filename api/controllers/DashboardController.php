<?php

require_once __DIR__ . '/../core/Controller.php';

class DashboardController extends Controller
{
    public function index(): void
    {
        $user = $this->requireActiveSubscription();
        $isDev = ($user['role_user'] ?? '') === 'developpeur';
        $clientDate = $_GET['client_date'] ?? date('Y-m-d');

        if ($isDev) {
            $stats = [
                'boutiques' => Shop::countAll(),
                'vendeurs' => User::countByRole('vendeur'),
                'abonnements_expires' => Abonnement::countExpired(),
            ];
            Response::success('Dashboard', ['stats' => $stats, 'recent' => []]);
            return;
        }

        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $boutiqueCode = $shop['code_boutique'];
        $s = Location::stats($boutiqueCode, $clientDate);
        $clients = Client::countByBoutique($boutiqueCode);
        $recent = Location::getRecentByBoutique($boutiqueCode, 10);

        Response::success('Dashboard', [
            'en_cours' => $s['en_cours'],
            'retours_prevus' => $s['retours_prevus'],
            'montant_jour' => $this->formatMoney($s['montant_jour']),
            'montant_jour_raw' => $s['montant_jour'],
            'clients' => $clients,
            'retards' => $s['retards'],
            'recent' => $recent,
            'stats' => [],
        ]);
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' F';
    }
}
