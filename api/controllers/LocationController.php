<?php

require_once __DIR__ . '/../core/Controller.php';

class LocationController extends Controller
{
    public function index(): void
    {
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }
        $search = trim($_GET['q'] ?? '');
        $statut = trim($_GET['statut'] ?? '');
        $locations = Location::allByBoutique($shop['code_boutique'], $search, $statut);
        Response::success('Locations', ['locations' => $locations]);
    }

    public function show(): void
    {
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $code = trim($_GET['code'] ?? '');
        if (!$code) {
            Response::error('Code location requis');
        }
        $location = Location::findByCode($code);
        if (!$location || $location['boutique_code'] !== $shop['code_boutique']) {
            Response::error('Location introuvable', [], 404);
        }

        $client = Client::findByCode($location['client_code']);
        $lignes = LigneLocation::findByLocation($code);
        $paiements = Paiement::allByLocation($code);

        Response::success('Détail location', [
            'location' => $location,
            'client' => $client,
            'lignes' => $lignes,
            'paiements' => $paiements,
        ]);
    }

    public function store(): void
    {
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $clientCode = trim($this->input('client_code', ''));
        if (!$clientCode) {
            Response::error('Client requis');
        }
        $client = Client::findByCode($clientCode);
        if (!$client || $client['boutique_code'] !== $shop['code_boutique']) {
            Response::error('Client introuvable', [], 404);
        }

        $dateSortie = trim($this->input('date_sortie', date('Y-m-d')));
        $dateRetour = trim($this->input('date_retour_prevue', ''));
        if (!$dateRetour) {
            Response::error('Date de retour prévue requise');
        }

        $lignes = $this->input('lignes', []);
        if (!is_array($lignes) || count($lignes) === 0) {
            Response::error('Ajoutez au moins un article');
        }

        $montant = 0.0;
        $lignesValidees = [];
        foreach ($lignes as $l) {
            $articleCode = trim($l['article_code'] ?? '');
            $quantite = (int) ($l['quantite'] ?? 0);
            $prix = (float) ($l['prix_unitaire'] ?? 0);
            if (!$articleCode || $quantite <= 0) {
                continue;
            }
            $article = Article::findActifByCode($articleCode);
            if (!$article || $article['boutique_code'] !== $shop['code_boutique']) {
                Response::error('Article invalide : ' . $articleCode, [], 400);
            }
            if ($quantite > (int) $article['quantite_article']) {
                Response::error('Stock insuffisant pour : ' . $article['libelle_article'], [], 400);
            }
            $montantLigne = $quantite * $prix;
            $montant += $montantLigne;
            $lignesValidees[] = [
                'article_code' => $articleCode,
                'quantite' => $quantite,
                'prix_unitaire' => $prix,
                'montant' => $montantLigne,
            ];
        }

        if (count($lignesValidees) === 0) {
            Response::error('Aucune ligne valide');
        }

        $avance = (float) $this->input('avance', 0);
        $reste = max(0, $montant - $avance);

        $codeLocation = 'LOC' . time() . mt_rand(100, 999);
        $now = date('Y-m-d H:i:s');

        $location = Location::create([
            'code_location' => $codeLocation,
            'boutique_code' => $shop['code_boutique'],
            'client_code' => $clientCode,
            'date_sortie_location' => $dateSortie,
            'date_retour_prevue_location' => $dateRetour,
            'montant_location' => $montant,
            'avance_location' => $avance,
            'reste_location' => $reste,
            'created_at_location' => $now,
        ], $lignesValidees);

        Response::success('Location enregistrée', ['location' => $location]);
    }

    public function retour(): void
    {
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $code = trim($this->input('code', ''));
        if (!$code) {
            Response::error('Code location requis');
        }
        $location = Location::findByCode($code);
        if (!$location || $location['boutique_code'] !== $shop['code_boutique']) {
            Response::error('Location introuvable', [], 404);
        }

        $lignes = $this->input('lignes', []);
        if (!is_array($lignes) || count($lignes) === 0) {
            Response::error('Indiquez les quantités retournées');
        }

        $lignesValidees = [];
        foreach ($lignes as $l) {
            $articleCode = trim($l['article_code'] ?? '');
            $quantite = (int) ($l['quantite'] ?? 0);
            if (!$articleCode || $quantite <= 0) {
                continue;
            }
            $lignesValidees[] = [
                'article_code' => $articleCode,
                'quantite' => $quantite,
            ];
        }
        if (count($lignesValidees) === 0) {
            Response::error('Aucune quantité valide');
        }

        $location = Location::retour($code, $lignesValidees);
        Response::success('Retour enregistré', ['location' => $location]);
    }

    public function addPaiement(): void
    {
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $code = trim($this->input('code', ''));
        if (!$code) {
            Response::error('Code location requis');
        }
        $location = Location::findByCode($code);
        if (!$location || $location['boutique_code'] !== $shop['code_boutique']) {
            Response::error('Location introuvable', [], 404);
        }

        $montant = (float) $this->input('montant', 0);
        if ($montant <= 0) {
            Response::error('Montant invalide');
        }
        $mode = trim($this->input('mode_paiement', 'especes'));
        $modesAutorises = ['especes', 'wave', 'orange', 'mtn', 'moov', 'carte', 'autre'];
        if (!in_array($mode, $modesAutorises, true)) {
            Response::error('Mode de paiement invalide');
        }

        $location = Location::addPaiement($code, [
            'code_paiement' => 'PAI' . time() . mt_rand(100, 999),
            'location_code' => $code,
            'montant_paiement' => $montant,
            'mode_paiement' => $mode,
            'reference_paiement' => trim($this->input('reference', '')) ?: null,
            'created_at_paiement' => date('Y-m-d H:i:s'),
        ]);

        Response::success('Paiement enregistré', ['location' => $location]);
    }
}
