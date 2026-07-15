<?php

require_once __DIR__ . '/../core/Controller.php';

class SearchController extends Controller
{
    public function search(): void
    {
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $query = trim($_GET['q'] ?? '');
        if (!$query) {
            Response::success('Résultats de recherche', ['results' => []]);
        }

        $results = Sale::search($shop['code_boutique'], $query);
        $formatted = array_map(function ($sale) {
            $date = new DateTime($sale['created_at_vente']);
            return [
                'type' => 'vente',
                'id' => $sale['code_vente'],
                'title' => 'Vente',
                'meta' => $date->format('d/m/Y H:i'),
                'amount' => (float) $sale['montant_vente'],
                'mode' => $sale['mode_paiement_vente'],
            ];
        }, $results);

        Response::success('Résultats de recherche', ['results' => $formatted]);
    }
}
