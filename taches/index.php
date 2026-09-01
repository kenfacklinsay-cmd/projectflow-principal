<?php
$pageTitle = 'Gestion des Tâches - GESPRO';
$currentPage = 'taches';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';

Auth::redirigerSiNonConnecte();
$user = Auth::getUser();
$db = Database::getInstance()->getConnection();

$projetId = isset($_GET['projet_id']) ? (int)$_GET['projet_id'] : null;
if ($projetId && !Auth::peutVoirProjet($projetId)) $projetId = null;

if (Auth::estAdmin()) {
    $sql = 'SELECT t.*, p.nom as projet_nom, u.prenom as assignee_prenom, u.nom as assignee_nom FROM taches t JOIN projets p ON t.projet_id = p.id LEFT JOIN utilisateurs u ON t.assignee_id = u.id WHERE t.statut != ?';
    $params = ['annule'];
    if ($projetId) { $sql .= ' AND t.projet_id = ?'; $params[] = $projetId; }
    $sql .= ' ORDER BY t.projet_id, t.ordre, t.date_creation';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
} elseif (Auth::estChefProjet()) {
    $sql = 'SELECT t.*, p.nom as projet_nom, u.prenom as assignee_prenom, u.nom as assignee_nom FROM taches t JOIN projets p ON t.projet_id = p.id AND p.chef_projet_id = ? LEFT JOIN utilisateurs u ON t.assignee_id = u.id WHERE t.statut != ?';
    $params = [$user['id'], 'annule'];
    if ($projetId) { $sql .= ' AND t.projet_id = ?'; $params[] = $projetId; }
    $sql .= ' ORDER BY t.projet_id, t.ordre, t.date_creation';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
} else {
    // Membre : ne voit que les tâches qui lui sont assignées
    $sql = 'SELECT t.*, p.nom as projet_nom, u.prenom as assignee_prenom, u.nom as assignee_nom FROM taches t JOIN projets p ON t.projet_id = p.id LEFT JOIN utilisateurs u ON t.assignee_id = u.id WHERE t.assignee_id = ? AND t.statut != ?';
    $params = [$user['id'], 'annule'];
    if ($projetId) { $sql .= ' AND t.projet_id = ?'; $params[] = $projetId; }
    $sql .= ' ORDER BY t.projet_id, t.ordre, t.date_creation';
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
}
$taches = $stmt->fetchAll(PDO::FETCH_ASSOC);

$recherche = trim($_GET['q'] ?? '');
if ($recherche !== '') {
    $taches = array_filter($taches, function ($t) use ($recherche) {
        return stripos($t['titre'], $recherche) !== false || stripos($t['projet_nom'], $recherche) !== false;
    });
}

// Liste projets pour filtre (membre : uniquement projets où il a des tâches assignées)
if (Auth::estMembre()) {
    $stmtProjets = $db->prepare('SELECT DISTINCT p.id, p.nom FROM projets p JOIN taches t ON t.projet_id = p.id WHERE t.assignee_id = ? ORDER BY p.nom');
    $stmtProjets->execute([$user['id']]);
    $projetsList = $stmtProjets->fetchAll(PDO::FETCH_ASSOC);
} else {
    $stmtProjets = $db->query('SELECT id, nom FROM projets ORDER BY nom');
    $projetsList = $stmtProjets->fetchAll(PDO::FETCH_ASSOC);
}
?>

<h1 class="page-title">Gestion des Tâches</h1>
<p class="breadcrumb"><a href="<?= SITE_URL ?>/dashboard.php">Accueil</a> / Tâches / Liste des tâches</p>

<div class="toolbar">
  <form method="get" action="" class="search-form-live" style="display:flex;gap:8px;flex-wrap:wrap;">
    <input type="text" name="q" class="search-input search-input-live" placeholder="Rechercher une tâche..." value="<?= htmlspecialchars($recherche) ?>">
    <?php if ($projetId): ?>
      <input type="hidden" name="projet_id" value="<?= $projetId ?>">
    <?php endif; ?>
    <select name="projet_id" class="form-control" style="width:auto;">
      <option value="">Tous les projets</option>
      <?php foreach ($projetsList as $pr): 
        if (!Auth::peutVoirProjet($pr['id'])) continue;
      ?>
        <option value="<?= (int)$pr['id'] ?>" <?= $projetId === (int)$pr['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pr['nom']) ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Rechercher</button>
  </form>
  <?php if (Auth::estAdmin() || Auth::estChefProjet()): ?>
    <a href="<?= SITE_URL ?>/taches/ajout.php<?= $projetId ? '?projet_id='.$projetId : '' ?>" class="btn btn-primary">Ajouter une tâche</a>
  <?php endif; ?>
</div>

<div id="search-result-area">
<div class="table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>Tâche</th>
        <th>Projet</th>
        <th>Assigné à</th>
        <th>Date échéance</th>
        <th>Statut</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($taches as $t): ?>
        <tr>
          <td><?= htmlspecialchars($t['titre']) ?></td>
          <td><?= htmlspecialchars($t['projet_nom']) ?></td>
          <td><?= $t['assignee_id'] ? htmlspecialchars($t['assignee_prenom'] . ' ' . $t['assignee_nom']) : '-' ?></td>
          <td><?= formatDateFr($t['date_echeance']) ?></td>
          <td>
            <?php
            $estAssignee = (int)($t['assignee_id'] ?? 0) === (int)$user['id'];
            $peutMajStatut = $estAssignee && $t['statut'] !== 'termine' && $t['statut'] !== 'annule';
            if ($peutMajStatut): ?>
              <form method="post" action="<?= SITE_URL ?>/taches/update_statut.php" style="display:inline-flex; align-items:center; gap:6px; flex-wrap:nowrap;">
                <input type="hidden" name="tache_id" value="<?= (int)$t['id'] ?>">
                <select name="statut" class="form-control" style="width:auto; min-width:100px; padding:4px 8px;">
                  <option value="a_faire" <?= $t['statut'] === 'a_faire' ? 'selected' : '' ?>>À faire</option>
                  <option value="en_cours" <?= $t['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                </select>
                <a href="<?= SITE_URL ?>/taches/terminer.php?id=<?= (int)$t['id'] ?>" class="btn btn-secondary btn-sm">Terminer</a>
              </form>
            <?php else: ?>
              <span class="badge badge-<?= $t['statut'] === 'termine' ? 'termine' : ($t['statut'] === 'en_cours' ? 'en-cours' : 'en-attente') ?>"><?= statutTacheLabel($t['statut']) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <a href="<?= SITE_URL ?>/taches/voir.php?id=<?= (int)$t['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Voir"><i class="fas fa-eye"></i></a>
            <?php
            $estAssigneeTache = (int)($t['assignee_id'] ?? 0) === (int)$user['id'];
            $peutModifier = Auth::estAdmin() || (Auth::estChefProjet() && Auth::peutVoirProjet($t['projet_id'])) || (Auth::estMembre() && $estAssigneeTache);
            $peutSupprimer = Auth::estAdmin() || (Auth::estChefProjet() && Auth::peutVoirProjet($t['projet_id']));
            if ($peutModifier):
            ?>
              <?php if (!empty($t['preuve_image'])): ?>
                <a href="<?= SITE_URL ?>/taches/voir.php?id=<?= (int)$t['id'] ?>#preuve" class="btn btn-secondary btn-sm btn-icon" title="Voir la preuve soumise par le membre"><i class="fas fa-image"></i></a>
              <?php endif; ?>
              <a href="<?= SITE_URL ?>/taches/editer.php?id=<?= (int)$t['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Modifier"><i class="fas fa-pencil-alt"></i></a>
            <?php endif; ?>
            <?php if ($peutSupprimer): ?>
              <a href="<?= SITE_URL ?>/taches/supprimer.php?id=<?= (int)$t['id'] ?>" class="btn btn-danger btn-sm btn-icon" title="Supprimer" onclick="return confirm('Supprimer cette tâche ?');"><i class="fas fa-trash-alt"></i></a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if (empty($taches)): ?>
  <p class="table-empty-msg" style="color:#718096;margin-top:16px;">Aucune tâche.</p>
<?php endif; ?>
</div>

<script>
(function() {
  var form = document.querySelector('.search-form-live');
  var input = form && form.querySelector('.search-input-live');
  if (!form || !input) return;
  var timeout;
  input.addEventListener('input', function() {
    clearTimeout(timeout);
    timeout = setTimeout(function() {
      var params = new URLSearchParams(new FormData(form));
      var url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
      fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function(r) { return r.text(); })
        .then(function(html) {
          var wrap = document.createElement('div');
          wrap.innerHTML = html;
          var newArea = wrap.querySelector('#search-result-area');
          var curArea = document.querySelector('#search-result-area');
          if (newArea && curArea) { curArea.innerHTML = newArea.innerHTML; }
          history.replaceState(null, '', url);
        });
    }, 400);
  });
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
