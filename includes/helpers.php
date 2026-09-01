<?php
/**
 * Helpers GESPRO
 */

function formatDateFr($date) {
    if (!$date) return '-';
    $d = date_create($date);
    return $d ? $d->format('d/m/Y') : $date;
}

function statutProjetLabel($code) {
    $map = ['en_cours' => 'En cours', 'termine' => 'Terminé', 'en_attente' => 'En attente', 'annule' => 'Annulé'];
    return $map[$code] ?? $code;
}

function statutTacheLabel($code) {
    $map = ['a_faire' => 'À faire', 'en_cours' => 'En cours', 'termine' => 'Terminé', 'annule' => 'Annulé'];
    return $map[$code] ?? $code;
}

function badgeStatutProjet($code) {
    $class = 'badge-en-cours';
    if ($code === 'termine') $class = 'badge-termine';
    if ($code === 'en_attente') $class = 'badge-en-attente';
    if ($code === 'annule') $class = 'badge-en-attente';
    return '<span class="badge ' . $class . '">' . htmlspecialchars(statutProjetLabel($code)) . '</span>';
}

/** Progression d'un projet (pourcentage tâches terminées) */
function getProgressionProjet($pdo, $projetId) {
    $stmt = $pdo->prepare('SELECT total_taches, taches_terminees, progression_pct FROM v_progression_projets WHERE id = ?');
    $stmt->execute([$projetId]);
    $row = $stmt->fetch();
    return $row ? (int) $row['progression_pct'] : 0;
}

/** URL de la photo de profil (ou null si pas de photo) */
function photoProfilUrl($user) {
    if (!empty($user['photo'])) {
        return defined('UPLOAD_PROFILES_URL') ? UPLOAD_PROFILES_URL . '/' . rawurlencode($user['photo']) : null;
    }
    return null;
}

/** URL de l'image de preuve d'une tâche (quand un membre marque la tâche terminée) */
function preuveTacheUrl($tache) {
    if (!empty($tache['preuve_image']) && defined('UPLOAD_TASK_PROOFS_URL')) {
        return UPLOAD_TASK_PROOFS_URL . '/' . rawurlencode($tache['preuve_image']);
    }
    return null;
}

function creerNotification($db, $utilisateur_id, $titre, $message, $lien = null, $type = 'info') {
    $stmt = $db->prepare('INSERT INTO notifications (utilisateur_id, titre, message, type, lien) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$utilisateur_id, $titre, $message, $type, $lien]);
}
