<?php

require_once __DIR__ . '/../core/Controller.php';

class SaleController extends Controller
{
    public function store(): void
    {
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $montant = (float) ($this->input('montant', 0));
        if (!$montant || $montant <= 0) {
            Response::error('Montant invalide');
        }

        $sale = Sale::create([
            'code_vente' => 'VTE' . time() . mt_rand(100, 999),
            'boutique_code' => $shop['code_boutique'],
            'montant_vente' => $montant,
            'mode_paiement_vente' => 'especes',
            'created_at_vente' => $this->input('client_now', date('Y-m-d H:i:s')),
        ]);

        Response::success('Vente enregistrée', ['sale' => $sale]);
    }
}
