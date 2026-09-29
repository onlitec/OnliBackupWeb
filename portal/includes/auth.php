<?php
// includes/auth.php - Controle de Autenticação e Autorização Granular

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['portal_user_id']) && !empty($_SESSION['portal_user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id' => $_SESSION['portal_user_id'],
        'name' => $_SESSION['portal_user_name'] ?? '',
        'email' => $_SESSION['portal_user_email'] ?? '',
        'role' => $_SESSION['portal_user_role'] ?? 'client'
    ];
}

function isAdmin() {
    return isLoggedIn() && (($_SESSION['portal_user_role'] ?? '') === 'admin');
}

function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: /portal/login.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdmin() {
    requireAuth();
    if (!isAdmin()) {
        header("Location: /portal/dashboard.php?error=unauthorized");
        exit;
    }
}

function getAllowedClientsForUser($userId, $userRole = 'client') {
    if ($userRole === 'admin') {
        // Admin pode ver todas as máquinas do Bareos
        try {
            $pg = getBareosDB();
            $stmt = $pg->query("SELECT name FROM client ORDER BY name ASC");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }

    $db = getPortalDB();
    $stmt = $db->prepare("SELECT bareos_client_name FROM portal_client_permissions WHERE user_id = ? ORDER BY bareos_client_name ASC");
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function userCanAccessClient($userId, $userRole, $clientName) {
    if ($userRole === 'admin') {
        return true;
    }
    $allowed = getAllowedClientsForUser($userId, $userRole);
    return in_array($clientName, $allowed);
}
