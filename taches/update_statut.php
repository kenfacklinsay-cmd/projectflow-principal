<?php
/**
 * Mise à jour du statut d'une tâche par un membre (uniquement À faire / En cours).
 * Pour marquer "Terminé", le membre doit passer par terminer.php (avec preuve).
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::redirigerSiNonConnecte();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/taches/index.php');
    exit;
}

$id = (int)($_POST['tache_id'] ?? 0);
$statut = $_POST['statut'] ?? '';

$statutsAutorises = ['a_faire', 'en_cours'];
if (!$id || !in_array($statut, $statutsAutorises, true)) {
    header('Location: ' . SITE_URL . '/taches/voir.php?id=' . $id . ($id ? '' : ''));
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT id, projet_id, assignee_id FROM taches WHERE id = ?');
$stmt->execute([$id]);
$t = $stmt->fetch();

if (!$t || !Auth::peutVoirProjet($t['projet_id'])) {
    header('Location: ' . SITE_URL . '/taches/index.php');
    exit;
}

$user = Auth::getUser();
$estAssignee = (int)($t['assignee_id'] ?? 0) === (int)$user['id'];
$membrePeutModifierStatut = $estAssignee;

if (!$membrePeutModifierStatut) {
    header('Location: ' . SITE_URL . '/taches/voir.php?id=' . $id);
    exit;
}

$stmt = $db->prepare('UPDATE taches SET statut = ? WHERE id = ?');
$stmt->execute([$statut, $id]);

header('Location: ' . SITE_URL . '/taches/voir.php?id=' . $id . '&msg=statut');
exit;
