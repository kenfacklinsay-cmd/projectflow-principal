<?php
/**
 * Script à exécuter une fois dans le navigateur pour ajouter la colonne "photo"
 * à la table utilisateurs si elle n'existe pas.
 * Exemple : http://localhost/essaie/install_photo_column.php
 */
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';

header('Content-Type: text/html; charset=utf-8');
echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Installation colonne photo</title></head><body style="font-family:sans-serif;padding:2rem;">';

try {
    $pdo = Database::getInstance()->getConnection();
} catch (Exception $e) {
    echo '<p style="color:red;">Erreur de connexion à la base : ' . htmlspecialchars($e->getMessage()) . '</p></body></html>';
    exit;
}

$dbName = DB_NAME;
$stmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = " . $pdo->quote($dbName) . " AND TABLE_NAME = 'utilisateurs' AND COLUMN_NAME = 'photo'");
$exists = $stmt && $stmt->fetch();

if ($exists) {
    echo '<p style="color:green;">La colonne <strong>photo</strong> existe déjà dans la table <strong>utilisateurs</strong>. Rien à faire.</p>';
    echo '<p><a href="' . SITE_URL . '/profil.php">Aller à Mon profil</a></p>';
    echo '</body></html>';
    exit;
}

try {
    $pdo->exec("ALTER TABLE `utilisateurs` ADD COLUMN `photo` varchar(255) DEFAULT NULL");
    echo '<p style="color:green;">La colonne <strong>photo</strong> a été ajoutée à la table <strong>utilisateurs</strong>.</p>';
    echo '<p><a href="' . SITE_URL . '/profil.php">Aller à Mon profil pour mettre une photo</a></p>';
} catch (PDOException $e) {
    echo '<p style="color:red;">Erreur : ' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<p>Vous pouvez exécuter manuellement dans phpMyAdmin ou MySQL :</p>';
    echo '<pre>USE ' . htmlspecialchars($dbName) . ";\nALTER TABLE `utilisateurs` ADD COLUMN `photo` varchar(255) DEFAULT NULL;</pre>";
}

echo '</body></html>';
