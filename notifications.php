<?php
$pageTitle = 'Notifications - PROJECTFLOW';
$currentPage = 'notifications';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/header.php';

Auth::redirigerSiNonConnecte();
$user = Auth::getUser();
$db = Database::getInstance()->getConnection();

// Marquer comme lues si demandé
if (isset($_GET['lire']) && (int)$_GET['lire'] > 0) {
    $db->prepare('UPDATE notifications SET lue = 1 WHERE id = ? AND utilisateur_id = ?')->execute([(int)$_GET['lire'], $user['id']]);
    header('Location: ' . SITE_URL . '/notifications.php');
    exit;
}
if (isset($_GET['toutes_lues'])) {
    $db->prepare('UPDATE notifications SET lue = 1 WHERE utilisateur_id = ?')->execute([$user['id']]);
    header('Location: ' . SITE_URL . '/notifications.php');
    exit;
}

$stmt = $db->prepare('SELECT * FROM notifications WHERE utilisateur_id = ? ORDER BY date_creation DESC LIMIT 100');
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
require_once __DIR__ . '/includes/helpers.php';
?>

<h1 class="page-title">Notifications</h1>
<p class="breadcrumb"><a href="<?= SITE_URL ?>/dashboard.php">Accueil</a> / Notifications</p>

<p style="margin-bottom:16px;">
  <a href="?toutes_lues=1" class="btn btn-secondary btn-sm">Tout marquer comme lu</a>
</p>

<div class="table-container">
  <table class="data-table">
    <thead>
      <tr>
        <th>Date</th>
        <th>Titre</th>
        <th>Message</th>
        <th>Statut</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($notifications as $n): ?>
        <tr style="<?= !$n['lue'] ? 'background:#f0f9ff;' : '' ?>">
          <td><?= date('d/m/Y H:i', strtotime($n['date_creation'])) ?></td>
          <td><?= htmlspecialchars($n['titre']) ?></td>
          <td><?= htmlspecialchars($n['message']) ?></td>
          <td><?= $n['lue'] ? 'Lu' : 'Non lu' ?></td>
          <td>
            <?php if (!$n['lue']): ?>
              <a href="?lire=<?= (int)$n['id'] ?>" class="btn btn-secondary btn-sm">Marquer lu</a>
            <?php endif; ?>
            <?php if ($n['lien']): ?>
              <a href="<?= htmlspecialchars($n['lien']) ?>" class="btn btn-primary btn-sm">Voir</a>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php if (empty($notifications)): ?>
  <p style="color:#718096;">Aucune notification.</p>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
