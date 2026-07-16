<?php

require_once __DIR__ . '/../core/Database.php';

class Retour
{
    public static function create(array $data): array
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO retours (code_retour, location_code, boutique_code, user_code, date_retour, statut_retour, observation_retour, created_at_retour)
             VALUES (:code, :location, :boutique, :user, :date, :statut, :observation, :created)'
        );
        $stmt->execute([
            'code' => $data['code_retour'],
            'location' => $data['location_code'],
            'boutique' => $data['boutique_code'],
            'user' => $data['user_code'],
            'date' => $data['date_retour'],
            'statut' => $data['statut_retour'] ?? 'termine',
            'observation' => $data['observation_retour'] ?? null,
            'created' => $data['created_at_retour'],
        ]);
        return self::findByCode($data['code_retour']);
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM retours WHERE code_retour = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function allByLocation(string $locationCode): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare(
            'SELECT * FROM retours WHERE location_code = :location_code ORDER BY created_at_retour DESC'
        );
        $stmt->execute(['location_code' => $locationCode]);
        $retours = $stmt->fetchAll();

        foreach ($retours as &$retour) {
            $stmt2 = $pdo->prepare(
                'SELECT lr.*, a.libelle_article
                 FROM ligne_retours lr
                 LEFT JOIN articles a ON a.code_article = lr.article_code
                 WHERE lr.retour_code = :retour_code
                 ORDER BY lr.id_ligne_retour ASC'
            );
            $stmt2->execute(['retour_code' => $retour['code_retour']]);
            $retour['lignes'] = $stmt2->fetchAll();
        }

        return $retours;
    }
}
