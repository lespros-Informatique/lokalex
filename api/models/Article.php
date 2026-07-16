<?php

require_once __DIR__ . '/../core/Database.php';

class Article
{
    public static function allByBoutique(string $boutiqueCode): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT a.*, c.libelle_categorie
             FROM articles a
             LEFT JOIN categories c ON c.code_categorie = a.categorie_code
             WHERE a.boutique_code = :boutique_code
             ORDER BY a.libelle_article ASC'
        );
        $stmt->execute(['boutique_code' => $boutiqueCode]);
        return $stmt->fetchAll();
    }

    public static function findActifByCode(string $code): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM articles WHERE code_article = :code AND statut_article = "actif" LIMIT 1'
        );
        $stmt->execute(['code' => $code]);
        $art = $stmt->fetch();
        return $art ?: null;
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM articles WHERE code_article = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        $art = $stmt->fetch();
        return $art ?: null;
    }

    public static function create(array $data): array
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO articles (code_article, boutique_code, categorie_code, libelle_article, quantite_article, prix_location_article, statut_article, created_at_article)
             VALUES (:code_article, :boutique_code, :categorie_code, :libelle_article, :quantite_article, :prix_location_article, :statut_article, :created_at_article)'
        );
        $stmt->execute([
            'code_article' => $data['code_article'],
            'boutique_code' => $data['boutique_code'],
            'categorie_code' => $data['categorie_code'],
            'libelle_article' => $data['libelle_article'],
            'quantite_article' => $data['quantite_article'] ?? 0,
            'prix_location_article' => $data['prix_location_article'] ?? 0,
            'statut_article' => $data['statut_article'] ?? 'actif',
            'created_at_article' => $data['created_at_article'],
        ]);
        return self::findByCode($data['code_article']);
    }

    public static function update(array $data): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE articles
             SET libelle_article = :libelle_article,
                 categorie_code = :categorie_code,
                 quantite_article = :quantite_article,
                 prix_location_article = :prix_location_article,
                 updated_at_article = :updated_at_article
             WHERE code_article = :code_article'
        );
        $stmt->execute([
            'code_article' => $data['code_article'],
            'libelle_article' => $data['libelle_article'],
            'categorie_code' => $data['categorie_code'],
            'quantite_article' => $data['quantite_article'],
            'prix_location_article' => $data['prix_location_article'],
            'updated_at_article' => $data['updated_at_article'],
        ]);
        return self::findByCode($data['code_article']);
    }

    public static function desactiver(string $code): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE articles SET statut_article = :statut, updated_at_article = :updated WHERE code_article = :code'
        );
        $stmt->execute([
            'code' => $code,
            'statut' => 'inactif',
            'updated' => date('Y-m-d H:i:s'),
        ]);
        return self::findByCode($code);
    }

    public static function adjustStock(string $code, int $delta): void
    {
        $stmt = Database::getConnection()->prepare(
            'UPDATE articles SET quantite_article = GREATEST(0, quantite_article + :delta), updated_at_article = :updated WHERE code_article = :code'
        );
        $stmt->execute([
            'code' => $code,
            'delta' => $delta,
            'updated' => date('Y-m-d H:i:s'),
        ]);
    }

    public static function history(string $code): array
    {
        $pdo = Database::getConnection();

        try {
            $stmt = $pdo->prepare(
                'SELECT ll.*, l.code_location, l.date_sortie_location, l.date_retour_prevue_location, l.date_retour_effective_location, l.statut_location, c.nom_client
                 FROM ligne_locations ll
                 JOIN locations l ON l.code_location = ll.location_code
                 LEFT JOIN clients c ON c.code_client = l.client_code
                 WHERE ll.article_code = :code
                 ORDER BY l.date_sortie_location DESC, l.created_at_location DESC'
            );
            $stmt->execute(['code' => $code]);
            $locations = $stmt->fetchAll();
        } catch (\Throwable $e) {
            $locations = [];
        }

        try {
            $stmt2 = $pdo->prepare(
                'SELECT lr.*, r.code_retour, r.date_retour, r.statut_retour, r.statut_restitution
                 FROM ligne_retours lr
                 JOIN retours r ON r.code_retour = lr.retour_code
                 WHERE lr.article_code = :code
                 ORDER BY r.date_retour DESC, lr.id_ligne_retour ASC'
            );
            $stmt2->execute(['code' => $code]);
            $retours = $stmt2->fetchAll();
        } catch (\Throwable $e) {
            $retours = [];
        }

        return [
            'locations' => $locations,
            'retours' => $retours,
        ];
    }
}
