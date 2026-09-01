<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Auth.php';
require_once __DIR__ . '/../includes/helpers.php';

Auth::redirigerSiNonConnecte();
$user = Auth::getUser();
$currentPage = $currentPage ?? '';
$headerPhotoUrl = photoProfilUrl($user);
$headerNotificationCount = 0;
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE utilisateur_id = ? AND lue = 0');
    $stmt->execute([$user['id']]);
    $headerNotificationCount = (int) $stmt->fetchColumn();
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'PROJECTFLOW'); ?></title>
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/fontawesome/css/all.min.css">
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <div class="sidebar-brand"><?= SITE_NAME ?></div>
      <nav class="sidebar-nav">
        <a href="<?= SITE_URL ?>/dashboard.php" class="<?= $currentPage === 'dashboard' ? 'active' : '' ?>"><i class="fas fa-chart-line"></i> <span>Tableau de bord</span></a>
        <div class="sep"></div>
        <a href="<?= SITE_URL ?>/projets/index.php" class="<?= $currentPage === 'projets' ? 'active' : '' ?>"><i class="fas fa-folder-open"></i> <span>Gestion des projets</span></a>
        <a href="<?= SITE_URL ?>/taches/index.php" class="<?= $currentPage === 'taches' ? 'active' : '' ?>"><i class="fas fa-tasks"></i> <span>Gestion des tâches</span></a>
        <?php if (Auth::estAdmin() || Auth::estChefProjet()): ?>
          <a href="<?= SITE_URL ?>/utilisateurs/index.php" class="<?= $currentPage === 'utilisateurs' ? 'active' : '' ?>"><i class="fas fa-users"></i> <span>Gestion des utilisateurs</span></a>
        <?php endif; ?>
        <div class="sep"></div>
        <a href="<?= SITE_URL ?>/notifications.php" class="<?= $currentPage === 'notifications' ? 'active' : '' ?>"><i class="fas fa-bell"></i> <span>Notifications</span><?php if ($headerNotificationCount > 0): ?><span class="sidebar-badge"><?= $headerNotificationCount > 99 ? '99+' : $headerNotificationCount ?></span><?php endif; ?></a>
        <a href="<?= SITE_URL ?>/messagerie.php" class="<?= $currentPage === 'messagerie' ? 'active' : '' ?>"><i class="fas fa-envelope"></i> <span>Messagerie</span></a>
        <a href="<?= SITE_URL ?>/profil.php" class="<?= $currentPage === 'profil' ? 'active' : '' ?>"><i class="fas fa-user-circle"></i> <span>Mon profil</span></a>
      </nav>
    </aside>
    <main class="main-content">
      <header class="top-header top-header-dashboard">
        <div class="top-header-actions">
          <button class="mobile-menu-toggle" type="button" aria-label="Ouvrir le menu"><i class="fas fa-bars"></i></button>
          <a href="<?= SITE_URL ?>/notifications.php" class="top-header-icon" title="Notifications"><i class="fas fa-bell"></i><?php if ($headerNotificationCount > 0): ?><span class="top-header-notif-badge"><?= $headerNotificationCount > 99 ? '99+' : $headerNotificationCount ?></span><?php endif; ?></a>
          <a href="<?= SITE_URL ?>/messagerie.php" class="top-header-icon" title="Messagerie"><i class="fas fa-envelope"></i></a>
          <a href="<?= SITE_URL ?>/profil.php" class="top-header-icon" title="Paramètres"><i class="fas fa-cog"></i></a>
          <div class="top-header-user">
            <span class="top-header-name"><?= htmlspecialchars(strtoupper($user['prenom'] . ' ' . $user['nom'])) ?></span>
            <i class="fas fa-chevron-down top-header-chevron"></i>
            <div class="top-header-dropdown">
              <a href="<?= SITE_URL ?>/profil.php">Mon profil</a>
              <a href="<?= SITE_URL ?>/logout.php">Déconnexion</a>
            </div>
          </div>
          <a href="<?= SITE_URL ?>/profil.php" class="top-header-avatar-wrap" title="Mon profil">
            <?php if ($headerPhotoUrl): ?>
              <img src="<?= htmlspecialchars($headerPhotoUrl) ?>" alt="" class="top-header-avatar">
            <?php else: ?>
              <div class="top-header-avatar top-header-avatar-default"><i class="fas fa-user"></i></div>
            <?php endif; ?>
          </a>
        </div>
      </header>
      <div class="page-content<?= ($currentPage ?? '') === 'messagerie' ? ' page-content-chat' : '' ?><?= !empty($pageContentExtraClass) ? ' ' . htmlspecialchars($pageContentExtraClass, ENT_QUOTES, 'UTF-8') : '' ?>">
        <?php if (($currentPage ?? '') !== 'messagerie' && ($currentPage ?? '') !== 'dashboard'): ?>
        <p class="welcome-msg">Bienvenue, <strong><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></strong> !</p>
        <?php endif; ?>
