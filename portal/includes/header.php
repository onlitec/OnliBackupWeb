<?php
// includes/header.php - Cabeçalho compartilhado no Tema White

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/auth.php';

$currentUser = getCurrentUser();
$pageTitle = $pageTitle ?? 'Portal de Auditoria & Clientes';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> — OnliBackup</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <!-- Portal White Theme CSS -->
    <link href="/portal/assets/css/portal-white.css" rel="stylesheet">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-white sticky-top">
    <div class="container-fluid px-lg-5">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/portal/">
            <span class="fs-4 text-primary"><i class="fa-solid fa-shield-halved"></i></span>
            <div>
                <span class="brand-text">OnliBackup</span>
                <span class="brand-sub ms-1 small d-none d-sm-inline">| Portal de Auditoria</span>
            </div>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarContent">
            <?php if ($currentUser): ?>
                <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-4">
                    <li class="nav-item">
                        <a class="nav-link fw-semibold <?= strpos($_SERVER['REQUEST_URI'], '/portal/dashboard.php') !== false ? 'text-primary' : 'text-secondary' ?>" href="/portal/dashboard.php">
                            <i class="fa-solid fa-chart-line me-1"></i> Meus Backups
                        </a>
                    </li>

                    <?php if (isAdmin()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle fw-semibold <?= strpos($_SERVER['REQUEST_URI'], '/portal/admin/') !== false ? 'text-primary' : 'text-secondary' ?>" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-user-shield me-1"></i> Painel Admin
                            </a>
                            <ul class="dropdown-menu border-0 shadow-sm rounded-3">
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
                    <div class="text-end d-none d-md-block">
                        <div class="fw-bold text-dark small mb-0"><?= htmlspecialchars($currentUser['name']) ?></div>
                        <div class="badge <?= $currentUser['role'] === 'admin' ? 'bg-primary' : 'bg-secondary' ?> bg-opacity-15 text-<?= $currentUser['role'] === 'admin' ? 'primary' : 'secondary' ?> border" style="font-size: 0.65rem;">
                            <?= $currentUser['role'] === 'admin' ? 'Administrador' : 'Cliente Auditor' ?>
                        </div>
                    </div>
                    <a href="/portal/logout.php" class="btn btn-outline-danger btn-sm rounded-3 px-3 fw-semibold">
                        <i class="fa-solid fa-arrow-right-from-bracket me-1"></i> Sair
                    </a>
                </div>
            <?php else: ?>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="btn btn-primary-white btn-sm px-3" href="/portal/login.php">
                            <i class="fa-solid fa-lock me-1"></i> Acessar Portal
                        </a>
                    </li>
                </ul>
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
