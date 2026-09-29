<?php
// portal/admin/permissions.php - Atribuição de Máquinas por Cliente

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getPortalDB();
$pg = getBareosDB();
$error = '';
$success = '';

// Buscar todos os clientes Bareos cadastrados no Director
$allBareosClients = [];
try {
    $stmtPg = $pg->query("SELECT clientid, name, uname, os FROM client ORDER BY name ASC");
    $allBareosClients = $stmtPg->fetchAll();
} catch (Exception $e) {
    $error = "Erro ao buscar máquinas do Bareos: " . $e->getMessage();
}

// Buscar usuários clientes do portal
$portalUsers = $db->query("SELECT id, name, email, role FROM portal_users WHERE role = 'client' ORDER BY name ASC")->fetchAll();

$selectedUserId = (int)($_GET['user_id'] ?? $_POST['user_id'] ?? ($portalUsers[0]['id'] ?? 0));

// Processar Atualização de Permissões
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_permissions'])) {
    $userId = (int)($_POST['user_id'] ?? 0);
    $selectedMachines = $_POST['machines'] ?? [];

    if ($userId > 0) {
        // Remover permissões atuais do usuário
        $del = $db->prepare("DELETE FROM portal_client_permissions WHERE user_id = ?");
        $del->execute([$userId]);

        // Inserir novas permissões selecionadas
        $ins = $db->prepare("INSERT INTO portal_client_permissions (user_id, bareos_client_name) VALUES (?, ?)");
        foreach ($selectedMachines as $mName) {
            $ins->execute([$userId, trim($mName)]);
        }

        $stmtUser = $db->prepare("SELECT email FROM portal_users WHERE id = ?");
        $stmtUser->execute([$userId]);
        $uEmail = $stmtUser->fetchColumn();

        logAudit('admin_update_permissions', "Atualizou permissões de máquinas para {$uEmail} (" . count($selectedMachines) . " máquinas)", $userId, $uEmail);
        $_SESSION['flash_success'] = "Permissões de máquinas atualizadas com sucesso!";
        header("Location: /portal/admin/permissions.php?user_id=" . $userId);
        exit;
    }
}

// Carregar permissões atuais do usuário selecionado
$currentPerms = [];
if ($selectedUserId > 0) {
    $stmtPerms = $db->prepare("SELECT bareos_client_name FROM portal_client_permissions WHERE user_id = ?");
    $stmtPerms->execute([$selectedUserId]);
    $currentPerms = $stmtPerms->fetchAll(PDO::FETCH_COLUMN);
}

$pageTitle = "Permissões de Máquinas por Cliente";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="fa-solid fa-key text-warning me-2"></i>Controle de Acesso por Máquina
        </h3>
        <p class="text-secondary small mb-0">Defina quais servidores e clientes Bareos cada usuário tem autorização para visualizar e auditar</p>
    </div>
    <a href="/portal/admin/users.php" class="btn btn-outline-secondary btn-sm rounded-3">
        <i class="fa-solid fa-arrow-left me-1"></i> Voltar aos Usuários
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- COLUNA ESQUERDA: SELEÇÃO DE CLIENTE -->
    <div class="col-md-4">
        <div class="card-white shadow-sm p-4 h-100">
            <h6 class="fw-bold text-dark mb-3">1. Selecione o Cliente</h6>
            <?php if (empty($portalUsers)): ?>
                <div class="text-muted small">Nenhum cliente cadastrado ainda. <a href="/portal/admin/users.php">Cadastre um cliente</a> primeiro.</div>
            <?php else: ?>
                <div class="list-group list-group-flush border rounded-3 overflow-hidden">
                    <?php foreach ($portalUsers as $pu): ?>
                        <a href="/portal/admin/permissions.php?user_id=<?= $pu['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3 <?= $pu['id'] === $selectedUserId ? 'active' : '' ?>">
                            <div>
                                <div class="fw-bold <?= $pu['id'] === $selectedUserId ? 'text-white' : 'text-dark' ?>"><?= htmlspecialchars($pu['name']) ?></div>
                                <small class="<?= $pu['id'] === $selectedUserId ? 'text-white-50' : 'text-muted' ?>"><?= htmlspecialchars($pu['email']) ?></small>
                            </div>
                            <i class="fa-solid fa-chevron-right small"></i>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- COLUNA DIREITA: CHECKBOXES DE MÁQUINAS BAREOS -->
    <div class="col-md-8">
        <div class="card-white shadow-sm p-4">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <div>
                    <h6 class="fw-bold text-dark mb-0">2. Máquinas e Servidores Bareos Autorizados</h6>
                    <span class="text-muted small">Marque as máquinas que o cliente poderá enxergar no painel e emitir relatórios</span>
                </div>
                <span class="badge bg-primary bg-opacity-10 text-primary border"><?= count($currentPerms) ?> máquina(s) selecionada(s)</span>
            </div>

            <?php if ($selectedUserId === 0): ?>
                <div class="text-center py-5 text-muted">Selecione um cliente ao lado para editar suas permissões.</div>
            <?php else: ?>
                <form method="POST" action="">
                    <input type="hidden" name="user_id" value="<?= $selectedUserId ?>">
                    <input type="hidden" name="save_permissions" value="1">

                    <div class="row g-3 my-2">
                        <?php foreach ($allBareosClients as $bc): ?>
                            <?php $checked = in_array($bc['name'], $currentPerms); ?>
                            <div class="col-md-6">
                                <label class="p-3 border rounded-3 d-flex align-items-start gap-3 w-100 cursor-pointer <?= $checked ? 'bg-light border-primary' : 'bg-white' ?>" style="cursor: pointer;">
                                    <input type="checkbox" name="machines[]" value="<?= htmlspecialchars($bc['name']) ?>" class="form-check-input mt-1" <?= $checked ? 'checked' : '' ?>>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark mb-1">
                                            <i class="fa-solid fa-server text-<?= $checked ? 'primary' : 'secondary' ?> me-1"></i>
                                            <?= htmlspecialchars($bc['name']) ?>
                                        </div>
                                        <div class="text-muted small">
                                            SO: <?= htmlspecialchars($bc['os'] ?: 'Windows/Linux') ?><br>
                                            Client ID: #<?= $bc['clientid'] ?>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary-white px-4 fw-semibold">
                            <i class="fa-solid fa-floppy-disk me-1"></i> Salvar Permissões
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
