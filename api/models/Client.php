<?php

require_once __DIR__ . '/../core/Database.php';

class Client
{
    public static function allByBoutique(string $boutiqueCode): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM clients WHERE boutique_code = :boutique_code ORDER BY nom_client ASC'
        );
        $stmt->execute(['boutique_code' => $boutiqueCode]);
        return $stmt->fetchAll();
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM clients WHERE code_client = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        $c = $stmt->fetch();
        return $c ?: null;
    }

    public static function findByPhone(string $phone, string $boutiqueCode): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM clients WHERE telephone_client = :phone AND boutique_code = :boutique_code LIMIT 1'
        );
        $stmt->execute(['phone' => $phone, 'boutique_code' => $boutiqueCode]);
        $c = $stmt->fetch();
        return $c ?: null;
    }

    public static function search(string $boutiqueCode, string $query, int $limit = 20): array
    {
        $like = '%' . $query . '%';
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM clients
             WHERE boutique_code = :boutique_code
               AND (nom_client LIKE :q1 OR telephone_client LIKE :q2)
             ORDER BY nom_client ASC LIMIT :limit'
        );
        $stmt->bindValue(':boutique_code', $boutiqueCode);
        $stmt->bindValue(':q1', $like);
        $stmt->bindValue(':q2', $like);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function create(array $data): array
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO clients (code_client, boutique_code, nom_client, telephone_client, adresse_client, statut_client, created_at_client)
             VALUES (:code_client, :boutique_code, :nom_client, :telephone_client, :adresse_client, :statut_client, :created_at_client)'
        );
        $stmt->execute([
            'code_client' => $data['code_client'],
            'boutique_code' => $data['boutique_code'],
            'nom_client' => $data['nom_client'],
            'telephone_client' => $data['telephone_client'] ?? null,
            'adresse_client' => $data['adresse_client'] ?? null,
            'statut_client' => $data['statut_client'] ?? 'actif',
            'created_at_client' => $data['created_at_client'],
        ]);
        return self::findByCode($data['code_client']);
    }

    public static function countByBoutique(string $boutiqueCode): int
    {
        $stmt = Database::getConnection()->prepare('SELECT COUNT(*) AS total FROM clients WHERE boutique_code = :boutique_code');
        $stmt->execute(['boutique_code' => $boutiqueCode]);
        return (int) $stmt->fetchColumn();
    }

    public static function historique(string $clientCode): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT l.*, c.nom_client
             FROM locations l
             LEFT JOIN clients c ON c.code_client = l.client_code
             WHERE l.client_code = :client_code
             ORDER BY l.created_at_location DESC'
        );
        $stmt->execute(['client_code' => $clientCode]);
        $locations = $stmt->fetchAll();

        $totalMontant = 0.0;
        $totalPaye = 0.0;
        foreach ($locations as $loc) {
            $totalMontant += (float) $loc['montant_location'];
            $totalPaye += (float) $loc['montant_location'] - (float) $loc['reste_location'];
        }

        return [
            'locations' => $locations,
            'total_locations' => count($locations),
            'total_montant' => $totalMontant,
            'total_paye' => $totalPaye,
        ];
    }
}
