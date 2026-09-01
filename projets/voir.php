<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::redirigerSiNonConnecte();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/projets/index.php'); exit; }

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT p.*, u.prenom as chef_prenom, u.nom as chef_nom FROM projets p LEFT JOIN utilisateurs u ON p.chef_projet_id = u.id WHERE p.id = ?');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p || !Auth::peutVoirProjet($id)) {
    header('Location: ' . SITE_URL . '/projets/index.php');
    exit;
}

$stmt = $db->prepare('SELECT progression_pct FROM v_progression_projets WHERE id = ?');
$stmt->execute([$id]);
$prog = $stmt->fetch();
$pct = $prog ? (int)$prog['progression_pct'] : 0;

$pageTitle = $p['nom'] . ' - GESPRO';
$currentPage = 'projets';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Gestion des Projets</h1>
<p class="breadcrumb"><a href="<?= SITE_URL ?>/dashboard.php">Accueil</a> / Projets / <?= htmlspecialchars($p['nom']) ?></p>

<div class="form-card">
  <div class="form-card-header">Informations du projet</div>
  <div class="form-card-body">
    <dl class="info-list">
      <dt>Nom du projet</dt>
      <dd><?= htmlspecialchars($p['nom']) ?></dd>
      <dt>Description</dt>
      <dd><?= nl2br(htmlspecialchars($p['description'] ?: '-')) ?></dd>
      <dt>Chef de projet</dt>
      <dd><?= htmlspecialchars($p['chef_prenom'] . ' ' . $p['chef_nom']) ?></dd>
      <dt>Date de création</dt>
      <dd><?= formatDateFr($p['date_creation']) ?></dd>
      <dt>Progression</dt>
      <dd>
        <div class="progression-bar"><div class="progression-bar-fill" style="width:<?= $pct ?>%"></div></div>
        <strong><?= $pct ?>%</strong>
      </dd>
      <dt>Statut</dt>
      <dd><?= badgeStatutProjet($p['statut']) ?></dd>
    </dl>
    <div class="form-actions" style="margin-top:20px;">
      <?php if (Auth::estAdmin() || (Auth::estChefProjet() && (int)$p['chef_projet_id'] === (int)Auth::getUser()['id'])): ?>
        <a href="<?= SITE_URL ?>/projets/editer.php?id=<?= $id ?>" class="btn btn-primary">Modifier</a>
        <a href="<?= SITE_URL ?>/projets/equipe.php?id=<?= $id ?>" class="btn btn-secondary">Gérer les utilisateurs</a>
      <?php endif; ?>
      <a href="<?= SITE_URL ?>/projets/index.php" class="btn btn-secondary">Retour à la liste</a>
      <a href="<?= SITE_URL ?>/taches/index.php?projet_id=<?= $id ?>" class="btn btn-secondary">Voir les tâches</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
