<?php

require_once __DIR__ . '/../core/Controller.php';

class HistoryController extends Controller
{
    public function index(): void
    {
        $user = $this->requireActiveSubscription();
        $isDev = ($user['role_user'] ?? '') === 'developpeur';

        $filter = $_GET['filter'] ?? 'today';
        $clientDate = $_GET['client_date'] ?? null;

        if ($isDev) {
            $locations = Location::all();
        } else {
            $shop = Shop::findByUserCode($user['code_user']);
            if (!$shop) {
                Response::error('Boutique introuvable', [], 404);
            }
            $locations = Location::allByBoutique($shop['code_boutique']);
        }

        $items = [];
        foreach ($locations as $loc) {
            $date = new DateTime($loc['created_at_location']);
            if ($this->matchFilter($date, $filter, $clientDate)) {
                $items[] = [
                    'type' => 'location',
                    'id' => $loc['code_location'],
                    'title' => $loc['nom_client'] ?? 'Client',
                    'meta' => $date->format('d/m/Y H:i'),
                    'amount' => (float) $loc['montant_location'],
                    'reste' => (float) $loc['reste_location'],
                    'statut' => $loc['statut_location'],
                ];
            }
        }

        usort($items, function ($a, $b) {
            return strtotime($b['meta']) - strtotime($a['meta']);
        });

        $total = count($items);
        $params = $this->paginationParams();
        $pageItems = array_slice($items, $params['offset'], $params['limit']);
        $this->paginatedResponse($pageItems, $total);
    }

    private function matchFilter(DateTime $date, string $filter, $clientDate = null): bool
    {
        $now = $clientDate ? new DateTime($clientDate) : new DateTime();
        if ($filter === 'today') {
            return $date->format('Y-m-d') === $now->format('Y-m-d');
        } elseif ($filter === 'week') {
            $weekAgo = (clone $now)->modify('-7 days');
            return $date >= $weekAgo;
        } elseif ($filter === 'month') {
            $monthAgo = (clone $now)->modify('-1 month');
            return $date >= $monthAgo;
        } elseif ($filter === 'year') {
            $yearAgo = (clone $now)->modify('-1 year');
            return $date >= $yearAgo;
        }
        return true;
    }
}
