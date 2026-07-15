<?php

require_once __DIR__ . '/../core/Controller.php';

class ReportController extends Controller
{
    public function index(): void
    {
        $user = $this->requireActiveSubscription();
        $isDev = ($user['role_user'] ?? '') === 'developpeur';

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

        $totalSales = array_sum(array_column($sales, 'montant_vente'));
        $totalExpenses = array_sum(array_column($expenses, 'montant_depense'));

        $period = $_GET['period'] ?? 'day';
        $clientDate = $_GET['client_date'] ?? null;
        $chartData = $this->buildChartData($sales, $expenses, $period, $clientDate);

        Response::success('Rapports', [
            'sales' => $this->formatMoney($totalSales),
            'expenses' => $this->formatMoney($totalExpenses),
            'net' => $this->formatMoney($totalSales - $totalExpenses),
            'chart' => $chartData,
        ]);
    }

    private function buildChartData(array $sales, array $expenses, string $period, $clientDate = null): array
    {
        $labels = [];
        $dataV = [];
        $dataE = [];

        $now = $clientDate ? new DateTime($clientDate) : new DateTime();

        if ($period === 'day') {
            $days = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
            for ($i = 6; $i >= 0; $i--) {
                $d = (clone $now)->modify("-$i days");
                $labels[] = $days[(int)$d->format('w')];
                $dayStr = $d->format('Y-m-d');
                $dataV[] = array_sum(array_column(array_filter($sales, function ($s) use ($dayStr) { return strpos($s['created_at_vente'], $dayStr) === 0; }), 'montant_vente'));
                $dataE[] = array_sum(array_column(array_filter($expenses, function ($e) use ($dayStr) { return strpos($e['date_depense_depense'], $dayStr) === 0; }), 'montant_depense'));
            }
        } elseif ($period === 'week') {
            for ($i = 3; $i >= 0; $i--) {
                $d = (clone $now)->modify("-$i weeks");
                $labels[] = 'S' . (4 - $i);
                $weekStart = (clone $d)->modify('monday this week')->format('Y-m-d');
                $weekEnd = (clone $d)->modify('sunday this week')->format('Y-m-d');
                $dataV[] = array_sum(array_column(array_filter($sales, function($s) use ($weekStart, $weekEnd) {
                    $d = substr($s['created_at_vente'], 0, 10);
                    return $d >= $weekStart && $d <= $weekEnd;
                }), 'montant_vente'));
                $dataE[] = array_sum(array_column(array_filter($expenses, function($e) use ($weekStart, $weekEnd) {
                    $d = substr($e['date_depense_depense'], 0, 10);
                    return $d >= $weekStart && $d <= $weekEnd;
                }), 'montant_depense'));
            }
        } else {
            $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
            for ($i = 5; $i >= 0; $i--) {
                $d = (clone $now)->modify("-$i months");
                $labels[] = $months[(int)$d->format('n') - 1];
                $month = $d->format('Y-m');
                $dataV[] = array_sum(array_column(array_filter($sales, function ($s) use ($month) { return strpos($s['created_at_vente'], $month) === 0; }), 'montant_vente'));
                $dataE[] = array_sum(array_column(array_filter($expenses, function ($e) use ($month) { return strpos($e['date_depense_depense'], $month) === 0; }), 'montant_depense'));
            }
        }

        return [
            'labels' => $labels,
            'sales' => $dataV,
            'expenses' => $dataE,
        ];
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' F';
    }
}
