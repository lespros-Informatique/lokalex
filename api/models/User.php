<?php

require_once __DIR__ . '/../core/Database.php';

class User
{
    public static function findByPhone(string $phone): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM users WHERE telephone_user = :phone LIMIT 1'
        );
        $stmt->execute(['phone' => $phone]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function create(array $data): array
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO users (code_user, role_user, nom_user, telephone_user, statut_user, created_at_user)
             VALUES (:code_user, :role_user, :nom_user, :telephone_user, :statut_user, :created_at_user)'
        );
        $stmt->execute([
            'code_user' => $data['code_user'],
            'role_user' => $data['role_user'] ?? 'vendeur',
            'nom_user' => $data['nom_user'],
            'telephone_user' => $data['telephone_user'],
            'statut_user' => $data['statut_user'],
            'created_at_user' => $data['created_at_user'],
        ]);
        return self::findByPhone($data['telephone_user']);
    }

    public static function findShopByUser(string $userCode): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM boutiques WHERE user_code = :user_code AND statut_boutique = "actif" LIMIT 1'
        );
        $stmt->execute(['user_code' => $userCode]);
        $shop = $stmt->fetch();
        return $shop ?: null;
    }

    public static function findByUserCode(string $userCode): ?array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT * FROM users WHERE code_user = :code_user LIMIT 1'
        );
        $stmt->execute(['code_user' => $userCode]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public static function countByRole(string $role): int
    {
        $stmt = Database::getConnection()->prepare('SELECT COUNT(*) AS total FROM users WHERE role_user = :role');
        $stmt->execute(['role' => $role]);
        return (int) $stmt->fetchColumn();
    }
}
