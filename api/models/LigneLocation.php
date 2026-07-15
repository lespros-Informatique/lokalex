<?php

require_once __DIR__ . '/../core/Database.php';

class LigneLocation
{
    public static function createMany(string $locationCode, array $lignes): void
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO ligne_locations (code_ligne_location, location_code, article_code, quantite_ligne_location, prix_unitaire_ligne_location, montant_ligne_location, created_at_ligne_location)
             VALUES (:code, :location, :article, :quantite, :prix, :montant, :created)'
        );
        foreach ($lignes as $l) {
            $stmt->execute([
                'code' => $l['code_ligne_location'],
                'location' => $locationCode,
                'article' => $l['article_code'],
                'quantite' => $l['quantite'],
                'prix' => $l['prix_unitaire'],
                'montant' => $l['montant'],
                'created' => $l['created_at'],
            ]);
        }
    }

    public static function findByLocation(string $locationCode): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT ll.*, a.libelle_article
             FROM ligne_locations ll
             LEFT JOIN articles a ON a.code_article = ll.article_code
             WHERE ll.location_code = :location_code
             ORDER BY ll.id_ligne_location ASC'
        );
        $stmt->execute(['location_code' => $locationCode]);
        return $stmt->fetchAll();
    }
}
