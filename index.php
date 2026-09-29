<?php
// OnliBackup Enterprise - Gateway Corporativo de Acesso
?>
<!DOCTYPE html>
<html lang="pt-BR" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnliBackup Enterprise — Central de Acesso</title>
    <!-- Anti-flicker theme loader -->
    <script>
        (function() {
            var theme = localStorage.getItem('onlibackup_theme');
            if (theme === 'dark' || theme === 'light') {
                document.documentElement.setAttribute('data-theme', theme);
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: light)').matches) {
                document.documentElement.setAttribute('data-theme', 'light');
            } else {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <style>
        :root, [data-theme="light"] {
            --bg-body: #f1f5f9;
            --bg-card: #ffffff;
            --bg-card-hover: #f8fafc;
            --border-card: #e2e8f0;
            --border-card-hover: #0284c7;
            --text-heading: #0f172a;
            --text-body: #475569;
            --text-muted: #64748b;
            --brand-primary: #0284c7;
            --brand-primary-hover: #0369a1;
            --accent-client: #059669;
            --accent-client-bg: rgba(5, 150, 105, 0.08);
            --accent-admin: #0284c7;
            --accent-admin-bg: rgba(2, 132, 199, 0.08);
            --shadow-card: 0 4px 20px -2px rgba(15, 23, 42, 0.06), 0 2px 6px -1px rgba(15, 23, 42, 0.04);
            --shadow-hover: 0 20px 30px -10px rgba(2, 132, 199, 0.15), 0 10px 15px -5px rgba(15, 23, 42, 0.04);
            --navbar-bg: rgba(255, 255, 255, 0.9);
            --pill-bg: #f8fafc;
            --pill-border: #cbd5e1;
            --ambient-glow: radial-gradient(circle at 50% 0%, rgba(2, 132, 199, 0.08) 0%, transparent 60%);
        }

        [data-theme="dark"] {
            --bg-body: #070b14;
            --bg-card: #0f172a;
            --bg-card-hover: #162032;
            --border-card: #1e293b;
            --border-card-hover: #38bdf8;
            --text-heading: #f8fafc;
            --text-body: #94a3b8;
            --text-muted: #64748b;
            --brand-primary: #38bdf8;
            --brand-primary-hover: #0ea5e9;
            --accent-client: #10b981;
            --accent-client-bg: rgba(16, 185, 129, 0.12);
            --accent-admin: #38bdf8;
            --accent-admin-bg: rgba(56, 189, 248, 0.12);
            --shadow-card: 0 4px 25px -4px rgba(0, 0, 0, 0.5), 0 2px 10px -2px rgba(0, 0, 0, 0.3);
            --shadow-hover: 0 20px 35px -8px rgba(56, 189, 248, 0.2), 0 10px 15px -5px rgba(0, 0, 0, 0.4);
            --navbar-bg: rgba(15, 23, 42, 0.85);
            --pill-bg: #162032;
            --pill-border: #27364b;
            --ambient-glow: radial-gradient(circle at 50% 0%, rgba(56, 189, 248, 0.08) 0%, transparent 60%);
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: var(--bg-body);
            background-image: var(--ambient-glow);
            background-repeat: no-repeat;
            background-position: top center;
            color: var(--text-body);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            transition: background-color 0.25s ease, color 0.25s ease;
            letter-spacing: -0.01em;
        }

        /* Navbar */
        .gateway-nav {
            background: var(--navbar-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border-card);
            padding: 14px 0;
        }

        .brand-logo-emblem {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            background: linear-gradient(135deg, #0284c7 0%, #1d4ed8 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #ffffff;
            font-size: 1.25rem;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.35);
        }

        .brand-logo-text {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.03em;
            line-height: 1;
        }
        .brand-logo-text span {
            color: var(--brand-primary);
        }

        /* Pill Toggle Button */
        .theme-pill-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 7px 16px;
            border-radius: 9999px;
            font-size: 0.82rem;
            font-weight: 600;
            background-color: var(--pill-bg);
            border: 1px solid var(--pill-border);
            color: var(--text-heading);
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            user-select: none;
        }
        .theme-pill-btn:hover {
            border-color: var(--brand-primary);
            color: var(--brand-primary);
            transform: translateY(-1px);
        }

        /* Status Dot */
        .live-status-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 700;
            background: var(--accent-client-bg);
            color: var(--accent-client);
            border: 1px solid var(--accent-client);
            letter-spacing: 0.04em;
        }
        .pulse-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background-color: var(--accent-client);
            box-shadow: 0 0 8px var(--accent-client);
            animation: pulseAnim 2s infinite ease-in-out;
        }
        @keyframes pulseAnim {
            0% { transform: scale(0.9); opacity: 0.8; }
            50% { transform: scale(1.4); opacity: 1; }
            100% { transform: scale(0.9); opacity: 0.8; }
        }

        /* Main Gateway Section */
        .gateway-hero {
            padding: 48px 0 32px 0;
            text-align: center;
        }
        .gateway-title {
            font-size: clamp(2rem, 3.5vw, 2.8rem);
            font-weight: 800;
            letter-spacing: -0.035em;
            color: var(--text-heading);
            margin-bottom: 12px;
        }
        .gateway-subtitle {
            font-size: 1.05rem;
            color: var(--text-muted);
            max-width: 580px;
            margin: 0 auto;
        }

        /* Gateway Cards */
        .gateway-card {
            background-color: var(--bg-card);
            border: 1.5px solid var(--border-card);
            border-radius: 24px;
            padding: 36px 32px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            box-shadow: var(--shadow-card);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
        }
        .gateway-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-hover);
            color: inherit;
        }
        .card-client:hover {
            border-color: var(--accent-client);
        }
        .card-admin:hover {
            border-color: var(--accent-admin);
        }

        .gateway-card-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }

        .card-icon-box {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.7rem;
            transition: transform 0.3s ease;
        }
        .gateway-card:hover .card-icon-box {
            transform: scale(1.08);
        }

        .card-client .card-icon-box {
            background: var(--accent-client-bg);
            color: var(--accent-client);
            border: 1px solid var(--accent-client);
        }
        .card-admin .card-icon-box {
            background: var(--accent-admin-bg);
            color: var(--accent-admin);
            border: 1px solid var(--accent-admin);
        }

        .card-tag {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            padding: 5px 12px;
            border-radius: 8px;
        }
        .card-client .card-tag {
            background: var(--accent-client-bg);
            color: var(--accent-client);
            border: 1px solid var(--accent-client);
        }
        .card-admin .card-tag {
            background: var(--accent-admin-bg);
            color: var(--accent-admin);
            border: 1px solid var(--accent-admin);
        }

        .gateway-card-name {
            font-size: 1.6rem;
            font-weight: 800;
            color: var(--text-heading);
            letter-spacing: -0.025em;
            margin-bottom: 8px;
        }
        .gateway-card-desc {
            font-size: 0.94rem;
            color: var(--text-body);
            line-height: 1.55;
            margin-bottom: 24px;
            min-height: 48px;
        }

        /* Pill Feature Badges inside Cards */
        .feature-chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 28px;
        }
        .feature-chip {
            font-size: 0.76rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            background: var(--pill-bg);
            border: 1px solid var(--border-card);
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        /* Gateway Buttons */
        .btn-portal-action {
            width: 100%;
            padding: 14px 20px;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.98rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.2s ease;
            text-decoration: none;
            border: none;
        }
        .btn-client {
            background: linear-gradient(135deg, #059669 0%, #0284c7 100%);
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(5, 150, 105, 0.3);
        }
        .btn-client:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(5, 150, 105, 0.4);
        }

        .btn-admin {
            background: linear-gradient(135deg, #0284c7 0%, #1d4ed8 100%);
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3);
        }
        .btn-admin:hover {
            opacity: 0.95;
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(2, 132, 199, 0.4);
        }

        /* Trust Footer */
        .trust-strip {
            border-top: 1px solid var(--border-card);
            padding: 24px 0;
            margin-top: 48px;
        }
        .trust-item {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-muted);
        }
        .trust-item i {
            color: var(--brand-primary);
            font-size: 0.95rem;
        }

        .gateway-footer {
            margin-top: auto;
            border-top: 1px solid var(--border-card);
            background: var(--bg-card);
            padding: 20px 0;
            font-size: 0.82rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body>

    <!-- NAVBAR CORPORATIVA -->
    <header class="gateway-nav">
        <div class="container d-flex justify-content-between align-items-center" style="max-width: 980px;">
            <a href="/" class="d-flex align-items-center gap-3 text-decoration-none">
                <div class="brand-logo-emblem">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div class="d-flex flex-column">
                    <div class="brand-logo-text">Onli<span>Backup</span></div>
                    <span class="text-muted" style="font-size: 0.68rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;">Enterprise</span>
                </div>
            </a>

            <div class="d-flex align-items-center gap-3">
                <div class="live-status-pill d-none d-sm-inline-flex">
                    <span class="pulse-dot"></span>
                    <span>ONLINE</span>
                </div>

                <button type="button" class="theme-pill-btn" title="Alternar tema">
                    <i class="theme-toggle-icon fa-solid fa-moon text-primary"></i>
                    <span class="theme-toggle-text d-none d-sm-inline">Tema Escuro</span>
                </button>
            </div>
        </div>
    </header>

    <!-- CONTEÚDO PRINCIPAL: APENAS AS 2 OPÇÕES COM DESIGN VISUAL DE IMPACTO -->
    <main class="flex-grow-1 d-flex align-items-center py-4">
        <div class="container" style="max-width: 980px;">
            
            <div class="gateway-hero">
                <h1 class="gateway-title">Central de Acesso</h1>
                <p class="gateway-subtitle">Selecione o portal corporativo para acessar seus backups ou gerenciar a governança da infraestrutura.</p>
            </div>

            <div class="row g-4 justify-content-center">
                
                <!-- OPÇÃO 1: PORTAL DO CLIENTE -->
                <div class="col-md-6">
                    <div class="gateway-card card-client">
                        <div>
                            <div class="gateway-card-top">
                                <div class="card-icon-box">
                                    <i class="fa-solid fa-building-shield"></i>
                                </div>
                                <span class="card-tag">Área do Cliente</span>
                            </div>

                            <h2 class="gateway-card-name">Portal do Cliente</h2>
                            <p class="gateway-card-desc">
                                Auditoria de integridade em tempo real, consulta de volumes e laudos oficiais de conformidade com a LGPD.
                            </p>

                            <div class="feature-chips">
                                <span class="feature-chip"><i class="fa-solid fa-check text-success"></i> Status ao Vivo</span>
                                <span class="feature-chip"><i class="fa-solid fa-file-pdf text-success"></i> Laudo LGPD</span>
                                <span class="feature-chip"><i class="fa-solid fa-key text-success"></i> Recuperação 2FA</span>
                            </div>
                        </div>

                        <div>
                            <a href="/portal/dashboard.php" class="btn-portal-action btn-client">
                                <span>Acessar Portal do Cliente</span>
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- OPÇÃO 2: PORTAL ADMINISTRADORES -->
                <div class="col-md-6">
                    <div class="gateway-card card-admin">
                        <div>
                            <div class="gateway-card-top">
                                <div class="card-icon-box">
                                    <i class="fa-solid fa-sliders"></i>
                                </div>
                                <span class="card-tag">Acesso Restrito TI</span>
                            </div>

                            <h2 class="gateway-card-name">Portal Administradores</h2>
                            <p class="gateway-card-desc">
                                Console unificado Bareos WebUI, Proxmox Backup Server, nuvem Google Cloud Storage e agentes Windows.
                            </p>

                            <div class="feature-chips">
                                <span class="feature-chip"><i class="fa-solid fa-server text-info"></i> Bareos 25.1</span>
                                <span class="feature-chip"><i class="fa-solid fa-cloud text-info"></i> PBS & GCS 5TB</span>
                                <span class="feature-chip"><i class="fa-brands fa-windows text-info"></i> Agentes Win64</span>
                            </div>
                        </div>

                        <div>
                            <a href="/portal/admin/index.php" class="btn-portal-action btn-admin">
                                <span>Acessar Portal Administradores</span>
                                <i class="fa-solid fa-shield"></i>
                            </a>
                        </div>
                    </div>
                </div>

            </div>

            <!-- TRUST STRIP DISCRETO -->
            <div class="trust-strip">
                <div class="row g-2 text-center">
                    <div class="col-6 col-md-3">
                        <div class="trust-item justify-content-center">
                            <i class="fa-solid fa-lock"></i>
                            <span>TLS 1.3 / AES-256</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="trust-item justify-content-center">
                            <i class="fa-solid fa-scale-balanced"></i>
                            <span>LGPD Art. 46</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="trust-item justify-content-center">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span>Regra 3-2-1 Offsite</span>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="trust-item justify-content-center">
                            <i class="fa-solid fa-circle-nodes"></i>
                            <span>Cluster bareos01</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <!-- FOOTER -->
    <footer class="gateway-footer">
        <div class="container text-center" style="max-width: 980px;">
            <div class="d-flex flex-column flex-sm-row justify-content-between align-items-center gap-2">
                <div>
                    <strong style="color: var(--text-heading);">Onlitec Soluções Tecnológicas</strong> © <?= date('Y') ?>
                </div>
                <div>
                    Plataforma Corporativa de Backup & Continuidade Operacional
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap Bundle & Theme Script -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/portal/assets/js/theme.js"></script>
</body>
</html>
