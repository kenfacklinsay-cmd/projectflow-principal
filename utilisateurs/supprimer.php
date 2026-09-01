<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::redirigerSiNonConnecte();
if (!Auth::estAdmin()) {
    header('Location: ' . SITE_URL . '/utilisateurs/index.php');
    exit;
}
$id = (int)($_GET['id'] ?? 0);
if (!$id || $id === (int)Auth::getUser()['id']) {
    header('Location: ' . SITE_URL . '/utilisateurs/index.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$db->prepare('DELETE FROM utilisateurs WHERE id = ?')->execute([$id]);
header('Location: ' . SITE_URL . '/utilisateurs/index.php?msg=supprime');
exit;
