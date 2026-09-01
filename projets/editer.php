<?php
$pageTitle = 'Modifier le projet - GESPRO';
$currentPage = 'projets';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';

Auth::redirigerSiNonConnecte();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/projets/index.php'); exit; }

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT * FROM projets WHERE id = ?');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p || !(Auth::estAdmin() || (Auth::estChefProjet() && (int)$p['chef_projet_id'] === (int)Auth::getUser()['id']))) {
    header('Location: ' . SITE_URL . '/projets/index.php');
    exit;
}

$erreurs = [];
$nom = trim($_POST['nom'] ?? $p['nom']);
$description = trim($_POST['description'] ?? $p['description']);
$date_creation = trim($_POST['date_creation'] ?? ($p['date_creation'] ? date('Y-m-d', strtotime($p['date_creation'])) : ''));
$statut = $_POST['statut'] ?? $p['statut'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($nom === '') $erreurs[] = 'Le nom du projet est requis.';
    if ($date_creation !== '') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_creation)) {
            $d = date_create_from_format('d/m/Y', $date_creation);
            if ($d) $date_creation = $d->format('Y-m-d');
            else $erreurs[] = 'Date invalide.';
        }
    } else $erreurs[] = 'La date de création est requise.';
    if (empty($erreurs)) {
        $chefId = Auth::estAdmin() && isset($_POST['chef_projet_id']) ? (int)$_POST['chef_projet_id'] : $p['chef_projet_id'];
        $ancienChefId = (int)$p['chef_projet_id'];
        $stmt = $db->prepare('UPDATE projets SET nom = ?, description = ?, date_creation = ?, statut = ?, chef_projet_id = ? WHERE id = ?');
        $stmt->execute([$nom, $description ?: null, $date_creation, $statut, $chefId, $id]);
        if ($chefId != $ancienChefId) {
            creerNotification(
                $db,
                $chefId,
                'Projet assigné',
                'Vous avez été désigné comme chef du projet « ' . $nom . ' ».',
                SITE_URL . '/projets/voir.php?id=' . $id,
                'info'
            );
        }
        header('Location: ' . SITE_URL . '/projets/voir.php?id=' . $id . '&msg=modifie');
        exit;
    }
}

$chefs = [];
if (Auth::estAdmin()) {
    $stmt = $db->query("SELECT id, nom, prenom FROM utilisateurs WHERE role_id = 2 AND actif = 1 ORDER BY nom, prenom");
    $chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<h1 class="page-title">Gestion des Projets</h1>
<p class="breadcrumb"><a href="<?= SITE_URL ?>/dashboard.php">Accueil</a> / Projets / Modifier</p>

<?php foreach ($erreurs as $e): ?>
  <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<div class="form-card">
  <div class="form-card-header">Modification du projet</div>
  <div class="form-card-body">
    <form method="post" action="">
      <div class="form-group">
        <label>Nom du projet</label>
        <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($nom) ?>" required>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control"><?= htmlspecialchars($description) ?></textarea>
      </div>
      <div class="form-group">
        <label>Date de création</label>
        <input type="date" name="date_creation" class="form-control" value="<?= htmlspecialchars($date_creation) ?>" required>
      </div>
      <?php if (Auth::estAdmin() && !empty($chefs)): ?>
      <div class="form-group">
        <label>Chef de projet</label>
        <select name="chef_projet_id" class="form-control">
          <?php foreach ($chefs as $c): ?>
            <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === (int)$p['chef_projet_id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="form-group">
        <label>Statut</label>
        <select name="statut" class="form-control">
          <option value="en_cours" <?= $statut === 'en_cours' ? 'selected' : '' ?>>En cours</option>
          <option value="en_attente" <?= $statut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
          <option value="termine" <?= $statut === 'termine' ? 'selected' : '' ?>>Terminé</option>
          <option value="annule" <?= $statut === 'annule' ? 'selected' : '' ?>>Annulé</option>
        </select>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= SITE_URL ?>/projets/voir.php?id=<?= $id ?>" class="btn btn-secondary">Annuler</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
