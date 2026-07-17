# PLAN D'IMPLÉMENTATION — LOKALEX (MVP location de matériel)

> Source de vérité unique : `sql/db.sql`. Aucune table/champ/modèle ne doit être inventé.
> On conserve l'architecture existante (router unique `api/index.php`, classes `Controller`/`Model`/`Response`, auth par téléphone) car elle est compatible.

---

## 1. Décisions clés issues de `db.sql`

- **Auth** : `users` n'a **pas de mot de passe**. Connexion par `telephone_user` (mécanisme de token base64 déjà présent dans `Controller::requireAuth` et `AuthController`). On garde tel quel.
- **`boutiques`** : il n'y a **pas** de champ `devise_boutique`. → Supprimer toutes les références à `devise_boutique` (`Shop.php`, `DeveloperController`, `index.html`, `app.js`).
- **`articles.quantite_article`** : c'est le **stock disponible**. `db.sql` n'a PAS de `quantite_disponible` séparé. → On n'invente pas de colonne ; `quantite_article` = stock dispo. À la création de location on décrémente, au retour on incrémente.
- **`locations.statut_location`** : `en_cours` / `terminee` / `retard`. Le statut `retard` est **calculé** (date_retour_prevue < aujourd'hui et toujours `en_cours`), jamais saisi.
- **Plus de `ventes` ni `depenses`** : ces tables NAFA disparaissent, remplacées par `locations` + `ligne_locations` + `paiements`.
- DB name : `db.sql` crée `db_location`. → `config/database.php` : `dbname = 'db_location'`.

---

## 2. Correspondance NAFA → LOKALEX

| Module NAFA | Module LOKALEX | Action |
|---|---|---|
| `SaleController` (ventes) | `LocationController` (locations) | Réécriture complète |
| `ExpenseController` (dépenses) | — | **Supprimé** (absent de db.sql) |
| `DashboardController` (ventes/dépenses) | `DashboardController` (locations/clients/stock) | Réécriture KPIs |
| `HistoryController` (ventes/dépenses) | `HistoryController` (locations) | Réécriture + filtres |
| `ReportController` (chart ventes/dépenses) | `ReportController` (chart locations) | Réécriture sur locations |
| `SearchController` (ventes) | `SearchController` (locations) | Réécriture |
| `AuthController` | `AuthController` | Conservé |
| `SubscriptionController` | `SubscriptionController` | Conservé |
| `DeveloperController` (users/shops/forfaits/abonnements) | `DeveloperController` | Conservé + retrait `devise` + détail sur locations |
| `Shop::createDefaultForUser` (devise) | `Shop` | Retrait `devise_boutique` |
| Pages `sale`/`expense` (front) | Pages `articles`/`clients`/`nouvelle-location`/`locations`/`retour`/`paiement` | Remplacement |

---

## 3. Backend — Modèles (dossier `api/models/`)

**Conserver / ajuster**
- `User.php` — déjà compatible (login par téléphone).
- `Shop.php` — retirer `devise_boutique` dans `createDefaultForUser` et `findByCode`. Ajouter `createDefaultForUser` (si besoin) sans devise.
- `Forfait.php` — compatible (déjà aligné sur db.sql).
- `Abonnement.php` — compatible.

**Créer**
- `Categorie.php` : `all(boutique_code)`, `findByCode`, `create` (code_categorie, boutique_code, libelle_categorie).
- `Article.php` : `allByBoutique`, `findByCode`, `create`, `update`, `desactiver` (statut actif/inactif), `adjustStock(code_article, delta)` (UPDATE quantite_article).
- `Client.php` : `allByBoutique`, `findByCode`, `findByPhone`, `search(boutique_code, q)`, `create`, `countByBoutique`, `historique(client_code)` (locations + total payé).
- `Location.php` : `create` (transaction : location + lignes + décrément stock), `allByBoutique`, `findByCode`, `retour` (incrément stock + date_retour_effective + statut), `addPaiement`, `stats(boutique_code, date)` pour le dashboard.
- `LigneLocation.php` : `createMany(location_code, lignes[])`.
- `Paiement.php` : `create`, `totalByLocation(location_code)`, `allByLocation`.

---

## 4. Backend — Contrôleurs (dossier `api/controllers/`)

**Nouveaux**
- `ArticleController` : `index` (GET /api/articles), `store` (POST), `update` (POST /api/articles/update), `desactiver` (POST).
- `CategorieController` : `index` (GET /api/categories), `store` (POST).
- `ClientController` : `index` (GET /api/clients), `search` (GET /api/clients/search), `store` (POST /api/clients), `historique` (GET /api/clients/historique).
- `LocationController` :
  - `store` (POST /api/locations) → crée location + lignes, décrémente le stock, recalcul `reste_location`.
  - `index` (GET /api/locations) + recherche (client/phone/statut).
  - `show` (GET /api/locations/show) → détail + lignes + paiements.
  - `retour` (POST /api/locations/retour) → quantités retournées, incrément stock, statut.
  - `addPaiement` (POST /api/locations/paiement) → insère paiement, recalcul `reste_location = montant - SUM(paiements)`.

**Réécrire**
- `DashboardController::index` → KPIs (voir §5).
- `HistoryController` → liste locations filtrées (aujourd'hui / semaine / mois / année).
- `ReportController` → totaux sur locations (montant total, encaissé, reste) + chart périodique.
- `SearchController` → recherche locations par nom client / téléphone / statut.
- `DeveloperController` → retirer `devise_boutique` ; `userDetail`/`shopDetail` affichent désormais les **locations** au lieu de ventes/dépenses.

**Supprimer**
- `SaleController.php`, `ExpenseController.php` + modèles `Sale.php`, `Expense.php` (tables inexistantes).

---

## 5. Logique métier (cœur du MVP)

**Création d'une location** (`LocationController::store`)
1. Vérifier session + abonnement actif (`requireActiveSubscription`).
2. Créer `locations` (code_location, boutique_code, client_code, dates, montant, avance, reste = montant - avance, statut = en_cours).
3. Pour chaque ligne : insérer `ligne_locations` (article_code, quantite, prix_unitaire, montant) puis `Article::adjustStock(-quantite)`.
4. Retourner la location complète + lignes.

**Retour matériel** (`LocationController::retour`)
- Pour chaque article retourné : `Article::adjustStock(+quantite_retournee)`.
- `date_retour_effective_location = NOW()`.
- Si toutes les quantités sont retournées → `statut_location = terminee`.

**Paiement** (`LocationController::addPaiement`)
- Insérer `paiements` (mode_paiement ∈ enum db.sql).
- `reste_location = montant_location - SUM(montant_paiement)`.

**Statut "retard"** (calculé à la lecture, ex. dashboard)
```sql
SELECT COUNT(*) FROM locations
WHERE boutique_code = :b AND statut_location = 'en_cours'
  AND date_retour_prevue_location < CURDATE();
```

**KPIs Dashboard** (par `boutique_code`)
- 📦 En cours : `COUNT` locations `en_cours`.
- 📅 Retours prévus aujourd'hui : `COUNT` où `date_retour_prevue_location = CURDATE()`.
- 💰 Montant du jour : `SUM(montant_location)` où `DATE(date_sortie_location) = CURDATE()`.
- 👥 Clients : `COUNT` clients boutique.
- ⚠️ En retard : requête ci-dessus.
- 10 dernières locations : `ORDER BY created_at_location DESC LIMIT 10`.

---

## 6. Frontend (`index.html` + `js/app.js` + `css/style.css`)

**Général**
- Titre/logo `NAFA` → `LOKALEX` (utiliser `images/lokalex-icon.png` déjà présent).
- `localStorage` clé `lokalex_session` (cohérence).
- Retirer `devise_boutique` partout.

**Pages à remplacer**
- `page-sale` → **Nouvelle location** : sélecteur client (recherche/creation rapide) + lignes article (article, quantité, prix) + date sortie/retour + montant/avance/reste + Enregistrer.
- `page-expense` → **Articles** : liste (nom, catégorie, stock, prix, statut) + ajout/modif/désactivation.
- Ajouter **Clients** : liste + recherche + historique (nb locations, total payé).
- Ajouter **Locations** : liste avec recherche (client/tel/statut) + badges statut (🟢🔵🔴) + boutons Retour / Paiement / détail.
- Ajouter **Retour** (modal depuis location) : saisie quantités retournées.
- Ajouter **Paiement** (modal depuis location) : montant + mode + date.
- **Dashboard** : 5 KPIs ci-dessus + 10 dernières locations (lien vers détail).
- **Historique** : filtres Aujourd'hui / Semaine / Mois / **Année** (db.sql = source).
- **Reports** : bascule sur locations (montant total, encaissé, reste) + chart réutilisé.
- **FAB** : remplacer Vente/Dépense par Articles / Nouvelle location / Clients.
- **Bottom-nav** : accueil, locations, historique, rapports, + dev (users/boutiques).

**`app.js`**
- Remplacer `handleSale`/`handleExpense` par `handleLocation` + `addLocationLine` + `handleRetour` + `handlePaiement`.
- Ajouter `renderArticles`, `renderClients`, `renderLocations`, `openLocationDetail`, `renderLocationDetail`.
- `renderDashboard` : nouveaux KPIs.
- `renderHistory`/`performSearch`/`renderReports` : source = locations.
- Garder helpers existants (`api`, `toast`, `formatMoney`, `escapeHtml`, `drawChart`, `formatFrenchDate`).

---

## 7. Routes (`api/index.php`)

```php
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/auth/me

GET    /api/dashboard
GET    /api/articles          POST /api/articles
POST   /api/articles/update   POST /api/articles/desactiver
GET    /api/categories        POST /api/categories
GET    /api/clients           POST /api/clients
GET    /api/clients/search    GET  /api/clients/historique
POST   /api/locations         GET  /api/locations
GET    /api/locations/show    POST /api/locations/retour
POST   /api/locations/paiement
GET    /api/history           GET  /api/reports
GET    /api/search
GET    /api/forfaits          POST /api/abonnements
POST   /api/dev/users  POST /api/dev/shops  POST /api/dev/forfaits
POST   /api/dev/abonnement/statut  POST /api/dev/abonnements
GET    /api/dev/users  GET /api/dev/shops  GET /api/dev/forfaits
GET    /api/dev/abonnements  GET /api/dev/user-detail  GET /api/dev/shop-detail
```

---

## 8. Ordre d'implémentation (phases)

1. **Config & nettoyage** : `database.php` (`db_location`), supprimer `devise_boutique`, supprimer `Sale.php`/`Expense.php`/`SaleController`/`ExpenseController`.
2. **Modèles domaine** : `Categorie`, `Article`, `Client`, `Location`, `LigneLocation`, `Paiement`.
3. **Contrôleurs CRUD** : `ArticleController`, `CategorieController`, `ClientController`.
4. **LocationController** (store/retour/paiement/show) + logique stock.
5. **Dashboard / History / Reports / Search** réécrits.
6. **DeveloperController** détail sur locations.
7. **Frontend** : index.html (pages) + app.js (handlers/render) + style.css (badges statut).
8. **Vérification** : importer `db.sql` dans MySQL, tester flux complet (login → article → client → location → retour → paiement → dashboard).

---

## 9. Points d'attention / risques

- **Respect strict db.sql** : ne jamais ajouter `quantite_disponible`, `devise_boutique`, `password`, etc.
- **MyISAM** : pas de FK contraignantes ; la cohérence (boutique_code, client_code…) est gérée par le code, pas le SGBD.
- **Stock = `quantite_article`** : toute la logique de dispo repose là-dessus (pas de champ "total" séparé).
- **Transactions** : la création de location (location + lignes + stock) doit être atomique (`BEGIN/COMMIT`) pour éviter stock désynchronisé.
- **MVP** : pas de gestion comptable, pas de multi-devises, pas de modules au-delà de la liste §2.
