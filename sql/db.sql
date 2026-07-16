-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : mer. 15 juil. 2026 à 11:37
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
-- Base de données : `db_location`
--

-- --------------------------------------------------------

--
-- Structure de la table `abonnements`
--

DROP TABLE IF EXISTS `abonnements`;
CREATE TABLE IF NOT EXISTS `abonnements` (
  `id_abonnement` int NOT NULL AUTO_INCREMENT,
  `code_abonnement` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `boutique_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `forfait_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_debut_abonnement` date NOT NULL,
  `date_fin_abonnement` date NOT NULL,
  `montant_abonnement` decimal(12,2) NOT NULL,
  `statut_abonnement` enum('en_attente','actif','expire','suspendu') COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `created_at_abonnement` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_abonnement` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_abonnement`),
  UNIQUE KEY `code_abonnement` (`code_abonnement`),
  KEY `fk_abonnements_boutiques` (`boutique_code`),
  KEY `fk_abonnements_forfaits` (`forfait_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `articles`
--

DROP TABLE IF EXISTS `articles`;
CREATE TABLE IF NOT EXISTS `articles` (
  `id_article` int NOT NULL AUTO_INCREMENT,
  `code_article` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `boutique_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categorie_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle_article` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite_article` int NOT NULL DEFAULT '0',
  `prix_location_article` decimal(12,2) DEFAULT '0.00',
  `statut_article` enum('actif','inactif') COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `created_at_article` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_article` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_article`),
  UNIQUE KEY `code_article` (`code_article`),
  KEY `fk_articles_boutiques` (`boutique_code`),
  KEY `fk_articles_categories` (`categorie_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `boutiques`
--

DROP TABLE IF EXISTS `boutiques`;
CREATE TABLE IF NOT EXISTS `boutiques` (
  `id_boutique` int NOT NULL AUTO_INCREMENT,
  `code_boutique` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle_boutique` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone_boutique` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse_boutique` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ville_boutique` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut_boutique` enum('actif','inactif') COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `created_at_boutique` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_boutique` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_boutique`),
  UNIQUE KEY `code_boutique` (`code_boutique`),
  UNIQUE KEY `user_code` (`user_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

DROP TABLE IF EXISTS `categories`;
CREATE TABLE IF NOT EXISTS `categories` (
  `id_categorie` int NOT NULL AUTO_INCREMENT,
  `code_categorie` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `boutique_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `libelle_categorie` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut_categorie` enum('actif','inactif') COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `created_at_categorie` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_categorie` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_categorie`),
  UNIQUE KEY `code_categorie` (`code_categorie`),
  KEY `fk_categories_boutiques` (`boutique_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE IF NOT EXISTS `clients` (
  `id_client` int NOT NULL AUTO_INCREMENT,
  `code_client` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `boutique_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nom_client` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone_client` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `adresse_client` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `statut_client` enum('actif','inactif') COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `created_at_client` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_client` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_client`),
  UNIQUE KEY `code_client` (`code_client`),
  KEY `fk_clients_boutiques` (`boutique_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `forfaits`
--

DROP TABLE IF EXISTS `forfaits`;
CREATE TABLE IF NOT EXISTS `forfaits` (
  `id_forfait` int NOT NULL AUTO_INCREMENT,
  `code_forfait` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `libelle_forfait` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `prix_forfait` decimal(12,2) NOT NULL,
  `duree_forfait` int NOT NULL COMMENT 'Nombre de jours',
  `description_forfait` text COLLATE utf8mb4_unicode_ci,
  `statut_forfait` enum('actif','inactif') COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
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
  `code_ligne_location` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `article_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantite_ligne_location` int NOT NULL,
  `prix_unitaire_ligne_location` decimal(12,2) NOT NULL,
  `montant_ligne_location` decimal(12,2) NOT NULL,
  `created_at_ligne_location` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_ligne_location` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_ligne_location`),
  UNIQUE KEY `code_ligne_location` (`code_ligne_location`),
  KEY `fk_lignes_locations` (`location_code`),
  KEY `fk_lignes_articles` (`article_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `locations`
--

DROP TABLE IF EXISTS `locations`;
CREATE TABLE IF NOT EXISTS `locations` (
  `id_location` int NOT NULL AUTO_INCREMENT,
  `code_location` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `boutique_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `date_sortie_location` date NOT NULL,
  `date_retour_prevue_location` date NOT NULL,
  `date_retour_effective_location` date DEFAULT NULL,
  `montant_location` decimal(12,2) NOT NULL,
  `avance_location` decimal(12,2) DEFAULT '0.00',
  `reste_location` decimal(12,2) DEFAULT '0.00',
  `statut_location` enum('en_cours','terminee','retard') COLLATE utf8mb4_unicode_ci DEFAULT 'en_cours',
  `created_at_location` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_location` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_location`),
  UNIQUE KEY `code_location` (`code_location`),
  KEY `fk_locations_boutiques` (`boutique_code`),
  KEY `fk_locations_clients` (`client_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `paiements`
--

DROP TABLE IF EXISTS `paiements`;
CREATE TABLE IF NOT EXISTS `paiements` (
  `id_paiement` int NOT NULL AUTO_INCREMENT,
  `code_paiement` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `location_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `montant_paiement` decimal(12,2) NOT NULL,
  `mode_paiement` enum('especes','wave','orange','mtn','moov','carte','autre') COLLATE utf8mb4_unicode_ci DEFAULT 'especes',
  `reference_paiement` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at_paiement` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_paiement` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_paiement`),
  UNIQUE KEY `code_paiement` (`code_paiement`),
  KEY `fk_paiements_locations` (`location_code`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE IF NOT EXISTS `users` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `code_user` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_user` enum('developpeur','vendeur') COLLATE utf8mb4_unicode_ci DEFAULT 'vendeur',
  `nom_user` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telephone_user` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `statut_user` enum('actif','inactif') COLLATE utf8mb4_unicode_ci DEFAULT 'actif',
  `created_at_user` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at_user` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `code_user` (`code_user`),
  UNIQUE KEY `telephone_user` (`telephone_user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
