# NAFA API

Backend PHP structuré pour l'application NAFA.

## Structure

```
api/
├── index.php              # Point d'entrée unique (router)
├── .htaccess              # Réécriture vers index.php
├── config/
│   └── database.php       # Configuration PDO
├── core/
│   ├── Database.php       # Connexion PDO singleton
│   ├── Response.php       # Helpers JSON (success/error)
│   ├── Controller.php     # Classe de base avec requireAuth()
│   ├── Auth.php           # Helpers authentification
│   └── Request.php        # Helpers récupération données
├── models/
│   ├── User.php           # Modèle utilisateurs
│   ├── Shop.php           # Modèle boutiques
│   ├── Sale.php           # Modèle ventes
│   └── Expense.php        # Modèle dépenses
├── controllers/
│   ├── AuthController.php # Login / Logout / Me
│   ├── DashboardController.php
│   ├── SaleController.php
│   ├── ExpenseController.php
│   ├── HistoryController.php
│   ├── ReportController.php
│   └── SearchController.php
├── routes/                # (réservé pour évolution)
└── middleware/            # (réservé pour évolution)
```

## Endpoints

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/auth/login` | Connexion par téléphone |
| POST | `/api/auth/logout` | Déconnexion |
| GET | `/api/auth/me` | Utilisateur connecté |
| GET | `/api/dashboard` | Données dashboard |
| POST | `/api/sales` | Créer une vente |
| POST | `/api/expenses` | Créer une dépense |
| GET | `/api/history` | Historique |
| POST | `/api/history/delete` | Supprimer une opération |
| GET | `/api/reports` | Rapports avec chart |
| GET | `/api/search` | Recherche de ventes |

## Authentification

- Header: `Authorization: Bearer <base64(phone:timestamp)>`
- Ou cookie: `nafa_user` (stocké en base64)

## Base de données

Utilise `sql/db.sql` pour la structure.
Pour migrer en SaaS 1 boutique/user, exécuter `sql/migrate_saas.sql`.
