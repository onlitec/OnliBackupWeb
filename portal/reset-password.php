<?php
// portal/reset-password.php - Validação do Código e Redefinição de Senha

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header("Location: /portal/dashboard.php");
    exit;
}

$error = '';
$emailVal = trim($_GET['email'] ?? $_POST['email'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $code = trim($_POST['code'] ?? '');
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($email) || empty($code) || empty($newPass)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif (strlen($newPass) < 6) {
        $error = 'A nova senha deve possuir pelo menos 6 caracteres.';
    } elseif ($newPass !== $confirmPass) {
        $error = 'A confirmação de senha não confere com a nova senha.';
    } else {
        $db = getPortalDB();
        
        // Buscar token ativo
        $stmt = $db->prepare("SELECT * FROM portal_password_resets WHERE email = ? AND used = 0 ORDER BY id DESC LIMIT 1");
        $stmt->execute([$email]);
        $reset = $stmt->fetch();

        if (!$reset) {
            $error = 'Nenhuma solicitação de recuperação encontrada para este e-mail. Solicite um novo código.';
        } elseif (strtotime($reset['expires_at']) < time()) {
            $error = 'Este código de verificação expirou. Por favor, solicite um novo código.';
            logAudit('password_reset_expired', 'Tentativa com código expirado', null, $email);
        } elseif ((int)$reset['attempts'] >= 5) {
            $error = 'Limite de tentativas excedido para este código por segurança. Solicite um novo código.';
            logAudit('password_reset_blocked', 'Muitas tentativas com código incorreto', null, $email);
        } else {
            $enteredHash = hash('sha256', $code);
            if (!hash_equals($reset['code_hash'], $enteredHash)) {
                // Incrementar tentativas
                $stmtAtt = $db->prepare("UPDATE portal_password_resets SET attempts = attempts + 1 WHERE id = ?");
                $stmtAtt->execute([$reset['id']]);
                $error = 'Código de verificação incorreto. Tente novamente.';
                logAudit('password_reset_bad_code', 'Código incorreto digitado', null, $email);
            } else {
                // Código válido! Atualizar senha
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $stmtUser = $db->prepare("UPDATE portal_users SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE email = ?");
                $stmtUser->execute([$hash, $email]);

                // Marcar token como utilizado
                $stmtUsed = $db->prepare("UPDATE portal_password_resets SET used = 1 WHERE id = ?");
                $stmtUsed->execute([$reset['id']]);

                logAudit('password_reset_success', 'Senha redefinida com sucesso via código de e-mail', null, $email);

                $_SESSION['flash_success'] = 'Sua senha foi redefinida com sucesso! Você já pode fazer login com suas novas credenciais.';
                header("Location: /portal/login.php");
                exit;
            }
        }
    }
}

$pageTitle = "Definir Nova Senha";
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center align-items-center py-5">
    <div class="col-md-6 col-lg-5 col-xl-4">
        <div class="card-white p-4 p-sm-5 shadow-sm">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-3" style="width: 58px; height: 58px;">
                    <i class="fa-solid fa-shield-check fa-xl"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">Verificação de Código</h4>
                <p class="text-secondary small mb-0">Insira o código de 6 dígitos recebido por e-mail e defina sua nova senha</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 small py-2 px-3 rounded-3 mb-4">
                    <i class="fa-solid fa-triangle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">E-mail</label>
                    <input type="email" name="email" class="form-control form-control-clean" value="<?= htmlspecialchars($emailVal) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Código de 6 Dígitos (Recebido por E-mail)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-hashtag"></i></span>
                        <input type="text" name="code" class="form-control form-control-clean border-start-0 text-center fw-bold fs-5 text-primary" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">Nova Senha</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="new_password" class="form-control form-control-clean border-start-0" placeholder="Mínimo 6 caracteres" minlength="6" required>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary">Confirmar Nova Senha</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="confirm_password" class="form-control form-control-clean border-start-0" placeholder="Repita a nova senha" minlength="6" required>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary-white py-2 fw-semibold">
                        <i class="fa-solid fa-circle-check me-2"></i>Salvar Nova Senha
                    </button>
                    <a href="/portal/forgot-password.php" class="btn btn-link text-decoration-none text-muted small text-center">
                        Não recebeu o código? Reenviar
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
