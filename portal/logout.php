<?php
// portal/logout.php - Encerramento de Sessão com Auditoria

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    logAudit('logout', 'Sessão encerrada pelo usuário');
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

header("Location: /portal/login.php");
exit;
