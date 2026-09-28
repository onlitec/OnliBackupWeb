<?php
session_start();
$is_auth = !empty($_SESSION['authenticated']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnliBackup — Google Drive Cloud Sync Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-bg: #0f172a;
            --card-bg: #1e293b;
            --accent-color: #10b981;
            --border-color: #334155;
            --text-light: #f8fafc;
            --text-muted: #94a3b8;
        }
        body {
            background-color: var(--primary-bg);
            color: var(--text-light);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
        }
        .navbar {
            background-color: var(--card-bg);
            border-bottom: 1px solid var(--border-color);
        }
        .card {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-light);
            border-radius: 12px;
        }
        .form-control, .form-select {
            background-color: #0f172a;
            border: 1px solid var(--border-color);
            color: var(--text-light);
        }
        .form-control:focus, .form-select:focus {
            background-color: #0f172a;
            border-color: var(--accent-color);
            color: var(--text-light);
            box-shadow: 0 0 0 0.25rem rgba(16, 185, 129, 0.25);
        }
        .nav-tabs .nav-link {
            color: var(--text-muted);
            border: none;
            border-bottom: 2px solid transparent;
        }
        .nav-tabs .nav-link.active {
            background-color: transparent;
            color: var(--accent-color);
            border-bottom: 2px solid var(--accent-color);
            font-weight: bold;
        }
        .table-dark {
            --bs-table-bg: var(--card-bg);
        }
        .modal-content {
            background-color: var(--card-bg);
            border: 1px solid var(--border-color);
            color: var(--text-light);
        }
        .terminal-log {
            background-color: #000;
            color: #10b981;
            font-family: "Courier New", Courier, monospace;
            padding: 15px;
            border-radius: 8px;
            height: 380px;
            overflow-y: auto;
            white-space: pre-wrap;
            font-size: 0.88rem;
        }
    </style>
</head>
<body>

<?php if (!$is_auth): ?>
<!-- LOGIN -->
<div class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card p-4 shadow" style="max-width: 420px; width: 100%;">
        <div class="text-center mb-4">
            <i class="fa-brands fa-google-drive fa-3x text-success mb-3"></i>
            <h4 class="fw-bold">Google Drive Cloud Sync</h4>
            <p class="text-muted small">Gerenciamento de Backup Offsite em Nuvem</p>
        </div>
        <form id="loginForm">
            <div class="mb-3">
                <label class="form-label">Senha de Acesso</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-muted"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" id="loginPass" class="form-control" placeholder="Senha do Administrador" required autofocus>
                </div>
            </div>
            <div id="loginErr" class="alert alert-danger py-2 d-none small"></div>
            <button type="submit" class="btn btn-success w-100 py-2 fw-semibold">
                <i class="fa-solid fa-right-to-bracket me-2"></i> Entrar
            </button>
        </form>
    </div>
</div>
<script>
document.getElementById('loginForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const pass = document.getElementById('loginPass').value;
    const btn = e.target.querySelector('button');
    btn.disabled = true;
    try {
        const formData = new FormData();
        formData.append('action', 'login');
        formData.append('password', pass);
        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            document.getElementById('loginErr').textContent = data.error || 'Senha incorreta';
            document.getElementById('loginErr').classList.remove('d-none');
            btn.disabled = false;
        }
    } catch(err) {
        document.getElementById('loginErr').textContent = 'Erro ao autenticar';
        document.getElementById('loginErr').classList.remove('d-none');
        btn.disabled = false;
    }
});
</script>
<?php exit; endif; ?>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark px-4 py-3">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <i class="fa-brands fa-google-drive text-success fs-3 me-2"></i>
            <div>
                <span class="fw-bold fs-5 text-white">OnliBackup</span>
                <span class="badge bg-success ms-2" style="font-size: 0.75rem;">Google Drive Sync</span>
            </div>
        </a>
        <div class="d-flex align-items-center gap-2">
            <a href="/pbs/" class="btn btn-outline-info btn-sm">
                <i class="fa-solid fa-cloud-arrow-up me-1"></i> PBS Nuvem Manager
            </a>
            <a href="/bareos-webui/" target="_blank" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-server me-1"></i> Bareos WebUI
            </a>
            <button class="btn btn-outline-danger btn-sm" onclick="logout()">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Sair
            </button>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 py-4">
    <!-- CARDS DE STATUS -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Status da Conexão</div>
                        <h4 class="fw-bold mb-0 mt-1" id="cardStatus">
                            <span class="badge bg-secondary">Verificando...</span>
                        </h4>
                    </div>
                    <i class="fa-brands fa-google-drive fa-2x text-success"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Espaço Total no Drive</div>
                        <h4 class="fw-bold mb-0 mt-1" id="cardTotalSpace">-</h4>
                    </div>
                    <i class="fa-solid fa-cloud fa-2x text-primary"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Espaço Utilizado</div>
                        <h4 class="fw-bold mb-0 mt-1 text-warning" id="cardUsedSpace">-</h4>
                    </div>
                    <i class="fa-solid fa-chart-pie fa-2x text-warning"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Espaço Livre</div>
                        <h4 class="fw-bold mb-0 mt-1 text-success" id="cardFreeSpace">-</h4>
                    </div>
                    <i class="fa-solid fa-circle-check fa-2x text-success"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- SEÇÃO QUANDO CONECTADO -->
    <div id="sectionConnected" class="d-none">
        <div class="card p-4 shadow-sm mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0 text-success">
                    <i class="fa-solid fa-circle-check me-2"></i>Conta Google Drive Conectada e Ativa
                </h5>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-info btn-sm" onclick="testConnection()">
                        <i class="fa-solid fa-plug me-1"></i> Testar Latência
                    </button>
                    <button class="btn btn-outline-light btn-sm" onclick="showLogsModal()">
                        <i class="fa-solid fa-terminal me-1"></i> Ver Logs
                    </button>
                    <button class="btn btn-warning btn-sm text-dark fw-semibold" onclick="triggerSync()">
                        <i class="fa-solid fa-rotate me-1"></i> Sincronizar Agora
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="disconnect()">
                        <i class="fa-solid fa-link-slash me-1"></i> Desconectar
                    </button>
                </div>
            </div>

            <!-- BARRA DE USO DE DISCO -->
            <div class="mb-4">
                <div class="d-flex justify-content-between text-muted small mb-1">
                    <span>Uso do Armazenamento no Google Drive</span>
                    <span id="txtStoragePercent">0%</span>
                </div>
                <div class="progress" style="height: 12px; background-color: #0f172a;">
                    <div id="barStorage" class="progress-bar bg-success" role="progressbar" style="width: 0%;"></div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="p-3 rounded bg-dark border border-secondary">
                        <div class="text-muted small">Pasta de Backup de Volumes:</div>
                        <div class="fw-bold font-monospace text-info mt-1">gdrive:OnliBackup/bareos-storage</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="p-3 rounded bg-dark border border-secondary">
                        <div class="text-muted small">Pasta de Backup do Catálogo PostgreSQL:</div>
                        <div class="fw-bold font-monospace text-info mt-1">gdrive:OnliBackup/catalog-dump</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SEÇÃO DE CONFIGURAÇÃO (QUANDO DESCONECTADO OU PARA RECONFIGURAR) -->
    <div id="sectionSetup">
        <div class="card p-4 shadow-sm mb-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-sliders me-2 text-info"></i>Configuração de Conexão com o Google Drive</h5>
            <p class="text-muted small">
                Selecione o método de autenticação que deseja utilizar para conectar este servidor à sua conta Google Drive:
            </p>

            <ul class="nav nav-tabs mb-4" id="setupTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="sa-tab" data-bs-toggle="tab" data-bs-target="#tab-sa" type="button" role="tab">
                        <i class="fa-solid fa-key me-2"></i>Conta de Serviço (Service Account) <span class="badge bg-success ms-1">Recomendado</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="oauth-tab" data-bs-toggle="tab" data-bs-target="#tab-oauth" type="button" role="tab">
                        <i class="fa-solid fa-user-lock me-2"></i>Token OAuth 2.0 (Conta Pessoal / Workspace)
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="setupTabsContent">
                <!-- ABA 1: SERVICE ACCOUNT -->
                <div class="tab-pane fade show active" id="tab-sa" role="tabpanel">
                    <div class="alert alert-secondary small py-2 mb-3">
                        <i class="fa-solid fa-circle-info me-2 text-info"></i>
                        A Conta de Serviço é ideal para servidores de produção pois <strong>nunca expira</strong> e não requer login interativo em navegador. Basta ativar a <em>Google Drive API</em> no Google Cloud e baixar a chave JSON.
                    </div>
                    <form id="formSA">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Upload do arquivo JSON da Service Account</label>
                            <input type="file" id="saFile" class="form-control" accept=".json">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">OU Cole o conteúdo JSON da chave abaixo:</label>
                            <textarea id="saJsonText" class="form-control font-monospace small" rows="5" placeholder='{"type": "service_account", "project_id": "...", "private_key": "..."}'></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">ID do Drive Compartilhado (Opcional - se usar Shared/Team Drive)</label>
                            <input type="text" id="saTeamDrive" class="form-control" placeholder="ex: 0AAxxxxxxx">
                        </div>
                        <div id="saAlert" class="alert alert-danger d-none py-2 small"></div>
                        <button type="submit" class="btn btn-success fw-semibold" id="btnSaveSA">
                            <i class="fa-solid fa-plug me-2"></i>Conectar e Validar Service Account
                        </button>
                    </form>
                </div>

                <!-- ABA 2: OAUTH TOKEN -->
                <div class="tab-pane fade" id="tab-oauth" role="tabpanel">
                    <div class="alert alert-secondary small py-2 mb-3">
                        <i class="fa-solid fa-circle-info me-2 text-info"></i>
                        Para conectar uma conta Google pessoal ou corporativa sem Service Account:
                        <ol class="mb-0 mt-1 ps-3">
                            <li>Em qualquer computador com rclone, rode: <code>rclone authorize "drive"</code></li>
                            <li>Faça login no Google no navegador que abrir e autorize o rclone.</li>
                            <li>Copie o bloco JSON retornado pelo comando (começando com <code>{"access_token"...</code>) e cole abaixo:</li>
                        </ol>
                    </div>
                    <form id="formOAuth">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Token de Acesso JSON (Paste do rclone authorize) *</label>
                            <textarea id="oauthToken" class="form-control font-monospace small" rows="5" placeholder='{"access_token":"ya29...","token_type":"Bearer","refresh_token":"1//...","expiry":"..."}' required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">ID do Drive Compartilhado (Opcional)</label>
                            <input type="text" id="oauthTeamDrive" class="form-control" placeholder="ex: 0AAxxxxxxx">
                        </div>
                        <div id="oauthAlert" class="alert alert-danger d-none py-2 small"></div>
                        <button type="submit" class="btn btn-success fw-semibold" id="btnSaveOAuth">
                            <i class="fa-solid fa-plug me-2"></i>Conectar com Token OAuth
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL LOGS -->
<div class="modal fade" id="logsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-terminal me-2 text-success"></i>Logs de Sincronização (/var/log/bareos/pbs-sync.log)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="terminal-log" id="logsContent">Carregando logs...</div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let logsModalInstance = null;

document.addEventListener('DOMContentLoaded', () => {
    logsModalInstance = new bootstrap.Modal(document.getElementById('logsModal'));
    checkStatus();
});

function formatBytes(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

async function checkStatus() {
    try {
        const res = await fetch('api.php?action=get_status');
        const data = await res.json();
        const statusBadge = document.getElementById('cardStatus');

        if (data.connected && data.quota) {
            statusBadge.innerHTML = '<span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> Conectado</span>';
            const total = data.quota.total || 0;
            const used = data.quota.used || 0;
            const free = data.quota.free || (total > used ? total - used : 0);

            document.getElementById('cardTotalSpace').textContent = total > 0 ? formatBytes(total) : 'Ilimitado';
            document.getElementById('cardUsedSpace').textContent = formatBytes(used);
            document.getElementById('cardFreeSpace').textContent = total > 0 ? formatBytes(free) : 'Disponível';

            let percent = 0;
            if (total > 0) {
                percent = Math.round((used / total) * 100);
            }
            document.getElementById('txtStoragePercent').textContent = percent + '% (' + formatBytes(used) + ' de ' + formatBytes(total) + ')';
            document.getElementById('barStorage').style.width = percent + '%';

            document.getElementById('sectionConnected').classList.remove('d-none');
            document.getElementById('sectionSetup').classList.add('d-none');
        } else {
            statusBadge.innerHTML = '<span class="badge bg-danger"><i class="fa-solid fa-circle-xmark me-1"></i> Desconectado</span>';
            document.getElementById('cardTotalSpace').textContent = '-';
            document.getElementById('cardUsedSpace').textContent = '-';
            document.getElementById('cardFreeSpace').textContent = '-';

            document.getElementById('sectionConnected').classList.add('d-none');
            document.getElementById('sectionSetup').classList.remove('d-none');
        }
    } catch(err) {
        console.error(err);
    }
}

// Salvar Service Account
document.getElementById('formSA').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSaveSA');
    const alertBox = document.getElementById('saAlert');
    btn.disabled = true;
    alertBox.classList.add('d-none');

    const formData = new FormData();
    formData.append('action', 'save_service_account');
    const file = document.getElementById('saFile').files[0];
    if (file) {
        formData.append('sa_file', file);
    }
    formData.append('sa_json', document.getElementById('saJsonText').value);
    formData.append('team_drive', document.getElementById('saTeamDrive').value);

    try {
        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert("✅ " + data.message);
            location.reload();
        } else {
            alertBox.textContent = data.error;
            alertBox.classList.remove('d-none');
        }
    } catch(err) {
        alertBox.textContent = 'Erro ao enviar dados para o servidor.';
        alertBox.classList.remove('d-none');
    } finally {
        btn.disabled = false;
    }
});

// Salvar OAuth
document.getElementById('formOAuth').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSaveOAuth');
    const alertBox = document.getElementById('oauthAlert');
    btn.disabled = true;
    alertBox.classList.add('d-none');

    const formData = new FormData();
    formData.append('action', 'save_oauth_token');
    formData.append('token_json', document.getElementById('oauthToken').value);
    formData.append('team_drive', document.getElementById('oauthTeamDrive').value);

    try {
        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            alert("✅ " + data.message);
            location.reload();
        } else {
            alertBox.textContent = data.error;
            alertBox.classList.remove('d-none');
        }
    } catch(err) {
        alertBox.textContent = 'Erro ao enviar dados para o servidor.';
        alertBox.classList.remove('d-none');
    } finally {
        btn.disabled = false;
    }
});

async function testConnection() {
    alert("Testando comunicação com o Google Drive...");
    try {
        const res = await fetch('api.php?action=test_connection');
        const data = await res.json();
        if (data.success) {
            alert(`✅ CONEXÃO ESTABELECIDA COM SUCESSO!\n\nLatência: ${data.latency_ms} ms\nEspaço Total: ${formatBytes(data.quota.total)}\nEspaço Utilizado: ${formatBytes(data.quota.used)}`);
        } else {
            alert(`❌ ERRO AO CONECTAR COM O GOOGLE DRIVE:\n\n${data.error}`);
        }
    } catch(err) {
        alert("Erro na requisição de teste.");
    }
}

async function triggerSync() {
    if (!confirm("Deseja iniciar o envio dos backups para o Google Drive agora?")) return;
    try {
        const res = await fetch('api.php?action=trigger_sync', { method: 'POST' });
        const data = await res.json();
        alert(data.message);
        showLogsModal();
    } catch(err) {
        alert("Falha ao iniciar sincronização.");
    }
}

async function disconnect() {
    if (!confirm("Deseja realmente desconectar a conta do Google Drive deste servidor?")) return;
    try {
        const res = await fetch('api.php?action=disconnect', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            location.reload();
        }
    } catch(err) {
        alert("Erro ao desconectar.");
    }
}

async function showLogsModal() {
    logsModalInstance.show();
    try {
        const res = await fetch('../pbs/api.php?action=get_logs');
        const data = await res.json();
        if (data.success) {
            document.getElementById('logsContent').textContent = data.logs;
        }
    } catch(err) {}
}

async function logout() {
    await fetch('api.php?action=logout');
    location.reload();
}
</script>
</body>
</html>
