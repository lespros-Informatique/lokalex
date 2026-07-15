<?php

require_once __DIR__ . '/../core/Controller.php';

class ClientController extends Controller
{
    public function index(): void
    {
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }
        Response::success('Clients', ['clients' => Client::allByBoutique($shop['code_boutique'])]);
    }

    public function search(): void
    {
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }
        $q = trim($_GET['q'] ?? '');
        if (!$q) {
            Response::success('Résultats', ['clients' => []]);
        }
        Response::success('Résultats', ['clients' => Client::search($shop['code_boutique'], $q)]);
    }

    public function store(): void
    {
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $nom = trim($this->input('nom', $this->input('nom_client', '')));
        if (!$nom) {
            Response::error('Nom requis');
        }

        $client = Client::create([
            'code_client' => 'CLI' . time() . mt_rand(100, 999),
            'boutique_code' => $shop['code_boutique'],
            'nom_client' => $nom,
            'telephone_client' => trim($this->input('telephone', $this->input('telephone_client', ''))) ?: null,
            'adresse_client' => trim($this->input('adresse', $this->input('adresse_client', ''))) ?: null,
            'statut_client' => 'actif',
            'created_at_client' => date('Y-m-d H:i:s'),
        ]);

        Response::success('Client ajouté', ['client' => $client]);
    }

    public function historique(): void
    {
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $code = trim($_GET['code'] ?? '');
        if (!$code) {
            Response::error('Code client requis');
        }
        $client = Client::findByCode($code);
        if (!$client || $client['boutique_code'] !== $shop['code_boutique']) {
            Response::error('Client introuvable', [], 404);
        }

        $hist = Client::historique($code);
        Response::success('Historique client', [
            'client' => $client,
            'locations' => $hist['locations'],
            'total_locations' => $hist['total_locations'],
            'total_montant' => $hist['total_montant'],
            'total_paye' => $hist['total_paye'],
        ]);
    }
}
