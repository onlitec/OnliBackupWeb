<?php
// OnliBackup Enterprise - Central de Acesso Corporativa
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnliBackup Enterprise — Central de Backup & Continuidade Operacional</title>
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

    <!-- BARRA SUPERIOR CORPORATIVA COM GLASSMORPHISM -->
    <nav class="navbar-corporate py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <a href="/" class="d-flex align-items-center gap-3 text-decoration-none">
                <div class="brand-emblem">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="d-flex flex-column">
                    <div class="brand-title">Onli<span>Backup</span></div>
                    <span class="text-muted" style="font-size: 0.7rem; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase;">
                        Enterprise Resilience
                    </span>
                </div>
            </a>
            
            <div class="d-flex align-items-center gap-3">
                <div class="d-none d-md-flex align-items-center gap-2 px-3 py-1 rounded-pill" style="background: var(--status-success-bg); border: 1px solid var(--status-success-border);">
                    <span class="pulse-indicator"></span>
                    <span style="font-size: 0.75rem; font-weight: 700; color: var(--status-success); letter-spacing: 0.04em;">CLUSTER ONLINE</span>
                </div>
                
                <button type="button" class="theme-pill-btn" title="Alternar tema visual">
                    <i class="theme-toggle-icon fa-solid fa-moon text-primary"></i>
                    <span class="theme-toggle-text d-none d-sm-inline">Tema Escuro</span>
                </button>
            </div>
        </div>
    </nav>

    <!-- ÁREA PRINCIPAL / HERO CORPORATIVO -->
    <main class="flex-grow-1 d-flex align-items-center py-5">
        <div class="container" style="max-width: 1140px;">
            
            <!-- HERO HEADLINE -->
            <div class="hero-section">
                <div class="hero-pill">
                    <i class="fa-solid fa-network-wired me-1"></i>
                    Continuidade de Negócios & Governança de Dados
                </div>
                <h1 class="hero-headline">
                    Central de Acesso Seguro & Gestão
                </h1>
                <p class="hero-subhead">
                    Plataforma corporativa de proteção contínua de dados, orquestração híbrida de volumes e conformidade técnica com a LGPD.
                </p>
            </div>

            <!-- AS DUAS OPÇÕES EXCLUSIVAS -->
            <div class="row g-4 justify-content-center">
                
                <!-- CARD 1: PORTAL DO CLIENTE -->
                <div class="col-md-6 col-lg-5">
                    <div class="corporate-card card-client">
                        <div>
                            <div class="card-badge-header">
                                <span class="category-tag" style="background: var(--status-success-bg); color: var(--status-success); border: 1px solid var(--status-success-border);">
                                    <i class="fa-solid fa-building-shield me-1"></i> Auditoria Corporativa
                                </span>
                                <span class="status-chip status-chip-success">
                                    <i class="fa-solid fa-check"></i> Autorizado
                                </span>
                            </div>

                            <div class="icon-holder" style="background: var(--status-success-bg); color: var(--status-success); border: 1px solid var(--status-success-border);">
                                <i class="fa-solid fa-shield-halved"></i>
                            </div>

                            <h2 class="card-title">Portal do Cliente</h2>
                            <p class="card-desc">
                                Acesso exclusivo para clientes corporativos. Acompanhe a integridade dos seus backups, laudos técnicos de conformidade e status dos servidores autorizados.
                            </p>

                            <ul class="feature-check-list">
                                <li>
                                    <i class="fa-solid fa-circle-check text-success"></i>
                                    <span>Monitoramento de saúde e jobs em tempo real</span>
                                </li>
                                <li>
                                    <i class="fa-solid fa-circle-check text-success"></i>
                                    <span>Histórico de retenção, arquivos e volumes protegidos</span>
                                </li>
                                <li>
                                    <i class="fa-solid fa-circle-check text-success"></i>
                                    <span>Emissão de laudo oficial de conformidade LGPD</span>
                                </li>
                                <li>
                                    <i class="fa-solid fa-circle-check text-success"></i>
                                    <span>Recuperação de senha autônoma com código via e-mail</span>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <a href="/portal/dashboard.php" class="btn-action-primary">
                                <span>Acessar Portal do Cliente</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: PORTAL ADMINISTRADORES -->
                <div class="col-md-6 col-lg-5">
                    <div class="corporate-card card-admin">
                        <div>
                            <div class="card-badge-header">
                                <span class="category-tag" style="background: var(--status-info-bg); color: var(--status-info); border: 1px solid var(--status-info-border);">
                                    <i class="fa-solid fa-sliders me-1"></i> Governança TI
                                </span>
                                <span class="status-chip status-chip-info">
                                    <i class="fa-solid fa-lock"></i> Restrito
                                </span>
                            </div>

                            <div class="icon-holder" style="background: var(--status-info-bg); color: var(--status-info); border: 1px solid var(--status-info-border);">
                                <i class="fa-solid fa-user-gear"></i>
                            </div>

                            <h2 class="card-title">Portal Administradores</h2>
                            <p class="card-desc">
                                Console central de governança técnica. Gerenciamento de clientes, orquestrador Bareos Director, Proxmox Backup Server, nuvem GCS e agentes Windows.
                            </p>

                            <ul class="feature-check-list">
                                <li>
                                    <i class="fa-solid fa-circle-check text-info"></i>
                                    <span>Console Bareos WebUI & Catálogo PostgreSQL</span>
                                </li>
                                <li>
                                    <i class="fa-solid fa-circle-check text-info"></i>
                                    <span>Proxmox Backup Server & Google Cloud Storage</span>
                                </li>
                                <li>
                                    <i class="fa-solid fa-circle-check text-info"></i>
                                    <span>Instalador oficial e comandos do Agente Windows</span>
                                </li>
                                <li>
                                    <i class="fa-solid fa-circle-check text-info"></i>
                                    <span>Gestão de permissões de máquinas e trilha de auditoria</span>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <a href="/portal/admin/index.php" class="btn-action-outline">
                                <span>Acessar Portal Administradores</span>
                                <i class="fa-solid fa-shield"></i>
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- SELOS DE CONFORMIDADE E SEGURANÇA (TRUST BAR) -->
            <div class="trust-badges-bar">
                <div class="row g-3 justify-content-center text-center">
                    <div class="col-6 col-md-3">
                        <div class="trust-badge-item justify-content-center">
                            <i class="fa-solid fa-lock"></i>
                            <span>Criptografia TLS 1.3 / AES-256</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="trust-badge-item justify-content-center">
                            <i class="fa-solid fa-scale-balanced"></i>
                            <span>Conformidade LGPD Art. 46</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="trust-badge-item justify-content-center">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>Política 3-2-1 Offsite Imutável</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="trust-badge-item justify-content-center">
                            <i class="fa-solid fa-server"></i>
                            <span>Cluster Bareos 25.1 / Debian 13</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- RODAPÉ CORPORATIVO INSTITUCIONAL -->
    <footer class="footer-corporate">
        <div class="container">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
                <div>
                    <span class="fw-bold" style="color: var(--text-primary);">Onlitec Soluções Tecnológicas Ltda.</span> © <?= date('Y') ?> • Todos os direitos reservados.
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span><i class="fa-solid fa-circle-nodes text-success me-1"></i> Servidor bareos01</span>
                    <span>•</span>
                    <span>Auditoria Criptográfica Ativa</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle & Theme Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/portal/assets/js/theme.js"></script>
</body>
</html>
