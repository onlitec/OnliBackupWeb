<?php
// portal/forgot-password.php - Solicitação de Recuperação com Envio de Código

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer.php';

if (isLoggedIn()) {
    header("Location: /portal/dashboard.php");
    exit;
}

$error = '';
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $emailVal = $email;

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, informe um endereço de e-mail válido.';
    } else {
        $db = getPortalDB();
        $stmt = $db->prepare("SELECT id, name, email, is_active FROM portal_users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && (int)$user['is_active'] === 1) {
            // Gerar código numérico de 6 dígitos seguro
            $code = sprintf('%06d', random_int(100000, 999999));
            $codeHash = hash('sha256', $code);
            $expiresAt = date('Y-m-d H:i:s', time() + 900); // 15 minutos

            // Invalidar códigos anteriores não utilizados
            $stmtDel = $db->prepare("UPDATE portal_password_resets SET used = 1 WHERE email = ? AND used = 0");
            $stmtDel->execute([$email]);

            // Inserir novo token
            $stmtIns = $db->prepare("INSERT INTO portal_password_resets (email, code_hash, expires_at) VALUES (?, ?, ?)");
            $stmtIns->execute([$email, $codeHash, $expiresAt]);

            // Enviar e-mail
            try {
                sendPasswordResetCode($email, $user['name'], $code);
                logAudit('password_reset_code_sent', 'Código de 6 dígitos enviado por e-mail', $user['id'], $email);
                $_SESSION['flash_success'] = "Enviamos um código de verificação de 6 dígitos para seu e-mail ({$email}). Ele expira em 15 minutos.";
                header("Location: /portal/reset-password.php?email=" . urlencode($email));
                exit;
            } catch (Exception $e) {
                // Caso o SMTP não esteja configurado ainda, avisar o usuário amigavelmente
                $mailer = new PortalMailer();
                if (!$mailer->isConfigured()) {
                    $error = "O servidor de e-mail (SMTP) ainda não foi configurado pelo Administrador. Para fins de demonstração, o código de recuperação gerado é: " . $code;
                    logAudit('password_reset_fallback', 'Código exibido em tela devido a SMTP não configurado', $user['id'], $email);
                } else {
                    $error = "Erro ao enviar e-mail: " . $e->getMessage();
                    logAudit('password_reset_mail_error', 'Falha no envio do código SMTP: ' . $e->getMessage(), $user['id'], $email);
                }
            }
        } else {
            // Mensagem genérica por segurança (timing safe)
            $_SESSION['flash_success'] = "Se o e-mail informado estiver cadastrado em nosso sistema, as instruções foram enviadas.";
            logAudit('password_reset_not_found', 'Solicitação para e-mail inexistente ou desativado', null, $email);
            header("Location: /portal/reset-password.php?email=" . urlencode($email));
            exit;
        }
    }
}

$pageTitle = "Recuperar Senha";
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center align-items-center py-5">
    <div class="col-md-6 col-lg-5 col-xl-4">
        <div class="card-white p-4 p-sm-5 shadow-sm">
            <div class="text-center mb-4">
                <div class="d-inline-flex align-items-center justify-content-center bg-info bg-opacity-10 text-info rounded-circle mb-3" style="width: 58px; height: 58px;">
                    <i class="fa-solid fa-key fa-xl"></i>
                </div>
                <h4 class="fw-bold text-dark mb-1">Recuperação de Senha</h4>
                <p class="text-secondary small mb-0">Digite seu e-mail cadastrado para receber o código de 6 dígitos</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-warning border-0 small py-2 px-3 rounded-3 mb-4">
                    <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-4">
                    <label class="form-label small fw-semibold text-secondary">E-mail Cadastrado</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control form-control-clean border-start-0" placeholder="seu-email@cliente.com" value="<?= htmlspecialchars($emailVal) ?>" required autofocus>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary-white py-2 fw-semibold">
                        <i class="fa-solid fa-paper-plane me-2"></i>Enviar Código de Verificação
                    </button>
                    <a href="/portal/login.php" class="btn btn-outline-secondary py-2 small rounded-3">
                        <i class="fa-solid fa-arrow-left me-1"></i> Voltar ao Login
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
