<?php
// portal/index.php - Roteador inicial
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header("Location: /portal/dashboard.php");
} else {
    header("Location: /portal/login.php");
}
exit;
