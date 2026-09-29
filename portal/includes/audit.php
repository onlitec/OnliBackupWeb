<?php
// includes/audit.php - Registro de Auditoria

require_once __DIR__ . '/db.php';

function logAudit($action, $details = '', $userId = null, $userEmail = null) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($userId === null && isset($_SESSION['portal_user_id'])) {
        $userId = $_SESSION['portal_user_id'];
    }
    if ($userEmail === null && isset($_SESSION['portal_user_email'])) {
        $userEmail = $_SESSION['portal_user_email'];
    }
    if (!$userEmail) {
        $userEmail = 'anonymous';
    }

    $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    if (strpos($ip, ',') !== false) {
        $ip = trim(explode(',', $ip)[0]);
    }
    $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 255);

    try {
        $db = getPortalDB();
        $stmt = $db->prepare("INSERT INTO portal_audit_logs (user_id, user_email, action, details, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$userId, $userEmail, $action, $details, $ip, $ua]);
    } catch (Exception $e) {
        // Fallback silencioso para não quebrar a aplicação caso o log falhe
        error_log("Falha ao registrar auditoria: " . $e->getMessage());
    }
}
