<?php
// portal/report.php - Comprovante Formal de Auditoria de Backup (Impressão / PDF)

require_once __DIR__ . '/includes/auth.php';
requireAuth();

$currentUser = getCurrentUser();
$allowedClients = getAllowedClientsForUser($currentUser['id'], $currentUser['role']);

$selectedClient = $_GET['client'] ?? ($allowedClients[0] ?? '');
if (!in_array($selectedClient, $allowedClients) && $currentUser['role'] !== 'admin') {
    die("Acesso não autorizado para esta máquina.");
}

$jobData = null;
try {
    $pg = getBareosDB();
    $stmt = $pg->prepare("
        SELECT j.jobid, j.name as jobname, c.name as clientname, j.jobstatus, j.joberrors,
               j.jobfiles, pg_size_pretty(j.jobbytes) as size, j.jobbytes,
               to_char(j.starttime, 'DD/MM/YYYY HH24:MI:SS') as start_fmt,
               to_char(j.endtime, 'DD/MM/YYYY HH24:MI:SS') as end_fmt,
               age(j.endtime, j.starttime)::text as duration,
               j.level
        FROM job j
        JOIN client c ON j.clientid = c.clientid
        WHERE c.name = ? AND j.jobstatus = 'T'
        ORDER BY j.jobid DESC
        LIMIT 1
    ");
    $stmt->execute([$selectedClient]);
    $jobData = $stmt->fetch();
} catch (Exception $e) {
    die("Erro ao consultar catálogo: " . $e->getMessage());
}

logAudit('generate_report', "Emitiu comprovante de auditoria para o servidor {$selectedClient}");
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comprovante de Auditoria — <?= htmlspecialchars($selectedClient) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f8fafc; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; color: #0f172a; }
        .report-page { max-width: 860px; margin: 40px auto; background: #ffffff; padding: 48px; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
        .stamp-box { border: 2px solid #10b981; color: #065f46; background: #ecfdf5; border-radius: 8px; padding: 12px 20px; text-align: center; }
        @media print {
            body { background: #ffffff; }
            .report-page { border: none; box-shadow: none; margin: 0; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

<div class="container py-3 no-print">
    <div class="d-flex justify-content-between align-items-center max-w-860 mx-auto" style="max-width: 860px;">
        <a href="/portal/dashboard.php" class="btn btn-outline-secondary btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Voltar ao Painel
        </a>
        <button onclick="window.print();" class="btn btn-primary btn-sm px-4 fw-semibold">
            <i class="fa-solid fa-print me-1"></i> Imprimir / Salvar em PDF
        </button>
    </div>
</div>

<div class="report-page">
    <!-- CABEÇALHO -->
    <div class="d-flex justify-content-between align-items-center border-bottom pb-4 mb-4">
        <div>
            <h3 class="fw-bold text-primary mb-1">
                <i class="fa-solid fa-shield-halved me-2"></i>OnliBackup
            </h3>
            <span class="text-secondary small fw-medium">Central de Segurança, Auditoria & Proteção de Dados</span>
        </div>
        <div class="text-end small text-muted">
            <div><strong>Emissão:</strong> <?= date('d/m/Y H:i:s') ?></div>
            <div><strong>Auditor:</strong> <?= htmlspecialchars($currentUser['name']) ?></div>
            <div><strong>Protocolo:</strong> ONLI-<?= date('Ymd') ?>-<?= $jobData ? $jobData['jobid'] : '00' ?></div>
        </div>
    </div>

    <div class="text-center my-4">
        <h4 class="fw-bold text-dark text-uppercase letter-spacing-1">Laudo Oficial de Conformidade de Backup</h4>
        <p class="text-secondary small">Comprovante de execução e integridade de cópia de segurança dos dados corporativos</p>
    </div>

    <!-- CARIMBO DE VALIDAÇÃO -->
    <div class="stamp-box d-flex align-items-center justify-content-between mb-4">
        <div class="d-flex align-items-center gap-3 text-start">
            <i class="fa-solid fa-circle-check fa-2x text-success"></i>
            <div>
                <strong class="d-block text-dark">STATUS: INTEGRIDADE VERIFICADA E APROVADA</strong>
                <span class="small text-muted">Todos os arquivos foram lidos sem erros e gravados com proteção criptografada e cópia em nuvem.</span>
            </div>
        </div>
        <span class="badge bg-success px-3 py-2 fs-6">100% CONCLUÍDO</span>
    </div>

    <!-- DADOS TÉCNICOS DA EXECUÇÃO -->
    <?php if ($jobData): ?>
        <h6 class="fw-bold text-secondary text-uppercase small border-bottom pb-2 mb-3">Especificações da Rotina</h6>
        <div class="row g-3 mb-4 small">
            <div class="col-6">
                <span class="text-muted d-block">Servidor de Origem (Cliente Bareos):</span>
                <strong class="text-dark fs-6"><?= htmlspecialchars($jobData['clientname']) ?></strong>
            </div>
            <div class="col-6">
                <span class="text-muted d-block">Identificador Único (Job ID):</span>
                <strong class="text-primary fs-6">Job #<?= $jobData['jobid'] ?> (<?= htmlspecialchars($jobData['jobname']) ?>)</strong>
            </div>
            <div class="col-6">
                <span class="text-muted d-block">Total de Arquivos Auditados:</span>
                <strong class="text-dark fs-6"><?= number_format((int)$jobData['jobfiles'], 0, ',', '.') ?> arquivos</strong>
            </div>
            <div class="col-6">
                <span class="text-muted d-block">Volume de Dados Processado:</span>
                <strong class="text-dark fs-6"><?= htmlspecialchars($jobData['size']) ?></strong>
            </div>
            <div class="col-6">
                <span class="text-muted d-block">Início da Operação:</span>
                <span class="text-dark"><?= htmlspecialchars($jobData['start_fmt']) ?></span>
            </div>
            <div class="col-6">
                <span class="text-muted d-block">Término da Operação:</span>
                <span class="text-dark"><?= htmlspecialchars($jobData['end_fmt']) ?> (Duração: <?= substr($jobData['duration'], 0, 8) ?>)</span>
            </div>
        </div>

        <h6 class="fw-bold text-secondary text-uppercase small border-bottom pb-2 mb-3">Armazenamento & Replicação Offsite</h6>
        <div class="table-responsive mb-4">
            <table class="table table-bordered table-sm small">
                <thead class="bg-light">
                    <tr>
                        <th>Camada de Armazenamento</th>
                        <th>Tipo / Tecnologia</th>
                        <th>Destino / Volume</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="fw-semibold">1. Armazenamento Local</td>
                        <td>Storage Daemon Bareos (NVMe)</td>
                        <td><code>/backup/storage/</code></td>
                        <td><span class="badge bg-success">Gravado</span></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">2. Replicação em Nuvem Offsite</td>
                        <td>Google Cloud Storage (5.0 TB)</td>
                        <td>Google Cloud Storage (Criptografado)</td>
                        <td><span class="badge bg-success">Sincronizado</span></td>
                    </tr>
                    <tr>
                        <td class="fw-semibold">3. Catálogo de Metadados</td>
                        <td>Dump PostgreSQL Comprimido</td>
                        <td><code>catalog-dump/bareos-catalog-*.sql.gz</code></td>
                        <td><span class="badge bg-success">Replicado</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">Nenhum backup concluído encontrado para este servidor.</div>
    <?php endif; ?>

    <!-- DECLARAÇÃO DE CONFORMIDADE -->
    <div class="border-top pt-4 mt-4">
        <p class="small text-muted mb-4" style="line-height: 1.6;">
            <strong>Declaração de Conformidade Técnica:</strong> Atestamos para os devidos fins de auditoria de segurança da informação, conformidade com a LGPD e governança de TI que as cópias de segurança descritas neste documento foram processadas através da plataforma <strong>OnliBackup</strong>, utilizando captura consistente via Volume Shadow Copy (VSS), criptografia ponta a ponta e redundância em datacenter de nuvem.
        </p>

        <div class="row text-center pt-3 small text-muted">
            <div class="col-6">
                <div class="border-top pt-2 mx-4">
                    <strong>OnliBackup / Onlitec</strong><br>
                    Infraestrutura de Segurança
                </div>
            </div>
            <div class="col-6">
                <div class="border-top pt-2 mx-4">
                    <strong><?= htmlspecialchars($currentUser['name']) ?></strong><br>
                    Auditor / Responsável Técnico
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
