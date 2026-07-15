<?php

require_once __DIR__ . '/../core/Database.php';

class Paiement
{
    public static function create(array $data): array
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO paiements (code_paiement, location_code, montant_paiement, mode_paiement, reference_paiement, created_at_paiement)
             VALUES (:code, :location, :montant, :mode, :reference, :created)'
        );
        $stmt->execute([
            'code' => $data['code_paiement'],
            'location' => $data['location_code'],
            'montant' => $data['montant_paiement'],
            'mode' => $data['mode_paiement'] ?? 'especes',
            'reference' => $data['reference_paiement'] ?? null,
            'created' => $data['created_at_paiement'],
        ]);
        return self::findByCode($data['code_paiement']);
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM paiements WHERE code_paiement = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        $p = $stmt->fetch();
        return $p ?: null;
    }

    public static function allByLocation(string $locationCode): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM paiements WHERE location_code = :location_code ORDER BY created_at_paiement ASC'
        );
        $stmt->execute(['location_code' => $locationCode]);
        return $stmt->fetchAll();
    }

    public static function totalByLocation(string $locationCode): float
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT COALESCE(SUM(montant_paiement),0) AS total FROM paiements WHERE location_code = :location_code'
        );
        $stmt->execute(['location_code' => $locationCode]);
        return (float) $stmt->fetchColumn();
    }
}
