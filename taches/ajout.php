<?php
$pageTitle = 'Ajout d\'une tâche - GESPRO';
$currentPage = 'taches';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/header.php';

Auth::redirigerSiNonConnecte();
if (!Auth::estAdmin() && !Auth::estChefProjet()) {
    header('Location: ' . SITE_URL . '/taches/index.php');
    exit;
}

$user = Auth::getUser();
$db = Database::getInstance()->getConnection();

$projetId = isset($_GET['projet_id']) ? (int)$_GET['projet_id'] : null;
if ($projetId && !Auth::peutVoirProjet($projetId)) $projetId = null;

// Projets disponibles
if (Auth::estAdmin()) {
    $stmt = $db->query('SELECT id, nom FROM projets ORDER BY nom');
} else {
    $stmt = $db->prepare('SELECT id, nom FROM projets WHERE chef_projet_id = ? ORDER BY nom');
    $stmt->execute([$user['id']]);
}
$projets = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Membres des équipes (pour assignation)
$membres = [];
$stmt = $db->query('SELECT DISTINCT u.id, u.nom, u.prenom FROM utilisateurs u JOIN equipes_projet ep ON ep.utilisateur_id = u.id JOIN projets p ON p.id = ep.projet_id WHERE u.role_id IN (2,3) AND u.actif = 1 ORDER BY u.nom, u.prenom');
$membres = $stmt->fetchAll(PDO::FETCH_ASSOC);
$chefs = [];
$stmt = $db->query("SELECT id, nom, prenom FROM utilisateurs WHERE role_id = 2 AND actif = 1");
$chefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$assignables = array_merge($chefs, $membres);
$assignables = array_unique($assignables, SORT_REGULAR);
// Simplifier: tous les utilisateurs actifs sauf admin
$stmt = $db->query("SELECT id, nom, prenom FROM utilisateurs WHERE actif = 1 AND id != 1 ORDER BY nom, prenom");
$assignables = $stmt->fetchAll(PDO::FETCH_ASSOC);

$erreurs = [];
$titre = trim($_POST['titre'] ?? '');
$description = trim($_POST['description'] ?? '');
$projet_id = (int)($_POST['projet_id'] ?? $projetId);
$statut = $_POST['statut'] ?? 'a_faire';
$priorite = $_POST['priorite'] ?? 'normale';
$date_echeance = trim($_POST['date_echeance'] ?? '');
$assignee_id = !empty($_POST['assignee_id']) ? (int)$_POST['assignee_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($titre === '') $erreurs[] = 'Le titre est requis.';
    if (!$projet_id) $erreurs[] = 'Choisir un projet.';
    if ($projet_id && !Auth::peutVoirProjet($projet_id)) $erreurs[] = 'Projet non autorisé.';
    if ($date_echeance !== '') {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_echeance)) {
            $d = date_create_from_format('d/m/Y', $date_echeance);
            $date_echeance = $d ? $d->format('Y-m-d') : '';
        }
    }
    if (empty($erreurs)) {
        $stmt = $db->prepare('INSERT INTO taches (projet_id, titre, description, statut, priorite, date_echeance, assignee_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$projet_id, $titre, $description ?: null, $statut, $priorite, $date_echeance ?: null, $assignee_id]);
        if ($assignee_id) {
            $stmt = $db->prepare('SELECT nom FROM projets WHERE id = ?');
            $stmt->execute([$projet_id]);
            $projetNom = $stmt->fetchColumn() ?: 'Projet';
            creerNotification(
                $db,
                $assignee_id,
                'Tâche assignée',
                'Une tâche vous a été assignée : « ' . $titre . ' » (projet ' . $projetNom . ').',
                SITE_URL . '/taches/index.php?projet_id=' . $projet_id,
                'info'
            );
        }
        header('Location: ' . SITE_URL . '/taches/index.php?projet_id=' . $projet_id . '&msg=ajoute');
        exit;
    }
}
?>

<h1 class="page-title">Gestion des Tâches</h1>
<p class="breadcrumb"><a href="<?= SITE_URL ?>/dashboard.php">Accueil</a> / Tâches / Ajout</p>

<?php foreach ($erreurs as $e): ?>
  <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<div class="form-card">
  <div class="form-card-header">Formulaire d'ajout de tâche</div>
  <div class="form-card-body">
    <form method="post" action="">
      <div class="form-group">
        <label>Projet</label>
        <select name="projet_id" class="form-control" required>
          <option value="">— Choisir —</option>
          <?php foreach ($projets as $pr): ?>
            <option value="<?= (int)$pr['id'] ?>" <?= $projet_id === (int)$pr['id'] ? 'selected' : '' ?>><?= htmlspecialchars($pr['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Titre</label>
        <input type="text" name="titre" class="form-control" placeholder="Titre de la tâche" value="<?= htmlspecialchars($titre) ?>" required>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control" placeholder="Description"><?= htmlspecialchars($description) ?></textarea>
      </div>
      <div class="form-group">
        <label>Statut</label>
        <select name="statut" class="form-control">
          <option value="a_faire" <?= $statut === 'a_faire' ? 'selected' : '' ?>>À faire</option>
          <option value="en_cours" <?= $statut === 'en_cours' ? 'selected' : '' ?>>En cours</option>
          <option value="termine" <?= $statut === 'termine' ? 'selected' : '' ?>>Terminé</option>
        </select>
      </div>
      <div class="form-group">
        <label>Priorité</label>
        <select name="priorite" class="form-control">
          <option value="basse" <?= $priorite === 'basse' ? 'selected' : '' ?>>Basse</option>
          <option value="normale" <?= $priorite === 'normale' ? 'selected' : '' ?>>Normale</option>
          <option value="haute" <?= $priorite === 'haute' ? 'selected' : '' ?>>Haute</option>
          <option value="urgente" <?= $priorite === 'urgente' ? 'selected' : '' ?>>Urgente</option>
        </select>
      </div>
      <div class="form-group">
        <label>Date d'échéance</label>
        <input type="date" name="date_echeance" class="form-control" value="<?= htmlspecialchars($date_echeance) ?>">
      </div>
      <div class="form-group">
        <label>Assigné à</label>
        <select name="assignee_id" class="form-control">
          <option value="">— Non assigné —</option>
          <?php foreach ($assignables as $m): ?>
            <option value="<?= (int)$m['id'] ?>" <?= $assignee_id === (int)$m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="<?= SITE_URL ?>/taches/index.php" class="btn btn-secondary">Annuler</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
