<?php
// portal/dashboard.php - Painel do Cliente (Monitoramento & Auditoria)

require_once __DIR__ . '/includes/auth.php';
requireAuth();

$currentUser = getCurrentUser();
logAudit('view_dashboard', 'Visualizou dashboard de backups');

$allowedClients = getAllowedClientsForUser($currentUser['id'], $currentUser['role']);

$clientsSummary = [];
$recentJobs = [];
$pgError = '';

if (!empty($allowedClients)) {
    try {
        $pg = getBareosDB();
        
        // Obter resumo de cada cliente permitido
        foreach ($allowedClients as $cName) {
            $stmt = $pg->prepare("
                SELECT j.jobid, j.name as jobname, c.name as clientname, j.jobstatus, j.joberrors,
                       j.jobfiles, pg_size_pretty(j.jobbytes) as size, j.jobbytes,
                       to_char(j.starttime, 'DD/MM/YYYY HH24:MI:SS') as start_fmt,
                       to_char(j.endtime, 'DD/MM/YYYY HH24:MI:SS') as end_fmt,
                       age(j.endtime, j.starttime)::text as duration,
                       j.level
                FROM job j
                JOIN client c ON j.clientid = c.clientid
                WHERE c.name = ?
                ORDER BY j.jobid DESC
                LIMIT 1
            ");
            $stmt->execute([$cName]);
            $latest = $stmt->fetch();

            $clientsSummary[] = [
                'name' => $cName,
                'has_backup' => (bool)$latest,
                'latest' => $latest
            ];
        }

        // Obter histórico de jobs recentes (últimos 20)
        $placeholders = implode(',', array_fill(0, count($allowedClients), '?'));
        $stmtJobs = $pg->prepare("
            SELECT j.jobid, j.name as jobname, c.name as clientname, j.jobstatus, j.joberrors,
                   j.jobfiles, pg_size_pretty(j.jobbytes) as size,
                   to_char(j.starttime, 'DD/MM/YYYY HH24:MI') as start_fmt,
                   to_char(j.endtime, 'DD/MM/YYYY HH24:MI') as end_fmt,
                   age(j.endtime, j.starttime)::text as duration,
                   j.level
            FROM job j
            JOIN client c ON j.clientid = c.clientid
            WHERE c.name IN ($placeholders)
            ORDER BY j.jobid DESC
            LIMIT 25
        ");
        $stmtJobs->execute($allowedClients);
        $recentJobs = $stmtJobs->fetchAll();

    } catch (Exception $e) {
        $pgError = "Erro ao consultar catálogo do Bareos: " . $e->getMessage();
    }
}

// Ler status da sincronização com a nuvem
$cloudSyncFile = '/var/www/html/config/sync_status.json';
$cloudInfo = null;
if (file_exists($cloudSyncFile)) {
    $cloudInfo = json_decode(file_get_contents($cloudSyncFile), true);
}

$pageTitle = "Painel de Auditoria & Backups";
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="fa-solid fa-server text-primary me-2"></i>Status Operacional de Backups
        </h3>
        <p class="text-secondary small mb-0">Visão consolidada de integridade, histórico e replicação em nuvem dos seus servidores</p>
    </div>
    <div class="d-flex gap-2">
        <a href="/portal/report.php" class="btn btn-outline-primary btn-sm px-3 fw-semibold rounded-3">
            <i class="fa-solid fa-file-pdf me-1"></i> Comprovante de Auditoria
        </a>
        <button class="btn btn-light btn-sm border px-3 text-secondary fw-semibold rounded-3" onclick="window.location.reload();">
            <i class="fa-solid fa-rotate me-1"></i> Atualizar
        </button>
    </div>
</div>

<?php if ($pgError): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($pgError) ?>
    </div>
<?php endif; ?>

<!-- BANNER DE REPLICAÇÃO EM NUVEM -->
<div class="card-white p-3 p-md-4 mb-4 border-start border-4 border-success">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="d-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle" style="width: 48px; height: 48px;">
                <i class="fa-brands fa-google-drive fa-xl"></i>
            </div>
            <div>
                <div class="d-flex align-items-center gap-2">
                    <h6 class="fw-bold text-dark mb-0">Google Cloud Storage (Google Cloud Storage 5.0 TB)</h6>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25" style="font-size: 0.65rem;">
                        <i class="fa-solid fa-shield-check me-1"></i>Ativo
                    </span>
                </div>
                <div class="text-secondary small mt-1">
                    Volumes e catálogo PostgreSQL espelhados automaticamente após cada rotina de backup com tolerância a desastres.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- CARDS DOS SERVIDORES AUTORIZADOS -->
<?php if (empty($allowedClients)): ?>
    <div class="card-white p-5 text-center my-4">
        <div class="text-muted mb-3"><i class="fa-solid fa-shield-halved fa-3x text-secondary"></i></div>
        <h5 class="fw-bold text-dark">Nenhum servidor atribuído</h5>
        <p class="text-secondary small mb-0">Sua conta ainda não possui permissão vinculada a nenhum servidor Bareos. Entre em contato com a equipe de TI.</p>
    </div>
<?php else: ?>
    <div class="row g-4 mb-5">
        <?php foreach ($clientsSummary as $cs): ?>
            <?php 
                $latest = $cs['latest'];
                $isOk = $latest && $latest['jobstatus'] === 'T';
                $isRunning = $latest && $latest['jobstatus'] === 'R';
                $isError = $latest && $latest['jobstatus'] === 'E';
                
                $statusClass = 'badge-status-success';
                $statusText = 'Operacional / Sucesso';
                $indicatorClass = 'indicator-success';
                
                if ($isRunning) {
                    $statusClass = 'badge-status-running';
                    $statusText = 'Em Execução';
                    $indicatorClass = 'indicator-running';
                } elseif ($isError) {
                    $statusClass = 'badge-status-error';
                    $statusText = 'Falha / Alerta';
                    $indicatorClass = 'indicator-error';
                } elseif (!$cs['has_backup']) {
                    $statusClass = 'badge-status-warning';
                    $statusText = 'Aguardando 1º Backup';
                    $indicatorClass = 'indicator-warning';
                }
            ?>
            <div class="col-md-6 col-xl-4">
                <div class="card-white p-4 h-100 shadow-sm d-flex flex-column justify-content-between">
                    <div>
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="text-muted small fw-bold text-uppercase">Servidor / Cliente</span>
                                <h5 class="fw-bold text-dark mb-0"><?= htmlspecialchars($cs['name']) ?></h5>
                            </div>
                            <span class="badge-status <?= $statusClass ?>">
                                <span class="status-indicator <?= $indicatorClass ?>"></span> <?= $statusText ?>
                            </span>
                        </div>

                        <?php if ($latest): ?>
                            <div class="border-top pt-3 mt-2">
                                <div class="row g-2 small">
                                    <div class="col-6">
                                        <span class="text-muted d-block">Arquivos Protegidos</span>
                                        <strong class="text-dark fs-6"><?= number_format((int)$latest['jobfiles'], 0, ',', '.') ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <span class="text-muted d-block">Tamanho no Volume</span>
                                        <strong class="text-primary fs-6"><?= htmlspecialchars($latest['size']) ?></strong>
                                    </div>
                                    <div class="col-12 mt-2">
                                        <span class="text-muted d-block">Último Backup Concluído</span>
                                        <span class="text-dark fw-medium"><?= htmlspecialchars($latest['end_fmt'] ?: $latest['start_fmt']) ?></span>
                                        <span class="text-muted">(Duração: <?= substr($latest['duration'], 0, 8) ?>)</span>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-muted small py-3">Nenhum registro de execução encontrado ainda para esta máquina.</div>
                        <?php endif; ?>
                    </div>

                    <div class="border-top pt-3 mt-3 d-flex justify-content-between align-items-center">
                        <span class="badge bg-light text-secondary border small">
                            <?= $latest ? ($latest['level'] === 'F' ? 'Backup Full' : ($latest['level'] === 'D' ? 'Diferencial' : 'Incremental')) : 'Aguardando' ?>
                        </span>
                        <a href="/portal/report.php?client=<?= urlencode($cs['name']) ?>" class="btn btn-sm btn-link text-decoration-none text-primary fw-semibold p-0">
                            Ver Laudo <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- TABELA DE HISTÓRICO DE AUDITORIA -->
    <div class="card-white shadow-sm overflow-hidden mb-5">
        <div class="card-header-clean d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-primary"></i>
                <h5 class="fw-bold mb-0">Histórico Recente de Execuções</h5>
            </div>
            <span class="badge bg-light text-muted border small"><?= count($recentJobs) ?> execuções listadas</span>
        </div>

        <div class="table-responsive">
            <table class="table table-clean table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>Job ID</th>
                        <th>Servidor</th>
                        <th>Rotina</th>
                        <th>Nível</th>
                        <th>Início</th>
                        <th>Duração</th>
                        <th>Arquivos</th>
                        <th>Volume</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentJobs)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">Nenhum job registrado no catálogo.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentJobs as $job): ?>
                            <?php 
                                $statusBadge = 'badge-status-success';
                                $statusLabel = 'Com sucesso';
                                if ($job['jobstatus'] === 'E') {
                                    $statusBadge = 'badge-status-error';
                                    $statusLabel = 'Com erro';
                                } elseif ($job['jobstatus'] === 'R') {
                                    $statusBadge = 'badge-status-running';
                                    $statusLabel = 'Em andamento';
                                }
                                $levelLabel = $job['level'] === 'F' ? 'Full' : ($job['level'] === 'D' ? 'Diferencial' : 'Incremental');
                            ?>
                            <tr>
                                <td class="fw-bold text-muted">#<?= $job['jobid'] ?></td>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($job['clientname']) ?></td>
                                <td class="text-secondary"><?= htmlspecialchars($job['jobname']) ?></td>
                                <td>
                                    <span class="badge <?= $job['level'] === 'F' ? 'bg-primary' : 'bg-secondary' ?> bg-opacity-10 text-<?= $job['level'] === 'F' ? 'primary' : 'secondary' ?> border" style="font-size: 0.7rem;">
                                        <?= $levelLabel ?>
                                    </span>
                                </td>
                                <td class="text-secondary small"><?= htmlspecialchars($job['start_fmt']) ?></td>
                                <td class="text-muted small"><?= substr($job['duration'], 0, 8) ?></td>
                                <td class="fw-medium text-dark"><?= number_format((int)$job['jobfiles'], 0, ',', '.') ?></td>
                                <td class="fw-semibold text-primary"><?= htmlspecialchars($job['size']) ?></td>
                                <td>
                                    <span class="badge-status <?= $statusBadge ?>" style="font-size: 0.7rem; padding: 4px 8px;">
                                        <?= $statusLabel ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
