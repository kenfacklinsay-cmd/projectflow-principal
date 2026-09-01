-- ============================================
-- GESPRO - Base de données - Gestion de Projets
-- ============================================
-- À exécuter après avoir créé la base (01_create_database.sql)
-- ou après : CREATE DATABASE IF NOT EXISTS gestion; USE gestion;

USE `gestion`;
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table: roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(50) NOT NULL,
  `libelle` varchar(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `roles` (`id`, `code`, `libelle`) VALUES
(1, 'admin', 'Administrateur'),
(2, 'chef_projet', 'Chef de projet'),
(3, 'membre', 'Membre d\'équipe');

-- ----------------------------
-- Table: utilisateurs
-- ----------------------------
DROP TABLE IF EXISTS `utilisateurs`;
CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `role_id` int(11) NOT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `photo` varchar(255) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `utilisateurs_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Mots de passe en clair (non chiffrés)
INSERT INTO `utilisateurs` (`id`, `email`, `mot_de_passe`, `nom`, `prenom`, `role_id`, `actif`) VALUES
(1, 'admin@gespro.com', 'password', 'Admin', 'Système', 1, 1),
(2, 'chef@gespro.com', 'password', 'Tech', 'Sir', 2, 1),
(3, 'membre@gespro.com', 'password', 'Rayan', 'Membre', 3, 1);

-- ----------------------------
-- Table: projets
-- ----------------------------
DROP TABLE IF EXISTS `projets`;
CREATE TABLE `projets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(255) NOT NULL,
  `description` text,
  `date_creation` date NOT NULL,
  `statut` enum('en_cours','termine','en_attente','annule') DEFAULT 'en_cours',
  `chef_projet_id` int(11) NOT NULL,
  `date_modification` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `chef_projet_id` (`chef_projet_id`),
  CONSTRAINT `projets_ibfk_1` FOREIGN KEY (`chef_projet_id`) REFERENCES `utilisateurs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `projets` (`id`, `nom`, `description`, `date_creation`, `statut`, `chef_projet_id`) VALUES
(1, 'Migration Serveur', 'Migration des serveurs vers la nouvelle infrastructure', '2026-02-01', 'en_cours', 2),
(2, 'Campagne Marketing', 'Lancement de la campagne marketing Q1', '2026-03-15', 'en_cours', 2);

-- ----------------------------
-- Table: equipes_projet (chef -> membres)
-- ----------------------------
DROP TABLE IF EXISTS `equipes_projet`;
CREATE TABLE `equipes_projet` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `projet_id` int(11) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `date_ajout` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `projet_utilisateur` (`projet_id`,`utilisateur_id`),
  UNIQUE KEY `un_seul_projet_par_membre` (`utilisateur_id`),
  CONSTRAINT `equipes_projet_ibfk_1` FOREIGN KEY (`projet_id`) REFERENCES `projets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `equipes_projet_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `equipes_projet` (`projet_id`, `utilisateur_id`) VALUES (1, 3);

-- ----------------------------
-- Table: tâches
-- ----------------------------
DROP TABLE IF EXISTS `taches`;
CREATE TABLE `taches` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `projet_id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text,
  `statut` enum('a_faire','en_cours','termine','annule') DEFAULT 'a_faire',
  `priorite` enum('basse','normale','haute','urgente') DEFAULT 'normale',
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  `date_echeance` date DEFAULT NULL,
  `assignee_id` int(11) DEFAULT NULL,
  `ordre` int(11) DEFAULT 0,
  `preuve_image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `projet_id` (`projet_id`),
  KEY `assignee_id` (`assignee_id`),
  CONSTRAINT `taches_ibfk_1` FOREIGN KEY (`projet_id`) REFERENCES `projets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `taches_ibfk_2` FOREIGN KEY (`assignee_id`) REFERENCES `utilisateurs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `taches` (`id`, `projet_id`, `titre`, `description`, `statut`, `priorite`, `date_echeance`, `assignee_id`) VALUES
(1, 1, 'Backup des données', 'Sauvegarder toutes les données avant migration', 'termine', 'haute', '2026-02-10', 3),
(2, 1, 'Installation nouveau serveur', 'Installer et configurer le nouveau serveur', 'en_cours', 'haute', '2026-02-20', 2),
(3, 1, 'Tests de migration', 'Tests complets après migration', 'a_faire', 'normale', '2026-02-28', NULL),
(4, 2, 'Création visuels', 'Créer les visuels pour la campagne', 'en_cours', 'normale', '2026-03-25', 3),
(5, 2, 'Rédaction contenus', 'Rédiger les textes des annonces', 'a_faire', 'normale', '2026-03-30', NULL);

-- ----------------------------
-- Table: notifications
-- ----------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `utilisateur_id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `type` varchar(50) DEFAULT 'info',
  `lue` tinyint(1) DEFAULT 0,
  `lien` varchar(500) DEFAULT NULL,
  `date_creation` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `utilisateur_id` (`utilisateur_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: messages (messagerie)
-- ----------------------------
DROP TABLE IF EXISTS `messages`;
CREATE TABLE `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `expediteur_id` int(11) NOT NULL,
  `destinataire_id` int(11) NOT NULL,
  `sujet` varchar(255) DEFAULT NULL,
  `contenu` text NOT NULL,
  `lu` tinyint(1) DEFAULT 0,
  `date_envoi` datetime DEFAULT CURRENT_TIMESTAMP,
  `supprime_expediteur` tinyint(1) DEFAULT 0,
  `supprime_destinataire` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `expediteur_id` (`expediteur_id`),
  KEY `destinataire_id` (`destinataire_id`),
  CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`expediteur_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`destinataire_id`) REFERENCES `utilisateurs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Vue: progression des projets (%)
-- ----------------------------
CREATE OR REPLACE VIEW `v_progression_projets` AS
SELECT
  p.id,
  p.nom,
  p.chef_projet_id,
  p.statut,
  COUNT(t.id) AS total_taches,
  SUM(CASE WHEN t.statut = 'termine' THEN 1 ELSE 0 END) AS taches_terminees,
  IF(COUNT(t.id) > 0,
    ROUND(SUM(CASE WHEN t.statut = 'termine' THEN 1 ELSE 0 END) * 100.0 / COUNT(t.id), 0),
    0
  ) AS progression_pct
FROM projets p
LEFT JOIN taches t ON t.projet_id = p.id AND t.statut != 'annule'
GROUP BY p.id, p.nom, p.chef_projet_id, p.statut;

SET FOREIGN_KEY_CHECKS = 1;
