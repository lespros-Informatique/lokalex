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
            $sales = Sale::getAll();
            $expenses = Expense::getAll();
        } else {
            $shop = Shop::findByUserCode($user['code_user']);
            if (!$shop) {
                Response::error('Boutique introuvable', [], 404);
            }
            $sales = Sale::getAllByShop($shop['code_boutique']);
            $expenses = Expense::getAllByShop($shop['code_boutique']);
        }

        $items = [];
        foreach ($sales as $sale) {
            $date = new DateTime($sale['created_at_vente']);
            if ($this->matchFilter($date, $filter, $clientDate)) {
                $items[] = [
                    'type' => 'vente',
                    'id' => $sale['code_vente'],
                    'title' => 'Vente',
                    'meta' => $date->format('d/m/Y H:i'),
                    'amount' => (float) $sale['montant_vente'],
                    'mode' => $sale['mode_paiement_vente'],
                ];
            }
        }
        foreach ($expenses as $expense) {
            $date = new DateTime($expense['date_depense_depense']);
            if ($this->matchFilter($date, $filter, $clientDate)) {
                $items[] = [
                    'type' => 'depense',
                    'id' => $expense['code_depense'],
                    'title' => $expense['libelle_depense'],
                    'meta' => $date->format('d/m/Y H:i'),
                    'amount' => (float) $expense['montant_depense'],
                    'mode' => '-',
                ];
            }
        }

        usort($items, function ($a, $b) { return strtotime($b['meta']) - strtotime($a['meta']); });

        Response::success('Historique', ['items' => $items]);
    }

    public function delete(): void
    {
        $user = $this->requireActiveSubscription();
        $type = $this->input('type', '');
        $id = $this->input('id', '');

        if ($type === 'vente') {
            Sale::delete($id);
        } elseif ($type === 'depense') {
            Expense::delete($id);
        } else {
            Response::error('Type invalide', [], 400);
        }

        Response::success('Opération supprimée');
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
        }
        return true;
    }
}
