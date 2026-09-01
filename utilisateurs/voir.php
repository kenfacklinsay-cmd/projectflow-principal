<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::redirigerSiNonConnecte();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/utilisateurs/index.php'); exit; }

$user = Auth::getUser();
$db = Database::getInstance()->getConnection();

// Admin peut voir tout le monde; chef voit membres de son équipe + lui; membre voit que lui
$stmt = $db->prepare('SELECT u.*, r.libelle as role_libelle FROM utilisateurs u JOIN roles r ON u.role_id = r.id WHERE u.id = ?');
$stmt->execute([$id]);
$u = $stmt->fetch();
if (!$u) { header('Location: ' . SITE_URL . '/utilisateurs/index.php'); exit; }

if (!Auth::estAdmin()) {
    if (Auth::estChefProjet()) {
        $stmt = $db->prepare('SELECT 1 FROM equipes_projet ep JOIN projets p ON p.id = ep.projet_id WHERE ep.utilisateur_id = ? AND p.chef_projet_id = ?');
        $stmt->execute([$id, $user['id']]);
        if (!$stmt->fetch() && (int)$id !== (int)$user['id']) {
            header('Location: ' . SITE_URL . '/utilisateurs/index.php');
            exit;
        }
    } else {
        if ((int)$id !== (int)$user['id']) {
            header('Location: ' . SITE_URL . '/utilisateurs/index.php');
            exit;
        }
    }
}

// Projets du chef auxquels cet utilisateur est affecté (pour afficher et retirer)
$projetsUtilisateur = [];
if (Auth::estChefProjet() && (int)$u['role_id'] === ROLE_MEMBRE) {
    $stmt = $db->prepare('SELECT p.id, p.nom FROM projets p JOIN equipes_projet ep ON ep.projet_id = p.id AND ep.utilisateur_id = ? WHERE p.chef_projet_id = ? ORDER BY p.nom');
    $stmt->execute([$id, $user['id']]);
    $projetsUtilisateur = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$pageTitle = $u['prenom'] . ' ' . $u['nom'] . ' - GESPRO';
$currentPage = 'utilisateurs';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Gestion des Utilisateurs</h1>
<p class="breadcrumb"><a href="<?= SITE_URL ?>/dashboard.php">Accueil</a> / Utilisateurs / <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></p>

<div class="form-card">
  <div class="form-card-header">Informations utilisateur</div>
  <div class="form-card-body">
    <dl class="info-list">
      <dt>Nom</dt>
      <dd><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></dd>
      <dt>Email</dt>
      <dd><?= htmlspecialchars($u['email']) ?></dd>
      <?php if (!empty($u['telephone'])): ?>
      <dt>Téléphone</dt>
      <dd><?= htmlspecialchars($u['telephone']) ?></dd>
      <?php endif; ?>
      <dt>Rôle</dt>
      <dd><?= htmlspecialchars($u['role_libelle']) ?></dd>
      <dt>Actif</dt>
      <dd><?= $u['actif'] ? 'Oui' : 'Non' ?></dd>
      <?php if (Auth::estChefProjet() && (int)$u['role_id'] === ROLE_MEMBRE): ?>
      <dt>Affecté à mes projets</dt>
      <dd>
        <?php if (empty($projetsUtilisateur)): ?>
          Aucun
        <?php else: ?>
          <ul style="margin:0; padding-left:20px;">
            <?php foreach ($projetsUtilisateur as $pr): ?>
              <li><?= htmlspecialchars($pr['nom']) ?> — <a href="<?= SITE_URL ?>/projets/equipe.php?id=<?= (int)$pr['id'] ?>&retirer=<?= (int)$id ?>" onclick="return confirm('Retirer cet utilisateur du projet ?');">Retirer du projet</a></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </dd>
      <?php endif; ?>
    </dl>
    <div class="form-actions" style="margin-top:20px;">
      <?php if (Auth::estAdmin() || (Auth::estChefProjet() && (int)$u['role_id'] === ROLE_MEMBRE) || (int)$id === (int)$user['id']): ?>
        <a href="<?= SITE_URL ?>/utilisateurs/editer.php?id=<?= $id ?>" class="btn btn-primary">Modifier</a>
      <?php endif; ?>
      <a href="<?= SITE_URL ?>/utilisateurs/index.php" class="btn btn-secondary">Retour</a>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
