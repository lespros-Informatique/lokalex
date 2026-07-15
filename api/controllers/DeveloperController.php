<?php

require_once __DIR__ . '/../core/Controller.php';

class DeveloperController extends Controller
{
    public function requireDeveloper(): array
    {
        $user = $this->requireAuth();
        if (($user['role_user'] ?? '') !== 'developpeur') {
            Response::error('Accès réservé aux développeurs', [], 403);
        }
        return $user;
    }

    public function listUsers(): void
    {
        $this->requireDeveloper();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        $search = trim($_GET['search'] ?? '');

        $where = '';
        $params = [];
        if ($search !== '') {
            $where = 'WHERE nom_user LIKE :search1 OR telephone_user LIKE :search2 OR code_user LIKE :search3';
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
        }

        $countSql = 'SELECT COUNT(*) FROM users ' . $where;
        $countStmt = Database::getConnection()->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        $limitInt = (int)$limit;
        $offsetInt = (int)(($page - 1) * $limit);
        $sql = 'SELECT id_user, code_user, role_user, nom_user, telephone_user, statut_user, created_at_user FROM users ' . $where . ' ORDER BY created_at_user DESC LIMIT ' . $limitInt . ' OFFSET ' . $offsetInt;
        $stmt = Database::getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $users = $stmt->fetchAll();

        Response::success('Liste des utilisateurs', [
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'has_more' => $page * $limit < $total,
            ],
        ]);
    }

    public function createUser(): void
    {
        $this->requireDeveloper();

        $phone = trim($this->input('phone', $this->input('telephone', '')));
        $name = trim($this->input('name', $this->input('nom_user', '')));
        $role = trim($this->input('role', 'vendeur'));

        if (!$phone) {
            Response::error('Numéro de téléphone requis');
        }
        if (!$name) {
            Response::error('Nom requis');
        }
        if (!in_array($role, ['vendeur', 'developpeur'], true)) {
            Response::error('Rôle invalide');
        }

        $existing = User::findByPhone($phone);
        if ($existing) {
            Response::error('Ce numéro est déjà utilisé', [], 409);
        }

        $code = 'USR' . time() . mt_rand(100, 999);
        $user = User::create([
            'code_user' => $code,
            'role_user' => $role,
            'nom_user' => $name,
            'telephone_user' => $phone,
            'statut_user' => 'actif',
            'created_at_user' => date('Y-m-d H:i:s'),
        ]);

        Response::success('Utilisateur créé', ['user' => $user]);
    }

    public function createShop(): void
    {
        $this->requireDeveloper();

        $userCode = trim($this->input('user_code', ''));
        $label = trim($this->input('label', $this->input('libelle_boutique', '')));
        $currency = trim($this->input('currency', 'F'));
        $forfaitCode = trim($this->input('forfait_code', ''));

        if (!$userCode) {
            Response::error('Code utilisateur requis');
        }
        if (!$label) {
            Response::error('Libellé boutique requis');
        }

        $user = User::findByUserCode($userCode);
        if (!$user) {
            Response::error('Utilisateur introuvable', [], 404);
        }

        $shop = Shop::findByUserCode($userCode);
        if ($shop) {
            Response::error('Cet utilisateur a déjà une boutique', [], 409);
        }

        $codeBoutique = 'BTE' . time() . mt_rand(100, 999);
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO boutiques (code_boutique, user_code, libelle_boutique, devise_boutique, statut_boutique, created_at_boutique)
             VALUES (:code_boutique, :user_code, :libelle_boutique, :devise_boutique, :statut_boutique, :created_at_boutique)'
        );
        $stmt->execute([
            'code_boutique' => $codeBoutique,
            'user_code' => $userCode,
            'libelle_boutique' => $label,
            'devise_boutique' => $currency,
            'statut_boutique' => 'actif',
            'created_at_boutique' => date('Y-m-d H:i:s'),
        ]);

        $shop = Shop::findByUserCode($userCode);

        $abonnement = null;
        if ($forfaitCode) {
            $forfait = Forfait::findByCode($forfaitCode);
            if ($forfait && ($forfait['statut_forfait'] ?? '') === 'actif') {
                $debut = date('Y-m-d');
                $fin = date('Y-m-d', strtotime('+' . (int) $forfait['duree_forfait'] . ' days'));
                $abonnement = Abonnement::create([
                    'code_abonnement' => 'ABO' . time() . mt_rand(100, 999),
                    'boutique_code' => $shop['code_boutique'],
                    'forfait_code' => $forfait['code_forfait'],
                    'date_debut_abonnement' => $debut,
                    'date_fin_abonnement' => $fin,
                    'montant_abonnement' => $forfait['prix_forfait'],
                    'statut_abonnement' => 'actif',
                ]);
            }
        }

        Response::success('Boutique créée', ['shop' => $shop, 'abonnement' => $abonnement]);
    }

    public function createAbonnement(): void
    {
        $this->requireDeveloper();

        $boutiqueCode = trim($this->input('boutique_code', ''));
        $forfaitCode = trim($this->input('forfait_code', ''));

        if (!$boutiqueCode) {
            Response::error('Code boutique requis');
        }
        if (!$forfaitCode) {
            Response::error('Forfait requis');
        }

        $shop = Shop::findByCode($boutiqueCode);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $forfait = Forfait::findByCode($forfaitCode);
        if (!$forfait || ($forfait['statut_forfait'] ?? '') !== 'actif') {
            Response::error('Forfait invalide', [], 404);
        }

        $debut = date('Y-m-d');
        $fin = date('Y-m-d', strtotime('+' . (int) $forfait['duree_forfait'] . ' days'));

        $abonnement = Abonnement::create([
            'code_abonnement' => 'ABO' . time() . mt_rand(100, 999),
            'boutique_code' => $shop['code_boutique'],
            'forfait_code' => $forfait['code_forfait'],
            'date_debut_abonnement' => $debut,
            'date_fin_abonnement' => $fin,
            'montant_abonnement' => $forfait['prix_forfait'],
            'statut_abonnement' => 'actif',
        ]);

        Response::success('Abonnement créé', ['abonnement' => $abonnement]);
    }

    public function userDetail(): void
    {
        $this->requireDeveloper();

        $userCode = trim($_GET['code'] ?? '');
        if (!$userCode) {
            Response::error('Code utilisateur requis');
        }

        $user = User::findByUserCode($userCode);
        if (!$user) {
            Response::error('Utilisateur introuvable', [], 404);
        }

        $shop = Shop::findByUserCode($userCode);

        $sales = [];
        $expenses = [];
        $transactions = [];
        $pagination = ['page' => 1, 'limit' => 20, 'total' => 0, 'has_more' => false];

        if ($shop) {
            $sales = Sale::getAllByShop($shop['code_boutique']);
            $expenses = Expense::getAllByShop($shop['code_boutique']);

            foreach ($sales as $sale) {
                $transactions[] = [
                    'type' => 'vente',
                    'id' => $sale['code_vente'],
                    'amount' => (float) $sale['montant_vente'],
                    'mode' => $sale['mode_paiement_vente'],
                    'date' => $sale['created_at_vente'],
                ];
            }

            foreach ($expenses as $expense) {
                $transactions[] = [
                    'type' => 'depense',
                    'id' => $expense['code_depense'],
                    'title' => $expense['libelle_depense'],
                    'amount' => (float) $expense['montant_depense'],
                    'mode' => '-',
                    'date' => $expense['date_depense_depense'],
                ];
            }

            usort($transactions, function ($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });

            $txPage = max(1, (int)($_GET['tx_page'] ?? 1));
            $txLimit = min(100, max(1, (int)($_GET['tx_limit'] ?? 20)));
            $total = count($transactions);
            $offset = ($txPage - 1) * $txLimit;
            $transactions = array_slice($transactions, $offset, $txLimit);
            $pagination = [
                'page' => $txPage,
                'limit' => $txLimit,
                'total' => $total,
                'has_more' => ($txPage * $txLimit) < $total,
            ];
        }

        Response::success('Détail utilisateur', [
            'user' => $user,
            'shop' => $shop,
            'transactions' => $transactions,
            'pagination' => $pagination,
        ]);
    }

    public function listShops(): void
    {
        $this->requireDeveloper();

        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));
        $search = trim($_GET['search'] ?? '');

        $where = '';
        $params = [];
        if ($search !== '') {
            $where = 'WHERE libelle_boutique LIKE :search1 OR code_boutique LIKE :search2 OR devise_boutique LIKE :search3';
            $params[':search1'] = '%' . $search . '%';
            $params[':search2'] = '%' . $search . '%';
            $params[':search3'] = '%' . $search . '%';
        }

        $countSql = 'SELECT COUNT(*) FROM boutiques ' . $where;
        $countStmt = Database::getConnection()->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        $limitInt = (int)$limit;
        $offsetInt = (int)(($page - 1) * $limit);
        $sql = 'SELECT id, code_boutique, user_code, libelle_boutique, devise_boutique, statut_boutique, created_at_boutique FROM boutiques ' . $where . ' ORDER BY created_at_boutique DESC LIMIT ' . $limitInt . ' OFFSET ' . $offsetInt;
        $stmt = Database::getConnection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $shops = $stmt->fetchAll();

        Response::success('Liste des boutiques', [
            'shops' => $shops,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'has_more' => $page * $limit < $total,
            ],
        ]);
    }

    public function shopDetail(): void
    {
        $this->requireDeveloper();

        $shopCode = trim($_GET['code'] ?? '');
        if (!$shopCode) {
            Response::error('Code boutique requis');
        }

        $stmt = Database::getConnection()->prepare('SELECT * FROM boutiques WHERE code_boutique = :code LIMIT 1');
        $stmt->execute(['code' => $shopCode]);
        $shop = $stmt->fetch();
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $sales = Sale::getAllByShop($shopCode);
        $expenses = Expense::getAllByShop($shopCode);

        $transactions = [];
        foreach ($sales as $sale) {
            $transactions[] = [
                'type' => 'vente',
                'id' => $sale['code_vente'],
                'amount' => (float) $sale['montant_vente'],
                'mode' => $sale['mode_paiement_vente'],
                'date' => $sale['created_at_vente'],
            ];
        }
        foreach ($expenses as $expense) {
            $transactions[] = [
                'type' => 'depense',
                'id' => $expense['code_depense'],
                'title' => $expense['libelle_depense'],
                'amount' => (float) $expense['montant_depense'],
                'mode' => '-',
                'date' => $expense['date_depense_depense'],
            ];
        }
        usort($transactions, function ($a, $b) { return strtotime($b['date']) - strtotime($a['date']); });

        $txPage = max(1, (int)($_GET['tx_page'] ?? 1));
        $txLimit = min(100, max(1, (int)($_GET['tx_limit'] ?? 20)));
        $total = count($transactions);
        $offset = ($txPage - 1) * $txLimit;
        $transactions = array_slice($transactions, $offset, $txLimit);
        $pagination = [
            'page' => $txPage,
            'limit' => $txLimit,
            'total' => $total,
            'has_more' => ($txPage * $txLimit) < $total,
        ];

        $totalSales = array_sum(array_column($sales, 'montant_vente'));
        $totalExpenses = array_sum(array_column($expenses, 'montant_depense'));

        Response::success('Détail boutique', [
            'shop' => $shop,
            'transactions' => $transactions,
            'totals' => [
                'sales' => $this->formatMoney($totalSales),
                'expenses' => $this->formatMoney($totalExpenses),
                'net' => $this->formatMoney($totalSales - $totalExpenses),
            ],
            'pagination' => $pagination,
        ]);
    }

    public function listForfaitsDev(): void
    {
        $this->requireDeveloper();
        $forfaits = Forfait::all();
        Response::success('Forfaits', ['forfaits' => $forfaits]);
    }

    public function createForfait(): void
    {
        $this->requireDeveloper();

        $libelle = trim($this->input('libelle', $this->input('libelle_forfait', '')));
        $prix = (float) $this->input('prix', 0);
        $duree = (int) $this->input('duree', 0);
        $description = trim($this->input('description', ''));

        if (!$libelle) {
            Response::error('Libellé requis');
        }
        if ($prix < 0) {
            Response::error('Prix invalide');
        }
        if ($duree <= 0) {
            Response::error('Durée invalide');
        }

        $forfait = Forfait::create([
            'code_forfait' => 'FOR' . time() . mt_rand(100, 999),
            'libelle_forfait' => $libelle,
            'prix_forfait' => $prix,
            'duree_forfait' => $duree,
            'description_forfait' => $description,
            'statut_forfait' => 'actif',
        ]);

        Response::success('Forfait créé', ['forfait' => $forfait]);
    }

    public function listAbonnements(): void
    {
        $this->requireDeveloper();
        $abonnements = Abonnement::all();
        Response::success('Abonnements', ['abonnements' => $abonnements]);
    }

    public function setAbonnementStatut(): void
    {
        $this->requireDeveloper();

        $code = trim($this->input('code', $this->input('code_abonnement', '')));
        $statut = trim($this->input('statut', ''));

        if (!$code) {
            Response::error('Code abonnement requis');
        }

        $statutsAutorises = ['en_attente', 'actif', 'expire', 'suspendu'];
        if (!in_array($statut, $statutsAutorises, true)) {
            Response::error('Statut invalide');
        }

        if (!Abonnement::findByCode($code)) {
            Response::error('Abonnement introuvable', [], 404);
        }

        $abonnement = Abonnement::updateStatut($code, $statut);
        Response::success('Statut mis à jour', ['abonnement' => $abonnement]);
    }

    private function formatMoney(float $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' F';
    }
}
