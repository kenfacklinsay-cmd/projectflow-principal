-- Ajouter la colonne photo aux utilisateurs (exécuter une seule fois)
USE `gestion`;
ALTER TABLE `utilisateurs` ADD COLUMN `photo` varchar(255) DEFAULT NULL AFTER `actif`;
