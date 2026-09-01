<?php
$pageTitle = 'Mon profil - PROJECTFLOW';
$currentPage = 'profil';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/header.php';

Auth::redirigerSiNonConnecte();
$user = Auth::getUser();
?>
<div class="profil-page-wrap">
  <h1 class="page-title">Mon profil</h1>
  <p class="breadcrumb"><a href="<?= SITE_URL ?>/dashboard.php">Accueil</a> / Mon profil</p>

  <?php if (isset($_GET['erreur'])): ?>
  <div class="alert alert-error">
    <?php
    switch ($_GET['erreur']) {
        case 'choisir': echo 'Veuillez sélectionner une image avant d\'enregistrer.'; break;
        case 'photo': echo 'Format d\'image non accepté. Utilisez JPEG, PNG, GIF ou WebP.'; break;
        case 'upload': echo 'Erreur lors de l\'envoi (dossier ou droits insuffisants). Vérifiez que le dossier uploads/profiles existe et est accessible en écriture.'; break;
        case 'db': echo 'La base de données ne permet pas d\'enregistrer la photo (vérifiez que la colonne « photo » existe dans la table utilisateurs).'; break;
        default: echo 'Une erreur s\'est produite. Réessayez.';
    }
    ?>
  </div>
  <?php endif; ?>
  <?php if (isset($_GET['msg']) && $_GET['msg'] === 'photo'): ?>
  <div class="alert alert-success">Photo de profil mise à jour.</div>
  <?php endif; ?>
  <div class="dashboard-profil-card">
    <div class="profil-photo-block">
      <?php $photoUrl = photoProfilUrl($user); ?>
      <div class="profil-avatar-wrap">
        <?php if ($photoUrl): ?>
          <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Photo" class="profil-avatar-img">
        <?php else: ?>
          <div class="profil-avatar-default"><i class="fas fa-user"></i></div>
        <?php endif; ?>
      </div>

      <form method="post" action="<?= SITE_URL ?>/upload_photo.php" enctype="multipart/form-data" class="profil-photo-form" id="form-photo">
        <input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp" id="photo-input" required style="display:none;">
        <label for="photo-input" class="btn btn-primary btn-sm">Choisir une photo</label>
        <p class="profil-hint">Cliquez sur « Choisir une photo », sélectionnez une image (JPEG , JPG, PNG, GIF ou WebP). Elle sera enregistrée automatiquement.</p>
      </form>
    </div>
    <div class="profil-info">
      <p><strong><?= htmlspecialchars(strtoupper($user['prenom'] . ' ' . $user['nom'])) ?></strong></p>
      <p><?= htmlspecialchars($user['email']) ?></p>
      <p><?= htmlspecialchars($user['role_libelle']) ?></p>
      <a href="<?= SITE_URL ?>/utilisateurs/editer.php?id=<?= (int)$user['id'] ?>" class="btn btn-primary">Modifier mon profil</a>
    </div>
  </div>
</div>

<script>
document.getElementById('photo-input').addEventListener('change', function() {
  if (this.files && this.files.length) document.getElementById('form-photo').submit();
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
