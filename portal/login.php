<?php
// portal/login.php - Tela de Login Corporativa Enterprise

require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        header("Location: /portal/admin/index.php");
    } else {
        header("Location: /portal/dashboard.php");
    }
    exit;
}

$error = '';
$emailVal = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $emailVal = $email;

    if (empty($email) || empty($password)) {
        $error = 'Por favor, informe seu e-mail e sua senha.';
    } else {
        $db = getPortalDB();
        $stmt = $db->prepare("SELECT * FROM portal_users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ((int)$user['is_active'] !== 1) {
                $error = 'Sua conta está desativada. Entre em contato com o suporte técnico.';
                logAudit('login_blocked', 'Tentativa de login em conta desativada', $user['id'], $email);
            } else {
                // Login com Sucesso
                $_SESSION['portal_user_id'] = $user['id'];
                $_SESSION['portal_user_name'] = $user['name'];
                $_SESSION['portal_user_email'] = $user['email'];
                $_SESSION['portal_user_role'] = $user['role'];

                logAudit('login_success', 'Login realizado com sucesso', $user['id'], $email);

                $redirect = $_GET['redirect'] ?? '';
                if (!empty($redirect)) {
                    if ($user['role'] !== 'admin' && strpos($redirect, '/admin') !== false) {
                        $redirect = '/portal/dashboard.php';
                    }
                } else {
                    $redirect = ($user['role'] === 'admin') ? '/portal/admin/index.php' : '/portal/dashboard.php';
                }

                header("Location: " . $redirect);
                exit;
            }
        } else {
            $error = 'E-mail ou senha incorretos. Verifique suas credenciais.';
            logAudit('login_failed', 'Tentativa de login com credenciais incorretas', null, $email);
        }
    }
}

$pageTitle = "Acesso Seguro Corporativo";
require_once __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center align-items-center py-5">
    <div class="col-md-6 col-lg-5 col-xl-4">
        <div class="card-white p-4 p-sm-5 shadow">
            
            <div class="text-center mb-4">
                <div class="brand-emblem mx-auto mb-3" style="width: 52px; height: 52px; font-size: 1.5rem; border-radius: 14px;">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <h3 class="fw-bold mb-1" style="color: var(--text-primary); letter-spacing: -0.02em;">Acesso Seguro</h3>
                <p class="text-secondary small mb-0">Informe suas credenciais autorizadas para prosseguir</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger border-0 small py-2 px-3 rounded-3 mb-4 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-circle-exclamation flex-shrink-0"></i>
                    <span><?= htmlspecialchars($error) ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">E-mail Corporativo</label>
                    <div class="input-group">
                        <span class="input-group-text border-end-0 text-muted"><i class="fa-solid fa-envelope"></i></span>
                        <input type="email" name="email" class="form-control form-control-clean border-start-0" placeholder="seu-email@empresa.com" value="<?= htmlspecialchars($emailVal) ?>" required autofocus>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small fw-semibold text-secondary mb-0">Senha de Acesso</label>
                        <a href="/portal/forgot-password.php" class="text-decoration-none small fw-semibold" style="color: var(--brand-primary);">
                            Esqueceu a senha?
                        </a>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text border-end-0 text-muted"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" name="password" class="form-control form-control-clean border-start-0" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn-action-primary">
                        <i class="fa-solid fa-right-to-bracket me-1"></i>
                        <span>Entrar no Sistema</span>
                    </button>
                </div>
            </form>

            <div class="mt-4 pt-3 border-top text-center text-muted small" style="border-color: var(--border-subtle) !important;">
                <i class="fa-solid fa-lock text-success me-1"></i> Canal Criptografado TLS v1.3 • OnliBackup
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
