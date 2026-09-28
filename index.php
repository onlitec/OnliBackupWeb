<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnliBackup — Central de Backup & Orquestração em Nuvem</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #0b1120;
            color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .portal-card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 16px;
            transition: all 0.3s ease;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        .portal-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 10px 25px -5px rgba(56, 189, 248, 0.25);
            color: inherit;
        }
        .portal-card.card-bareos:hover { border-color: #3b82f6; }
        .portal-card.card-pbs:hover { border-color: #0284c7; }
        .portal-card.card-gdrive:hover { border-color: #10b981; }
        .icon-circle {
            width: 68px;
            height: 68px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="fw-bold display-5 text-white mb-2">
            <span class="text-info">OnliBackup</span> Central
        </h1>
        <p class="text-secondary fs-5">Servidor bareos01 (Debian 13) — Orquestração Local & Multi-Cloud</p>
    </div>

    <div class="row g-4 justify-content-center" style="max-width: 1100px; margin: 0 auto;">
        <!-- BAREOS WEBUI -->
        <div class="col-md-4">
            <a href="/bareos-webui/" class="portal-card card-bareos p-4 h-100 shadow d-flex flex-column justify-content-between">
                <div>
                    <div class="icon-circle bg-primary bg-opacity-25 text-primary">
                        <i class="fa-solid fa-server fa-2x"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Bareos WebUI</h4>
                    <p class="text-secondary small mb-4">
                        Gerenciamento do Director, catálogo PostgreSQL, jobs do Windows Server (HikCentral), pools de volumes e restaurações.
                    </p>
                </div>
                <div class="d-flex align-items-center text-primary fw-semibold small">
                    Acessar Console Bareos <i class="fa-solid fa-arrow-right ms-2"></i>
                </div>
            </a>
        </div>

        <!-- PBS MANAGER -->
        <div class="col-md-4">
            <a href="/pbs/" class="portal-card card-pbs p-4 h-100 shadow d-flex flex-column justify-content-between">
                <div>
                    <div class="icon-circle bg-info bg-opacity-25 text-info">
                        <i class="fa-solid fa-cloud-arrow-up fa-2x"></i>
                    </div>
                    <h4 class="fw-bold mb-2">PBS Nuvem</h4>
                    <p class="text-secondary small mb-4">
                        Configuração de múltiplos servidores Proxmox Backup Server (10 TB Datastore), teste de latência e snapshots remotos.
                    </p>
                </div>
                <div class="d-flex align-items-center text-info fw-semibold small">
                    Gerenciar Proxmox Servers <i class="fa-solid fa-arrow-right ms-2"></i>
                </div>
            </a>
        </div>

        <!-- GOOGLE DRIVE MANAGER -->
        <div class="col-md-4">
            <a href="/gdrive/" class="portal-card card-gdrive p-4 h-100 shadow d-flex flex-column justify-content-between">
                <div>
                    <div class="icon-circle bg-success bg-opacity-25 text-success">
                        <i class="fa-brands fa-google-drive fa-2x"></i>
                    </div>
                    <h4 class="fw-bold mb-2">Google Drive</h4>
                    <p class="text-secondary small mb-4">
                        Sincronização offsite via rclone para contas Google Pessoais ou Workspace, monitoramento de quota (5.0 TB) e logs.
                    </p>
                </div>
                <div class="d-flex align-items-center text-success fw-semibold small">
                    Configurar Google Drive <i class="fa-solid fa-arrow-right ms-2"></i>
                </div>
            </a>
        </div>
    </div>

    <!-- WINDOWS CLIENT DOWNLOAD & GUIDE BANNER -->
    <div class="mt-4 mx-auto p-4 rounded-4 shadow" style="max-width: 1100px; background-color: #1e293b; border: 1px solid #334155;">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div>
                <h5 class="fw-bold text-white mb-1">
                    <i class="fa-brands fa-windows text-info me-2"></i>Agente Bareos Windows (x64) para Clientes
                </h5>
                <p class="text-secondary small mb-0">
                    Instalador oficial para Windows Server (HikCentral, servidores de arquivos e SQL Server) com suporte a Snapshot VSS.
                </p>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <a href="/downloads/Bareos-win64.exe" class="btn btn-primary px-4 fw-semibold">
                    <i class="fa-solid fa-download me-2"></i>Baixar Instalador (36 MB)
                </a>
                <button class="btn btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#installInstructions">
                    <i class="fa-solid fa-circle-question me-1"></i>Instruções HikCentral
                </button>
            </div>
        </div>

        <div class="collapse mt-3 pt-3 border-top border-secondary border-opacity-25" id="installInstructions">
            <div class="row g-3 small">
                <div class="col-md-6">
                    <h6 class="text-info fw-bold mb-2"><i class="fa-solid fa-sliders me-1"></i>Parâmetros na Instalação do Windows:</h6>
                    <ul class="text-secondary ps-3 mb-2">
                        <li><strong class="text-white">Client Name:</strong> <code>hikcentral-fd</code></li>
                        <li><strong class="text-white">Director Name:</strong> <code>bareos-dir</code></li>
                        <li><strong class="text-white">Password:</strong> <code>OnliBackupHikCentral@2080</code></li>
                        <li><strong class="text-white">Porta Local:</strong> <code>9102</code> (padrão)</li>
                        <li><strong class="text-white">Diretório em Backup:</strong> <code>C:\Program Files (x86)\HikCentral\VSM Servers</code></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-info fw-bold mb-2"><i class="fa-solid fa-shield-halved me-1"></i>Liberação de Rede & Firewall:</h6>
                    <p class="text-secondary mb-1">1. Executar no PowerShell do Windows Server como Administrador:</p>
                    <pre class="bg-dark p-2 rounded text-info mb-2"><code>New-NetFirewallRule -DisplayName "Bareos FD" -Direction Inbound -LocalPort 9102 -Protocol TCP -Action Allow</code></pre>
                    <p class="text-secondary mb-0">2. No roteador com IP público <code>177.137.33.146</code>: Encaminhar (NAT Port Forward) a porta <strong>TCP 9102</strong> para o IP interno do Windows Server.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mt-5 text-secondary small">
        IP: <code>172.20.120.37</code> • Kernel 6.12 • PostgreSQL 17 • Bareos 25.1 • PBS Client 4.2.6 • Rclone 1.60
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
