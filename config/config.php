<?php
/**
 * Configuration GESPRO
 */
define('DB_HOST', 'localhost');
define('DB_NAME', 'projectflows');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'PROJECTFLOW');
define('SITE_URL', 'http://localhost/essaie');

/** Dossier des photos de profil (relatif à la racine web) */
define('UPLOAD_PROFILES_DIR', __DIR__ . '/../uploads/profiles');
define('UPLOAD_PROFILES_URL', SITE_URL . '/uploads/profiles');

/** Dossier des preuves de tâches (image quand un membre marque une tâche terminée) */
define('UPLOAD_TASK_PROOFS_DIR', __DIR__ . '/../uploads/task_proofs');
define('UPLOAD_TASK_PROOFS_URL', SITE_URL . '/uploads/task_proofs');

// Rôles
define('ROLE_ADMIN', 1);
define('ROLE_CHEF_PROJET', 2);
define('ROLE_MEMBRE', 3);

session_start();

spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../classes/' . $class . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
