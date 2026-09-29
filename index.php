<?php
// OnliBackup - Central de Acesso Unificada (Cliente & Administradores)
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnliBackup — Central de Acesso Seguro</title>
    <!-- Anti-flicker theme loader -->
    <script>
        (function() {
            var theme = localStorage.getItem('onlibackup_theme');
            if (theme === 'dark' || theme === 'light') {
                document.documentElement.setAttribute('data-theme', theme);
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
            } else {
                document.documentElement.setAttribute('data-theme', 'light');
            }
        })();
    </script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="/portal/assets/css/theme.css" rel="stylesheet">
</head>
<body class="d-flex flex-column min-vh-100">

    <!-- BARRA SUPERIOR MINIMALISTA COM SELETOR WHITE / DARK -->
    <header class="py-3 border-bottom" style="background-color: var(--bg-surface); border-color: var(--border-color) !important;">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="/" class="d-flex align-items-center gap-2 text-decoration-none">
                <span class="fs-4 text-primary"><i class="fa-solid fa-shield-halved"></i></span>
                <span class="fw-bold fs-5 brand-text">OnliBackup</span>
                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 ms-1 d-none d-sm-inline">Enterprise</span>
            </a>
            <div>
                <button type="button" class="theme-toggle-btn" title="Alternar tema">
                    <i class="theme-toggle-icon fa-solid fa-moon text-primary"></i>
                    <span class="theme-toggle-text">Tema Escuro</span>
                </button>
            </div>
        </div>
    </header>

    <!-- ÁREA PRINCIPAL COM AS DUAS OPÇÕES -->
    <main class="flex-grow-1 d-flex align-items-center py-5">
        <div class="container" style="max-width: 1040px;">
            
            <div class="text-center mb-5">
                <div class="status-badge-live mb-3">
                    <span class="pulse-dot"></span> SISTEMA OPERACIONAL & BACKUPS CONSOLIDADOS
                </div>
                <h1 class="fw-bold display-5 mb-2" style="letter-spacing: -0.5px;">
                    Central de Acesso Seguro
                </h1>
                <p class="text-secondary fs-5 mx-auto" style="max-width: 650px;">
                    Selecione o seu portal de destino abaixo para consultar seus relatórios de backup ou gerenciar a infraestrutura:
                </p>
            </div>

            <!-- GRID COM EXATAMENTE 2 OPÇÕES -->
            <div class="row g-4 justify-content-center">
                
                <!-- CARD 1: PORTAL DO CLIENTE -->
                <div class="col-md-6">
                    <div class="choice-card">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-3 py-2 rounded-pill fw-semibold">
                                    <i class="fa-solid fa-users me-1"></i> Área do Cliente
                                </span>
                                <span class="badge-status badge-status-success">
                                    <span class="status-indicator indicator-success"></span> Disponível
                                </span>
                            </div>

                            <div class="choice-icon bg-primary bg-opacity-10 text-primary">
                                <i class="fa-solid fa-building-shield"></i>
                            </div>

                            <h3 class="fw-bold mb-2">Portal do Cliente</h3>
                            <p class="text-secondary mb-4">
                                Acesso exclusivo para clientes corporativos. Acompanhe a integridade, status dos jobs executados, volumes de dados e laudos de auditoria dos servidores autorizados.
                            </p>

                            <ul class="list-unstyled mb-4 small text-secondary">
                                <li class="mb-2 d-flex align-items-center">
                                    <i class="fa-solid fa-circle-check text-success me-2"></i>
                                    <span>Status de execução e integridade em tempo real</span>
                                </li>
                                <li class="mb-2 d-flex align-items-center">
                                    <i class="fa-solid fa-circle-check text-success me-2"></i>
                                    <span>Histórico de retenção, arquivos e volumes</span>
                                </li>
                                <li class="d-flex align-items-center">
                                    <i class="fa-solid fa-circle-check text-success me-2"></i>
                                    <span>Recuperação autônoma de senha via e-mail</span>
                                </li>
                            </ul>
                        </div>

                        <div class="mt-2">
                            <a href="/portal/" class="btn btn-primary-white btn-choice">
                                <span>Acessar Portal do Cliente</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: PORTAL ADMINISTRADORES -->
                <div class="col-md-6">
                    <div class="choice-card">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-3 py-2 rounded-pill fw-semibold">
                                    <i class="fa-solid fa-key me-1"></i> Acesso Restrito TI
                                </span>
                                <span class="badge-status badge-status-running">
                                    <span class="status-indicator indicator-running"></span> Governança
                                </span>
                            </div>

                            <div class="choice-icon bg-warning bg-opacity-10 text-warning">
                                <i class="fa-solid fa-user-gear"></i>
                            </div>

                            <h3 class="fw-bold mb-2">Portal Administradores</h3>
                            <p class="text-secondary mb-4">
                                Console central de governança técnica. Gerenciamento de clientes e acessos, console Bareos Director, Proxmox Backup Server, nuvem Google Cloud Storage e agentes Windows.
                            </p>

                            <ul class="list-unstyled mb-4 small text-secondary">
                                <li class="mb-2 d-flex align-items-center">
                                    <i class="fa-solid fa-circle-check text-primary me-2"></i>
                                    <span>Bareos WebUI & Catálogo PostgreSQL</span>
                                </li>
                                <li class="mb-2 d-flex align-items-center">
                                    <i class="fa-solid fa-circle-check text-primary me-2"></i>
                                    <span>Proxmox Backup Server & Google Cloud Storage</span>
                                </li>
                                <li class="d-flex align-items-center">
                                    <i class="fa-solid fa-circle-check text-primary me-2"></i>
                                    <span>Gestão de clientes, permissões e instaladores</span>
                                </li>
                            </ul>
                        </div>

                        <div class="mt-2">
                            <a href="/portal/admin/" class="btn btn-outline-primary btn-choice" style="border-width: 2px;">
                                <span>Acessar Portal Administradores</span>
                                <i class="fa-solid fa-lock"></i>
                            </a>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </main>

    <!-- RODAPÉ CLEAN -->
    <footer class="footer-clean">
        <div class="container text-center">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <div>
                    <span class="fw-semibold text-secondary">OnliBackup Enterprise</span> © <?= date('Y') ?> • Todos os direitos reservados
                </div>
                <div class="text-muted small">
                    <i class="fa-solid fa-shield-halved text-success me-1"></i> Ambiente Auditado • Criptografia TLS v1.3
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap & Theme Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/portal/assets/js/theme.js"></script>
</body>
</html>
