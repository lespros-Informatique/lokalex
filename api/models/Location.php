<?php

require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/LigneLocation.php';
require_once __DIR__ . '/Article.php';
require_once __DIR__ . '/Paiement.php';

class Location
{
    public static function create(array $data, array $lignes): array
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO locations (code_location, boutique_code, client_code, date_sortie_location, date_retour_prevue_location, montant_location, avance_location, reste_location, statut_location, created_at_location)
                 VALUES (:code, :boutique, :client, :sortie, :retour, :montant, :avance, :reste, :statut, :created)'
            );
            $stmt->execute([
                'code' => $data['code_location'],
                'boutique' => $data['boutique_code'],
                'client' => $data['client_code'],
                'sortie' => $data['date_sortie_location'],
                'retour' => $data['date_retour_prevue_location'],
                'montant' => $data['montant_location'],
                'avance' => $data['avance_location'] ?? 0,
                'reste' => $data['reste_location'],
                'statut' => 'en_cours',
                'created' => $data['created_at_location'],
            ]);

            $pdoLignes = [];
            foreach ($lignes as $l) {
                $pdoLignes[] = [
                    'code_ligne_location' => 'LL' . time() . mt_rand(100, 999) . '_' . $l['article_code'],
                    'article_code' => $l['article_code'],
                    'quantite' => $l['quantite'],
                    'prix_unitaire' => $l['prix_unitaire'],
                    'montant' => $l['montant'],
                    'created_at' => $data['created_at_location'],
                ];
                Article::adjustStock($l['article_code'], -$l['quantite']);
            }
            LigneLocation::createMany($data['code_location'], $pdoLignes);

            if ((float) ($data['avance_location'] ?? 0) > 0) {
                Paiement::create([
                    'code_paiement' => 'PAI' . time() . mt_rand(100, 999),
                    'location_code' => $data['code_location'],
                    'montant_paiement' => $data['avance_location'],
                    'mode_paiement' => 'especes',
                    'reference_paiement' => null,
                    'created_at_paiement' => $data['created_at_location'],
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return self::findByCode($data['code_location']);
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM locations WHERE code_location = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        $l = $stmt->fetch();
        return $l ?: null;
    }

    public static function all(): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT l.*, c.nom_client, c.telephone_client
             FROM locations l
             LEFT JOIN clients c ON c.code_client = l.client_code
             ORDER BY l.created_at_location DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function allByBoutique(string $boutiqueCode, string $search = '', string $statut = ''): array
    {
        $sql = 'SELECT l.*, c.nom_client, c.telephone_client
                FROM locations l
                LEFT JOIN clients c ON c.code_client = l.client_code
                WHERE l.boutique_code = :boutique_code';
        $params = ['boutique_code' => $boutiqueCode];
        if ($search !== '') {
            $sql .= ' AND (c.nom_client LIKE :s1 OR c.telephone_client LIKE :s2 OR l.code_location LIKE :s3)';
            $like = '%' . $search . '%';
            $params['s1'] = $like;
            $params['s2'] = $like;
            $params['s3'] = $like;
        }
        if ($statut !== '') {
            $sql .= ' AND l.statut_location = :statut';
            $params['statut'] = $statut;
        }
        $sql .= ' ORDER BY l.created_at_location DESC';
        $stmt = Database::getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function getRecentByBoutique(string $boutiqueCode, int $limit = 10): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT l.*, c.nom_client
             FROM locations l
             LEFT JOIN clients c ON c.code_client = l.client_code
             WHERE l.boutique_code = :boutique_code
             ORDER BY l.created_at_location DESC LIMIT :limit'
        );
        $stmt->bindValue(':boutique_code', $boutiqueCode);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function retour(string $code, array $lignes): ?array
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            foreach ($lignes as $l) {
                Article::adjustStock($l['article_code'], $l['quantite']);
            }
            $stmt = $pdo->prepare(
                'UPDATE locations SET date_retour_effective_location = :date, statut_location = :statut, updated_at_location = :updated WHERE code_location = :code'
            );
            $stmt->execute([
                'code' => $code,
                'date' => date('Y-m-d'),
                'statut' => 'terminee',
                'updated' => date('Y-m-d H:i:s'),
            ]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return self::findByCode($code);
    }

    public static function addPaiement(string $code, array $paiementData): array
    {
        $pdo = Database::getConnection();
        $pdo->beginTransaction();
        try {
            Paiement::create($paiementData);
            $location = self::findByCode($code);
            $totalPaye = Paiement::totalByLocation($code);
            $reste = max(0, (float) $location['montant_location'] - $totalPaye);
            $stmt = $pdo->prepare('UPDATE locations SET reste_location = :reste, updated_at_location = :updated WHERE code_location = :code');
            $stmt->execute([
                'code' => $code,
                'reste' => $reste,
                'updated' => date('Y-m-d H:i:s'),
            ]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return self::findByCode($code);
    }

    public static function stats(string $boutiqueCode, string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $pdo = Database::getConnection();

        $enCours = $pdo->prepare('SELECT COUNT(*) FROM locations WHERE boutique_code = :b AND statut_location = \'en_cours\'');
        $enCours->execute(['b' => $boutiqueCode]);

        $retoursPrevus = $pdo->prepare('SELECT COUNT(*) FROM locations WHERE boutique_code = :b AND date_retour_prevue_location = :d');
        $retoursPrevus->execute(['b' => $boutiqueCode, 'd' => $date]);

        $montantJour = $pdo->prepare('SELECT COALESCE(SUM(montant_location),0) FROM locations WHERE boutique_code = :b AND date_sortie_location = :d');
        $montantJour->execute(['b' => $boutiqueCode, 'd' => $date]);

        $retards = $pdo->prepare('SELECT COUNT(*) FROM locations WHERE boutique_code = :b AND statut_location = \'en_cours\' AND date_retour_prevue_location < CURDATE()');
        $retards->execute(['b' => $boutiqueCode]);

        return [
            'en_cours' => (int) $enCours->fetchColumn(),
            'retours_prevus' => (int) $retoursPrevus->fetchColumn(),
            'montant_jour' => (float) $montantJour->fetchColumn(),
            'retards' => (int) $retards->fetchColumn(),
        ];
    }
}
