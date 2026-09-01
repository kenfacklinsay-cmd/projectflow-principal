<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Auth.php';

if (Auth::estConnecte()) {
    header('Location: ' . SITE_URL . '/dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT * FROM utilisateurs WHERE email = ? AND actif = 1');
        $stmt->execute([$email]);
        $u = $stmt->fetch();
        if ($u && $password === $u['mot_de_passe']) {
            $_SESSION['user_id'] = $u['id'];
            header('Location: ' . SITE_URL . '/dashboard.php');
            exit;
        }
        $error = 'Email ou mot de passe incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion - PROJECTFLOW</title>
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
  <link rel="stylesheet" href="assets/css/theme.css">
</head>
<body>
  <div class="login-page">
    <div class="login-box">
      <div class="login-logo-wrap">
        <img src="<?= SITE_URL ?>/assets/img/logo2.png" alt="GESPRO" class="login-logo">
      </div>
      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="post" action="">
        <div class="form-group">
          <label>Email</label>
          <input type="email" name="email" class="form-control" placeholder="votre@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label>Mot de passe</label>
          <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
        <button type="submit" class="btn btn-primary">Connexion</button>
      </form>
    </div>
  </div>
</body>
</html>
