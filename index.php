<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';

if (Auth::estConnecte()) {
    header('Location: ' . SITE_URL . '/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>PROJECTFLOW - Gestionnaire de Projets Simplifié</title>
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
</head>
<body class="homepage">
  <div class="landing-page">
    <header class="landing-header">
      <a href="<?= SITE_URL ?>/index.php" class="landing-brand">
        <img src="<?= SITE_URL ?>/assets/img/logo1.png" alt="GESPRO" class="landing-logo">
        <div class="landing-brand-text">
          <span class="landing-brand-name">PROJECTFLOW</span>
          <span class="landing-brand-tagline">Gestionnaire de Projets Simplifié</span>
        </div>
      </a>
      <nav class="landing-nav">
        <a href="<?= SITE_URL ?>/index.php" class="landing-nav-link active">Accueil</a>
        <a href="<?= SITE_URL ?>/connexion.php" class="landing-nav-link landing-nav-cta">Connexion</a>
      </nav>
    </header>

    <main class="landing-hero">
      <h1 class="landing-headline">Organisez, planifiez et suivez vos projets</h1>
      <p class="landing-subheadline">Attribuez des tâches, définissez les priorités, suivez l'avancement et gérez tout depuis une seule plateforme.</p>
      <a href="<?= SITE_URL ?>/connexion.php" class="landing-btn-cta">
        Commencer
        <span class="landing-btn-arrow">→</span>
      </a>
      <p class="landing-hint">Gratuit pour toujours. Aucune carte requise.</p>
    </main>
  </div>
</body>
</html>
