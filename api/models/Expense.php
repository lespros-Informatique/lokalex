<?php

require_once __DIR__ . '/../core/Database.php';

class Expense
{
    public static function create(array $data): array
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO depenses (code_depense, boutique_code, libelle_depense, montant_depense, date_depense_depense, created_at_depense)
             VALUES (:code_depense, :boutique_code, :libelle_depense, :montant_depense, :date_depense_depense, :created_at_depense)'
        );
        $stmt->execute([
            'code_depense' => $data['code_depense'],
            'boutique_code' => $data['boutique_code'],
            'libelle_depense' => $data['libelle_depense'],
            'montant_depense' => $data['montant_depense'],
            'date_depense_depense' => $data['date_depense_depense'],
            'created_at_depense' => $data['created_at_depense'],
        ]);
        $id = Database::getConnection()->lastInsertId();
        return self::findById((int)$id);
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM depenses WHERE id_depense = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $expense = $stmt->fetch();
        return $expense ?: null;
    }

    public static function getTodayByShop(string $shopCode, string $date = null): array
    {
        $date = $date ?? date('Y-m-d');
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM depenses WHERE boutique_code = :boutique_code AND DATE(date_depense_depense) = :date'
        );
        $stmt->execute(['boutique_code' => $shopCode, 'date' => $date]);
        return $stmt->fetchAll();
    }

    public static function getAllByShop(string $shopCode): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM depenses WHERE boutique_code = :boutique_code ORDER BY date_depense_depense DESC'
        );
        $stmt->execute(['boutique_code' => $shopCode]);
        return $stmt->fetchAll();
    }

    public static function getAll(): array
    {
        $stmt = Database::getConnection()->query('SELECT * FROM depenses ORDER BY date_depense_depense DESC');
        return $stmt->fetchAll();
    }

    public static function delete(string $codeDepense): bool
    {
        $stmt = Database::getConnection()->prepare('DELETE FROM depenses WHERE code_depense = :code_depense');
        return $stmt->execute(['code_depense' => $codeDepense]);
    }
}
