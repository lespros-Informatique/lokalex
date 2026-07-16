-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : jeu. 16 juil. 2026 à 15:00
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `db_lokalex`
--

-- --------------------------------------------------------

--
-- Structure de la table `abonnements`
--

DROP TABLE IF EXISTS `abonnements`;
CREATE TABLE IF NOT EXISTS `abonnements` (
  `id_abonnement` int NOT NULL AUTO_INCREMENT,
  `code_abonnement` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `boutique_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `forfait_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_debut_abonnement` date NOT NULL,
  `date_fin_abonnement` date NOT NULL,
  `montant_abonnement` decimal(12,2) NOT NULL,
  `statut_abonnement` enum('en_attente','actif','expire','suspendu') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `created_at_abonnement` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_abonnement` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_abonnement`),
  UNIQUE KEY `code_abonnement` (`code_abonnement`),
  KEY `fk_abonnements_boutiques` (`boutique_code`),
  KEY `fk_abonnements_forfaits` (`forfait_code`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `abonnements`
--

INSERT INTO `abonnements` (`id_abonnement`, `code_abonnement`, `boutique_code`, `forfait_code`, `date_debut_abonnement`, `date_fin_abonnement`, `montant_abonnement`, `statut_abonnement`, `created_at_abonnement`, `updated_at_abonnement`) VALUES
(1, 'ABOLOKA01', 'BTELOKA01', 'FREE001', '2026-07-15', '2026-07-22', 0.00, 'actif', '2026-07-15 12:59:25', NULL),
(2, 'ABO1784127830877', 'BTE1784127830910', 'FREE001', '2026-07-15', '2026-07-22', 0.00, 'actif', '2026-07-15 15:03:50', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `articles`
--

DROP TABLE IF EXISTS `articles`;
CREATE TABLE IF NOT EXISTS `articles` (
  `id_article` int NOT NULL AUTO_INCREMENT,
  `code_article` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `boutique_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle_article` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite_article` int NOT NULL DEFAULT '0',
  `prix_location_article` decimal(12,2) DEFAULT '0.00',
  `statut_article` enum('actif','inactif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `created_at_article` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_article` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_article`),
  UNIQUE KEY `code_article` (`code_article`),
  KEY `fk_articles_boutiques` (`boutique_code`),
  KEY `fk_articles_categories` (`categorie_code`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `articles`
--

INSERT INTO `articles` (`id_article`, `code_article`, `boutique_code`, `categorie_code`, `libelle_article`, `quantite_article`, `prix_location_article`, `statut_article`, `created_at_article`, `updated_at_article`) VALUES
(1, 'ART1784123005592', 'BTELOKA01', '', 'chaises bleu', 161, 200.00, 'actif', '2026-07-15 13:43:25', '2026-07-15 23:19:12'),
(2, 'ART1784123074755', 'BTELOKA01', '', 'Bache rouge', 0, 10000.00, 'actif', '2026-07-15 13:44:34', '2026-07-15 23:08:38');

-- --------------------------------------------------------

--
-- Structure de la table `boutiques`
--

DROP TABLE IF EXISTS `boutiques`;
CREATE TABLE IF NOT EXISTS `boutiques` (
  `id_boutique` int NOT NULL AUTO_INCREMENT,
  `code_boutique` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle_boutique` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone_boutique` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse_boutique` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville_boutique` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut_boutique` enum('actif','inactif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `created_at_boutique` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_boutique` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_boutique`),
  UNIQUE KEY `code_boutique` (`code_boutique`),
  UNIQUE KEY `user_code` (`user_code`)
) ENGINE=MyISAM AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `boutiques`
--

INSERT INTO `boutiques` (`id_boutique`, `code_boutique`, `user_code`, `libelle_boutique`, `telephone_boutique`, `adresse_boutique`, `ville_boutique`, `statut_boutique`, `created_at_boutique`, `updated_at_boutique`) VALUES
(1, 'BTELOKA01', 'USRVEND001', 'Boutique Demo', NULL, NULL, NULL, 'actif', '2026-07-15 12:59:25', NULL),
(2, 'BTE1784127830910', 'USR1784127820477', 'Beauty Max', NULL, NULL, NULL, 'actif', '2026-07-15 15:03:50', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id_categorie` int NOT NULL AUTO_INCREMENT,
  `code_categorie` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `boutique_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `libelle_categorie` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut_categorie` enum('actif','inactif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `created_at_categorie` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_categorie` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_categorie`),
  UNIQUE KEY `code_categorie` (`code_categorie`),
  KEY `fk_categories_boutiques` (`boutique_code`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id_categorie`, `code_categorie`, `boutique_code`, `libelle_categorie`, `statut_categorie`, `created_at_categorie`, `updated_at_categorie`) VALUES
(1, 'CAT001', NULL, 'Chaises', 'actif', '2026-07-15 13:42:37', NULL),
(2, 'CAT002', NULL, 'Tables', 'actif', '2026-07-15 13:42:37', NULL),
(3, 'CAT003', NULL, 'Bâches', 'actif', '2026-07-15 13:42:37', NULL),
(4, 'CAT004', NULL, 'Tentes', 'actif', '2026-07-15 13:42:37', NULL),
(5, 'CAT005', NULL, 'Vaisselle', 'actif', '2026-07-15 13:42:37', NULL),
(6, 'CAT006', NULL, 'Matériel de cuisine', 'actif', '2026-07-15 13:42:37', NULL),
(7, 'CAT007', NULL, 'Sonorisation', 'actif', '2026-07-15 13:42:37', NULL),
(8, 'CAT008', NULL, 'Éclairage', 'actif', '2026-07-15 13:42:37', NULL),
(9, 'CAT009', NULL, 'Décoration', 'actif', '2026-07-15 13:42:37', NULL),
(10, 'CAT010', NULL, 'Autres matériels', 'actif', '2026-07-15 13:42:37', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE IF NOT EXISTS `clients` (
  `id_client` int NOT NULL AUTO_INCREMENT,
  `code_client` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `boutique_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom_client` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone_client` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse_client` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut_client` enum('actif','inactif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `created_at_client` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_client` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_client`),
  UNIQUE KEY `code_client` (`code_client`),
  KEY `fk_clients_boutiques` (`boutique_code`)
) ENGINE=MyISAM AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id_client`, `code_client`, `boutique_code`, `nom_client`, `telephone_client`, `adresse_client`, `statut_client`, `created_at_client`, `updated_at_client`) VALUES
(1, 'CLI1784123135420', 'BTELOKA01', 'camara Kifolbien Georges', '+2250566015517', 'Zone', 'actif', '2026-07-15 13:45:35', NULL),
(2, 'CLI1784124232397', 'BTELOKA01', 'Alfred', '+2250599009988', 'Zone', 'actif', '2026-07-15 14:03:52', NULL),
(3, 'CLI1784128009941', 'BTE1784127830910', 'Alfred', '+2250599009988', 'Zone', 'actif', '2026-07-15 15:06:49', NULL),
(4, 'CLI1784128115667', 'BTELOKA01', 'Alfred', '+2250599009988', 'Zone', 'actif', '2026-07-15 15:08:35', NULL),
(5, 'CLI1784135461534', 'BTELOKA01', 'hier', '0677115566', NULL, 'actif', '2026-07-15 17:11:01', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `forfaits`
--

DROP TABLE IF EXISTS `forfaits`;
CREATE TABLE IF NOT EXISTS `forfaits` (
  `id_forfait` int NOT NULL AUTO_INCREMENT,
  `code_forfait` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle_forfait` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `prix_forfait` decimal(12,2) NOT NULL,
  `duree_forfait` int NOT NULL COMMENT 'Nombre de jours',
  `description_forfait` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `statut_forfait` enum('actif','inactif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `created_at_forfait` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_forfait` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_forfait`),
  UNIQUE KEY `code_forfait` (`code_forfait`)
) ENGINE=MyISAM AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `forfaits`
--

INSERT INTO `forfaits` (`id_forfait`, `code_forfait`, `libelle_forfait`, `prix_forfait`, `duree_forfait`, `description_forfait`, `statut_forfait`, `created_at_forfait`, `updated_at_forfait`) VALUES
(1, 'FREE001', 'Essai Gratuit', 0.00, 7, 'Essai gratuit pendant 7 jours', 'actif', '2026-07-15 11:10:07', NULL),
(2, 'MENS001', 'Mensuel', 2000.00, 30, 'Abonnement mensuel', 'actif', '2026-07-15 11:10:07', NULL),
(3, 'TRIM001', 'Trimestriel', 5500.00, 90, 'Abonnement trimestriel', 'actif', '2026-07-15 11:10:07', NULL),
(4, 'ANNU001', 'Annuel', 20000.00, 365, 'Abonnement annuel', 'actif', '2026-07-15 11:10:07', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `ligne_locations`
--

DROP TABLE IF EXISTS `ligne_locations`;
CREATE TABLE IF NOT EXISTS `ligne_locations` (
  `id_ligne_location` int NOT NULL AUTO_INCREMENT,
  `code_ligne_location` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `article_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite_ligne_location` int NOT NULL,
  `prix_unitaire_ligne_location` decimal(12,2) NOT NULL,
  `montant_ligne_location` decimal(12,2) NOT NULL,
  `created_at_ligne_location` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_ligne_location` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_ligne_location`),
  UNIQUE KEY `code_ligne_location` (`code_ligne_location`),
  KEY `fk_lignes_locations` (`location_code`),
  KEY `fk_lignes_articles` (`article_code`)
) ENGINE=MyISAM AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `ligne_locations`
--

INSERT INTO `ligne_locations` (`id_ligne_location`, `code_ligne_location`, `location_code`, `article_code`, `quantite_ligne_location`, `prix_unitaire_ligne_location`, `montant_ligne_location`, `created_at_ligne_location`, `updated_at_ligne_location`) VALUES
(1, 'LL1784123495963_ART1', 'LOC1784123495535', 'ART1784123074755', 1, 10000.00, 10000.00, '2026-07-15 13:51:35', NULL),
(2, 'LL1784123495554_ART1', 'LOC1784123495535', 'ART1784123005592', 1, 200.00, 200.00, '2026-07-15 13:51:35', NULL),
(3, 'LL1784124662736_ART1', 'LOC1784124662451', 'ART1784123005592', 20, 200.00, 4000.00, '2026-07-15 14:11:02', NULL),
(4, 'LL1784124768707_ART1', 'LOC1784124768523', 'ART1784123005592', 1, 200.00, 200.00, '2026-07-15 14:12:48', NULL),
(5, 'LL1784128158481_ART1', 'LOC1784128158475', 'ART1784123005592', 7, 200.00, 1400.00, '2026-07-15 15:09:18', NULL),
(6, 'LL1784128455951_ART1', 'LOC1784128455281', 'ART1784123005592', 1, 200.00, 200.00, '2026-07-15 15:14:15', NULL),
(7, 'LL1784136180633_ART1', 'LOC1784136180418', 'ART1784123005592', 10, 200.00, 2000.00, '2026-07-15 17:23:00', NULL),
(8, 'LL1784155767126_ART1', 'LOC1784155767417', 'ART1784123005592', 4, 200.00, 800.00, '2026-07-15 22:49:27', NULL),
(9, 'LL1784157255972_ART1', 'LOC1784157255776', 'ART1784123005592', 6, 200.00, 1200.00, '2026-07-15 23:14:15', NULL),
(10, 'LL1784157552858_ART1', 'LOC1784157552151', 'ART1784123005592', 1, 200.00, 200.00, '2026-07-15 23:19:12', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `ligne_retours`
--

DROP TABLE IF EXISTS `ligne_retours`;
CREATE TABLE IF NOT EXISTS `ligne_retours` (
  `id_ligne_retour` int NOT NULL AUTO_INCREMENT,
  `code_ligne_retour` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `retour_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `article_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite_bonne` int NOT NULL DEFAULT '0',
  `quantite_endommagee` int NOT NULL DEFAULT '0',
  `quantite_perdue` int NOT NULL DEFAULT '0',
  `observation_ligne_retour` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at_ligne_retour` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_ligne_retour` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_ligne_retour`),
  UNIQUE KEY `code_ligne_retour` (`code_ligne_retour`),
  KEY `fk_ligne_retours_retours` (`retour_code`),
  KEY `fk_ligne_retours_articles` (`article_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `locations`
--

DROP TABLE IF EXISTS `locations`;
CREATE TABLE IF NOT EXISTS `locations` (
  `id_location` int NOT NULL AUTO_INCREMENT,
  `code_location` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `boutique_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_sortie_location` date NOT NULL,
  `date_retour_prevue_location` date NOT NULL,
  `date_retour_effective_location` date DEFAULT NULL,
  `montant_location` decimal(12,2) NOT NULL,
  `avance_location` decimal(12,2) DEFAULT '0.00',
  `reste_location` decimal(12,2) DEFAULT '0.00',
  `statut_location` enum('en_cours','terminee','retard') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'en_cours',
  `created_at_location` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_location` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_location`),
  UNIQUE KEY `code_location` (`code_location`),
  KEY `fk_locations_boutiques` (`boutique_code`),
  KEY `fk_locations_clients` (`client_code`)
) ENGINE=MyISAM AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `locations`
--

INSERT INTO `locations` (`id_location`, `code_location`, `boutique_code`, `client_code`, `date_sortie_location`, `date_retour_prevue_location`, `date_retour_effective_location`, `montant_location`, `avance_location`, `reste_location`, `statut_location`, `created_at_location`, `updated_at_location`) VALUES
(1, 'LOC1784123495535', 'BTELOKA01', 'CLI1784123135420', '2026-07-15', '2026-07-16', NULL, 10200.00, 5000.00, 5200.00, 'en_cours', '2026-07-15 13:51:35', NULL),
(2, 'LOC1784124662451', 'BTELOKA01', 'CLI1784124232397', '2026-07-15', '2026-07-15', NULL, 4000.00, 4000.00, 0.00, 'en_cours', '2026-07-15 14:11:02', NULL),
(3, 'LOC1784124768523', 'BTELOKA01', 'CLI1784124232397', '2026-07-12', '2026-07-14', '2026-07-15', 200.00, 100.00, 0.00, 'retard', '2026-07-11 14:12:48', '2026-07-15 14:32:36'),
(4, 'LOC1784128158475', 'BTELOKA01', 'CLI1784123135420', '2026-07-15', '2026-07-15', NULL, 1400.00, 1000.00, 400.00, 'en_cours', '2026-07-15 15:09:18', NULL),
(5, 'LOC1784128455281', 'BTELOKA01', 'CLI1784123135420', '2026-07-15', '2026-07-15', '2026-07-15', 200.00, 200.00, 0.00, 'terminee', '2026-07-15 15:14:15', '2026-07-15 15:16:29'),
(6, 'LOC1784136180418', 'BTELOKA01', 'CLI1784123135420', '2026-07-15', '2026-07-16', '2026-07-15', 2000.00, 2000.00, 0.00, 'terminee', '2026-07-15 17:23:00', '2026-07-15 23:01:10'),
(7, 'LOC1784155767417', 'BTELOKA01', 'CLI1784123135420', '2026-07-15', '2026-07-16', '2026-07-15', 800.00, 10000.00, 0.00, 'terminee', '2026-07-15 22:49:27', '2026-07-15 22:50:10'),
(8, 'LOC1784157255776', 'BTELOKA01', 'CLI1784124232397', '2026-07-15', '2026-07-16', NULL, 1200.00, 2000.00, 0.00, 'en_cours', '2026-07-15 23:14:15', NULL),
(9, 'LOC1784157552151', 'BTELOKA01', 'CLI1784124232397', '2026-07-15', '2026-07-15', NULL, 200.00, 0.00, 200.00, 'en_cours', '2026-07-15 23:19:12', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

DROP TABLE IF EXISTS `paiements`;
CREATE TABLE IF NOT EXISTS `paiements` (
  `id_paiement` int NOT NULL AUTO_INCREMENT,
  `code_paiement` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant_paiement` decimal(12,2) NOT NULL,
  `mode_paiement` enum('especes','wave','orange','mtn','moov','carte','autre') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'especes',
  `reference_paiement` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at_paiement` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_paiement` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_paiement`),
  UNIQUE KEY `code_paiement` (`code_paiement`),
  KEY `fk_paiements_locations` (`location_code`)
) ENGINE=MyISAM AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `paiements`
--

INSERT INTO `paiements` (`id_paiement`, `code_paiement`, `location_code`, `montant_paiement`, `mode_paiement`, `reference_paiement`, `created_at_paiement`, `updated_at_paiement`) VALUES
(1, 'PAI1784125890643', 'LOC1784124768523', 100.00, 'especes', NULL, '2026-07-15 14:31:30', NULL),
(2, 'PAI1784125917934', 'LOC1784124768523', 200.00, 'especes', NULL, '2026-07-15 14:31:57', NULL),
(3, 'PAI1784128455725', 'LOC1784128455281', 200.00, 'especes', NULL, '2026-07-15 15:14:15', NULL),
(4, 'PAI1784136180130', 'LOC1784136180418', 2000.00, 'especes', NULL, '2026-07-15 17:23:00', NULL),
(5, 'PAI1784155767277', 'LOC1784155767417', 10000.00, 'especes', NULL, '2026-07-15 22:49:27', NULL),
(6, 'PAI1784157255969', 'LOC1784157255776', 2000.00, 'especes', NULL, '2026-07-15 23:14:15', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `retours`
--

DROP TABLE IF EXISTS `retours`;
CREATE TABLE IF NOT EXISTS `retours` (
  `id_retour` int NOT NULL AUTO_INCREMENT,
  `code_retour` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `boutique_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_retour` date NOT NULL,
  `statut_retour` enum('partiel','termine') COLLATE utf8mb4_unicode_ci DEFAULT 'termine',
  `observation_retour` text COLLATE utf8mb4_unicode_ci,
  `created_at_retour` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_retour` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_retour`),
  UNIQUE KEY `code_retour` (`code_retour`),
  KEY `fk_retours_locations` (`location_code`),
  KEY `fk_retours_boutiques` (`boutique_code`),
  KEY `fk_retours_users` (`user_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `code_user` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_user` enum('developpeur','vendeur') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'vendeur',
  `nom_user` varchar(150) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone_user` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut_user` enum('actif','inactif') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `created_at_user` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_user` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `code_user` (`code_user`),
  UNIQUE KEY `telephone_user` (`telephone_user`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id_user`, `code_user`, `role_user`, `nom_user`, `telephone_user`, `statut_user`, `created_at_user`, `updated_at_user`) VALUES
(1, 'USRDEV001', 'developpeur', 'Admin LOKALEX', '770000001', 'actif', '2026-07-15 12:59:25', NULL),
(2, 'USRVEND001', 'vendeur', 'Demo Vendeur', '770000002', 'actif', '2026-07-15 12:59:25', NULL),
(3, 'USR1784127820477', 'vendeur', 'Tahno Richard', '0566015516', 'actif', '2026-07-15 15:03:40', NULL);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
