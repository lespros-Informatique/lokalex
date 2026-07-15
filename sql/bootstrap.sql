-- Données initiales pour démarrer LOKALEX en local.
-- Un administrateur (developpeur) et un vendeur de démonstration avec boutique + abonnement actif.

INSERT INTO users (code_user, role_user, nom_user, telephone_user, statut_user, created_at_user, updated_at_user)
VALUES ('USRDEV001', 'developpeur', 'Admin LOKALEX', '770000001', 'actif', NOW(), NULL);

INSERT INTO users (code_user, role_user, nom_user, telephone_user, statut_user, created_at_user, updated_at_user)
VALUES ('USRVEND001', 'vendeur', 'Demo Vendeur', '770000002', 'actif', NOW(), NULL);

INSERT INTO boutiques (code_boutique, user_code, libelle_boutique, telephone_boutique, adresse_boutique, ville_boutique, statut_boutique, created_at_boutique, updated_at_boutique)
VALUES ('BTELOKA01', 'USRVEND001', 'Boutique Demo', NULL, NULL, NULL, 'actif', NOW(), NULL);

INSERT INTO abonnements (code_abonnement, boutique_code, forfait_code, date_debut_abonnement, date_fin_abonnement, montant_abonnement, statut_abonnement, created_at_abonnement, updated_at_abonnement)
VALUES ('ABOLOKA01', 'BTELOKA01', 'FREE001', CURDATE(), DATE_ADD(CURDATE(), INTERVAL 7 DAY), 0.00, 'actif', NOW(), NULL);


