<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::redirigerSiNonConnecte();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/projets/index.php'); exit; }

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT chef_projet_id FROM projets WHERE id = ?');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p || !(Auth::estAdmin() || (Auth::estChefProjet() && (int)$p['chef_projet_id'] === (int)Auth::getUser()['id']))) {
    header('Location: ' . SITE_URL . '/projets/index.php');
    exit;
}

$db->prepare('DELETE FROM projets WHERE id = ?')->execute([$id]);
header('Location: ' . SITE_URL . '/projets/index.php?msg=supprime');
exit;
