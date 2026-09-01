<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';

Auth::redirigerSiNonConnecte();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SITE_URL . '/profil.php');
    exit;
}

if (empty($_FILES['photo']['name']) || !isset($_FILES['photo']['tmp_name']) || !is_uploaded_file($_FILES['photo']['tmp_name'])) {
    header('Location: ' . SITE_URL . '/profil.php?erreur=choisir');
    exit;
}

$user = Auth::getUser();
$file = $_FILES['photo'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    header('Location: ' . SITE_URL . '/profil.php?erreur=upload');
    exit;
}

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($ext, $allowedExt)) {
    header('Location: ' . SITE_URL . '/profil.php?erreur=photo');
    exit;
}

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
    header('Location: ' . SITE_URL . '/profil.php?erreur=photo');
    exit;
}

$dir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'profiles';
if (!is_dir($dir)) {
    if (!@mkdir($dir, 0755, true)) {
        header('Location: ' . SITE_URL . '/profil.php?erreur=upload');
        exit;
    }
}

$filename = 'user_' . (int)$user['id'] . '_' . time() . '.' . $ext;
$destPath = $dir . DIRECTORY_SEPARATOR . $filename;

if (!@move_uploaded_file($file['tmp_name'], $destPath)) {
    header('Location: ' . SITE_URL . '/profil.php?erreur=upload');
    exit;
}

$db = Database::getInstance()->getConnection();
$oldPhoto = isset($user['photo']) ? $user['photo'] : null;

try {
    $stmt = $db->prepare('UPDATE utilisateurs SET photo = ? WHERE id = ?');
    $stmt->execute([$filename, $user['id']]);
} catch (PDOException $e) {
    @unlink($destPath);
    header('Location: ' . SITE_URL . '/profil.php?erreur=db');
    exit;
}

if ($oldPhoto) {
    $oldPath = $dir . DIRECTORY_SEPARATOR . $oldPhoto;
    if (file_exists($oldPath)) {
        @unlink($oldPath);
    }
}

header('Location: ' . SITE_URL . '/profil.php?msg=photo');
exit;
