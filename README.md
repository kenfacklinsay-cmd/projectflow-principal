# GESPRO — Gestion de Projets Simplifiée

Application web de gestion de projets avec tâches, utilisateurs, notifications et messagerie.

## Prérequis

- PHP 7.4+ avec PDO MySQL
- MySQL / MariaDB (WAMP fournit Apache + PHP + MySQL)

## Installation

1. **Créer la base de données**
   - Ouvrir phpMyAdmin ou la console MySQL
   - Exécuter le contenu de `sql/01_create_database.sql`
   - Puis exécuter `sql/gespro_complet.sql` (ou importer le fichier)

2. **Configurer la connexion** (si besoin)  
   Éditer `config/config.php` : `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`, `SITE_URL`. Par défaut la base s’appelle **gestion** (WampServer).

3. **Accéder à l’application**  
   URL : `http://localhost/essaie` (ou celle définie dans `SITE_URL`).

## Comptes de test

| Email              | Mot de passe | Rôle             |
|--------------------|-------------|------------------|
| admin@gespro.com   | password    | Administrateur   |
| chef@gespro.com    | password    | Chef de projet   |
| membre@gespro.com  | password    | Membre d’équipe  |

## Rôles et droits

- **Administrateur** : accès à tout ; gestion de tous les utilisateurs et projets.
- **Chef de projet** : gestion de ses projets et de leurs tâches ; gestion des membres de ses équipes (ajout/retrait) ; vue sur les membres de ses équipes.
- **Membre d’équipe** : vue sur les projets auxquels il est affecté, ses tâches, son profil, notifications et messagerie.

## Fonctionnalités

- **Projets** : ajout, modification, consultation, suppression ; progression en % (tâches terminées / total).
- **Tâches** : liées à un projet ; statuts (À faire, En cours, Terminé) ; assignation ; mise à jour de la progression du projet.
- **Utilisateurs** : gestion selon le rôle (admin : tous ; chef : équipe ; membre : soi).
- **Équipes** : le chef de projet peut ajouter/retirer des membres sur chaque projet (page « Gérer l’équipe » depuis la fiche projet).
- **Notifications** : créées notamment lorsqu’une tâche est marquée terminée (notification au chef de projet).
- **Messagerie** : envoi de messages entre utilisateurs (reçus / envoyés).

## Structure des dossiers

- `config/` — configuration et connexion
- `classes/` — Database, Auth
- `includes/` — header, footer, helpers
- `assets/css/` — styles (interface bleu foncé / blanc comme les maquettes)
- `sql/` — scripts SQL complets de l’application
- `projets/`, `taches/`, `utilisateurs/` — pages CRUD et équipes

## SQL

Tout le code SQL de l’application se trouve dans le dossier **sql** :
- `01_create_database.sql` — création de la base `gestion`
- `gespro_complet.sql` — tables, vues, données initiales
- `README.md` — instructions d’exécution
