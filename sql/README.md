# Scripts SQL GESPRO

## Installation

1. **Créer la base** (si nécessaire) :
   ```sql
   -- Exécuter dans MySQL/phpMyAdmin :
   SOURCE 01_create_database.sql;
   ```

2. **Créer les tables et données** :
   ```sql
   USE gestion;
   SOURCE gespro_complet.sql;
   ```

Ou exécuter dans l’ordre :
- `01_create_database.sql` — crée la base `gestion`
- `gespro_complet.sql` — crée toutes les tables, vues et données initiales

## Comptes de test

| Email              | Mot de passe | Rôle           |
|--------------------|-------------|----------------|
| admin@gespro.com   | password    | Administrateur |
| chef@gespro.com    | password    | Chef de projet |
| membre@gespro.com  | password    | Membre d’équipe|
