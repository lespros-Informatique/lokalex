# PROMPT D'IMPLÉMENTATION – NAFA

## Contexte

Tu es un développeur Full Stack senior et UX Designer.

Tu dois développer **NAFA**, une application SaaS web responsive (Mobile First) destinée aux petits commerçants africains.

Le projet doit être pensé comme un **produit extrêmement simple**, moderne et rapide.

NAFA **n'est PAS** un logiciel de gestion commerciale, ni un ERP, ni un logiciel de stock.

La philosophie du produit est :

> **"Une calculatrice intelligente qui garde l'historique des ventes."**

L'utilisateur doit pouvoir apprendre toute l'application en moins de **30 secondes**.

Chaque action importante doit prendre moins de **3 secondes**.

L'interface doit être minimaliste.

Ne jamais ajouter des fonctionnalités non demandées.

---

# Objectif

Le commerçant veut simplement connaître :

* combien il a vendu aujourd'hui
* combien il a dépensé
* combien il lui reste
* son évolution

L'application doit répondre uniquement à ce besoin.

---

# Base de données

Utiliser exclusivement la base SQL fournie.

Ne pas modifier la structure.

Respecter les clés métier (`code_user`, `code_boutique`, etc.).

Toutes les relations se font avec les codes métier.

---

# Identité visuelle

Nom :

NAFA

Signification :

Profit – Bénéfice – Gain.

Couleurs :

Couleur principale :

#16A34A

Blanc :

#FFFFFF

Vert foncé :

#166534

Vert clair :

#DCFCE7

Aucun dégradé.

Aucun effet inutile.

Aucune interface chargée.

Police :

Poppins.

Style :

Simple

Professionnel

Épuré

Moderne

Beaucoup d'espaces blancs.

---

# Responsive

Priorité absolue au mobile.

L'application doit être parfaite sur smartphone.

Puis tablette.

Puis desktop.

---

# Fonctionnalités V1

Aucune autre fonctionnalité ne doit être ajoutée.

## 1. Authentification

Connexion par téléphone.

Sélection de la boutique.

Ouverture de session de caisse.

---

## 2. Tableau de bord

Le tableau de bord est le cœur de l'application.

Afficher :

Ventes du jour

Dépenses du jour

Net du jour

Nombre de ventes

Répartition :

Espèces

Wave

Orange Money

MTN

Moov

Carte

Deux gros boutons :

➕ Vente

➖ Dépense

Aucun graphique compliqué.

---

## 3. Nouvelle vente

Écran extrêmement simple.

Montant

Mode de paiement

Bouton :

Enregistrer

Une vente doit pouvoir être enregistrée en moins de trois secondes.

Aucun article.

Aucun client.

Aucune TVA.

Aucune remise.

Aucun stock.

---

## 4. Dépenses

Formulaire :

Libellé

Montant

Bouton :

Enregistrer

Rien d'autre.

---

## 5. Historique

Filtres :

Aujourd'hui

Cette semaine

Ce mois

Afficher :

Date

Montant

Mode de paiement

Type

(Vente ou Dépense)

Possibilité de supprimer une opération.

---

## 6. Rapports

Afficher :

Total des ventes

Total des dépenses

Résultat net

Évolution par :

Jour

Semaine

Mois

Graphiques simples.

---

## 7. Session de caisse

À l'ouverture :

Fond de caisse.

À la fermeture :

Résumé automatique :

Ventes

Dépenses

Net

Durée de la session.

---

# UX

L'application doit donner l'impression :

"d'une calculatrice moderne."

Très peu de clics.

Très gros boutons.

Police lisible.

Peu de texte.

Icônes simples.

---

# Ce qui est interdit

Ne jamais ajouter :

Gestion de stock

Produits

Catégories

Clients

Fournisseurs

Achats

Factures

Commandes

CRM

Comptabilité

Multi-entrepôts

Statistiques complexes

ERP

Modules inutiles

Le projet doit rester volontairement minimaliste.

---

---

# Expérience utilisateur

L'utilisateur doit ouvrir NAFA et comprendre immédiatement son fonctionnement.

Le temps d'apprentissage doit être inférieur à 30 secondes.

L'objectif est qu'un commerçant qui utilise aujourd'hui une calculatrice préfère utiliser NAFA dès le premier jour.

Chaque écran doit être pensé avec cette question :

"Est-ce que cette fonctionnalité aide le commerçant à enregistrer une vente plus vite ?"

Si la réponse est NON,

alors cette fonctionnalité ne doit pas exister.

NAFA doit devenir l'application la plus simple d'Afrique pour suivre ses ventes quotidiennes.


attention soit fidel a la db.sql qui est dans le dossier sql c'est la bae de donne du projet n'ajour rien et fait seulement le cote front sans rien de dinamique et surtout mobile first comme une vrai app developpe en flutter avec btnNavBar et ou btn floating