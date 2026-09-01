<?php
$pageTitle = 'Modifier la tâche - GESPRO';
$currentPage = 'taches';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';

Auth::redirigerSiNonConnecte();
$user = Auth::getUser();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/taches/index.php'); exit; }

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT t.*, p.nom AS projet_nom FROM taches t JOIN projets p ON t.projet_id = p.id WHERE t.id = ?');
$stmt->execute([$id]);
$t = $stmt->fetch();
if (!$t || !Auth::peutVoirProjet($t['projet_id'])) {
    header('Location: ' . SITE_URL . '/taches/index.php');
    exit;
}

$estAssignee = (int)($t['assignee_id'] ?? 0) === (int)$user['id'];
$estChefSurProjet = Auth::estChefProjet() && Auth::peutVoirProjet($t['projet_id']);
$peutAccesComplet = Auth::estAdmin() || $estChefSurProjet;
$peutAccesMembre = Auth::estMembre() && $estAssignee;
if (!$peutAccesComplet && !$peutAccesMembre) {
    header('Location: ' . SITE_URL . '/taches/index.php');
    exit;
}

$estVueMembre = $peutAccesMembre && !$peutAccesComplet;

if ($peutAccesComplet) {
    if (Auth::estAdmin()) {
        $stmt = $db->query('SELECT id, nom FROM projets ORDER BY nom');
    } else {
        $stmt = $db->prepare('SELECT id, nom FROM projets WHERE chef_projet_id = ? ORDER BY nom');
        $stmt->execute([$user['id']]);
    }
    $projets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $db->query("SELECT id, nom, prenom FROM utilisateurs WHERE actif = 1 AND id != 1 ORDER BY nom, prenom");
    $assignables = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $projets = [];
    $assignables = [];
}

$erreurs = [];
$titre = trim($_POST['titre'] ?? $t['titre']);
$description = trim($_POST['description'] ?? $t['description']);
$projet_id = (int)($_POST['projet_id'] ?? $t['projet_id']);
$statut = $_POST['statut'] ?? $t['statut'];
$priorite = $_POST['priorite'] ?? $t['priorite'];
$date_echeance = trim($_POST['date_echeance'] ?? ($t['date_echeance'] ? date('Y-m-d', strtotime($t['date_echeance'])) : ''));
$assignee_id = isset($_POST['assignee_id'])
    ? ($_POST['assignee_id'] !== '' ? (int)$_POST['assignee_id'] : null)
    : ($t['assignee_id'] !== null ? (int)$t['assignee_id'] : null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($estVueMembre) {
        $projet_id = (int)$t['projet_id'];
        $assignee_id = $t['assignee_id'] !== null ? (int)$t['assignee_id'] : null;
        if (in_array($t['statut'], ['termine', 'annule'], true)) {
            $statut = $t['statut'];
        } elseif (!in_array($statut, ['a_faire', 'en_cours'], true)) {
            $statut = $t['statut'];
        }
    }
    if ($titre === '') $erreurs[] = 'Le titre est requis.';
    if ($date_echeance !== '') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_echeance)) {
            $d = date_create_from_format('d/m/Y', $date_echeance);
            $date_echeance = $d ? $d->format('Y-m-d') : null;
        }
    } else $date_echeance = null;
    if (empty($erreurs)) {
        $ancienStatut = $t['statut'];
        $ancienAssignee = (int)($t['assignee_id'] ?? 0);
        $stmt = $db->prepare('UPDATE taches SET projet_id = ?, titre = ?, description = ?, statut = ?, priorite = ?, date_echeance = ?, assignee_id = ? WHERE id = ?');
        $stmt->execute([$projet_id, $titre, $description ?: null, $statut, $priorite, $date_echeance, $assignee_id, $id]);
        if (!$estVueMembre && $statut === 'termine' && $ancienStatut !== 'termine') {
            $stmt = $db->prepare('SELECT chef_projet_id FROM projets WHERE id = ?');
            $stmt->execute([$projet_id]);
            $row = $stmt->fetch();
            if ($row) {
                creerNotification($db, $row['chef_projet_id'], 'Tâche terminée', 'La tâche « ' . $titre . ' » a été marquée comme terminée.', SITE_URL . '/taches/voir.php?id=' . $id, 'info');
            }
        }
        if (!$estVueMembre && $assignee_id && $assignee_id != $ancienAssignee) {
            $stmt = $db->prepare('SELECT nom FROM projets WHERE id = ?');
            $stmt->execute([$projet_id]);
            $projetNom = $stmt->fetchColumn() ?: 'Projet';
            creerNotification($db, $assignee_id, 'Tâche assignée', 'Une tâche vous a été assignée : « ' . $titre . ' » (projet ' . $projetNom . ').', SITE_URL . '/taches/voir.php?id=' . $id, 'info');
        }
        header('Location: ' . SITE_URL . '/taches/voir.php?id=' . $id . '&msg=modifie');
        exit;
    }
}
?>

<h1 class="page-title">Gestion des Tâches</h1>
<p class="breadcrumb"><a href="<?= SITE_URL ?>/dashboard.php">Accueil</a> / Tâches / Modifier</p>

<?php foreach ($erreurs as $e): ?>
  <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<div class="form-card">
  <div class="form-card-header"><?= $estVueMembre ? 'Mettre à jour ma tâche' : 'Modification de la tâche' ?></div>
  <div class="form-card-body">
    <?php if ($estVueMembre): ?>
      <p class="form-hint" style="margin-bottom:16px;color:#64748b;font-size:14px;">Vous pouvez modifier le contenu et l’avancement de votre tâche. Pour la marquer comme terminée, utilisez le bouton prévu sur la fiche (avec preuve si demandée).</p>
    <?php endif; ?>
    <form method="post" action="">
      <div class="form-group">
        <label>Projet</label>
        <?php if ($estVueMembre): ?>
          <p class="form-static"><?= htmlspecialchars($t['projet_nom']) ?></p>
        <?php else: ?>
          <select name="projet_id" class="form-control" required>
            <?php foreach ($projets as $pr): ?>
              <option value="<?= (int)$pr['id'] ?>" <?= $projet_id === (int)$pr['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pr['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label>Titre</label>
        <input type="text" name="titre" class="form-control" value="<?= htmlspecialchars($titre) ?>" required>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control"><?= htmlspecialchars($description) ?></textarea>
      </div>
      <div class="form-group">
        <label>Statut</label>
        <?php if ($estVueMembre): ?>
          <?php if ($t['statut'] === 'termine' || $t['statut'] === 'annule'): ?>
            <p class="form-static"><?= htmlspecialchars(statutTacheLabel($t['statut'])) ?></p>
            <input type="hidden" name="statut" value="<?= htmlspecialchars($t['statut']) ?>">
          <?php else: ?>
            <select name="statut" class="form-control">
              <option value="a_faire" <?= $statut === 'a_faire' ? 'selected' : '' ?>>À faire</option>
              <option value="en_cours" <?= $statut === 'en_cours' ? 'selected' : '' ?>>En cours</option>
            </select>
            <p class="form-hint" style="margin-top:6px;font-size:13px;color:#64748b;">Pour « Terminé », utilisez la fiche détail ou le bouton dédié.</p>
          <?php endif; ?>
        <?php else: ?>
          <select name="statut" class="form-control">
            <option value="a_faire" <?= $statut === 'a_faire' ? 'selected' : '' ?>>À faire</option>
            <option value="en_cours" <?= $statut === 'en_cours' ? 'selected' : '' ?>>En cours</option>
            <option value="termine" <?= $statut === 'termine' ? 'selected' : '' ?>>Terminé</option>
            <option value="annule" <?= $statut === 'annule' ? 'selected' : '' ?>>Annulé</option>
          </select>
        <?php endif; ?>
      </div>
      <div class="form-group">
        <label>Priorité</label>
        <select name="priorite" class="form-control">
          <option value="basse" <?= $priorite === 'basse' ? 'selected' : '' ?>>Basse</option>
          <option value="normale" <?= $priorite === 'normale' ? 'selected' : '' ?>>Normale</option>
          <option value="haute" <?= $priorite === 'haute' ? 'selected' : '' ?>>Haute</option>
          <option value="urgente" <?= $priorite === 'urgente' ? 'selected' : '' ?>>Urgente</option>
        </select>
      </div>
      <div class="form-group">
        <label>Date d'échéance</label>
        <input type="date" name="date_echeance" class="form-control" value="<?= htmlspecialchars($date_echeance) ?>">
      </div>
      <?php if (!$estVueMembre): ?>
        <div class="form-group">
          <label>Assigné à</label>
          <select name="assignee_id" class="form-control">
            <option value="">— Non assigné —</option>
            <?php foreach ($assignables as $m): ?>
              <option value="<?= (int)$m['id'] ?>" <?= $assignee_id === (int)$m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= SITE_URL ?>/taches/voir.php?id=<?= $id ?>" class="btn btn-secondary">Annuler</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
