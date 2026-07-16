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
        $total = count($locations);
        $params = $this->paginationParams();
        $items = array_slice($locations, $params['offset'], $params['limit']);
        $this->paginatedResponse($items, $total);
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

        try {
            $retours = Retour::allByLocation($code);
        } catch (\Throwable $e) {
            $retours = [];
        }
        try {
            $pendingRestitution = Retour::findPendingRestitutionByLocation($code);
        } catch (\Throwable $e) {
            $pendingRestitution = null;
        }

        Response::success('Détail location', [
            'location' => $location,
            'client' => $client,
            'lignes' => $lignes,
            'paiements' => $paiements,
            'retours' => $retours,
            'pending_restitution' => $pendingRestitution,
        ]);
    }

    public function store(): void
    {
        $this->requireCsrf();
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
        $this->requireCsrf();
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

        if ((float) ($location['reste_location'] ?? 0) > 0) {
            Response::error('Impossible de faire un retour tant que le reste à payer est supérieur à 0', [], 400);
        }

        $lignes = $this->input('lignes', []);
        if (!is_array($lignes) || count($lignes) === 0) {
            Response::error('Indiquez les quantités retournées');
        }

        $lignesValidees = [];
        $totalRendu = 0;
        $totalRestitution = 0;

        $retoursExistants = Retour::allByLocation($code);
        $retourExistant = !empty($retoursExistants) ? $retoursExistants[0] : null;
        $lignesRetourExistantes = $retourExistant['lignes'] ?? [];

        $dejaRetourneParArticle = [];
        foreach ($lignesRetourExistantes as $lr) {
            $articleCode = $lr['article_code'];
            $totalLigne = (int) ($lr['quantite_bonne'] ?? 0) + (int) ($lr['quantite_endommagee'] ?? 0) + (int) ($lr['quantite_perdue'] ?? 0);
            $dejaRetourneParArticle[$articleCode] = ($dejaRetourneParArticle[$articleCode] ?? 0) + $totalLigne;
        }

        foreach ($lignes as $l) {
            $articleCode = trim($l['article_code'] ?? '');
            $bonne = (int) ($l['quantite_bonne'] ?? 0);
            $endommagee = (int) ($l['quantite_endommagee'] ?? 0);
            $perdue = (int) ($l['quantite_perdue'] ?? 0);
            $montantRestitution = (float) ($l['montant_restitution'] ?? 0);
            $observation = trim($l['observation_ligne_retour'] ?? '');
            $totalLigne = $bonne + $endommagee + $perdue;

            if (!$articleCode || $totalLigne <= 0) {
                continue;
            }

            $dejaRetourne = $dejaRetourneParArticle[$articleCode] ?? 0;
            $stmtLigneLoc = Database::getConnection()->prepare('SELECT quantite_ligne_location FROM ligne_locations WHERE location_code = :code AND article_code = :article LIMIT 1');
            $stmtLigneLoc->execute(['code' => $code, 'article' => $articleCode]);
            $quantiteLouee = (int) $stmtLigneLoc->fetchColumn();

            if ($dejaRetourne + $totalLigne > $quantiteLouee) {
                Response::error("Quantité retournée dépasse la quantité louée pour l'article {$articleCode} (déjà retourné: {$dejaRetourne}, loué: {$quantiteLouee})", [], 400);
            }

            if ($montantRestitution < 0) {
                Response::error('Montant de restitution invalide', [], 400);
            }

            $lignesValidees[] = [
                'article_code' => $articleCode,
                'quantite_bonne' => $bonne,
                'quantite_endommagee' => $endommagee,
                'quantite_perdue' => $perdue,
                'montant_restitution' => $montantRestitution,
                'observation_ligne_retour' => $observation ?: null,
            ];
            $totalRendu += $totalLigne;
            $totalRestitution += $montantRestitution;
        }
        if (count($lignesValidees) === 0) {
            Response::error('Aucune quantité valide');
        }

        $pdo = Database::getConnection();
        $stmt = $pdo->prepare('SELECT SUM(quantite_ligne_location) as total FROM ligne_locations WHERE location_code = :code');
        $stmt->execute(['code' => $code]);
        $totalLivre = (int) $stmt->fetchColumn();

        $statutRetour = ($totalRendu >= $totalLivre) ? 'termine' : 'partiel';
        $retourCode = 'RET' . time() . mt_rand(100, 999);

        $retours = Retour::allByLocation($code);
        $existingRetourCode = !empty($retours) ? $retours[0]['code_retour'] : null;

        try {
            $location = Location::retour($code, $lignesValidees, $retourCode, $shop['code_boutique'], $user['code_user'], $statutRetour, $existingRetourCode);
            Response::success('Retour enregistré', ['location' => $location, 'retour_code' => $existingRetourCode ?: $retourCode, 'total_restitution' => $totalRestitution]);
        } catch (\Throwable $e) {
            Response::error('Retour invalide : ' . $e->getMessage(), ['trace' => $e->getTraceAsString()], 400);
        }
    }

    public function payerRestitution(): void
    {
        $this->requireCsrf();
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $code = trim($this->input('code', ''));
        if (!$code) {
            Response::error('Code retour requis');
        }

        $retour = Retour::findByCode($code);
        if (!$retour || $retour['boutique_code'] !== $shop['code_boutique']) {
            Response::error('Retour introuvable', [], 404);
        }

        if ($retour['statut_restitution'] === 'paye') {
            Response::error('Restitution déjà payée');
        }

        $montant = (float) ($this->input('montant', 0));
        if ($montant <= 0) {
            Response::error('Montant de restitution invalide');
        }

        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('UPDATE retours SET statut_restitution = :statut, updated_at_retour = :updated WHERE code_retour = :code');
            $stmt->execute([
                'statut' => 'paye',
                'updated' => date('Y-m-d H:i:s'),
                'code' => $code,
            ]);

            $location = Location::findByCode($retour['location_code']);
            if ($location && $location['statut_location'] === 'en_cours') {
                $stmt2 = $pdo->prepare('UPDATE locations SET statut_location = :statut, updated_at_location = :updated WHERE code_location = :code');
                $stmt2->execute([
                    'statut' => 'terminee',
                    'updated' => date('Y-m-d H:i:s'),
                    'code' => $retour['location_code'],
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        $location = Location::findByCode($retour['location_code']);
        Response::success('Restitution payée', ['location' => $location]);
    }

    public function addPaiement(): void
    {
        $this->requireCsrf();
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
