<?php
$pageTitle = 'Modifier utilisateur - PROJECTFLOW';
$currentPage = 'utilisateurs';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';

Auth::redirigerSiNonConnecte();
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: ' . SITE_URL . '/utilisateurs/index.php'); exit; }

$user = Auth::getUser();
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT * FROM utilisateurs WHERE id = ?');
$stmt->execute([$id]);
$u = $stmt->fetch();
if (!$u) { header('Location: ' . SITE_URL . '/utilisateurs/index.php'); exit; }

$peutModifier = Auth::estAdmin();
if (Auth::estChefProjet() && (int)$u['role_id'] === ROLE_MEMBRE) {
    $stmt = $db->prepare('SELECT 1 FROM equipes_projet ep JOIN projets p ON p.id = ep.projet_id WHERE ep.utilisateur_id = ? AND p.chef_projet_id = ?');
    $stmt->execute([$id, $user['id']]);
    $peutModifier = $peutModifier || $stmt->fetch();
}
if ((int)$id === (int)$user['id']) $peutModifier = true;

if (!$peutModifier) {
    header('Location: ' . SITE_URL . '/utilisateurs/index.php');
    exit;
}

$stmt = $db->query('SELECT id, libelle FROM roles ORDER BY id');
$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

$erreurs = [];
$email = trim($_POST['email'] ?? $u['email']);
$nom = trim($_POST['nom'] ?? $u['nom']);
$prenom = trim($_POST['prenom'] ?? $u['prenom']);
$telephone = trim($_POST['telephone'] ?? $u['telephone'] ?? '');
$role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : (int)$u['role_id'];
$mot_de_passe = $_POST['mot_de_passe'] ?? '';
$actif = isset($_POST['actif']) ? 1 : 0;

$estSonPropreProfil = (int)$id === (int)$user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$email || !$nom || !$prenom) $erreurs[] = 'Tous les champs obligatoires doivent être remplis.';
    // L'admin ne peut pas modifier le mot de passe d'un autre utilisateur
    if (!$estSonPropreProfil) $mot_de_passe = '';
    if ($mot_de_passe !== '' && strlen($mot_de_passe) < 6) $erreurs[] = 'Le mot de passe doit faire au moins 6 caractères.';
    $stmt = $db->prepare('SELECT id FROM utilisateurs WHERE email = ? AND id != ?');
    $stmt->execute([$email, $id]);
    if ($stmt->fetch()) $erreurs[] = 'Cet email est déjà utilisé.';
    if (Auth::estChefProjet() && (int)$u['role_id'] === ROLE_MEMBRE) {
        $role_id = ROLE_MEMBRE; // chef ne peut pas changer le rôle
    }
    if (empty($erreurs)) {
        if ($mot_de_passe !== '') {
            $stmt = $db->prepare('UPDATE utilisateurs SET email = ?, mot_de_passe = ?, nom = ?, prenom = ?, telephone = ?, role_id = ?, actif = ? WHERE id = ?');
            $stmt->execute([$email, $mot_de_passe, $nom, $prenom, $telephone ?: null, $role_id, $actif, $id]);
        } else {
            $stmt = $db->prepare('UPDATE utilisateurs SET email = ?, nom = ?, prenom = ?, telephone = ?, role_id = ?, actif = ? WHERE id = ?');
            $stmt->execute([$email, $nom, $prenom, $telephone ?: null, $role_id, $actif, $id]);
        }
        header('Location: ' . SITE_URL . '/utilisateurs/voir.php?id=' . $id . '&msg=modifie');
        exit;
    }
}
$actif = isset($_POST['actif']) ? 1 : (int)$u['actif'];
?>

<h1 class="page-title">Gestion des Utilisateurs</h1>
<p class="breadcrumb"><a href="<?= SITE_URL ?>/dashboard.php">Accueil</a> / Utilisateurs / Modifier</p>

<?php foreach ($erreurs as $e): ?>
  <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<div class="form-card">
  <div class="form-card-header">Modification</div>
  <div class="form-card-body">
    <form method="post" action="">
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($email) ?>" required>
      </div>
      <?php if ($estSonPropreProfil): ?>
      <div class="form-group">
        <label>Nouveau mot de passe (laisser vide pour ne pas changer)</label>
        <input type="password" name="mot_de_passe" class="form-control">
      </div>
      <?php endif; ?>
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
      <?php if (Auth::estAdmin()): ?>
      <div class="form-group">
        <label>Rôle</label>
        <select name="role_id" class="form-control">
          <?php foreach ($roles as $r): ?>
            <option value="<?= (int)$r['id'] ?>" <?= $role_id === (int)$r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['libelle']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php endif; ?>
      <div class="form-group">
        <label><input type="checkbox" name="actif" value="1" <?= $actif ? 'checked' : '' ?>> Actif</label>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= SITE_URL ?>/utilisateurs/voir.php?id=<?= $id ?>" class="btn btn-secondary">Annuler</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
