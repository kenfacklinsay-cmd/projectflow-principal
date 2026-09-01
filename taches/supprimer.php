<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::redirigerSiNonConnecte();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/taches/index.php'); exit; }

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT t.projet_id, p.chef_projet_id FROM taches t JOIN projets p ON t.projet_id = p.id WHERE t.id = ?');
$stmt->execute([$id]);
$row = $stmt->fetch();
if (!$row || !(Auth::estAdmin() || (Auth::estChefProjet() && (int)$row['chef_projet_id'] === (int)Auth::getUser()['id']))) {
    header('Location: ' . SITE_URL . '/taches/index.php');
    exit;
}

$db->prepare('DELETE FROM taches WHERE id = ?')->execute([$id]);
header('Location: ' . SITE_URL . '/taches/index.php?msg=supprime');
exit;
