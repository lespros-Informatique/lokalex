<?php

require_once __DIR__ . '/../core/Database.php';

class Forfait
{
    public static function all(): array
    {
        $stmt = Database::getConnection()->query('SELECT * FROM forfaits ORDER BY prix_forfait ASC');
        return $stmt->fetchAll();
    }

    public static function getActifs(): array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM forfaits WHERE statut_forfait = :statut ORDER BY prix_forfait ASC');
        $stmt->execute(['statut' => 'actif']);
        return $stmt->fetchAll();
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM forfaits WHERE code_forfait = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        $forfait = $stmt->fetch();
        return $forfait ?: null;
    }

    public static function create(array $data): array
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO forfaits (code_forfait, libelle_forfait, prix_forfait, duree_forfait, description_forfait, statut_forfait)
             VALUES (:code_forfait, :libelle_forfait, :prix_forfait, :duree_forfait, :description_forfait, :statut_forfait)'
        );
        $stmt->execute([
            'code_forfait' => $data['code_forfait'],
            'libelle_forfait' => $data['libelle_forfait'],
            'prix_forfait' => $data['prix_forfait'],
            'duree_forfait' => $data['duree_forfait'],
            'description_forfait' => $data['description_forfait'],
            'statut_forfait' => $data['statut_forfait'] ?? 'actif',
        ]);
        $id = Database::getConnection()->lastInsertId();
        return self::findById((int) $id);
    }

    public static function updateStatut(string $code, string $statut): ?array
    {
        $stmt = Database::getConnection()->prepare('UPDATE forfaits SET statut_forfait = :statut WHERE code_forfait = :code');
        $stmt->execute(['statut' => $statut, 'code' => $code]);
        return self::findByCode($code);
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM forfaits WHERE id_forfait = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $forfait = $stmt->fetch();
        return $forfait ?: null;
    }
}
