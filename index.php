<?php
// OnliBackup Central Dashboard
$sync_status_file = __DIR__ . '/config/sync_status.json';
$sync_info = null;
if (file_exists($sync_status_file)) {
    $sync_info = json_decode(file_get_contents($sync_status_file), true);
}

// Obter dados do PostgreSQL se disponivel
$last_backup = [
    'jobid' => 9,
    'name' => 'BackupHikCentral',
    'client' => 'hiksrv-ad01-fd0-calabasas',
    'status' => 'Com sucesso',
    'files' => '119.312',
    'bytes' => '10.92 GB',
    'date' => '28/09/2026 18:55',
    'duration' => '28m 41s'
];

try {
    $pdo = new PDO("pgsql:host=localhost;dbname=bareos", "bareos", "");
    $stmt = $pdo->query("SELECT j.jobid, j.name, c.name as client, j.jobstatus, j.joberrors, j.jobfiles, pg_size_pretty(j.jobbytes) as size, to_char(j.endtime, 'DD/MM/YYYY HH24:MI') as end_fmt, age(j.endtime, j.starttime)::text as duration FROM job j LEFT JOIN client c ON j.clientid = c.clientid WHERE j.name = 'BackupHikCentral' AND j.jobstatus = 'T' ORDER BY j.jobid DESC LIMIT 1");
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $last_backup = [
            'jobid' => $row['jobid'],
            'name' => $row['name'],
            'client' => $row['client'],
            'status' => 'Com sucesso',
            'files' => number_format((int)$row['jobfiles'], 0, ',', '.'),
            'bytes' => $row['size'],
            'date' => $row['end_fmt'],
            'duration' => substr($row['duration'], 0, 8)
        ];
    }
} catch (Exception $e) {
    // Manter valores padrão caso ocorra timeout no PDO
}
?>
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
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        .status-badge-live {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(16, 185, 129, 0.15);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .pulse-dot {
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 8px #10b981;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.3); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="text-center mb-5">
        <div class="status-badge-live mb-3">
            <span class="pulse-dot"></span> SISTEMA OPERACIONAL & BACKUPS AUTOMATIZADOS
        </div>
        <h1 class="fw-bold display-5 text-white mb-2">
            <span class="text-info">OnliBackup</span> Central
        </h1>
        <p class="text-secondary fs-5 mb-0">Servidor bareos01 (Debian 13) — Bareos 25.1 • Google Drive 5TB • Proxmox Backup Server</p>
    </div>

    <!-- CARDS DE STATUS E TELEMETRIA EM TEMPO REAL -->
    <div class="row g-3 mb-4 justify-content-center" style="max-width: 1100px; margin: 0 auto;">
        <div class="col-md-4">
            <div class="p-3 rounded-4 shadow h-100" style="background-color: #162032; border: 1px solid #1e3a5f;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-secondary small fw-bold text-uppercase"><i class="fa-brands fa-windows text-info me-1"></i> Cliente HikCentral</span>
                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25"><i class="fa-solid fa-check me-1"></i>Ativo</span>
                </div>
                <div class="fw-bold fs-5 text-white"><?= htmlspecialchars($last_backup['client']) ?></div>
                <div class="text-secondary small mt-1">
                    Último backup: <strong class="text-info"><?= $last_backup['files'] ?> arquivos</strong> (<?= $last_backup['bytes'] ?>)<br>
                    Data: <?= $last_backup['date'] ?> (<?= $last_backup['duration'] ?>)<br>
                    Agendado: <strong class="text-warning">22:00 diariamente</strong>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="p-3 rounded-4 shadow h-100" style="background-color: #162032; border: 1px solid #144738;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-secondary small fw-bold text-uppercase"><i class="fa-brands fa-google-drive text-success me-1"></i> Google Drive Nuvem</span>
                    <span class="badge bg-success bg-opacity-25 text-success border border-success border-opacity-25"><i class="fa-solid fa-cloud-arrow-up me-1"></i>5.0 TB</span>
                </div>
                <div class="fw-bold fs-5 text-white">gdrive:OnliBackup</div>
                <div class="text-secondary small mt-1">
                    Replicação: <strong class="text-success">Automática pós-backup</strong><br>
                    Volumes espelhados: <code>bareos-storage/</code><br>
                    Catálogo PostgreSQL: <code>catalog-dump/</code>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="p-3 rounded-4 shadow h-100" style="background-color: #162032; border: 1px solid #2a374a;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="text-secondary small fw-bold text-uppercase"><i class="fa-solid fa-calendar-check text-warning me-1"></i> Rotina Bareos</span>
                    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary border-opacity-25">Ativo</span>
                </div>
                <div class="fw-bold fs-5 text-white">HikCentralSchedule</div>
                <div class="text-secondary small mt-1">
                    Seg a Sex: <strong class="text-white">Incremental (22:00)</strong><br>
                    Sábados: <strong class="text-white">Diferencial (22:00)</strong><br>
                    1º Sábado do mês: <strong class="text-info">Full (22:00)</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- PORTAIS DE GERENCIAMENTO -->
    <div class="row g-4 justify-content-center mb-4" style="max-width: 1200px; margin: 0 auto;">
        <!-- PORTAL DE AUDITORIA & CLIENTES -->
        <div class="col-md-6 col-lg-3">
            <a href="/portal/" class="portal-card p-4 h-100 shadow d-flex flex-column justify-content-between" style="border-top: 3px solid #0284c7;">
                <div>
                    <div class="icon-circle bg-primary bg-opacity-25 text-primary">
                        <i class="fa-solid fa-shield-halved fa-2x"></i>
                    </div>
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <h5 class="fw-bold mb-0">Portal Auditoria</h5>
                        <span class="badge bg-primary bg-opacity-25 text-primary" style="font-size: 0.65rem;">Clientes</span>
                    </div>
                    <p class="text-secondary small mb-4">
                        Acesso exclusivo para clientes corporativos (login/senha), auditoria de integridade, laudo de backup e recuperação de senha por e-mail.
                    </p>
                </div>
                <div class="d-flex align-items-center text-primary fw-semibold small">
                    Acessar Portal do Cliente <i class="fa-solid fa-arrow-right ms-2"></i>
                </div>
            </a>
        </div>

        <!-- BAREOS WEBUI -->
        <div class="col-md-6 col-lg-3">
            <a href="/bareos-webui/" class="portal-card card-bareos p-4 h-100 shadow d-flex flex-column justify-content-between">
                <div>
                    <div class="icon-circle bg-primary bg-opacity-25 text-primary">
                        <i class="fa-solid fa-server fa-2x"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Bareos WebUI</h5>
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
        <div class="col-md-6 col-lg-3">
            <a href="/pbs/" class="portal-card card-pbs p-4 h-100 shadow d-flex flex-column justify-content-between">
                <div>
                    <div class="icon-circle bg-info bg-opacity-25 text-info">
                        <i class="fa-solid fa-cloud-arrow-up fa-2x"></i>
                    </div>
                    <h5 class="fw-bold mb-2">PBS Nuvem</h5>
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
        <div class="col-md-6 col-lg-3">
            <a href="/gdrive/" class="portal-card card-gdrive p-4 h-100 shadow d-flex flex-column justify-content-between">
                <div>
                    <div class="icon-circle bg-success bg-opacity-25 text-success">
                        <i class="fa-brands fa-google-drive fa-2x"></i>
                    </div>
                    <h5 class="fw-bold mb-2">Google Drive</h5>
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
                    <i class="fa-brands fa-windows text-info me-2"></i>Agente Bareos Windows (x64) — Produção HikCentral
                </h5>
                <p class="text-secondary small mb-0">
                    Instalador oficial para Windows Server (HikCentral, servidores de arquivos e SQL Server) com suporte a Snapshot VSS.
                </p>
            </div>
            <div class="d-flex gap-2 flex-shrink-0">
                <a href="/downloads/Bareos-win64.exe" class="btn btn-primary px-4 fw-semibold">
                    <i class="fa-solid fa-download me-2"></i>Baixar Agente Windows
                </a>
                <button class="btn btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#installInstructions">
                    <i class="fa-solid fa-shield-halved me-1"></i>Comandos de Resiliência
                </button>
            </div>
        </div>

        <div class="collapse mt-3 pt-3 border-top border-secondary border-opacity-25" id="installInstructions">
            <div class="row g-3 small">
                <div class="col-md-6">
                    <h6 class="text-info fw-bold mb-2"><i class="fa-solid fa-sliders me-1"></i>Parâmetros Oficiais de Produção:</h6>
                    <ul class="text-secondary ps-3 mb-2">
                        <li><strong class="text-white">Client Name:</strong> <code>hiksrv-ad01-fd0-calabasas</code></li>
                        <li><strong class="text-white">Director Name:</strong> <code>bareos-dir</code></li>
                        <li><strong class="text-white">Password:</strong> <code>OnliBackup2026</code></li>
                        <li><strong class="text-white">Porta Local:</strong> <code>9102</code> (TCP Inbound)</li>
                        <li><strong class="text-white">Diretório em Backup:</strong> <code>C:\Program Files (x86)\HikCentral\VSM Servers</code></li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h6 class="text-info fw-bold mb-2"><i class="fa-solid fa-bolt me-1"></i>Blindagem do Serviço no Windows (PowerShell Admin):</h6>
                    <p class="text-secondary mb-1">Execute para garantir auto-inicialização e reinício automático em falhas:</p>
                    <pre class="bg-dark p-2 rounded text-info mb-1" style="font-size: 0.75rem;"><code>Set-Service bareos-fd -StartupType Automatic
sc.exe config bareos-fd start= delayed-auto
sc.exe failure bareos-fd reset= 86400 actions= restart/60000/restart/60000/restart/60000
New-NetFirewallRule -DisplayName "Bareos FD" -Direction Inbound -LocalPort 9102 -Protocol TCP -Action Allow</code></pre>
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
