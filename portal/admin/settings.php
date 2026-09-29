<?php
// portal/admin/settings.php - Configurações SMTP & Teste de Disparo de E-mail

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/mailer.php';
requireAdmin();

$error = '';
$testOutput = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_smtp') {
        setSetting('smtp_host', trim($_POST['smtp_host'] ?? ''));
        setSetting('smtp_port', trim($_POST['smtp_port'] ?? '587'));
        setSetting('smtp_secure', trim($_POST['smtp_secure'] ?? 'tls'));
        setSetting('smtp_user', trim($_POST['smtp_user'] ?? ''));
        if (!empty($_POST['smtp_pass'])) {
            setSetting('smtp_pass', $_POST['smtp_pass']);
        }
        setSetting('smtp_from_email', trim($_POST['smtp_from_email'] ?? ''));
        setSetting('smtp_from_name', trim($_POST['smtp_from_name'] ?? 'OnliBackup — Auditoria'));

        logAudit('admin_update_smtp', 'Atualizou configurações do servidor SMTP');
        $_SESSION['flash_success'] = 'Configurações de SMTP salvas com sucesso!';
        header("Location: /portal/admin/settings.php");
        exit;
    } elseif ($action === 'test_smtp') {
        $testEmail = trim($_POST['test_email'] ?? '');
        if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Por favor, informe um e-mail de destino válido para o teste.';
        } else {
            try {
                $mailer = new PortalMailer();
                $subject = "Teste de Conexão SMTP — OnliBackup";
                $html = "
                    <div style='font-family: sans-serif; padding: 20px; background: #f8fafc;'>
                        <div style='background: #fff; padding: 24px; border-radius: 8px; border: 1px solid #e2e8f0; max-width: 500px;'>
                            <h3 style='color: #0284c7; margin-top: 0;'>✅ Teste SMTP Bem-Sucedido!</h3>
                            <p style='color: #334155;'>Este é um e-mail de teste enviado pela plataforma <strong>OnliBackup</strong>.</p>
                            <p style='color: #64748b; font-size: 13px;'>Data e Hora: " . date('d/m/Y H:i:s') . "<br>Host SMTP: " . htmlspecialchars(getSetting('smtp_host')) . "</p>
                            <hr style='border: none; border-top: 1px solid #e2e8f0;'>
                            <small style='color: #94a3b8;'>OnliBackup • Onlitec</small>
                        </div>
                    </div>
                ";
                $mailer->send($testEmail, $subject, $html, "Teste SMTP bem-sucedido no OnliBackup em " . date('d/m/Y H:i:s'));
                $_SESSION['flash_success'] = "E-mail de teste enviado com sucesso para: {$testEmail}!";
                logAudit('admin_test_smtp_success', "Disparou teste SMTP com sucesso para {$testEmail}");
                header("Location: /portal/admin/settings.php");
                exit;
            } catch (Exception $e) {
                $error = "Falha no envio do e-mail de teste: " . $e->getMessage();
                logAudit('admin_test_smtp_failed', "Falha no teste SMTP para {$testEmail}: " . $e->getMessage());
            }
        }
    }
}

$pageTitle = "Configurações de E-mail (SMTP)";
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">
            <i class="fa-solid fa-envelope-gear text-secondary me-2"></i>Configurações de E-mail (SMTP)
        </h3>
        <p class="text-secondary small mb-0">Parâmetros para envio de códigos de recuperação de senha e alertas corporativos aos clientes</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
        <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- COLUNA ESQUERDA: FORMULÁRIO DE CONFIGURAÇÃO SMTP -->
    <div class="col-md-7">
        <div class="card-white shadow-sm p-4">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-server text-primary me-2"></i>Servidor de Saída (SMTP)</h5>

            <form method="POST" action="">
                <input type="hidden" name="action" value="save_smtp">

                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label small fw-semibold text-secondary">Host / Servidor SMTP</label>
                        <input type="text" name="smtp_host" class="form-control form-control-clean" placeholder="smtp.gmail.com ou smtp.office365.com" value="<?= htmlspecialchars(getSetting('smtp_host')) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Porta</label>
                        <input type="number" name="smtp_port" class="form-control form-control-clean" placeholder="587" value="<?= htmlspecialchars(getSetting('smtp_port', '587')) ?>" required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-secondary">Criptografia</label>
                        <select name="smtp_secure" class="form-select form-control-clean">
                            <option value="tls" <?= getSetting('smtp_secure', 'tls') === 'tls' ? 'selected' : '' ?>>STARTTLS (Porta 587)</option>
                            <option value="ssl" <?= getSetting('smtp_secure') === 'ssl' ? 'selected' : '' ?>>SSL / TLS Direto (Porta 465)</option>
                            <option value="none" <?= getSetting('smtp_secure') === 'none' ? 'selected' : '' ?>>Nenhuma (Porta 25)</option>
                        </select>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label small fw-semibold text-secondary">Usuário / E-mail de Autenticação</label>
                        <input type="email" name="smtp_user" class="form-control form-control-clean" placeholder="backup@onlitec.com.br" value="<?= htmlspecialchars(getSetting('smtp_user')) ?>" required>
                    </div>

                    <div class="col-md-12">
                        <label class="form-label small fw-semibold text-secondary">Senha de Acesso / App Password</label>
                        <input type="password" name="smtp_pass" class="form-control form-control-clean" placeholder="<?= getSetting('smtp_pass') ? '•••••••••••• (Preencha apenas para alterar)' : 'Senha de aplicativo' ?>">
                        <span class="text-muted small">Para contas Google, utilize uma <strong>Senha de App (App Password)</strong> de 16 caracteres.</span>
                    </div>

                    <div class="col-md-6 mt-3">
                        <label class="form-label small fw-semibold text-secondary">E-mail Remetente (From Email)</label>
                        <input type="email" name="smtp_from_email" class="form-control form-control-clean" placeholder="backup@onlitec.com.br" value="<?= htmlspecialchars(getSetting('smtp_from_email')) ?>">
                    </div>

                    <div class="col-md-6 mt-3">
                        <label class="form-label small fw-semibold text-secondary">Nome de Exibição (From Name)</label>
                        <input type="text" name="smtp_from_name" class="form-control form-control-clean" placeholder="OnliBackup — Auditoria" value="<?= htmlspecialchars(getSetting('smtp_from_name', 'OnliBackup — Auditoria')) ?>">
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary-white px-4 fw-semibold">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Salvar Configurações
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- COLUNA DIREITA: TESTE DE DISPARO -->
    <div class="col-md-5">
        <div class="card-white shadow-sm p-4 h-100">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-paper-plane text-success me-2"></i>Testar Conexão de E-mail</h5>
            <p class="text-secondary small">Envie um e-mail de teste para verificar se o servidor SMTP está autenticando e entregando as mensagens corretamente.</p>

            <form method="POST" action="">
                <input type="hidden" name="action" value="test_smtp">

                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary">E-mail Destinatário do Teste</label>
                    <input type="email" name="test_email" class="form-control form-control-clean" placeholder="seu-email@dominio.com" required>
                </div>

                <div class="d-grid">
                    <button type="submit" class="btn btn-outline-success py-2 fw-semibold">
                        <i class="fa-solid fa-paper-plane me-2"></i>Enviar E-mail de Teste
                    </button>
                </div>
            </form>

            <div class="alert alert-light border mt-4 small text-muted">
                <strong><i class="fa-solid fa-circle-info text-info me-1"></i> Dica de Segurança:</strong>
                Ao utilizar Gmail ou Google Workspace, ative a Verificação em Duas Etapas e crie uma <em>Senha de Aplicativo</em> na conta Google em <strong>Segurança &gt; Senhas de app</strong>.
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
