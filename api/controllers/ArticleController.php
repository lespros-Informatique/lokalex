<?php

require_once __DIR__ . '/../core/Controller.php';

class ArticleController extends Controller
{
    public function index(): void
    {
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }
        $articles = Article::allByBoutique($shop['code_boutique']);
        $total = count($articles);
        $params = $this->paginationParams();
        $items = array_slice($articles, $params['offset'], $params['limit']);
        $this->paginatedResponse($items, $total);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $libelle = trim($this->input('libelle', $this->input('libelle_article', '')));
        $categorie = trim($this->input('categorie_code', $this->input('categorie', '')));
        $quantite = (int) $this->input('quantite', 0);
        $prix = (float) $this->input('prix_location', 0);

        if (!$libelle) {
            Response::error('Libellé requis');
        }

        $article = Article::create([
            'code_article' => 'ART' . time() . mt_rand(100, 999),
            'boutique_code' => $shop['code_boutique'],
            'categorie_code' => $categorie,
            'libelle_article' => $libelle,
            'quantite_article' => $quantite,
            'prix_location_article' => $prix,
            'statut_article' => 'actif',
            'created_at_article' => date('Y-m-d H:i:s'),
        ]);

        Response::success('Article ajouté', ['article' => $article]);
    }

    public function update(): void
    {
        $this->requireCsrf();
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $code = trim($this->input('code_article', ''));
        if (!$code) {
            Response::error('Code article requis');
        }
        $article = Article::findByCode($code);
        if (!$article || $article['boutique_code'] !== $shop['code_boutique']) {
            Response::error('Article introuvable', [], 404);
        }

        $updated = Article::update([
            'code_article' => $code,
            'libelle_article' => trim($this->input('libelle', $article['libelle_article'])),
            'categorie_code' => trim($this->input('categorie_code', $article['categorie_code'])),
            'quantite_article' => (int) $this->input('quantite', $article['quantite_article']),
            'prix_location_article' => (float) $this->input('prix_location', $article['prix_location_article']),
            'updated_at_article' => date('Y-m-d H:i:s'),
        ]);

        Response::success('Article mis à jour', ['article' => $updated]);
    }

    public function desactiver(): void
    {
        $this->requireCsrf();
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $code = trim($this->input('code_article', ''));
        if (!$code) {
            Response::error('Code article requis');
        }
        $article = Article::findByCode($code);
        if (!$article || $article['boutique_code'] !== $shop['code_boutique']) {
            Response::error('Article introuvable', [], 404);
        }

        $updated = Article::desactiver($code);
        Response::success('Article désactivé', ['article' => $updated]);
    }
}
