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

        $results = Location::allByBoutique($shop['code_boutique'], $query);
        $formatted = array_map(function ($loc) {
            $date = new DateTime($loc['created_at_location']);
            return [
                'type' => 'location',
                'id' => $loc['code_location'],
                'title' => $loc['nom_client'] ?? 'Client',
                'meta' => $date->format('d/m/Y H:i'),
                'amount' => (float) $loc['montant_location'],
                'reste' => (float) $loc['reste_location'],
                'statut' => $loc['statut_location'],
            ];
        }, $results);

        Response::success('Résultats de recherche', ['results' => $formatted]);
    }
}
