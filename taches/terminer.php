<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../classes/Auth.php';

Auth::redirigerSiNonConnecte();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header('Location: ' . SITE_URL . '/taches/index.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT t.*, p.nom as projet_nom, p.chef_projet_id FROM taches t JOIN projets p ON t.projet_id = p.id WHERE t.id = ?');
$stmt->execute([$id]);
$t = $stmt->fetch();

if (!$t || !Auth::peutVoirProjet($t['projet_id'])) {
    header('Location: ' . SITE_URL . '/taches/index.php');
    exit;
}

// Seule la personne à qui la tâche est assignée peut marquer la tâche comme terminée (avec preuve)
$user = Auth::getUser();
$estAssignee = (int)($t['assignee_id'] ?? 0) === (int)$user['id'];

$peutTerminer = $estAssignee
    && $t['statut'] !== 'termine'
    && $t['statut'] !== 'annule';

if (!$peutTerminer) {
    header('Location: ' . SITE_URL . '/taches/voir.php?id=' . $id);
    exit;
}

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fichierOk = !empty($_FILES['preuve']['name'])
        && isset($_FILES['preuve']['tmp_name'])
        && is_uploaded_file($_FILES['preuve']['tmp_name']);

    if (!$fichierOk) {
        $erreurs[] = 'Veuillez joindre une image pour prouver que la tâche est terminée.';
    } else {
        $file = $_FILES['preuve'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $erreurs[] = 'Erreur lors de l\'envoi du fichier. Veuillez réessayer.';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowedExt)) {
                $erreurs[] = 'Format d\'image non autorisé. Utilisez JPG, PNG, GIF ou WebP.';
            } else {
                $mimeOk = true;
                if (function_exists('finfo_open')) {
                    $finfo = @finfo_open(FILEINFO_MIME_TYPE);
                    if ($finfo) {
                        $mime = @finfo_file($finfo, $file['tmp_name']);
                        finfo_close($finfo);
                        $allowedMime = ['image/jpeg', 'image/pjpeg', 'image/png', 'image/x-png', 'image/gif', 'image/webp'];
                        if ($mime && !in_array($mime, $allowedMime)) {
                            $mimeOk = false;
                        }
                    }
                }
                if (!$mimeOk) {
                    $erreurs[] = 'Le fichier doit être une image (JPG, PNG, GIF ou WebP).';
                }
            }
        }
    }

    if (empty($erreurs)) {
        $dir = UPLOAD_TASK_PROOFS_DIR;
        if (!is_dir($dir)) {
            if (!@mkdir($dir, 0755, true)) {
                $erreurs[] = 'Impossible de créer le dossier d\'upload.';
            }
        }

        if (empty($erreurs)) {
            $filename = 'task_' . $id . '_' . time() . '.' . $ext;
            $destPath = $dir . DIRECTORY_SEPARATOR . $filename;

            if (!@move_uploaded_file($file['tmp_name'], $destPath)) {
                $erreurs[] = 'Impossible d\'enregistrer l\'image.';
            } else {
                try {
                    $stmt = $db->prepare('UPDATE taches SET statut = ?, preuve_image = ? WHERE id = ?');
                    $stmt->execute(['termine', $filename, $id]);

                    $chef_projet_id = (int)$t['chef_projet_id'];
                    if ($chef_projet_id) {
                        creerNotification(
                            $db,
                            $chef_projet_id,
                            'Tâche terminée',
                            'La tâche « ' . $t['titre'] . ' » a été marquée comme terminée avec une preuve par ' . htmlspecialchars($user['prenom'] . ' ' . $user['nom']) . '.',
                            SITE_URL . '/taches/voir.php?id=' . $id,
                            'info'
                        );
                    }
                    header('Location: ' . SITE_URL . '/taches/voir.php?id=' . $id . '&msg=termine');
                    exit;
                } catch (PDOException $e) {
                    @unlink($destPath);
                    $erreurs[] = 'Erreur lors de l\'enregistrement.';
                }
            }
        }
    }
}

$pageTitle = 'Marquer la tâche comme terminée - GESPRO';
$currentPage = 'taches';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Gestion des Tâches</h1>
<p class="breadcrumb">
  <a href="<?= SITE_URL ?>/dashboard.php">Accueil</a> / <a href="<?= SITE_URL ?>/taches/index.php">Tâches</a> / Marquer comme terminée
</p>

<?php foreach ($erreurs as $e): ?>
  <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<div class="form-card">
  <div class="form-card-header">Marquer la tâche comme terminée</div>
  <div class="form-card-body">
    <p>
      Pour marquer la tâche <strong><?= htmlspecialchars($t['titre']) ?></strong> comme terminée, vous devez joindre une image (capture d’écran, photo, etc.) prouvant que la tâche a bien été réalisée.
    </p>
    <form method="post" action="" enctype="multipart/form-data">
      <div class="form-group">
        <label for="preuve">Image de preuve <span style="color:red;">*</span></label>
        <input type="file" id="preuve" name="preuve" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp" required>
        <small>Formats acceptés : JPG, PNG, GIF, WebP.</small>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Marquer comme terminée et envoyer la preuve</button>
        <a href="<?= SITE_URL ?>/taches/voir.php?id=<?= $id ?>" class="btn btn-secondary">Annuler</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
