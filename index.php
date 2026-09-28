<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnliBackup — Central de Backup & Orquestração</title>
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
            border-color: #38bdf8;
            box-shadow: 0 10px 25px -5px rgba(56, 189, 248, 0.25);
            color: inherit;
        }
        .icon-circle {
            width: 72px;
            height: 72px;
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
        <p class="text-secondary fs-5">Servidor bareos01 (Debian 13) — Orquestração & Nuvem</p>
    </div>

    <div class="row g-4 justify-content-center" style="max-width: 900px; margin: 0 auto;">
        <!-- BAREOS WEBUI -->
        <div class="col-md-6">
            <a href="/bareos-webui/" class="portal-card p-4 h-100 shadow">
                <div class="icon-circle bg-primary bg-opacity-25 text-primary">
                    <i class="fa-solid fa-server fa-2x"></i>
                </div>
                <h4 class="fw-bold mb-2">Bareos WebUI</h4>
                <p class="text-secondary small mb-4">
                    Gerenciamento central do Director, catálogo PostgreSQL, jobs do Windows Server (HikCentral), pools de volumes e restaurações.
                </p>
                <div class="d-flex align-items-center text-info fw-semibold small">
                    Acessar Console Bareos <i class="fa-solid fa-arrow-right ms-2"></i>
                </div>
            </a>
        </div>

        <!-- PBS MANAGER -->
        <div class="col-md-6">
            <a href="/pbs/" class="portal-card p-4 h-100 shadow">
                <div class="icon-circle bg-info bg-opacity-25 text-info">
                    <i class="fa-solid fa-cloud-arrow-up fa-2x"></i>
                </div>
                <h4 class="fw-bold mb-2">PBS Nuvem Manager</h4>
                <p class="text-secondary small mb-4">
                    Configuração amigável de múltiplos servidores Proxmox Backup Server, testes de conectividade ao vivo, visualização de snapshots e sincronização.
                </p>
                <div class="d-flex align-items-center text-info fw-semibold small">
                    Gerenciar Proxmox Servers <i class="fa-solid fa-arrow-right ms-2"></i>
                </div>
            </a>
        </div>
    </div>

    <div class="text-center mt-5 text-secondary small">
        IP: <code>172.20.120.37</code> • Kernel 6.12 • PostgreSQL 17 • Bareos 25.1 • PBS Client 4.2.6
    </div>
</div>
</body>
</html>
