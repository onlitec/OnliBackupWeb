<?php
// portal/index.php - Roteador inicial inteligente
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: /portal/admin/index.php");
    } else {
        header("Location: /portal/dashboard.php");
    }
} else {
    header("Location: /portal/login.php");
}
exit;
