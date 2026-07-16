<?php

require_once __DIR__ . '/../core/Controller.php';

class CategorieController extends Controller
{
    public function index(): void
    {
        $this->requireActiveSubscription();
        $categories = Categorie::all();
        $total = count($categories);
        $params = $this->paginationParams();
        $items = array_slice($categories, $params['offset'], $params['limit']);
        $this->paginatedResponse($items, $total);
    }

    public function store(): void
    {
        $this->requireCsrf();
        $this->requireActiveSubscription();

        $libelle = trim($this->input('libelle', $this->input('libelle_categorie', '')));
        if (!$libelle) {
            Response::error('Libellé requis');
        }

        $categorie = Categorie::create([
            'code_categorie' => 'CAT' . time() . mt_rand(100, 999),
            'boutique_code' => null,
            'libelle_categorie' => $libelle,
            'statut_categorie' => 'actif',
            'created_at_categorie' => date('Y-m-d H:i:s'),
        ]);

        Response::success('Catégorie ajoutée', ['categorie' => $categorie]);
    }
}
