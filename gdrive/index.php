<?php
session_start();
$AUTH_PASS = '$R74g20m@2080';
$is_authenticated = !empty($_SESSION['authenticated']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google Drive Manager — OnliBackup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #0b1120;
            color: #f8fafc;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            min-height: 100vh;
        }
        .navbar {
            background-color: #0f172a;
            border-bottom: 1px solid #1e293b;
        }
        .card {
            background-color: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            color: #f8fafc;
        }
        .nav-tabs {
            border-bottom: 1px solid #334155;
        }
        .nav-tabs .nav-link {
            color: #94a3b8;
            border: none;
            padding: 12px 20px;
            font-weight: 500;
        }
        .nav-tabs .nav-link.active {
            color: #38bdf8;
            background-color: transparent;
            border-bottom: 2px solid #38bdf8;
        }
        .form-control, .form-select {
            background-color: #0f172a;
            border: 1px solid #334155;
            color: #f8fafc;
        }
        .form-control:focus, .form-select:focus {
            background-color: #0f172a;
            border-color: #38bdf8;
            color: #f8fafc;
            box-shadow: 0 0 0 0.25rem rgba(56, 189, 248, 0.25);
        }
        .terminal-log {
            background-color: #020617;
            color: #38bdf8;
            font-family: monospace;
            padding: 15px;
            border-radius: 8px;
            height: 380px;
            overflow-y: auto;
            white-space: pre-wrap;
            border: 1px solid #1e293b;
            font-size: 0.85rem;
        }
        .pulse {
            animation: pulse-animation 2s infinite;
        }
        @keyframes pulse-animation {
            0% { box-shadow: 0 0 0 0 rgba(56, 189, 248, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(56, 189, 248, 0); }
            100% { box-shadow: 0 0 0 0 rgba(56, 189, 248, 0); }
        }
        .google-btn {
            background-color: #4285F4;
            color: white;
            transition: all 0.2s ease;
        }
        .google-btn:hover {
            background-color: #3367D6;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(66, 133, 244, 0.3);
        }
    </style>
</head>
<body>

<?php if (!$is_authenticated): ?>
<!-- TELA DE LOGIN -->
<div class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card p-4 shadow-lg" style="width: 100%; max-width: 420px;">
        <div class="text-center mb-4">
            <i class="fa-brands fa-google-drive fa-3x text-success mb-2"></i>
            <h4 class="fw-bold">Google Drive Manager</h4>
            <p class="text-muted small">OnliBackup — Servidor bareos01</p>
        </div>
        <form id="loginForm">
            <div class="mb-3">
                <label class="form-label small fw-semibold">Senha de Acesso</label>
                <div class="input-group">
                    <span class="input-group-text bg-dark border-secondary text-secondary"><i class="fa-solid fa-lock"></i></span>
                    <input type="password" id="loginPass" class="form-control" placeholder="Digite a senha administrativa" required autofocus>
                </div>
            </div>
            <div id="loginErr" class="alert alert-danger py-2 small d-none"></div>
            <button type="submit" class="btn btn-primary w-100 fw-semibold">
                <i class="fa-solid fa-arrow-right-to-bracket me-2"></i>Entrar
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
            <a href="/" class="btn btn-outline-warning btn-sm">
                <i class="fa-solid fa-home me-1"></i> Portal
            </a>
            <a href="/pbs/" class="btn btn-outline-info btn-sm">
                <i class="fa-solid fa-cloud-arrow-up me-1"></i> PBS Nuvem
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
                <div>
                    <h5 class="fw-bold mb-0 text-success">
                        <i class="fa-solid fa-circle-check me-2"></i>Conta Google Drive Conectada e Ativa
                    </h5>
                    <div class="text-muted small mt-1" id="authModeLabel">Modo de Autenticação: Ativo</div>
                </div>
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
                <div class="progress" style="height: 14px; background-color: #0f172a; border-radius: 8px;">
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

    <!-- SEÇÃO DE CONFIGURAÇÃO (QUANDO DESCONECTADO) -->
    <div id="sectionSetup">
        <div class="card p-4 shadow-sm mb-4">
            <h5 class="fw-bold mb-3"><i class="fa-solid fa-sliders me-2 text-info"></i>Configuração de Conexão com o Google Drive</h5>
            <p class="text-muted small">
                Selecione o método de autenticação que deseja utilizar para conectar este servidor à sua conta Google Drive:
            </p>

            <ul class="nav nav-tabs mb-4" id="setupTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="browser-tab" data-bs-toggle="tab" data-bs-target="#tab-browser" type="button" role="tab">
                        <i class="fa-brands fa-google me-2 text-primary"></i>Login no Navegador <span class="badge bg-primary ms-1">1-Clique</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="sa-tab" data-bs-toggle="tab" data-bs-target="#tab-sa" type="button" role="tab">
                        <i class="fa-solid fa-key me-2 text-success"></i>Conta de Serviço (JSON) <span class="badge bg-success ms-1">24/7 Produção</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="oauth-tab" data-bs-toggle="tab" data-bs-target="#tab-oauth" type="button" role="tab">
                        <i class="fa-solid fa-terminal me-2 text-warning"></i>Token Manual (CLI)
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="setupTabsContent">
                <!-- ABA 1: LOGIN NO NAVEGADOR (1-CLIQUE) -->
                <div class="tab-pane fade show active" id="tab-browser" role="tabpanel">
                    <div class="p-4 rounded border border-primary bg-primary bg-opacity-10 mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="fw-bold text-white mb-2">
                                    <i class="fa-brands fa-google text-primary me-2"></i>Autenticação Rápida com Sua Conta Google
                                </h5>
                                <p class="text-secondary small mb-3">
                                    Conecte diretamente pela Web. Ao clicar no botão, uma janela oficial do Google abrirá para você fazer login com seu Gmail ou Google Workspace e autorizar o acesso. A Web GUI cuidará de todo o resto automaticamente!
                                </p>
                                <div class="mb-3" style="max-width: 480px;">
                                    <label class="form-label small text-muted">ID do Drive Compartilhado (Opcional - se usar Team/Shared Drive corporativo):</label>
                                    <input type="text" id="browserTeamDrive" class="form-control form-control-sm" placeholder="ex: 0AAxxxxxxx">
                                </div>
                                <button type="button" class="btn google-btn px-4 py-2 fw-bold fs-6" id="btnStartBrowserAuth" onclick="startBrowserAuth()">
                                    <i class="fa-brands fa-google me-2"></i>Conectar com o Google no Navegador
                                </button>
                            </div>
                            <div class="col-md-4 text-center d-none d-md-block">
                                <i class="fa-solid fa-cloud-arrow-up fa-5x text-primary opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ABA 2: SERVICE ACCOUNT JSON -->
                <div class="tab-pane fade" id="tab-sa" role="tabpanel">
                    <div class="alert alert-secondary small py-2 mb-3">
                        <i class="fa-solid fa-circle-info me-2 text-success"></i>
                        A Conta de Serviço é ideal para servidores de produção corporativos pois <strong>nunca expira</strong> e não exige login periódico. Basta ativar a <em>Google Drive API</em> no Google Cloud Console e baixar a chave JSON.
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
                            <label class="form-label small fw-semibold">ID do Drive Compartilhado (Opcional)</label>
                            <input type="text" id="saTeamDrive" class="form-control" placeholder="ex: 0AAxxxxxxx">
                        </div>
                        <div id="saAlert" class="alert alert-danger d-none py-2 small"></div>
                        <button type="submit" class="btn btn-success fw-semibold" id="btnSaveSA">
                            <i class="fa-solid fa-plug me-2"></i>Conectar e Validar Service Account
                        </button>
                    </form>
                </div>

                <!-- ABA 3: OAUTH TOKEN MANUAL -->
                <div class="tab-pane fade" id="tab-oauth" role="tabpanel">
                    <div class="alert alert-secondary small py-2 mb-3">
                        <i class="fa-solid fa-circle-info me-2 text-warning"></i>
                        Se preferir gerar o token via linha de comando no seu computador pessoal:
                        <ol class="mb-0 mt-1 ps-3">
                            <li>Rode no seu terminal: <code>rclone authorize "drive"</code></li>
                            <li>Autorize no navegador e copie o bloco JSON gerado.</li>
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
                        <button type="submit" class="btn btn-warning fw-semibold text-dark" id="btnSaveOAuth">
                            <i class="fa-solid fa-plug me-2"></i>Conectar com Token OAuth
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL ASSISTENTE DE LOGIN DO NAVEGADOR -->
<div class="modal fade" id="modalBrowserAuth" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="background-color: #1e293b; border: 1px solid #334155; color: #f8fafc;">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold">
                    <i class="fa-brands fa-google text-primary me-2"></i>Assistente de Conexão com Google Drive
                </h5>
                <button type="button" class="btn-close btn-close-white" onclick="cancelBrowserAuth()"></button>
            </div>
            <div class="modal-body p-4">
                <!-- PASSO 1: AGUARDANDO AUTORIZAÇÃO -->
                <div id="authStepWaiting" class="text-center py-3">
                    <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status"></div>
                    <h5 class="fw-bold mb-2">Aguardando Autorização na Página do Google...</h5>
                    <p class="text-muted small mb-4">
                        Uma nova janela do Google foi aberta para você autorizar o acesso à sua conta.<br>
                        Assim que você clicar em <strong>"Permitir"</strong>, esta tela detectará automaticamente a conexão.
                    </p>

                    <div class="mb-4">
                        <a id="btnReopenGoogle" href="#" target="_blank" class="btn btn-outline-primary btn-sm">
                            <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> A página não abriu? Clique aqui para abrir a tela do Google
                        </a>
                    </div>

                    <!-- AJUDANTE DE RELAY CASO NÃO REDIRECIONE AUTOMATICAMENTE -->
                    <div class="p-3 rounded bg-dark border border-secondary text-start mt-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fa-solid fa-lightbulb text-warning fs-5 me-2"></i>
                            <span class="fw-semibold small">Se a aba final do Google exibir "Não é possível acessar esse site":</span>
                        </div>
                        <p class="text-muted small mb-2">
                            Isso é normal da segurança do Google ao rodar em rede local! Basta <strong>copiar a URL inteira</strong> que ficou na barra de endereços daquela aba do navegador e colar abaixo:
                        </p>
                        <div class="input-group">
                            <input type="text" id="relayUrlInput" class="form-control form-control-sm font-monospace" placeholder="Cole aqui a URL que começa com http://127.0.0.1:53682/...">
                            <button class="btn btn-primary btn-sm px-3 fw-semibold" type="button" id="btnRelay" onclick="submitRelayCallback()">
                                <i class="fa-solid fa-circle-check me-1"></i> Validar e Conectar
                            </button>
                        </div>
                        <div id="relayMsg" class="small mt-2 d-none"></div>
                    </div>
                </div>

                <!-- PASSO 2: SUCESSO -->
                <div id="authStepSuccess" class="text-center py-4 d-none">
                    <i class="fa-solid fa-circle-check text-success fa-4x mb-3"></i>
                    <h4 class="fw-bold text-success mb-2">Google Drive Conectado com Sucesso!</h4>
                    <p class="text-muted small mb-0">Atualizando painel de controle...</p>
                </div>

                <!-- PASSO 3: ERRO -->
                <div id="authStepError" class="text-center py-4 d-none">
                    <i class="fa-solid fa-circle-xmark text-danger fa-4x mb-3"></i>
                    <h5 class="fw-bold text-danger mb-2">Falha na Autorização</h5>
                    <p class="text-muted small mb-3" id="authErrorTxt">Não foi possível concluir a autenticação com o Google.</p>
                    <button class="btn btn-outline-secondary btn-sm" onclick="cancelBrowserAuth()">Fechar</button>
                </div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="cancelBrowserAuth()">Cancelar Conexão</button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL LOGS -->
<div class="modal fade" id="logsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content" style="background-color: #1e293b; border: 1px solid #334155; color: #f8fafc;">
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
let browserAuthModalInstance = null;
let pollTimer = null;

document.addEventListener('DOMContentLoaded', () => {
    logsModalInstance = new bootstrap.Modal(document.getElementById('logsModal'));
    browserAuthModalInstance = new bootstrap.Modal(document.getElementById('modalBrowserAuth'));
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

            const authMode = data.auth_mode === 'service_account' ? 'Conta de Serviço (Service Account)' : 'OAuth 2.0 (Navegador/Pessoal)';
            document.getElementById('authModeLabel').textContent = 'Modo de Autenticação: ' + authMode;

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

// INICIAR LOGIN NO NAVEGADOR
async function startBrowserAuth() {
    const teamDrive = document.getElementById('browserTeamDrive').value.trim();
    const btn = document.getElementById('btnStartBrowserAuth');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Iniciando Google OAuth...';

    // Reset modal
    document.getElementById('authStepWaiting').classList.remove('d-none');
    document.getElementById('authStepSuccess').classList.add('d-none');
    document.getElementById('authStepError').classList.add('d-none');
    document.getElementById('relayUrlInput').value = '';
    document.getElementById('relayMsg').classList.add('d-none');

    try {
        const formData = new FormData();
        formData.append('action', 'start_browser_auth');
        formData.append('team_drive', teamDrive);

        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success && data.auth_url) {
            document.getElementById('btnReopenGoogle').href = data.auth_url;
            browserAuthModalInstance.show();

            // Abrir popup/aba com a tela do Google
            window.open(data.auth_url, '_blank');

            // Iniciar polling
            startPollingAuth();
        } else {
            alert("Erro ao iniciar autenticação: " + (data.error || 'Falha desconhecida'));
        }
    } catch(err) {
        alert("Erro de comunicação com o servidor.");
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-brands fa-google me-2"></i>Conectar com o Google no Navegador';
    }
}

function startPollingAuth() {
    if (pollTimer) clearInterval(pollTimer);
    pollTimer = setInterval(async () => {
        try {
            const res = await fetch('api.php?action=check_browser_auth');
            const data = await res.json();

            if (data.status === 'completed') {
                clearInterval(pollTimer);
                document.getElementById('authStepWaiting').classList.add('d-none');
                document.getElementById('authStepSuccess').classList.remove('d-none');
                setTimeout(() => {
                    browserAuthModalInstance.hide();
                    location.reload();
                }, 2000);
            } else if (data.status === 'error') {
                clearInterval(pollTimer);
                document.getElementById('authStepWaiting').classList.add('d-none');
                document.getElementById('authStepError').classList.remove('d-none');
                document.getElementById('authErrorTxt').textContent = data.error || 'Erro na autorização do Google.';
            }
        } catch(err) {
            console.error("Polling error:", err);
        }
    }, 2000);
}

// RELAY DO CALLBACK
async function submitRelayCallback() {
    const rawInput = document.getElementById('relayUrlInput').value.trim();
    const msgBox = document.getElementById('relayMsg');
    const btn = document.getElementById('btnRelay');

    if (!rawInput) {
        msgBox.className = 'small mt-2 text-danger';
        msgBox.textContent = 'Cole a URL da barra de endereços antes de enviar.';
        msgBox.classList.remove('d-none');
        return;
    }

    btn.disabled = true;
    msgBox.className = 'small mt-2 text-info';
    msgBox.textContent = 'Transmitindo callback para o Google Drive...';
    msgBox.classList.remove('d-none');

    try {
        const formData = new FormData();
        formData.append('action', 'relay_callback');
        formData.append('callback_input', rawInput);

        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();

        if (data.success) {
            msgBox.className = 'small mt-2 text-success fw-bold';
            msgBox.textContent = '✅ Conectado com sucesso! Atualizando painel...';
            setTimeout(() => {
                browserAuthModalInstance.hide();
                location.reload();
            }, 1500);
        } else {
            msgBox.className = 'small mt-2 text-danger';
            msgBox.textContent = 'Erro ao processar: ' + (data.error || 'Tente novamente');
            btn.disabled = false;
        }
    } catch(err) {
        msgBox.className = 'small mt-2 text-danger';
        msgBox.textContent = 'Erro ao conectar ao servidor.';
        btn.disabled = false;
    }
}

async function cancelBrowserAuth() {
    if (pollTimer) clearInterval(pollTimer);
    try {
        await fetch('api.php?action=cancel_browser_auth', { method: 'POST' });
    } catch(e) {}
    browserAuthModalInstance.hide();
}

// SALVAR SERVICE ACCOUNT
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

// SALVAR OAUTH MANUAL
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
