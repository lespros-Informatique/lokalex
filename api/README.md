# LOKALEX API

Backend PHP structuré pour l'application LOKALEX (gestion de location de matériel).

## Structure

```
api/
├── index.php              # Point d'entrée unique (router)
├── .htaccess              # Réécriture vers index.php
├── config/
│   └── database.php       # Configuration PDO (db_location)
├── core/
│   ├── Database.php       # Connexion PDO singleton
│   ├── Response.php       # Helpers JSON (success/error)
│   ├── Controller.php     # Classe de base avec requireAuth()
│   ├── Auth.php           # Helpers authentification
│   └── Request.php        # Helpers récupération données
├── models/
│   ├── User.php / Shop.php / Categorie.php / Article.php
│   ├── Client.php / Location.php / LigneLocation.php / Paiement.php
│   └── Forfait.php / Abonnement.php
└── controllers/
    ├── AuthController.php / DashboardController.php
    ├── CategorieController.php / ArticleController.php
    ├── ClientController.php / LocationController.php
    ├── HistoryController.php / ReportController.php / SearchController.php
    └── DeveloperController.php / SubscriptionController.php
```

## Endpoints

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/auth/login` | Connexion par téléphone |
| POST | `/api/auth/logout` | Déconnexion |
| GET | `/api/auth/me` | Utilisateur connecté |
| GET | `/api/dashboard` | KPIs (locations/clients/stock) |
| GET | `/api/articles` | Liste articles |
| POST | `/api/articles` | Créer un article |
| POST | `/api/articles/update` | Modifier un article |
| POST | `/api/articles/desactiver` | Désactiver un article |
| GET | `/api/categories` | Liste catégories |
| POST | `/api/categories` | Créer une catégorie |
| GET | `/api/clients` | Liste clients |
| GET | `/api/clients/search` | Rechercher un client |
| POST | `/api/clients` | Créer un client |
| GET | `/api/clients/historique` | Historique client |
| POST | `/api/locations` | Créer une location (+ lignes, − stock) |
| GET | `/api/locations` | Lister les locations |
| GET | `/api/locations/show` | Détail location |
| POST | `/api/locations/retour` | Retour matériel (+ stock) |
| POST | `/api/locations/paiement` | Ajouter un paiement |
| GET | `/api/history` | Historique (filtres jour/semaine/mois/année) |
| GET | `/api/reports` | Rapports + chart |
| GET | `/api/search` | Recherche de locations |

## Authentification
- Header : `Authorization: Bearer <base64(phone:timestamp)>`
- Ou cookie : `nafa_user` (stocké en base64)

## Base de données
Utilise `sql/db.sql` (base `db_location`) comme seule source de vérité.
