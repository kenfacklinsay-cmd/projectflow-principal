<?php
$pageTitle = 'Utilisateurs du projet - GESPRO';
$currentPage = 'projets';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';

Auth::redirigerSiNonConnecte();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/projets/index.php'); exit; }

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT p.*, u.prenom as chef_prenom, u.nom as chef_nom FROM projets p LEFT JOIN utilisateurs u ON p.chef_projet_id = u.id WHERE p.id = ?');
$stmt->execute([$id]);
$projet = $stmt->fetch();
if (!$projet || !Auth::peutGererEquipe($id)) {
    header('Location: ' . SITE_URL . '/projets/index.php');
    exit;
}

// Retirer un utilisateur du projet
if (isset($_GET['retirer']) && (int)$_GET['retirer'] > 0) {
    $uid = (int)$_GET['retirer'];
    $db->prepare('DELETE FROM equipes_projet WHERE projet_id = ? AND utilisateur_id = ?')->execute([$id, $uid]);
    header('Location: ' . SITE_URL . '/projets/equipe.php?id=' . $id . '&msg=retire');
    exit;
}

// Membres actuels du projet
$stmt = $db->prepare('SELECT u.id, u.nom, u.prenom, u.email FROM equipes_projet ep JOIN utilisateurs u ON u.id = ep.utilisateur_id WHERE ep.projet_id = ? ORDER BY u.nom, u.prenom');
$stmt->execute([$id]);
$membres = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<h1 class="page-title">Gestion des Projets</h1>
<p class="breadcrumb"><a href="<?= SITE_URL ?>/dashboard.php">Accueil</a> / <a href="<?= SITE_URL ?>/projets/index.php">Projets</a> / <?= htmlspecialchars($projet['nom']) ?> / Utilisateurs</p>

<?php if (isset($_GET['msg'])): ?>
  <div class="alert alert-success">
    <?= $_GET['msg'] === 'retire' ? 'Utilisateur retiré du projet.' : '' ?>
  </div>
<?php endif; ?>

<p style="margin-bottom:16px; color:#555;">Pour <strong>ajouter</strong> un utilisateur à ce projet, utilisez la page <a href="<?= SITE_URL ?>/utilisateurs/index.php?projet_id=<?= $id ?>">Gestion des utilisateurs</a>.</p>

<div class="table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>Nom</th>
        <th>Email</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($membres as $m): ?>
        <tr>
          <td><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></td>
          <td><?= htmlspecialchars($m['email']) ?></td>
          <td>
            <a href="?id=<?= $id ?>&retirer=<?= (int)$m['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Retirer cet utilisateur du projet ?');">Retirer</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if (empty($membres)): ?>
  <p style="color:#718096;">Aucun utilisateur affecté à ce projet.</p>
<?php endif; ?>

<p style="margin-top:16px;">
  <a href="<?= SITE_URL ?>/projets/voir.php?id=<?= $id ?>" class="btn btn-secondary">Retour au projet</a>
</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
