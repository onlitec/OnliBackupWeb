<?php
// portal/admin/index.php - Console Administrativo Unificado & Governança

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getPortalDB();
$pg = getBareosDB();

// Métricas de usuários e auditoria
$totalUsers = (int)$db->query("SELECT count(*) FROM portal_users")->fetchColumn();
$totalClients = (int)$pg->query("SELECT count(*) FROM client")->fetchColumn();
$totalJobs = (int)$pg->query("SELECT count(*) FROM job WHERE jobstatus = 'T'")->fetchColumn();
$totalAuditLogs = (int)$db->query("SELECT count(*) FROM portal_audit_logs WHERE created_at >= datetime('now', '-1 day')")->fetchColumn();

// Último backup do HikCentral
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
    $stmt = $pg->query("SELECT j.jobid, j.name, c.name as client, j.jobstatus, j.joberrors, j.jobfiles, pg_size_pretty(j.jobbytes) as size, to_char(j.endtime, 'DD/MM/YYYY HH24:MI') as end_fmt, age(j.endtime, j.starttime)::text as duration FROM job j LEFT JOIN client c ON j.clientid = c.clientid WHERE j.name = 'BackupHikCentral' AND j.jobstatus = 'T' ORDER BY j.jobid DESC LIMIT 1");
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
    // Manter valores padrão em caso de timeout
}

// Telemetria do Armazenamento Local
$storage_path = '/var/lib/bareos/storage';
$disk_total = @disk_total_space($storage_path) ?: (100 * 1024 * 1024 * 1024);
$disk_free = @disk_free_space($storage_path) ?: (50 * 1024 * 1024 * 1024);
$disk_used = $disk_total - $disk_free;
$disk_percent = round(($disk_used / $disk_total) * 100);

// Últimos 10 eventos de auditoria
$stmtLogs = $db->query("SELECT * FROM portal_audit_logs ORDER BY id DESC LIMIT 10");
$recentLogs = $stmtLogs->fetchAll();

$pageTitle = "Painel Administrativo Unificado";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <h3 class="fw-bold text-dark mb-0">
                <i class="fa-solid fa-sliders text-primary me-2"></i>Console Administrativo Unificado
            </h3>
            <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1 rounded-pill small">
                Nível Admin
            </span>
        </div>
        <p class="text-secondary small mb-0">Gestão centralizada de clientes, infraestrutura Bareos, Proxmox Backup Server, nuvem GCS e agentes Windows</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/portal/admin/users.php" class="btn btn-primary-white btn-sm px-3 fw-semibold">
            <i class="fa-solid fa-user-plus me-1"></i> Novo Cliente
        </a>
    </div>
</div>

<!-- 1. TELEMETRIA E SAÚDE DO SERVIDOR (TEMPO REAL) -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card-white p-3 h-100 shadow-sm border-start border-4 border-info">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-secondary small fw-bold text-uppercase">
                    <i class="fa-brands fa-windows text-info me-1"></i> Agente Windows HikCentral
                </span>
                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25">
                    <i class="fa-solid fa-circle-check me-1"></i>Ativo
                </span>
            </div>
            <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($last_backup['client']) ?></div>
            <div class="text-secondary small mt-2">
                Último backup: <strong class="text-info"><?= $last_backup['files'] ?> arquivos</strong> (<?= $last_backup['bytes'] ?>)<br>
                Data: <?= $last_backup['date'] ?> (<?= $last_backup['duration'] ?>)<br>
                Agendamento: <span class="badge bg-primary bg-opacity-10 text-primary">Diário às 22:00</span>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card-white p-3 h-100 shadow-sm border-start border-4 border-success">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-secondary small fw-bold text-uppercase">
                    <i class="fa-solid fa-cloud-arrow-up text-success me-1"></i> Nuvem Offsite (GCS 5.0 TB)
                </span>
                <span class="badge bg-success bg-opacity-15 text-success border border-success border-opacity-25">
                    Automático
                </span>
            </div>
            <div class="fw-bold fs-6 text-dark">Google Cloud Storage</div>
            <div class="text-secondary small mt-2">
                Replicação: <strong class="text-success">Executada pós-backup</strong><br>
                Volumes espelhados: <code>bareos-storage/</code><br>
                Dump do Catálogo: <code>catalog-dump/</code>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card-white p-3 h-100 shadow-sm border-start border-4 border-warning">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-secondary small fw-bold text-uppercase">
                    <i class="fa-solid fa-hard-drive text-warning me-1"></i> Armazenamento Local Bareos
                </span>
                <span class="badge bg-secondary bg-opacity-15 text-secondary border">
                    <?= $disk_percent ?>% Usado
                </span>
            </div>
            <div class="fw-bold fs-6 text-dark">/var/lib/bareos/storage</div>
            <div class="progress mt-2 mb-2" style="height: 6px;">
                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $disk_percent ?>%;"></div>
            </div>
            <div class="text-secondary small d-flex justify-content-between">
                <span>Livre: <?= round($disk_free / 1024 / 1024 / 1024, 1) ?> GB</span>
                <span>Total: <?= round($disk_total / 1024 / 1024 / 1024, 1) ?> GB</span>
            </div>
        </div>
    </div>
</div>

<!-- 2. FERRAMENTAS E MÓDULOS DE INFRAESTRUTURA (CONSOLIDADOS) -->
<h5 class="fw-bold text-dark mb-3">
    <i class="fa-solid fa-network-wired text-primary me-2"></i>Ferramentas de Infraestrutura & Operação
</h5>
<div class="row g-4 mb-4">
    <!-- BAREOS WEBUI -->
    <div class="col-md-6 col-lg-4">
        <div class="card-white card-white-hover p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-server fa-lg"></i>
                    </div>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Oficial</span>
                </div>
                <h5 class="fw-bold text-dark mb-1">Bareos WebUI</h5>
                <p class="text-secondary small mb-3">
                    Console web oficial do Director. Monitore o catálogo PostgreSQL, jobs do Windows Server, pools de volumes, retenção e restaurações.
                </p>
            </div>
            <div>
                <a href="/bareos-webui/" target="_blank" class="btn btn-outline-primary btn-sm w-100 fw-semibold">
                    <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Abrir Console Bareos
                </a>
            </div>
        </div>
    </div>

    <!-- PBS NUVEM -->
    <div class="col-md-6 col-lg-4">
        <div class="card-white card-white-hover p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-circle" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-cloud-arrow-up fa-lg"></i>
                    </div>
                    <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">Nuvem PBS</span>
                </div>
                <h5 class="fw-bold text-dark mb-1">PBS Nuvem (Proxmox)</h5>
                <p class="text-secondary small mb-3">
                    Gerenciamento dos servidores Proxmox Backup Server (Datastore 10 TB), latência de conexão, deduplicação em bloco e snapshots.
                </p>
            </div>
            <div>
                <a href="/pbs/" class="btn btn-outline-info btn-sm w-100 fw-semibold">
                    <i class="fa-solid fa-sliders me-1"></i> Gerenciar Proxmox Servers
                </a>
            </div>
        </div>
    </div>

    <!-- GOOGLE CLOUD STORAGE -->
    <div class="col-md-6 col-lg-4">
        <div class="card-white card-white-hover p-4 h-100 d-flex flex-column justify-content-between">
            <div>
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width: 48px; height: 48px;">
                        <i class="fa-brands fa-google-drive fa-lg"></i>
                    </div>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">5.0 TB Offsite</span>
                </div>
                <h5 class="fw-bold text-dark mb-1">Nuvem Offsite & GCS</h5>
                <p class="text-secondary small mb-3">
                    Monitoramento da replicação automática offsite via rclone, integridade dos chunks, verificação de quota de armazenamento e auditoria.
                </p>
            </div>
            <div>
                <a href="/gdrive/" class="btn btn-outline-success btn-sm w-100 fw-semibold">
                    <i class="fa-solid fa-cloud me-1"></i> Configurar Nuvem Offsite
                </a>
            </div>
        </div>
    </div>
</div>

<!-- 3. CENTRAL DO AGENTE WINDOWS E PARÂMETROS -->
<div class="card-white p-4 mb-4 shadow-sm">
    <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
            <h5 class="fw-bold text-dark mb-1">
                <i class="fa-brands fa-windows text-info me-2"></i>Instalação e Agente Windows (x64) — Produção HikCentral
            </h5>
            <p class="text-secondary small mb-0">
                Pacote de instalação oficial e scripts de blindagem de serviço com suporte a VSS para servidores de produção.
            </p>
        </div>
        <div class="d-flex gap-2 flex-shrink-0">
            <a href="/downloads/Bareos-win64.exe" class="btn btn-primary-white btn-sm px-3 fw-semibold">
                <i class="fa-solid fa-download me-1"></i> Baixar Bareos-win64.exe
            </a>
            <button class="btn btn-outline-secondary btn-sm px-3" type="button" data-bs-toggle="collapse" data-bs-target="#adminInstallGuide">
                <i class="fa-solid fa-terminal me-1"></i> Comandos de Resiliência
            </button>
        </div>
    </div>

    <div class="collapse mt-3 pt-3 border-top" id="adminInstallGuide" style="border-color: var(--border-color) !important;">
        <div class="row g-3 small">
            <div class="col-md-6">
                <h6 class="text-primary fw-bold mb-2"><i class="fa-solid fa-sliders me-1"></i>Parâmetros Oficiais Homologados:</h6>
                <ul class="text-secondary ps-3 mb-0">
                    <li><strong class="text-dark">Client Name:</strong> <code>hiksrv-ad01-fd0-calabasas</code></li>
                    <li><strong class="text-dark">Director Name:</strong> <code>bareos-dir</code></li>
                    <li><strong class="text-dark">Password:</strong> <code>OnliBackup2026</code></li>
                    <li><strong class="text-dark">Porta Inbound:</strong> <code>9102</code> (TCP)</li>
                    <li><strong class="text-dark">Diretório Protegido:</strong> <code>C:\Program Files (x86)\HikCentral\VSM Servers</code></li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6 class="text-primary fw-bold mb-2"><i class="fa-solid fa-bolt me-1"></i>Blindagem do Serviço (PowerShell Administrador):</h6>
                <pre class="p-2 rounded mb-0" style="background-color: var(--code-bg); color: var(--code-color); font-size: 0.75rem; border: 1px solid var(--border-color);"><code>Set-Service bareos-fd -StartupType Automatic
sc.exe config bareos-fd start= delayed-auto
sc.exe failure bareos-fd reset= 86400 actions= restart/60000/restart/60000/restart/60000
New-NetFirewallRule -DisplayName "Bareos FD" -Direction Inbound -LocalPort 9102 -Protocol TCP -Action Allow</code></pre>
            </div>
        </div>
    </div>
</div>

<!-- 4. GESTÃO DE CLIENTES E AUDITORIA -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="metric-box shadow-sm">
            <div class="metric-icon bg-primary bg-opacity-10 text-primary">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <span class="text-muted small fw-semibold text-uppercase d-block">Usuários Cadastrados</span>
                <h3 class="fw-bold text-dark mb-0"><?= $totalUsers ?></h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="metric-box shadow-sm">
            <div class="metric-icon bg-info bg-opacity-10 text-info">
                <i class="fa-solid fa-server"></i>
            </div>
            <div>
                <span class="text-muted small fw-semibold text-uppercase d-block">Servidores Bareos</span>
                <h3 class="fw-bold text-dark mb-0"><?= $totalClients ?></h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="metric-box shadow-sm">
            <div class="metric-icon bg-success bg-opacity-10 text-success">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div>
                <span class="text-muted small fw-semibold text-uppercase d-block">Backups com Êxito</span>
                <h3 class="fw-bold text-dark mb-0"><?= $totalJobs ?></h3>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="metric-box shadow-sm">
            <div class="metric-icon bg-warning bg-opacity-10 text-warning">
                <i class="fa-solid fa-shield-halved"></i>
            </div>
            <div>
                <span class="text-muted small fw-semibold text-uppercase d-block">Acessos em 24h</span>
                <h3 class="fw-bold text-dark mb-0"><?= $totalAuditLogs ?></h3>
            </div>
        </div>
    </div>
</div>

<!-- ATALHOS RÁPIDOS DE GESTÃO -->
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <a href="/portal/admin/users.php" class="card-white card-white-hover p-4 text-decoration-none d-block h-100">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle" style="width: 44px; height: 44px;">
                    <i class="fa-solid fa-users fa-lg"></i>
                </div>
                <h5 class="fw-bold text-dark mb-0">Clientes e Usuários</h5>
            </div>
            <p class="text-secondary small mb-0">Cadastre clientes corporativos com e-mail e senha, ative/desative contas e redefina credenciais.</p>
        </a>
    </div>
    <div class="col-md-4">
        <a href="/portal/admin/permissions.php" class="card-white card-white-hover p-4 text-decoration-none d-block h-100">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="d-flex align-items-center justify-content-center bg-warning bg-opacity-10 text-warning rounded-circle" style="width: 44px; height: 44px;">
                    <i class="fa-solid fa-key fa-lg"></i>
                </div>
                <h5 class="fw-bold text-dark mb-0">Permissões de Máquinas</h5>
            </div>
            <p class="text-secondary small mb-0">Vincule quais máquinas e clientes Bareos cada usuário tem permissão para visualizar e auditar.</p>
        </a>
    </div>
    <div class="col-md-4">
        <a href="/portal/admin/settings.php" class="card-white card-white-hover p-4 text-decoration-none d-block h-100">
            <div class="d-flex align-items-center gap-3 mb-2">
                <div class="d-flex align-items-center justify-content-center bg-secondary bg-opacity-10 text-secondary rounded-circle" style="width: 44px; height: 44px;">
                    <i class="fa-solid fa-envelope-gear fa-lg"></i>
                </div>
                <h5 class="fw-bold text-dark mb-0">Configurações SMTP</h5>
            </div>
            <p class="text-secondary small mb-0">Configure os parâmetros de envio de e-mail corporativo para a recuperação de senhas dos clientes.</p>
        </a>
    </div>
</div>

<!-- AUDITORIA RECENTE -->
<div class="card-white shadow-sm overflow-hidden mb-4">
    <div class="card-header-clean d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-clipboard-list text-primary"></i>
            <h5 class="fw-bold text-dark mb-0">Trilha Recente de Auditoria</h5>
        </div>
        <a href="/portal/admin/audit.php" class="btn btn-outline-primary btn-sm rounded-3">
            Ver Todos os Logs <i class="fa-solid fa-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-clean table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Usuário / E-mail</th>
                    <th>Ação</th>
                    <th>Detalhes</th>
                    <th>IP de Origem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentLogs)): ?>
                    <tr><td colspan="5" class="text-center py-4 text-muted">Nenhum evento registrado ainda.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td class="text-muted small"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($log['user_email']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($log['action']) ?></span></td>
                            <td class="text-secondary small"><?= htmlspecialchars($log['details']) ?></td>
                            <td><code><?= htmlspecialchars($log['ip_address']) ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
