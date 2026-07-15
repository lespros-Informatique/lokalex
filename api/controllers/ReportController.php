<?php

require_once __DIR__ . '/../core/Controller.php';

class ReportController extends Controller
{
    public function index(): void
    {
        $user = $this->requireActiveSubscription();
        $isDev = ($user['role_user'] ?? '') === 'developpeur';

        if ($isDev) {
            $locations = Location::all();
        } else {
            $shop = Shop::findByUserCode($user['code_user']);
            if (!$shop) {
                Response::error('Boutique introuvable', [], 404);
            }
            $locations = Location::allByBoutique($shop['code_boutique']);
        }

        $totalMontant = 0.0;
        $totalEncaisse = 0.0;
        $totalReste = 0.0;
        foreach ($locations as $loc) {
            $montant = (float) $loc['montant_location'];
            $reste = (float) $loc['reste_location'];
            $totalMontant += $montant;
            $totalReste += $reste;
            $totalEncaisse += ($montant - $reste);
        }

        $period = $_GET['period'] ?? 'day';
        $clientDate = $_GET['client_date'] ?? null;
        $chartData = $this->buildChartData($locations, $period, $clientDate);

        Response::success('Rapports', [
            'montant' => $this->formatMoney($totalMontant),
            'encaisse' => $this->formatMoney($totalEncaisse),
            'reste' => $this->formatMoney($totalReste),
            'chart' => $chartData,
        ]);
    }

    private function buildChartData(array $locations, string $period, $clientDate = null): array
    {
        $labels = [];
        $dataMontant = [];
        $dataEncaisse = [];

        $now = $clientDate ? new DateTime($clientDate) : new DateTime();

        if ($period === 'day') {
            $days = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
            for ($i = 6; $i >= 0; $i--) {
                $d = (clone $now)->modify("-$i days");
                $labels[] = $days[(int) $d->format('w')];
                $dayStr = $d->format('Y-m-d');
                $m = 0.0;
                $e = 0.0;
                foreach ($locations as $loc) {
                    if (substr($loc['created_at_location'], 0, 10) === $dayStr) {
                        $m += (float) $loc['montant_location'];
                        $e += ((float) $loc['montant_location'] - (float) $loc['reste_location']);
                    }
                }
                $dataMontant[] = $m;
                $dataEncaisse[] = $e;
            }
        } elseif ($period === 'week') {
            for ($i = 3; $i >= 0; $i--) {
                $d = (clone $now)->modify("-$i weeks");
                $labels[] = 'S' . (4 - $i);
                $weekStart = (clone $d)->modify('monday this week')->format('Y-m-d');
                $weekEnd = (clone $d)->modify('sunday this week')->format('Y-m-d');
                $m = 0.0;
                $e = 0.0;
                foreach ($locations as $loc) {
                    $dayStr = substr($loc['created_at_location'], 0, 10);
                    if ($dayStr >= $weekStart && $dayStr <= $weekEnd) {
                        $m += (float) $loc['montant_location'];
                        $e += ((float) $loc['montant_location'] - (float) $loc['reste_location']);
                    }
                }
                $dataMontant[] = $m;
                $dataEncaisse[] = $e;
            }
        } else {
            $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];
            for ($i = 5; $i >= 0; $i--) {
                $d = (clone $now)->modify("-$i months");
                $labels[] = $months[(int) $d->format('n') - 1];
                $month = $d->format('Y-m');
                $m = 0.0;
                $e = 0.0;
                foreach ($locations as $loc) {
                    if (strpos($loc['created_at_location'], $month) === 0) {
                        $m += (float) $loc['montant_location'];
                        $e += ((float) $loc['montant_location'] - (float) $loc['reste_location']);
                    }
                }
                $dataMontant[] = $m;
                $dataEncaisse[] = $e;
            }
        }

        return [
            'labels' => $labels,
            'sales' => $dataMontant,
            'expenses' => $dataEncaisse,
        ];
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' F';
    }
}
