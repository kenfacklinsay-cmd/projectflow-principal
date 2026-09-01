<?php
/**
 * Gestion authentification et utilisateur courant
 */
require_once __DIR__ . '/Database.php';

class Auth {
    public static function estConnecte() {
        return isset($_SESSION['user_id']);
    }

    public static function getUser() {
        if (!self::estConnecte()) return null;
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT u.*, r.code as role_code, r.libelle as role_libelle FROM utilisateurs u JOIN roles r ON u.role_id = r.id WHERE u.id = ? AND u.actif = 1');
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }

    public static function estAdmin() {
        $u = self::getUser();
        return $u && $u['role_code'] === 'admin';
    }

    public static function estChefProjet() {
        $u = self::getUser();
        return $u && $u['role_code'] === 'chef_projet';
    }

    public static function estMembre() {
        $u = self::getUser();
        return $u && $u['role_code'] === 'membre';
    }

    public static function peutGererTousUtilisateurs() {
        return self::estAdmin();
    }

    public static function peutGererEquipe($projetId) {
        $u = self::getUser();
        if (!$u) return false;
        if ($u['role_code'] === 'admin') return true;
        if ($u['role_code'] === 'chef_projet') {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare('SELECT id FROM projets WHERE chef_projet_id = ? AND id = ?');
            $stmt->execute([$u['id'], $projetId]);
            return (bool) $stmt->fetch();
        }
        return false;
    }

    public static function peutVoirProjet($projetId) {
        $u = self::getUser();
        if (!$u) return false;
        if ($u['role_code'] === 'admin') return true;
        if ($u['role_code'] === 'chef_projet') {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT id FROM projets WHERE chef_projet_id = ? AND id = ?');
            $stmt->execute([$u['id'], $projetId]);
            if ($stmt->fetch()) return true;
        }
        // Membre : ne peut voir que les projets où il a au moins une tâche assignée
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare('SELECT 1 FROM taches WHERE projet_id = ? AND assignee_id = ? LIMIT 1');
        $stmt->execute([$projetId, $u['id']]);
        return (bool) $stmt->fetch();
    }

    public static function redirigerSiNonConnecte() {
        if (!self::estConnecte()) {
            header('Location: ' . SITE_URL . '/index.php');
            exit;
        }
    }
}
