<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Auth.php';
$_SESSION = [];
session_destroy();
header('Location: ' . SITE_URL . '/connexion.php');
exit;
