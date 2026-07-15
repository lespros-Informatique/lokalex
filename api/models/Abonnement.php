<?php

require_once __DIR__ . '/../core/Database.php';

class Abonnement
{
    public static function findActiveByBoutique(string $boutiqueCode): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT a.* FROM abonnements a
             WHERE a.boutique_code = :boutique_code
               AND a.statut_abonnement = :statut
               AND a.date_fin_abonnement >= CURDATE()
             ORDER BY a.date_fin_abonnement DESC
             LIMIT 1'
        );
        $stmt->execute(['boutique_code' => $boutiqueCode, 'statut' => 'actif']);
        $abonnement = $stmt->fetch();
        return $abonnement ?: null;
    }

    public static function isActive(string $boutiqueCode): bool
    {
        return self::findActiveByBoutique($boutiqueCode) !== null;
    }

    public static function all(): array
    {
        $stmt = Database::getConnection()->query('SELECT * FROM abonnements ORDER BY created_at_abonnement DESC');
        return $stmt->fetchAll();
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM abonnements WHERE code_abonnement = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        $abonnement = $stmt->fetch();
        return $abonnement ?: null;
    }

    public static function updateStatut(string $code, string $statut): ?array
    {
        $stmt = Database::getConnection()->prepare('UPDATE abonnements SET statut_abonnement = :statut WHERE code_abonnement = :code');
        $stmt->execute(['statut' => $statut, 'code' => $code]);
        return self::findByCode($code);
    }

    public static function countExpired(): int
    {
        $stmt = Database::getConnection()->query(
            "SELECT COUNT(*) AS total FROM abonnements
             WHERE NOT (statut_abonnement = 'actif' AND date_fin_abonnement >= CURDATE())"
        );
        return (int) $stmt->fetchColumn();
    }

    public static function findByBoutique(string $boutiqueCode): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM abonnements WHERE boutique_code = :boutique_code ORDER BY date_fin_abonnement DESC'
        );
        $stmt->execute(['boutique_code' => $boutiqueCode]);
        return $stmt->fetchAll();
    }

    public static function create(array $data): array
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO abonnements (code_abonnement, boutique_code, forfait_code, date_debut_abonnement, date_fin_abonnement, montant_abonnement, statut_abonnement)
             VALUES (:code_abonnement, :boutique_code, :forfait_code, :date_debut_abonnement, :date_fin_abonnement, :montant_abonnement, :statut_abonnement)'
        );
        $stmt->execute([
            'code_abonnement' => $data['code_abonnement'],
            'boutique_code' => $data['boutique_code'],
            'forfait_code' => $data['forfait_code'],
            'date_debut_abonnement' => $data['date_debut_abonnement'],
            'date_fin_abonnement' => $data['date_fin_abonnement'],
            'montant_abonnement' => $data['montant_abonnement'],
            'statut_abonnement' => $data['statut_abonnement'] ?? 'actif',
        ]);
        $id = Database::getConnection()->lastInsertId();
        return self::findById((int) $id);
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM abonnements WHERE id_abonnement = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $abonnement = $stmt->fetch();
        return $abonnement ?: null;
    }
}
