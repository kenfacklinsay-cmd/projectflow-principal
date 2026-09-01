-- Ajouter la colonne preuve_image aux tâches (exécuter une seule fois si la table existe déjà)
USE `gestion`;
ALTER TABLE `taches` ADD COLUMN `preuve_image` varchar(255) DEFAULT NULL AFTER `ordre`;
