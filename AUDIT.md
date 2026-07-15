# Audit du projet NAFA

Audit statique du code (pas d'exécution). Date : 13/07/2026.
Portée : `index.html`, `js/app.js`, `css/style.css`, `api/**`, `sql/db.sql`, `docs/IMPLEMENT.md`.

---

## 1. Résumé exécutif

| Critère | État | Gravité |
|---|---|---|
| Sécurité – authentification | **Critique** | 🔴 |
| Sécurité – autorisation (suppression) | **Critique** | 🔴 |
| Sécurité – XSS frontend | **Moyen** | 🟠 |
| Cohérence du flux (shop) | **Bug fonctionnel** | 🟠 |
| Code mort / architecture | **À nettoyer** | 🟡 |
| Robustesse (codes en double) | **Risque** | 🟡 |
| Docs vs réalité | **Incohérent** | 🟡 |

Le projet est visuellement propre et cohérent avec la charte (`#16A34A`, mobile-first, bottom-nav + FAB). Côté backend, l'authentification est **factice** et certaines routes modifient des données sans vérification d'appartenance.

---

## 2. Sécurité

### 🔴 2.1 Authentification forgable (CRITIQUE)
`api/core/Controller.php:28` `requireAuth()` décode le token `base64(phone:timestamp)` et cherche l'utilisateur **uniquement par le numéro de téléphone**. Il n'y a **aucune vérification de mot de passe** (la table `users` n'a pas de champ mot de passe) et le token n'est pas signé.

Conséquence : `base64_encode("+221770000000:")` suffit à s'authentifier comme n'importe quel utilisateur dont on connaît le téléphone. Le token `nafa_token` posé par `AuthController::login` (`api/controllers/AuthController.php:21`) contient `telephone:time()` — n'importe qui peut le reproduire.

`api/core/Auth.php:12` `getUserFromToken()` et `api/core/Auth.php:5` `getPhoneFromHeader()` sont en plus **non utilisés** (doublon mort de `requireAuth`).

**Correctif** : ajouter un secret signé (ex. `hash_hmac`) ou un vrai mot de passe + hash, et vérifier la signature côté `requireAuth`. Ne jamais faire confiance au seul numéro.

### 🔴 2.2 Suppression sans contrôle d'appartenance (CRITIQUE)
`api/controllers/HistoryController.php:59` `delete()` accepte `type` + `id` et supprime n'importe quelle vente/dépense par son `code_*`, pour **n'importe quel utilisateur authentifié** (même un simple vendeur, même une autre boutique). Pas de `requireDeveloper()`, pas de vérif que l'opération appartient à la boutique de l'utilisateur.

**Correctif** : limiter aux devs OU vérifier que `boutique_code` correspond à la boutique de l'utilisateur courant avant `DELETE`.

### 🟠 2.3 XSS par injection de HTML
`js/app.js` injecte des données DB via `innerHTML` sans échappement : `renderDevUsers` (lignes ~389), `openUserDetail` (lignes ~429-447), `renderDevShops` (lignes ~289), `openShopDetail`. Un `nom_user` ou `libelle_boutique` contenant du HTML/JS serait exécuté. `code_user` est en plus inséré directement dans des `onclick="app.openUserDetail('...')"` (template string) — injection d'attribut possible.

**Correctif** : échapper (`textContent` ou helper `escapeHtml`) ou utiliser `createElement`/templates DOM.

### 🟡 2.4 CORS trop ouvert
`api/core/Response.php:9` envoie `Access-Control-Allow-Origin: *` sur toutes les réponses. Comme le front est same-origin ce n'est pas exploitable aujourd'hui, mais à corriger si on ouvre l'API.

### 🟡 2.5 Cookies / HTTPS
`AuthController.php:22-23` : `setcookie(..., '', false, true)` → `secure=false`. Les cookies (dont `nafa_user` qui contient tout l'objet user en base64) circulent en clair en HTTP. Acceptable en dev local, à passer en `secure=true` en production. Note : `nafa_token` est posé mais **non reli par le serveur** (`requireAuth` lit `nafa_user` en fallback, pas `nafa_token`) → cookie redondant.

---

## 3. Bugs fonctionnels

### 🟠 3.1 Page "Choisir une boutique" jamais utilisée
`index.html` déclare `#page-shop` (vide, `.shop-list` non peuplé) mais `app.handleLogin` (`js/app.js:117`) va directement au dashboard avec `data.data.shop`. Si l'utilisateur n'a **pas de boutique**, `renderDashboard` (`js/app.js:151`) fait un `return` précoce → dashboard vide, utilisateur bloqué. Or **seul un développeur** peut créer une boutique (`DeveloperController::createShop`). Un vendeur sans boutique ne peut rien faire.

**Correctif** : soit afficher la sélection de boutique après login (peupler `#shop-list`), soit créer automatiquement une boutique par défaut, soit permettre au vendeur de lier une boutique.

### 🟡 3.2 Incohérence des fuseaux horaires
PHP insère `date('Y-m-d H:i:s')` (heure locale serveur) dans `created_at_vente`/`date_depense_depense`, mais le default SQL est `CURRENT_TIMESTAMP` (UTC, voir `sql/db.sql:12` `SET time_zone = "+00:00"`). `DashboardController` filtre "aujourd'hui" avec `date('Y-m-d')` (PHP) alors que `Sale::getTodayByShop`/`Expense::getTodayByShop` utilisent `CURDATE()` (MySQL). Décalages possibles selon l'hébergement.

### 🟡 3.3 `search` ne couvre que les ventes
`api/controllers/SearchController.php` et `Sale::search` ne cherchent que dans `ventes`. Les dépenses ne sont pas recherchables (acceptable si volontaire, à confirmer).

---

## 4. Robustesse & données

### 🟡 4.1 Codes métier en collision (race condition)
`code_vente = 'VTE'.time()`, `code_depense='DEP'.time()`, `code_user='USR'.time()`, `code_boutique='BTE'.time()` (`SaleController`, `ExpenseController`, `DeveloperController`, `Shop`). La colonne est `UNIQUE`. Deux créations dans la **même seconde** provoquent une erreur 500 (violation de clé unique). À fort trafic ou en boucle, c'est garanti.

**Correctif** : utiliser un UUID ou `uniqid('', true)` + random, ou s'appuyer sur l'AUTO_INCREMENT et générer le code après insertion.

### 🟡 4.2 `montant` en `float` côté PHP
`SaleController.php:15` et `ExpenseController.php:16` castent en `(float)`. Perte de précision possible sur des décimales. La colonne SQL est `decimal(12,2)` → préférer passer la chaîne directement ou arrondir explicitement.

### 🟡 4.3 `role_user` enum avec valeurs vides
`sql/db.sql:77` : `enum('developpeur','vendeur','','')` — deux entrées vides étranges. À nettoyer en `enum('developpeur','vendeur')`.

---

## 5. Code mort / architecture

Fichiers et méthodes jamais appelés (à supprimer ou brancher) :

- `api/core/Request.php` — entièrement inutilisé (`Controller::input` fait le travail).
- `api/core/Auth.php` (`getPhoneFromHeader`, `getUserFromToken`) — inutilisé.
- `api/models/User.php::findShopByUser` — inutilisé (`Shop::findByUserCode` est utilisé à la place).
- `api/models/Shop.php::findByCode`, `Shop::createDefaultForUser` — inutilisés.
- `api/models/Sale.php::getRecentByShop` — inutilisé (dashboard filtre via `getAll`).
- `api/routes/` et `api/middleware/` — **dossiers vides** ; le routage est codé en dur dans `api/index.php:32`. La doc annonce ces dossiers "réservés pour évolution" mais rien ne les charge.

Recommandation : soit implémenter un vrai routeur lisant `routes/`, soit retirer les dossiers vides pour ne pas induire en erreur.

---

## 6. Documentation vs réalité

- `api/README.md:53` : « Header `Authorization: Bearer <base64(phone:password)>` » → **faux**, il n'y a pas de password (c'est `phone:timestamp`).
- `api/README.md:59` : référence `sql/migrate_saas.sql` → **fichier inexistant** (seul `sql/db.sql` existe).
- `docs/IMPLEMENT.md` décrit une **session de caisse** (fond de caisse, ouverture/fermeture) → non implémentée côté API ni front. Fonctionnalité V1 annoncée manquante.
- `docs/IMPLEMENT.md` demande un front « sans rien de dynamique » mais le front est une SPA JS complète avec état/localStorage — cohérent avec le besoin réel, mais à valider par rapport au brief.

---

## 7. Frontend – points mineurs

- `index.html:5` : `maximum-scale=1.0, user-scalable=no` → **bloque le zoom** (problème d'accessibilité). À retirer.
- `js/app.js:89` : `console.log` de toutes les réponses API en clair (y compris données user) → à retirer en prod.
- `app.js` stocke la session (user + shop) dans `localStorage` (`saveSession`) → les données user persistent en clair côté client. Acceptable pour un MVP, à sécuriser si données sensibles.
- `drawChart` (`js/app.js:578`) recalcule la largeur depuis le DOM à chaque render ; correct mais dépend du polyfill `roundRect` (présent, lignes 692-705).

---

## 8. Plan d'action priorisé

1. **Authentification réelle** (signature HMAC ou mot de passe hashé) — bloquant sécurité.
2. **Autorisation sur `HistoryController::delete`** (vérif boutique ou rôle dev).
3. **Échappement XSS** dans tous les `innerHTML` du front.
4. **Corriger le flux boutique** (sélection/auto-création) pour débloquer les vendeurs sans boutique.
5. **Génération de codes uniques** sans `time()` seul.
6. Nettoyage du code mort + alignement docs/SQL (supprimer `migrate_saas.sql` de la doc, corriger la description du token).
7. Retirer `user-scalable=no` et les `console.log` de prod.

---

## 9. Points positifs

- Architecture backend claire (MVC léger, modèles statiques, prepared statements → **pas d'injection SQL** détectée).
- Charte graphique et mobile-first bien respectés (couleurs, Poppins, bottom-nav, FAB, modales bottom-sheet).
- Séparation propre API / front, CORS et OPTIONS gérés.
- SQL avec clés étrangères et `utf8mb4` corrects.
