<?php
$pageTitle = 'Gestion des Utilisateurs - PROJECTFLOW';
$currentPage = 'utilisateurs';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';

Auth::redirigerSiNonConnecte();
$user = Auth::getUser();
$db = Database::getInstance()->getConnection();

// Chef : ajouter un utilisateur à un de ses projets (formulaire dans la gestion utilisateurs)
if (Auth::estChefProjet() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_au_projet'])) {
    $projetId = (int)($_POST['projet_id'] ?? 0);
    $uid = (int)($_POST['utilisateur_id'] ?? 0);
    if ($projetId && $uid && Auth::peutGererEquipe($projetId)) {
        $stmt = $db->prepare('SELECT id FROM utilisateurs WHERE id = ? AND role_id = ? AND actif = 1');
        $stmt->execute([$uid, ROLE_MEMBRE]);
        if ($stmt->fetch()) {
            $stmt = $db->prepare('SELECT nom FROM projets WHERE id = ?');
            $stmt->execute([$projetId]);
            $projetNom = $stmt->fetchColumn() ?: 'Projet';
            try {
                $db->prepare('DELETE FROM equipes_projet WHERE utilisateur_id = ?')->execute([$uid]);
                $db->prepare('INSERT INTO equipes_projet (projet_id, utilisateur_id) VALUES (?, ?)')->execute([$projetId, $uid]);
                creerNotification($db, $uid, 'Ajout à un projet', 'Vous avez été ajouté au projet « ' . $projetNom . ' ».', SITE_URL . '/projets/voir.php?id=' . $projetId, 'info');
            } catch (PDOException $e) {}
        }
        header('Location: ' . SITE_URL . '/utilisateurs/index.php?msg=ajoute_au_projet&projet_id=' . $projetId);
        exit;
    }
}

// Admin: tous les utilisateurs. Chef: seulement les membres de ses équipes (+ lui-même). Membre: seulement lui / liste limitée
if (Auth::estAdmin()) {
    $stmt = $db->query('SELECT u.*, r.libelle as role_libelle FROM utilisateurs u JOIN roles r ON u.role_id = r.id ORDER BY u.nom, u.prenom');
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif (Auth::estChefProjet()) {
    // Chefs voient les membres affectés à leurs projets + autres chefs ? On dit: membres des équipes de ses projets
    $stmt = $db->prepare('SELECT DISTINCT u.id, u.email, u.nom, u.prenom, u.telephone, u.role_id, u.actif, r.libelle as role_libelle FROM utilisateurs u JOIN roles r ON u.role_id = r.id JOIN equipes_projet ep ON ep.utilisateur_id = u.id JOIN projets p ON p.id = ep.projet_id AND p.chef_projet_id = ? ORDER BY u.nom, u.prenom');
    $stmt->execute([$user['id']]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Ajouter le chef lui-même
    $stmt = $db->prepare('SELECT u.id, u.email, u.nom, u.prenom, u.telephone, u.role_id, u.actif, r.libelle as role_libelle FROM utilisateurs u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
    $stmt->execute([$user['id']]);
    $me = $stmt->fetch();
    $ids = array_column($users, 'id');
    if ($me && !in_array($me['id'], $ids)) $users[] = $me;
} else {
    // Membre: ne peut que voir sa fiche
    $users = [$user];
}
$users = array_values($users);

// Pour le chef : ses projets et les utilisateurs disponibles (membres) pour le formulaire "Ajouter au projet"
$projetsChef = [];
$utilisateursDisponiblesPourProjet = [];
if (Auth::estChefProjet()) {
    $stmt = $db->prepare('SELECT id, nom FROM projets WHERE chef_projet_id = ? ORDER BY nom');
    $stmt->execute([$user['id']]);
    $projetsChef = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $projetIdForm = (int)($_GET['projet_id'] ?? $_POST['projet_id'] ?? 0);
    if ($projetIdForm && Auth::peutGererEquipe($projetIdForm)) {
        $stmt = $db->prepare('SELECT u.id, u.nom, u.prenom FROM utilisateurs u WHERE u.role_id = ? AND u.actif = 1 AND u.id NOT IN (SELECT utilisateur_id FROM equipes_projet) ORDER BY u.nom, u.prenom');
        $stmt->execute([ROLE_MEMBRE]);
        $utilisateursDisponiblesPourProjet = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$recherche = trim($_GET['q'] ?? '');
if ($recherche !== '') {
    $users = array_filter($users, function ($u) use ($recherche) {
        return stripos($u['nom'], $recherche) !== false || stripos($u['prenom'], $recherche) !== false || stripos($u['email'], $recherche) !== false;
    });
}
?>

<h1 class="page-title">Gestion des Utilisateurs</h1>
<p class="breadcrumb"><a href="<?= SITE_URL ?>/dashboard.php">Accueil</a> / Utilisateurs / Liste</p>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'ajoute_au_projet'): ?>
  <div class="alert alert-success">Utilisateur ajouté au projet.</div>
<?php endif; ?>

<?php $showFormAjouter = (Auth::estChefProjet() && !empty($projetsChef)) && !empty($_GET['projet_id']); ?>

<div class="toolbar">
  <form method="get" action="" class="search-form-live" style="display:flex;gap:8px;flex:1;">
    <input type="text" name="q" class="search-input search-input-live" placeholder="Rechercher un utilisateur..." value="<?= htmlspecialchars($recherche) ?>">
    <button type="submit" class="btn btn-secondary">Rechercher</button>
  </form>
  <?php if (Auth::estAdmin()): ?>
    <a href="<?= SITE_URL ?>/utilisateurs/ajout.php" class="btn btn-primary">Ajouter un utilisateur</a>
  <?php elseif (Auth::estChefProjet() && !empty($projetsChef)): ?>
    <button type="button" class="btn btn-primary" id="btn-ajouter-membre" onclick="var f=document.getElementById('form-ajouter-membre'); var t=document.getElementById('tableau-utilisateurs'); if(f&&t){ f.style.display=f.style.display==='none'?'block':'none'; t.style.display=f.style.display==='block'?'none':'block'; }">
      Ajouter un membre
    </button>
  <?php endif; ?>
</div>

<?php if (Auth::estChefProjet() && !empty($projetsChef)): ?>
<div class="form-card" id="form-ajouter-membre" style="margin-bottom:24px; display:<?= $showFormAjouter ? 'block' : 'none' ?>;">
  <div class="form-card-header">Ajouter un membre à un projet</div>
  <div class="form-card-body">
    <form method="get" action="" style="margin-bottom:20px;">
      <input type="hidden" name="q" value="<?= htmlspecialchars($recherche ?? '') ?>">
      <div class="form-group">
        <label for="projet_id">Projet</label>
        <select name="projet_id" id="projet_id" class="form-control" style="max-width:320px;" onchange="this.form.submit()">
          <option value="">— Choisir un projet —</option>
          <?php $pid = (int)($_GET['projet_id'] ?? 0); foreach ($projetsChef as $pr): ?>
            <option value="<?= (int)$pr['id'] ?>" <?= $pid === (int)$pr['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pr['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>

    <?php if (!empty($_GET['projet_id']) && Auth::peutGererEquipe((int)$_GET['projet_id'])): ?>
    <?php if (empty($utilisateursDisponiblesPourProjet)): ?>
      <p style="color:#718096;">Aucun membre disponible. Tous les membres sont déjà affectés à une équipe de projet.</p>
    <?php else: ?>
    <form method="post" action="">
      <input type="hidden" name="projet_id" value="<?= (int)$_GET['projet_id'] ?>">
      <div class="form-group" style="margin-bottom:16px;">
        <label for="utilisateur_id">Choisir le membre</label>
        <select name="utilisateur_id" id="utilisateur_id" class="form-control" style="max-width:320px;" required>
          <option value="">— Choisir un membre —</option>
          <?php foreach ($utilisateursDisponiblesPourProjet as $d): ?>
            <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['prenom'] . ' ' . $d['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" name="ajouter_au_projet" class="btn btn-primary">Ajouter</button>
    </form>
    <?php endif; ?>
    <?php else: ?>
    <p style="color:#718096;">Choisissez d’abord un projet ci-dessus.</p>
    <?php endif; ?>
  </div>
</div>
<?php endif; ?>

<div id="search-result-area">
<?php $cacheTableauFormOuvert = Auth::estChefProjet() && !empty($projetsChef) && $showFormAjouter; ?>
<div id="tableau-utilisateurs" class="table-container" style="<?= $cacheTableauFormOuvert ? 'display:none;' : '' ?>">
  <table class="data-table">
    <thead>
      <tr>
        <th>Nom</th>
        <th>Email</th>
        <th>Téléphone</th>
        <th>Rôle</th>
        <th>Actif</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($users as $u): ?>
        <tr>
          <td><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></td>
          <td><?= htmlspecialchars($u['email']) ?></td>
          <td><?= htmlspecialchars($u['telephone'] ?? '') ?></td>
          <td><?= htmlspecialchars($u['role_libelle']) ?></td>
          <td><?= $u['actif'] ? 'Oui' : 'Non' ?></td>
          <td>
            <a href="<?= SITE_URL ?>/utilisateurs/voir.php?id=<?= (int)$u['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Voir"><i class="fas fa-eye"></i></a>
            <?php 
            $peutModifier = Auth::estAdmin() || (Auth::estChefProjet() && (int)$u['id'] !== (int)Auth::getUser()['id'] && (int)$u['role_id'] === ROLE_MEMBRE);
            if ($peutModifier): 
            ?>
              <a href="<?= SITE_URL ?>/utilisateurs/editer.php?id=<?= (int)$u['id'] ?>" class="btn btn-secondary btn-sm btn-icon" title="Modifier"><i class="fas fa-pencil-alt"></i></a>
            <?php endif; ?>
            <?php if (Auth::estAdmin() && (int)$u['id'] !== (int)$user['id']): ?>
              <a href="<?= SITE_URL ?>/utilisateurs/supprimer.php?id=<?= (int)$u['id'] ?>" class="btn btn-danger btn-sm btn-icon" title="Supprimer" onclick="return confirm('Supprimer cet utilisateur ?');"><i class="fas fa-trash-alt"></i></a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
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
