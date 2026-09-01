<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::redirigerSiNonConnecte();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/taches/index.php'); exit; }

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT t.*, p.nom as projet_nom, p.chef_projet_id, u.prenom as assignee_prenom, u.nom as assignee_nom FROM taches t JOIN projets p ON t.projet_id = p.id LEFT JOIN utilisateurs u ON t.assignee_id = u.id WHERE t.id = ?');
$stmt->execute([$id]);
$t = $stmt->fetch();
if (!$t || !Auth::peutVoirProjet($t['projet_id'])) {
    header('Location: ' . SITE_URL . '/taches/index.php');
    exit;
}
// Membre : ne peut ouvrir que les tâches qui lui sont assignées
if (Auth::estMembre() && (int)($t['assignee_id'] ?? 0) !== (int)Auth::getUser()['id']) {
    header('Location: ' . SITE_URL . '/taches/index.php');
    exit;
}

$pageTitle = $t['titre'] . ' - GESPRO';
$currentPage = 'taches';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Gestion des Tâches</h1>
<p class="breadcrumb"><a href="<?= SITE_URL ?>/dashboard.php">Accueil</a> / Tâches / <?= htmlspecialchars($t['titre']) ?></p>

<?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-success">
    <?php
    if ($_GET['msg'] === 'termine') {
        echo 'Tâche marquée comme terminée avec succès. La preuve a été enregistrée.';
    } elseif ($_GET['msg'] === 'modifie') {
        echo 'Tâche modifiée.';
    } elseif ($_GET['msg'] === 'statut') {
        echo 'Statut mis à jour.';
    }
    ?>
  </div>
<?php endif; ?>

<div class="form-card">
  <div class="form-card-header">Détail de la tâche</div>
  <div class="form-card-body">
    <dl class="info-list">
      <dt>Titre</dt>
      <dd><?= htmlspecialchars($t['titre']) ?></dd>
      <dt>Projet</dt>
      <dd><a href="<?= SITE_URL ?>/projets/voir.php?id=<?= (int)$t['projet_id'] ?>"><?= htmlspecialchars($t['projet_nom']) ?></a></dd>
      <dt>Description</dt>
      <dd><?= nl2br(htmlspecialchars($t['description'] ?: '-')) ?></dd>
      <dt>Statut</dt>
      <dd>
        <span class="badge badge-<?= $t['statut'] === 'termine' ? 'termine' : ($t['statut'] === 'en_cours' ? 'en-cours' : 'en-attente') ?>"><?= statutTacheLabel($t['statut']) ?></span>
        <?php
        $user = Auth::getUser();
        $estAssignee = (int)($t['assignee_id'] ?? 0) === (int)$user['id'];
        $membrePeutChangerStatut = $estAssignee && $t['statut'] !== 'annule' && $t['statut'] !== 'termine';
        if ($membrePeutChangerStatut): ?>
        <form method="post" action="<?= SITE_URL ?>/taches/update_statut.php" style="display:inline-block; margin-left:12px;">
          <input type="hidden" name="tache_id" value="<?= $id ?>">
          <select name="statut" class="form-control" style="display:inline-block; width:auto; padding:4px 8px; vertical-align:middle;">
            <option value="a_faire" <?= $t['statut'] === 'a_faire' ? 'selected' : '' ?>>À faire</option>
            <option value="en_cours" <?= $t['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
          </select>
          <button type="submit" class="btn btn-primary" style="margin-left:6px; vertical-align:middle;">Mettre à jour le statut</button>
        </form>
        <?php endif; ?>
      </dd>
      <dt>Priorité</dt>
      <dd><?= ucfirst($t['priorite']) ?></dd>
      <dt>Assigné à</dt>
      <dd><?= $t['assignee_id'] ? htmlspecialchars($t['assignee_prenom'] . ' ' . $t['assignee_nom']) : '-' ?></dd>
      <dt>Date d'échéance</dt>
      <dd><?= formatDateFr($t['date_echeance']) ?></dd>
      <?php if (!empty($t['preuve_image'])): $preuveUrl = preuveTacheUrl($t); if ($preuveUrl): ?>
      <dt id="preuve">Preuve de réalisation</dt>
      <dd><a href="<?= htmlspecialchars($preuveUrl) ?>" target="_blank" rel="noopener"><img src="<?= htmlspecialchars($preuveUrl) ?>" alt="Preuve" style="max-width:300px; max-height:200px; border:1px solid #ccc; border-radius:4px;"></a> <small>(image soumise par le membre)</small></dd>
      <?php endif; endif; ?>
    </dl>
    <div class="form-actions" style="margin-top:20px;">
      <?php
      $membrePeutEditer = Auth::estMembre() && $estAssignee;
      $chefOuAdminPeutEditer = Auth::estAdmin() || (Auth::estChefProjet() && Auth::peutVoirProjet($t['projet_id']));
      if ($membrePeutEditer || $chefOuAdminPeutEditer): ?>
        <a href="<?= SITE_URL ?>/taches/editer.php?id=<?= $id ?>" class="btn btn-primary"><?= $membrePeutEditer && !$chefOuAdminPeutEditer ? 'Mettre à jour la tâche' : 'Modifier la tâche' ?></a>
      <?php endif; ?>
      <?php
      $membrePeutTerminer = $estAssignee && $t['statut'] !== 'termine' && $t['statut'] !== 'annule';
      if ($membrePeutTerminer): ?>
        <a href="<?= SITE_URL ?>/taches/terminer.php?id=<?= $id ?>" class="btn btn-secondary">Marquer comme terminée (avec preuve)</a>
      <?php endif; ?>
      <a href="<?= SITE_URL ?>/taches/index.php" class="btn btn-secondary">Retour</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
