-- Ajouter la colonne téléphone aux utilisateurs (exécuter une seule fois si la table existe déjà)
USE `gestion`;
ALTER TABLE `utilisateurs` ADD COLUMN `telephone` varchar(20) DEFAULT NULL AFTER `photo`;
