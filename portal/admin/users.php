<?php
// portal/admin/users.php - Gestão de Clientes e Usuários

require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getPortalDB();
$error = '';
$success = '';

// Processar Ações (Criar, Ativar, Desativar, Excluir, Redefinir Senha)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role = $_POST['role'] === 'admin' ? 'admin' : 'client';

        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Preencha todos os campos obrigatórios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'E-mail inválido.';
        } elseif (strlen($password) < 6) {
            $error = 'A senha deve ter no mínimo 6 caracteres.';
        } else {
            // Verificar duplicidade
            $stmt = $db->prepare("SELECT id FROM portal_users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Este e-mail já está cadastrado no sistema.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmtIns = $db->prepare("INSERT INTO portal_users (name, email, password_hash, role) VALUES (?, ?, ?, ?)");
                $stmtIns->execute([$name, $email, $hash, $role]);
                $newId = $db->lastInsertId();

                logAudit('admin_create_user', "Cadastrou usuário {$email} (Perfil: {$role})", $newId, $email);
                $_SESSION['flash_success'] = "Cliente '{$name}' cadastrado com sucesso!";
                header("Location: /portal/admin/users.php");
                exit;
            }
        }
    } elseif ($action === 'toggle_status') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newStatus = (int)($_POST['new_status'] ?? 1);
        if ($userId === (int)$_SESSION['portal_user_id']) {
            $error = 'Você não pode desativar seu próprio usuário.';
        } else {
            $stmt = $db->prepare("UPDATE portal_users SET is_active = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
            logAudit('admin_toggle_user_status', "Alterou status do usuário ID {$userId} para {$newStatus}");
            $_SESSION['flash_success'] = "Status do usuário atualizado com sucesso!";
            header("Location: /portal/admin/users.php");
            exit;
        }
    } elseif ($action === 'reset_pass') {
        $userId = (int)($_POST['user_id'] ?? 0);
        $newPass = $_POST['new_password'] ?? '';
        if (strlen($newPass) < 6) {
            $error = 'A nova senha deve ter no mínimo 6 caracteres.';
        } else {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE portal_users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$hash, $userId]);
            logAudit('admin_reset_user_pass', "Redefiniu senha do usuário ID {$userId}");
            $_SESSION['flash_success'] = "Senha redefinida com sucesso!";
            header("Location: /portal/admin/users.php");
            exit;
        }
    } elseif ($action === 'delete') {
        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId === (int)$_SESSION['portal_user_id']) {
            $error = 'Você não pode excluir seu próprio usuário.';
        } else {
            $stmt = $db->prepare("DELETE FROM portal_users WHERE id = ?");
            $stmt->execute([$userId]);
            logAudit('admin_delete_user', "Excluiu usuário ID {$userId}");
            $_SESSION['flash_success'] = "Usuário excluído com sucesso!";
            header("Location: /portal/admin/users.php");
            exit;
        }
    }
}

// Listar Usuários com contagem de máquinas vinculadas
$users = $db->query("
    SELECT u.*, 
           (SELECT count(*) FROM portal_client_permissions WHERE user_id = u.id) as permissions_count
    FROM portal_users u
    ORDER BY u.role ASC, u.name ASC
")->fetchAll();

$pageTitle = "Gestão de Clientes e Usuários";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="fa-solid fa-users text-primary me-2"></i>Gestão de Clientes e Usuários
        </h3>
        <p class="text-secondary small mb-0">Cadastre clientes corporativos, gerencie status e redefina credenciais de acesso</p>
    </div>
    <button class="btn btn-primary-white btn-sm px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNewUser">
        <i class="fa-solid fa-user-plus me-1"></i> Cadastrar Novo Cliente
    </button>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="card-white shadow-sm overflow-hidden mb-4">
    <div class="table-responsive">
        <table class="table table-clean table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nome do Cliente</th>
                    <th>E-mail</th>
                    <th>Perfil</th>
                    <th>Máquinas Autorizadas</th>
                    <th>Status</th>
                    <th>Data Cadastro</th>
                    <th class="text-end">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="fw-bold text-dark"><?= htmlspecialchars($u['name']) ?></td>
                        <td class="text-secondary"><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <span class="badge <?= $u['role'] === 'admin' ? 'bg-primary' : 'bg-secondary' ?> bg-opacity-10 text-<?= $u['role'] === 'admin' ? 'primary' : 'secondary' ?> border">
                                <?= $u['role'] === 'admin' ? 'Administrador' : 'Cliente Auditor' ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['role'] === 'admin'): ?>
                                <span class="badge bg-light text-dark border">Todas as Máquinas</span>
                            <?php else: ?>
                                <a href="/portal/admin/permissions.php?user_id=<?= $u['id'] ?>" class="badge bg-info bg-opacity-10 text-info border text-decoration-none">
                                    <i class="fa-solid fa-key me-1"></i> <?= $u['permissions_count'] ?> máquina(s)
                                </a>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int)$u['is_active'] === 1): ?>
                                <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25">Ativo</span>
                            <?php else: ?>
                                <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm">
                                <a href="/portal/admin/permissions.php?user_id=<?= $u['id'] ?>" class="btn btn-light border text-secondary" title="Gerenciar Permissões">
                                    <i class="fa-solid fa-key"></i>
                                </a>
                                <button class="btn btn-light border text-secondary" title="Redefinir Senha" onclick="openResetPassModal(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['name'])) ?>')">
                                    <i class="fa-solid fa-lock-open"></i>
                                </button>
                                <?php if ($u['id'] !== (int)$_SESSION['portal_user_id']): ?>
                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Alterar status deste usuário?');">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= (int)$u['is_active'] === 1 ? 0 : 1 ?>">
                                        <button type="submit" class="btn btn-light border text-<?= (int)$u['is_active'] === 1 ? 'warning' : 'success' ?>" title="<?= (int)$u['is_active'] === 1 ? 'Desativar' : 'Ativar' ?>">
                                            <i class="fa-solid fa-power-off"></i>
                                        </button>
                                    </form>
                                    <form method="POST" action="" class="d-inline" onsubmit="return confirm('Tem certeza que deseja excluir permanentemente este usuário?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                        <button type="submit" class="btn btn-light border text-danger" title="Excluir">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL CADASTRAR NOVO USUÁRIO -->
<div class="modal fade" id="modalNewUser" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-user-plus text-primary me-2"></i>Cadastrar Novo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create">
                <div class="modal-body py-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nome Completo / Empresa</label>
                        <input type="text" name="name" class="form-control form-control-clean" placeholder="Ex: HikCentral Calabasas" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">E-mail do Cliente (Login)</label>
                        <input type="email" name="email" class="form-control form-control-clean" placeholder="cliente@empresa.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Senha de Acesso</label>
                        <input type="password" name="password" class="form-control form-control-clean" placeholder="Mínimo 6 caracteres" minlength="6" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Perfil de Acesso</label>
                        <select name="role" class="form-select form-control-clean">
                            <option value="client" selected>Cliente Auditor (Visualiza apenas suas máquinas atribuídas)</option>
                            <option value="admin">Administrador (Gestão total do portal e de todos os servidores)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-white px-4 fw-semibold">Cadastrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL REDEFINIR SENHA -->
<div class="modal fade" id="modalResetPass" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow-lg rounded-4">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="fa-solid fa-lock text-warning me-2"></i>Redefinir Senha do Usuário</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="reset_pass">
                <input type="hidden" name="user_id" id="resetUserId" value="">
                <div class="modal-body py-4">
                    <p class="text-secondary small mb-3">Redefinindo senha para: <strong id="resetUserName" class="text-dark"></strong></p>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-secondary">Nova Senha</label>
                        <input type="password" name="new_password" class="form-control form-control-clean" placeholder="Mínimo 6 caracteres" minlength="6" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary-white px-4 fw-semibold">Atualizar Senha</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openResetPassModal(id, name) {
    document.getElementById('resetUserId').value = id;
    document.getElementById('resetUserName').innerText = name;
    new bootstrap.Modal(document.getElementById('modalResetPass')).show();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
