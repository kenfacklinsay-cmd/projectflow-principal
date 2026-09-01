<?php
$pageTitle = 'Ajout utilisateur - PROJECTFLOW';
$currentPage = 'utilisateurs';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/header.php';

Auth::redirigerSiNonConnecte();
if (!Auth::estAdmin()) {
    header('Location: ' . SITE_URL . '/utilisateurs/index.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->query('SELECT id, libelle FROM roles ORDER BY id');
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$erreurs = [];
$email = '';
$nom = '';
$prenom = '';
$telephone = '';
$role_id = 0;
$mot_de_passe = '';
$actif = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $role_id = (int)($_POST['role_id'] ?? 0);
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    $actif = isset($_POST['actif']) ? 1 : 0;

    if (!$email || !$nom || !$prenom) $erreurs[] = 'Tous les champs obligatoires doivent être remplis.';
    if ($mot_de_passe !== '' && strlen($mot_de_passe) < 6) $erreurs[] = 'Le mot de passe doit faire au moins 6 caractères.';
    if ($mot_de_passe === '') $erreurs[] = 'Indiquez un mot de passe.';
    $stmt = $db->prepare('SELECT id FROM utilisateurs WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) $erreurs[] = 'Cet email est déjà utilisé.';
    if (empty($erreurs)) {
        $stmt = $db->prepare('INSERT INTO utilisateurs (email, mot_de_passe, nom, prenom, telephone, role_id, actif) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$email, $mot_de_passe, $nom, $prenom, $telephone ?: null, $role_id, $actif]);
        header('Location: ' . SITE_URL . '/utilisateurs/index.php?msg=ajoute');
        exit;
    }
}
require_once __DIR__ . '/../includes/helpers.php';
?>

<h1 class="page-title">Gestion des Utilisateurs</h1>
<p class="breadcrumb"><a href="<?= SITE_URL ?>/dashboard.php">Accueil</a> / Utilisateurs / Ajout</p>

<?php foreach ($erreurs as $e): ?>
  <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<div class="form-card">
  <div class="form-card-header">Formulaire d'ajout</div>
  <div class="form-card-body">
    <form method="post" action="" autocomplete="off">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required autocomplete="new-email">
      </div>
      <div class="form-group">
        <label>Mot de passe</label>
        <input type="password" name="mot_de_passe" class="form-control" required autocomplete="new-password">
      </div>
      <div class="form-group">
        <label>Nom</label>
        <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($nom) ?>" required>
      </div>
      <div class="form-group">
        <label>Prénom</label>
        <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($prenom) ?>" required>
      </div>
      <div class="form-group">
        <label>Téléphone</label>
        <input type="text" name="telephone" class="form-control" value="<?= htmlspecialchars($telephone) ?>" placeholder="Ex. 06 12 34 56 78">
      </div>
      <div class="form-group">
        <label>Rôle</label>
        <select name="role_id" class="form-control">
          <?php foreach ($roles as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $role_id === (int)$r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['libelle']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label><input type="checkbox" name="actif" value="1" <?= $actif ? 'checked' : '' ?>> Actif</label>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= SITE_URL ?>/utilisateurs/index.php" class="btn btn-secondary">Annuler</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
