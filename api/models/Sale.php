<?php

require_once __DIR__ . '/../core/Database.php';

class Sale
{
    public static function create(array $data): array
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO ventes (code_vente, boutique_code, montant_vente, mode_paiement_vente, created_at_vente)
             VALUES (:code_vente, :boutique_code, :montant_vente, :mode_paiement_vente, :created_at_vente)'
        );
        $stmt->execute([
            'code_vente' => $data['code_vente'],
            'boutique_code' => $data['boutique_code'],
            'montant_vente' => $data['montant_vente'],
            'mode_paiement_vente' => $data['mode_paiement_vente'],
            'created_at_vente' => $data['created_at_vente'],
        ]);
        $id = Database::getConnection()->lastInsertId();
        return self::findById((int)$id);
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM ventes WHERE id_vente = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $sale = $stmt->fetch();
        return $sale ?: null;
    }

    public static function getRecentByShop(string $shopCode, int $limit = 10): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM ventes WHERE boutique_code = :boutique_code ORDER BY created_at_vente DESC LIMIT :limit'
        );
        $stmt->bindValue(':boutique_code', $shopCode);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function getTodayByShop(string $shopCode, string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM ventes WHERE boutique_code = :boutique_code AND DATE(created_at_vente) = :date'
        );
        $stmt->execute(['boutique_code' => $shopCode, 'date' => $date]);
        return $stmt->fetchAll();
    }

    public static function getAllByShop(string $shopCode): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM ventes WHERE boutique_code = :boutique_code ORDER BY created_at_vente DESC'
        );
        $stmt->execute(['boutique_code' => $shopCode]);
        return $stmt->fetchAll();
    }

    public static function getAll(): array
    {
        $stmt = Database::getConnection()->query('SELECT * FROM ventes ORDER BY created_at_vente DESC');
        return $stmt->fetchAll();
    }

    public static function search(string $shopCode, string $query, int $limit = 20): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM ventes
             WHERE boutique_code = :boutique_code
               AND (CAST(montant_vente AS CHAR) LIKE :query1 OR DATE_FORMAT(created_at_vente, "%d/%m/%Y %H:%i") LIKE :query2)
             ORDER BY created_at_vente DESC
             LIMIT :limit'
        );
        $like = '%' . $query . '%';
        $stmt->bindValue(':boutique_code', $shopCode);
        $stmt->bindValue(':query1', $like);
        $stmt->bindValue(':query2', $like);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function delete(string $codeVente): bool
    {
        $stmt = Database::getConnection()->prepare('DELETE FROM ventes WHERE code_vente = :code_vente');
        return $stmt->execute(['code_vente' => $codeVente]);
    }
}
