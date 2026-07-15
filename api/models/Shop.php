<?php

require_once __DIR__ . '/../core/Database.php';

class Shop
{
    public static function findByUserCode(string $userCode): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM boutiques WHERE user_code = :user_code AND statut_boutique = "actif" LIMIT 1'
        );
        $stmt->execute(['user_code' => $userCode]);
        $shop = $stmt->fetch();
        return $shop ?: null;
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM boutiques WHERE code_boutique = :code LIMIT 1'
        );
        $stmt->execute(['code' => $code]);
        $shop = $stmt->fetch();
        return $shop ?: null;
    }

    public static function countAll(): int
    {
        $stmt = Database::getConnection()->query('SELECT COUNT(*) AS total FROM boutiques');
        return (int) $stmt->fetchColumn();
    }

    public static function createDefaultForUser(string $userCode): array
    {
        $codeBoutique = 'BTE' . time() . mt_rand(100, 999);
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO boutiques (code_boutique, user_code, libelle_boutique, devise_boutique, statut_boutique, created_at_boutique)
             VALUES (:code_boutique, :user_code, :libelle_boutique, :devise_boutique, :statut_boutique, :created_at_boutique)'
        );
        $stmt->execute([
            'code_boutique' => $codeBoutique,
            'user_code' => $userCode,
            'libelle_boutique' => 'Ma boutique',
            'devise_boutique' => 'F',
            'statut_boutique' => 'actif',
            'created_at_boutique' => date('Y-m-d H:i:s'),
        ]);
        return self::findByUserCode($userCode);
    }
}
