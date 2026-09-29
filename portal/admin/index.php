<?php
// portal/admin/index.php - Visão Geral Administrativa

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getPortalDB();
$pg = getBareosDB();

$totalUsers = (int)$db->query("SELECT count(*) FROM portal_users")->fetchColumn();
$totalClients = (int)$pg->query("SELECT count(*) FROM client")->fetchColumn();
$totalJobs = (int)$pg->query("SELECT count(*) FROM job WHERE jobstatus = 'T'")->fetchColumn();
$totalAuditLogs = (int)$db->query("SELECT count(*) FROM portal_audit_logs WHERE created_at >= datetime('now', '-1 day')")->fetchColumn();

// Últimos 10 eventos de auditoria
$stmtLogs = $db->query("SELECT * FROM portal_audit_logs ORDER BY id DESC LIMIT 10");
$recentLogs = $stmtLogs->fetchAll();

$pageTitle = "Painel Administrativo";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="fa-solid fa-gauge-high text-primary me-2"></i>Administração do Portal
        </h3>
        <p class="text-secondary small mb-0">Gestão de clientes corporativos, controle de acesso a máquinas e auditoria de segurança</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/portal/admin/users.php" class="btn btn-primary-white btn-sm px-3 fw-semibold">
            <i class="fa-solid fa-user-plus me-1"></i> Novo Cliente
        </a>
    </div>
</div>

<!-- CARDS DE MÉTRICAS -->
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
            <h5 class="fw-bold mb-0">Trilha Recente de Auditoria</h5>
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
