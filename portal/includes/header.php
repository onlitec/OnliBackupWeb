<?php
// includes/header.php - Cabeçalho Corporativo Enterprise (Padrão Tema White)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth.php';

$currentUser = getCurrentUser();
$isAdmin = isAdmin();
$portalLabel = $isAdmin ? 'Portal Administradores' : 'Portal do Cliente';
$pageTitle = $pageTitle ?? ($isAdmin ? 'Portal Administradores' : 'Portal do Cliente');
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — OnliBackup Enterprise</title>
    <!-- Anti-flicker theme loader: Padrão Tema White -->
    <script>
        (function() {
            var theme = localStorage.getItem('onlibackup_theme');
            if (theme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Corporate Theme CSS -->
    <link href="/portal/assets/css/theme.css?v=2.3" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-corporate sticky-top py-2">
    <div class="container-fluid px-lg-5">
        <a class="navbar-brand d-flex align-items-center gap-3" href="<?= $isAdmin ? '/portal/admin/' : '/portal/dashboard.php' ?>">
            <div class="brand-emblem">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div class="d-flex flex-column">
                <div class="brand-title">Onli<span>Backup</span></div>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted" style="font-size: 0.7rem; font-weight: 600; letter-spacing: 0.04em;">
                        <?= $portalLabel ?>
                    </span>
                    <span class="badge <?= $isAdmin ? 'bg-primary' : 'bg-success' ?> bg-opacity-10 text-<?= $isAdmin ? 'primary' : 'success' ?> border border-<?= $isAdmin ? 'primary' : 'success' ?> border-opacity-25" style="font-size: 0.6rem; padding: 2px 6px;">
                        <?= $isAdmin ? 'SLA Governança' : 'Auditado LGPD' ?>
                    </span>
                </div>
            </div>
        </a>

        <!-- Mobile theme toggle button -->
        <div class="d-flex align-items-center gap-2 d-lg-none ms-auto me-2">
            <button type="button" class="theme-pill-btn px-2 py-1" title="Alternar tema">
                <i class="theme-toggle-icon fa-solid fa-moon text-primary"></i>
            </button>
        </div>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <?php if ($currentUser): ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                    <li class="nav-item">
                        <a class="nav-link fw-semibold <?= strpos($_SERVER['REQUEST_URI'], '/portal/dashboard.php') !== false ? 'text-primary' : 'text-secondary' ?>" href="/portal/dashboard.php">
                            <i class="fa-solid fa-chart-line me-1"></i> <?= $isAdmin ? 'Visão Geral Backups' : 'Meus Backups' ?>
                        </a>
                    </li>

                    <?php if ($isAdmin): ?>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold <?= ($_SERVER['REQUEST_URI'] === '/portal/admin/' || $_SERVER['REQUEST_URI'] === '/portal/admin/index.php') ? 'text-primary' : 'text-secondary' ?>" href="/portal/admin/index.php">
                                <i class="fa-solid fa-sliders me-1"></i> Console Admin
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle fw-semibold <?= (strpos($_SERVER['REQUEST_URI'], '/portal/admin/') !== false && $_SERVER['REQUEST_URI'] !== '/portal/admin/' && $_SERVER['REQUEST_URI'] !== '/portal/admin/index.php') ? 'text-primary' : 'text-secondary' ?>" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-user-shield me-1"></i> Governança TI
                            </a>
                            <ul class="dropdown-menu border-0 shadow rounded-3">
                                <li>
                                    <a class="dropdown-item py-2" href="/portal/admin/users.php">
                                        <i class="fa-solid fa-users text-primary me-2"></i> Cadastro de Clientes
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2" href="/portal/admin/permissions.php">
                                        <i class="fa-solid fa-key text-warning me-2"></i> Permissões por Máquina
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2" href="/portal/admin/audit.php">
                                        <i class="fa-solid fa-clipboard-list text-info me-2"></i> Trilha de Auditoria
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item py-2" href="/portal/admin/settings.php">
                                        <i class="fa-solid fa-envelope-gear text-secondary me-2"></i> Configurações SMTP
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>

                <div class="d-flex align-items-center gap-3">
                    <!-- Alternador de Tema White / Dark na Navbar -->
                    <button type="button" class="theme-pill-btn d-none d-lg-inline-flex" title="Alternar tema">
                        <i class="theme-toggle-icon fa-solid fa-moon text-primary"></i>
                        <span class="theme-toggle-text">Tema Escuro</span>
                    </button>

                    <div class="text-end d-none d-md-block ms-1 me-1">
                        <div class="fw-bold small mb-0" style="color: var(--text-primary);"><?= htmlspecialchars($currentUser['name']) ?></div>
                        <div class="text-muted" style="font-size: 0.7rem; font-family: var(--font-mono);">
                            <?= htmlspecialchars($currentUser['email']) ?>
                        </div>
                    </div>

                    <a href="/portal/logout.php" class="btn btn-outline-danger btn-sm rounded-pill px-3 fw-semibold">
                        <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Sair
                    </a>
                </div>
            <?php else: ?>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <button type="button" class="theme-pill-btn me-2" title="Alternar tema">
                        <i class="theme-toggle-icon fa-solid fa-moon text-primary"></i>
                        <span class="theme-toggle-text">Tema Escuro</span>
                    </button>
                    <a class="btn-action-primary py-2 px-3 rounded-pill text-decoration-none" style="font-size: 0.85rem;" href="/portal/login.php">
                        <i class="fa-solid fa-lock me-1"></i> Acessar Portal
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</nav>

<main class="container-fluid px-lg-5 py-4 flex-grow-1">
<?php
// Exibir mensagens flash se houver
if (!empty($_SESSION['flash_success'])) {
    echo '<div class="alert alert-success border-0 shadow-sm alert-dismissible fade show rounded-3 mb-4" role="alert"><i class="fa-solid fa-circle-check me-2"></i>' . htmlspecialchars($_SESSION['flash_success']) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['flash_success']);
}
if (!empty($_SESSION['flash_error'])) {
    echo '<div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show rounded-3 mb-4" role="alert"><i class="fa-solid fa-circle-exclamation me-2"></i>' . htmlspecialchars($_SESSION['flash_error']) . '<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>';
    unset($_SESSION['flash_error']);
}
?>
