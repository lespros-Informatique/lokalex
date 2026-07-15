Tu es un développeur senior full-stack chargé de transformer un ancien projet appelé NAFA en un nouveau projet appelé LOKALEX.

CONTEXTE :
Le projet actuel est une ancienne version (NAFA). Le nouveau projet est LOKALEX.

La base de données officielle du nouveau projet est déjà présente dans :
sql/db.sql

Cette nouvelle base est légèrement similaire à l'ancien projet, mais elle contient les nouvelles règles métier.

Ta mission :
Analyser entièrement le projet existant (structure, backend, frontend, modèles, contrôleurs, vues, API, scripts SQL), puis adapter le code pour correspondre exactement au nouveau projet LOKALEX.

IMPORTANT :
- Ne réutilise pas aveuglément l'ancien fonctionnement NAFA.
- La nouvelle base db.sql est la seule source de vérité.
- Tu dois adapter le code aux nouvelles tables, relations et champs présents dans db.sql.
- N'invente aucune table, aucun champ, aucun module qui n'existe pas dans la base.
- Ne crée pas de fonctionnalités inutiles.
- Le projet est un MVP, donc rester simple, rapide et efficace.
- Conserve la structure et les bonnes pratiques déjà présentes dans le projet quand elles sont compatibles.
- Le résultat doit être propre, maintenable et prêt pour une évolution future.

==================================================
OBJECTIF DU PRODUIT : LOKALEX
==================================================

Lokalex est une application simple de gestion de location de matériel pour les commerçants.

Le commerçant doit pouvoir :
- gérer son matériel disponible à la location ;
- gérer ses clients ;
- créer une location rapidement ;
- suivre les retours ;
- suivre les paiements.

==================================================
GESTION DES COMPTES ET ABONNEMENTS
==================================================

Le système d'administration garde le même principe :

Un administrateur :
- crée les formules d'abonnement ;
- crée les comptes utilisateurs/vendeurs ;
- attribue une formule à chaque vendeur.

Le vendeur :
- se connecte uniquement à son espace ;
- ne gère pas les abonnements ;
- ne voit que ses propres données.

Ne pas modifier ce principe.

==================================================
ESPACE VENDEUR
==================================================

Créer/adapter uniquement les modules nécessaires au MVP.

--------------------------------------------------
1. TABLEAU DE BORD
--------------------------------------------------

À la connexion, le vendeur voit :

KPIs :

📦 Nombre de locations en cours

📅 Retours prévus aujourd'hui

💰 Montant des locations du jour

👥 Nombre de clients

⚠️ Locations en retard


En dessous :

Afficher les 10 dernières locations.

Les statistiques doivent être calculées uniquement avec les données 

--------------------------------------------------
2. ARTICLES
--------------------------------------------------

Gestion du matériel disponible à la location.

Informations :

- Nom
- Catégorie
- Quantité totale
- Quantité disponible
- Prix de location (si prévu dans la base)
- Statut


Actions :

- Ajouter un article
- Modifier un article
- Désactiver un article


Exemples :

Chaise plastique
500 disponibles

Table
80 disponibles

Bâche
12 disponibles

Marmite
25 disponibles


Lorsqu'une location est créée :
➡️ le stock disponible diminue automatiquement.


Lorsqu'un retour est effectué :
➡️ le stock disponible augmente automatiquement.


Respecter la logique de stock définie dans db.sql.

--------------------------------------------------
3. CLIENTS
--------------------------------------------------

Gestion simple des clients.

Informations :

- Nom
- Téléphone
- Adresse (si disponible dans la base)


Fonctions :

- Ajouter un client
- Rechercher un client
- Voir son historique


Historique client :

- Nombre de locations
- Montant total payé/loué selon la structure de la base


Lors d'une nouvelle location :

Le vendeur recherche le client.

Si le client existe :
➡️ il est sélectionné.

Si le client n'existe pas :
➡️ création rapide du client directement depuis la location.

--------------------------------------------------
4. NOUVELLE LOCATION
--------------------------------------------------

Processus rapide :

1. Choisir le client.

2. Ajouter les articles :

Exemple :

100 chaises

20 tables

2 bâches


3. Ajouter :

- Date de sortie
- Date de retour prévue
- Montant
- Avance
- Reste à payer


Puis :

Enregistrer.


Après validation :

- créer la location ;
- enregistrer les lignes de location ;
- diminuer automatiquement le stock.


Ne pas ajouter de processus complexe.

--------------------------------------------------
5. LOCATIONS
--------------------------------------------------

Afficher toutes les locations du vendeur.

Recherche :

- Nom client
- Téléphone
- Statut


Chaque ligne affiche :

- Client
- Date
- Montant
- Reste à payer
- Statut


Statuts :

🟢 En cours

🔵 Terminée

🔴 En retard


Les statuts doivent respecter les valeurs prévues dans la base.

--------------------------------------------------
6. RETOUR MATÉRIEL
--------------------------------------------------

Depuis une location :

Bouton :

Retour


Le vendeur indique les quantités retournées :

Exemple :

100 chaises

20 tables

2 bâches


Après validation :

- augmenter le stock ;
- mettre à jour la location ;
- changer le statut si tout est retourné.


--------------------------------------------------
7. PAIEMENTS
--------------------------------------------------

Depuis une location :

Ajouter un paiement.


Informations :

- Montant
- Mode paiement
- Date


Après paiement :

Le reste à payer est recalculé automatiquement.


Ne pas créer une gestion comptable complète.
C'est uniquement un suivi de paiement de location MVP.

--------------------------------------------------
8. HISTORIQUE
--------------------------------------------------

Afficher toutes les locations.

Filtres :

- Aujourd'hui
- Cette semaine
- Ce mois
- Cette année


==================================================
RÈGLES TECHNIQUES
==================================================

Avant toute modification :

1. Lire entièrement :
- sql/db.sql
- structure backend
- modèles
- contrôleurs
- vues frontend


2. Faire une correspondance :

Ancien module NAFA → Nouveau module Lokalex.


3. Adapter :
- noms des tables ;
- requêtes SQL ;
- relations ;
- modèles ;
- contrôleurs ;
- API ;
- interfaces.


Ne jamais créer une logique contraire à la base.

==================================================
RÉSULTAT ATTENDU
==================================================

À la fin :

- L'application doit fonctionner de Lokalex.
- Le vendeur doit pouvoir gérer son activité de location de A à Z.
- L'interface doit rester simple telquelle.
- Le code doit rester compatible avec une évolution future.
- Aucun module inutile ne doit être ajouté.

Priorité :
Simplicité MVP > complexité.

je rappel que db.sql est la base de donnee qui est exporte pour que tu voi la structure des tables