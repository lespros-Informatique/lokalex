<?php

require_once __DIR__ . '/../core/Controller.php';

class ExpenseController extends Controller
{
    public function store(): void
    {
        $user = $this->requireActiveSubscription();
        $shop = Shop::findByUserCode($user['code_user']);
        if (!$shop) {
            Response::error('Boutique introuvable', [], 404);
        }

        $libelle = trim($this->input('libelle', ''));
        $montant = (float) ($this->input('montant', 0));

        if (!$libelle) {
            Response::error('Libellé requis');
        }
        if (!$montant || $montant <= 0) {
            Response::error('Montant invalide');
        }

        $expense = Expense::create([
                'code_depense' => 'DEP' . time() . mt_rand(100, 999),
            'boutique_code' => $shop['code_boutique'],
            'libelle_depense' => $libelle,
            'montant_depense' => $montant,
            'date_depense_depense' => $this->input('client_now', date('Y-m-d H:i:s')),
            'created_at_depense' => $this->input('client_now', date('Y-m-d H:i:s')),
        ]);

        Response::success('Dépense enregistrée', ['expense' => $expense]);
    }
}
