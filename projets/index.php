<?php
$pageTitle = 'Gestion des Projets - GESPRO';
$currentPage = 'projets';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';

Auth::redirigerSiNonConnecte();
$user = Auth::getUser();
$db = Database::getInstance()->getConnection();

if (Auth::estAdmin()) {
    $stmt = $db->query('SELECT p.*, u.prenom as chef_prenom, u.nom as chef_nom, v.progression_pct FROM projets p LEFT JOIN utilisateurs u ON p.chef_projet_id = u.id LEFT JOIN v_progression_projets v ON v.id = p.id ORDER BY p.nom');
} elseif (Auth::estChefProjet()) {
    $stmt = $db->prepare('SELECT p.*, u.prenom as chef_prenom, u.nom as chef_nom, v.progression_pct FROM projets p LEFT JOIN utilisateurs u ON p.chef_projet_id = u.id LEFT JOIN v_progression_projets v ON v.id = p.id WHERE p.chef_projet_id = ? ORDER BY p.nom');
    $stmt->execute([$user['id']]);
} else {
    $stmt = $db->prepare('SELECT p.*, u.prenom as chef_prenom, u.nom as chef_nom, v.progression_pct FROM projets p JOIN taches t ON t.projet_id = p.id AND t.assignee_id = ? LEFT JOIN utilisateurs u ON p.chef_projet_id = u.id LEFT JOIN v_progression_projets v ON v.id = p.id GROUP BY p.id ORDER BY p.nom');
    $stmt->execute([$user['id']]);
}
$projets = $stmt->fetchAll(PDO::FETCH_ASSOC);

$recherche = trim($_GET['q'] ?? '');
if ($recherche !== '') {
    $projets = array_filter($projets, function ($p) use ($recherche) {
        return stripos($p['nom'], $recherche) !== false || stripos($p['chef_prenom'] . ' ' . $p['chef_nom'], $recherche) !== false;
    });
}

$parPage = 5;
$page = max(1, (int)($_GET['page'] ?? 1));
$total = count($projets);
$projets = array_slice($projets, ($page - 1) * $parPage, $parPage);
$nbPages = $total ? (int)ceil($total / $parPage) : 1;
?>

<h1 class="page-title">Gestion des Projets</h1>
<p class="breadcrumb"><a href="<?= SITE_URL ?>/dashboard.php">Accueil</a> / Projets / Liste des projets</p>

<?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-success">
    <?php
    if ($_GET['msg'] === 'ajoute') echo 'Projet ajouté.';
    elseif ($_GET['msg'] === 'modifie') echo 'Projet modifié.';
    elseif ($_GET['msg'] === 'supprime') echo 'Projet supprimé.';
    ?>
  </div>
<?php endif; ?>

<div class="toolbar">
  <form method="get" action="" class="search-form-live" style="display:flex;gap:8px;flex:1;">
    <input type="text" name="q" class="search-input search-input-live" placeholder="Rechercher un projet..." value="<?= htmlspecialchars($recherche) ?>">
    <button type="submit" class="btn btn-secondary">Rechercher</button>
  </form>
  <?php if (Auth::estAdmin() || Auth::estChefProjet()): ?>
    <a href="<?= SITE_URL ?>/projets/ajout.php" class="btn btn-primary">Ajouter un projet</a>
  <?php endif; ?>
</div>

<div id="search-result-area">
<div class="table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>Projet</th>
        <th>Chef de projet</th>
        <th>Date de création</th>
        <th>Progression</th>
        <th>Statut</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($projets as $p):
        $pct = (int)($p['progression_pct'] ?? 0);
      ?>
        <tr>
          <td><?= htmlspecialchars($p['nom']) ?></td>
          <td><?= htmlspecialchars(($p['chef_prenom'] ?? '') . ' ' . ($p['chef_nom'] ?? '')) ?></td>
          <td><?= formatDateFr($p['date_creation']) ?></td>
          <td><?= $pct ?>%</td>
          <td><?= $p['statut'] === 'en_cours' ? ($pct >= 50 ? '<span class="badge badge-en-cours">En cours</span>' : '<span class="badge badge-orange">En cours</span>') : badgeStatutProjet($p['statut']) ?></td>
          <td>
            <a href="<?= SITE_URL ?>/projets/voir.php?id=<?= (int)$p['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Voir"><i class="fas fa-eye"></i></a>
            <?php if (Auth::estAdmin() || (Auth::estChefProjet() && (int)$p['chef_projet_id'] === (int)$user['id'])): ?>
              <a href="<?= SITE_URL ?>/projets/editer.php?id=<?= (int)$p['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Modifier"><i class="fas fa-pencil-alt"></i></a>
              <a href="<?= SITE_URL ?>/projets/supprimer.php?id=<?= (int)$p['id'] ?>" class="btn btn-danger btn-sm btn-icon" title="Supprimer" onclick="return confirm('Supprimer ce projet ?');"><i class="fas fa-trash-alt"></i></a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if ($nbPages > 1): ?>
<div class="pagination">
  <?php for ($i = 1; $i <= $nbPages; $i++): ?>
    <?php $params = array_merge($_GET, ['page' => $i]); ?>
    <a href="?<?= http_build_query($params) ?>" class="<?= $i === $page ? 'current' : '' ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
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
