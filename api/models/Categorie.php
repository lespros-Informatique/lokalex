<?php

require_once __DIR__ . '/../core/Database.php';

class Categorie
{
    public static function all(string $boutiqueCode): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM categories WHERE boutique_code = :boutique_code ORDER BY libelle_categorie ASC'
        );
        $stmt->execute(['boutique_code' => $boutiqueCode]);
        return $stmt->fetchAll();
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM categories WHERE code_categorie = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        $cat = $stmt->fetch();
        return $cat ?: null;
    }

    public static function create(array $data): array
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO categories (code_categorie, boutique_code, libelle_categorie, statut_categorie, created_at_categorie)
             VALUES (:code_categorie, :boutique_code, :libelle_categorie, :statut_categorie, :created_at_categorie)'
        );
        $stmt->execute([
            'code_categorie' => $data['code_categorie'],
            'boutique_code' => $data['boutique_code'],
            'libelle_categorie' => $data['libelle_categorie'],
            'statut_categorie' => $data['statut_categorie'] ?? 'actif',
            'created_at_categorie' => $data['created_at_categorie'],
        ]);
        return self::findByCode($data['code_categorie']);
    }
}
