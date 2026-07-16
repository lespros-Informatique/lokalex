<?php

require_once __DIR__ . '/../core/Database.php';

class LigneRetour
{
    public static function create(array $data): array
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO ligne_retours (code_ligne_retour, retour_code, article_code, quantite_bonne, quantite_endommagee, quantite_perdue, montant_restitution, observation_ligne_retour, created_at_ligne_retour)
             VALUES (:code, :retour, :article, :bonne, :endommagee, :perdue, :montant_restitution, :observation, :created)'
        );
        $stmt->execute([
            'code' => $data['code_ligne_retour'],
            'retour' => $data['retour_code'],
            'article' => $data['article_code'],
            'bonne' => $data['quantite_bonne'],
            'endommagee' => $data['quantite_endommagee'],
            'perdue' => $data['quantite_perdue'],
            'montant_restitution' => $data['montant_restitution'] ?? 0,
            'observation' => $data['observation_ligne_retour'] ?? null,
            'created' => $data['created_at_ligne_retour'],
        ]);
        return self::findByCode($data['code_ligne_retour']);
    }

    public static function findByCode(string $code): ?array
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM ligne_retours WHERE code_ligne_retour = :code LIMIT 1');
        $stmt->execute(['code' => $code]);
        $r = $stmt->fetch();
        return $r ?: null;
    }

    public static function allByRetour(string $retourCode): array
    {
        $stmt = Database::getConnection()->prepare(
            'SELECT lr.*, a.libelle_article
             FROM ligne_retours lr
             LEFT JOIN articles a ON a.code_article = lr.article_code
             WHERE lr.retour_code = :retour_code
             ORDER BY lr.id_ligne_retour ASC'
        );
        $stmt->execute(['retour_code' => $retourCode]);
        return $stmt->fetchAll();
    }
}
