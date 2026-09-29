<?php
// portal/admin/audit.php - Trilha Completa de Auditoria de Segurança

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getPortalDB();

// Filtros
$filterEmail = trim($_GET['email'] ?? '');
$filterAction = trim($_GET['action'] ?? '');
$filterDate = trim($_GET['date'] ?? '');

$where = [];
$params = [];

if (!empty($filterEmail)) {
    $where[] = "user_email LIKE ?";
    $params[] = "%$filterEmail%";
}
if (!empty($filterAction)) {
    $where[] = "action = ?";
    $params[] = $filterAction;
}
if (!empty($filterDate)) {
    $where[] = "date(created_at) = ?";
    $params[] = $filterDate;
}

$whereSql = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

// Exportação CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=onlibackup-audit-' . date('Ymd-His') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Data/Hora', 'E-mail do Usuário', 'Ação', 'Detalhes', 'Endereço IP', 'User-Agent']);
    
    $stmtExp = $db->prepare("SELECT id, created_at, user_email, action, details, ip_address, user_agent FROM portal_audit_logs $whereSql ORDER BY id DESC");
    $stmtExp->execute($params);
    while ($row = $stmtExp->fetch()) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
}

// Contagem e Paginação
$countStmt = $db->prepare("SELECT count(*) FROM portal_audit_logs $whereSql");
$countStmt->execute($params);
$totalLogs = (int)$countStmt->fetchColumn();

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50;
$offset = ($page - 1) * $limit;
$totalPages = ceil($totalLogs / $limit);

$stmt = $db->prepare("SELECT * FROM portal_audit_logs $whereSql ORDER BY id DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Listar ações distintas para o filtro
$distinctActions = $db->query("SELECT DISTINCT action FROM portal_audit_logs ORDER BY action ASC")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = "Trilha de Auditoria";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="fa-solid fa-clipboard-list text-primary me-2"></i>Trilha de Auditoria & Conformidade
        </h3>
        <p class="text-secondary small mb-0">Registro imutável de logins, recuperações de senha, acessos e ações administrativas para LGPD e segurança</p>
    </div>
    <div class="d-flex gap-2">
        <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>" class="btn btn-outline-success btn-sm px-3 fw-semibold rounded-3">
            <i class="fa-solid fa-file-csv me-1"></i> Exportar para CSV
        </a>
    </div>
</div>

<!-- FILTROS DE AUDITORIA -->
<div class="card-white p-3 mb-4 shadow-sm">
    <form method="GET" action="" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-semibold text-secondary">E-mail do Usuário</label>
            <input type="text" name="email" class="form-control form-control-clean form-control-sm" placeholder="Pesquisar por e-mail..." value="<?= htmlspecialchars($filterEmail) ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary">Tipo de Ação</label>
            <select name="action" class="form-select form-control-clean form-select-sm">
                <option value="">Todas as ações</option>
                <?php foreach ($distinctActions as $act): ?>
                    <option value="<?= htmlspecialchars($act) ?>" <?= $filterAction === $act ? 'selected' : '' ?>><?= htmlspecialchars($act) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-semibold text-secondary">Data do Evento</label>
            <input type="date" name="date" class="form-control form-control-clean form-select-sm" value="<?= htmlspecialchars($filterDate) ?>">
        </div>
        <div class="col-md-2 d-flex gap-1">
            <button type="submit" class="btn btn-primary-white btn-sm w-100 fw-semibold">
                <i class="fa-solid fa-filter me-1"></i> Filtrar
            </button>
            <a href="/portal/admin/audit.php" class="btn btn-light border btn-sm text-secondary" title="Limpar Filtros">
                <i class="fa-solid fa-rotate-left"></i>
            </a>
        </div>
    </form>
</div>

<!-- TABELA DE LOGS -->
<div class="card-white shadow-sm overflow-hidden mb-4">
    <div class="card-header-clean d-flex justify-content-between align-items-center">
        <span class="text-secondary small fw-bold text-uppercase">Registros Encontrados: <?= number_format($totalLogs, 0, ',', '.') ?></span>
        <span class="text-muted small">Página <?= $page ?> de <?= max(1, $totalPages) ?></span>
    </div>

    <div class="table-responsive">
        <table class="table table-clean table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Usuário / E-mail</th>
                    <th>Ação Executada</th>
                    <th>Detalhes do Evento</th>
                    <th>IP de Origem</th>
                    <th>Navegador / Dispositivo</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">Nenhum registro de auditoria encontrado com os filtros selecionados.</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <?php
                            $badgeClass = 'bg-light text-dark';
                            if (strpos($log['action'], 'login_success') !== false) {
                                $badgeClass = 'bg-success bg-opacity-10 text-success border border-success border-opacity-25';
                            } elseif (strpos($log['action'], 'failed') !== false || strpos($log['action'], 'blocked') !== false) {
                                $badgeClass = 'bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25';
                            } elseif (strpos($log['action'], 'password') !== false) {
                                $badgeClass = 'bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25';
                            } elseif (strpos($log['action'], 'admin_') !== false) {
                                $badgeClass = 'bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25';
                            }
                        ?>
                        <tr>
                            <td class="text-muted small" style="white-space: nowrap;"><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                            <td class="fw-semibold text-dark"><?= htmlspecialchars($log['user_email']) ?></td>
                            <td><span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($log['action']) ?></span></td>
                            <td class="text-secondary small"><?= htmlspecialchars($log['details']) ?></td>
                            <td><code><?= htmlspecialchars($log['ip_address']) ?></code></td>
                            <td class="text-muted small text-truncate" style="max-width: 200px;" title="<?= htmlspecialchars($log['user_agent']) ?>">
                                <?= htmlspecialchars($log['user_agent']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="p-3 border-top d-flex justify-content-center">
            <nav>
                <ul class="pagination pagination-sm mb-0">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
