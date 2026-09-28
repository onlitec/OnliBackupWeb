<?php
session_start();
$is_auth = !empty($_SESSION['authenticated']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OnliBackup — Proxmox Backup Server (PBS) Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-bg: #0f172a;
            --card-bg: #1e293b;
            --accent-color: #38bdf8;
            --border-color: #334155;
            --text-light: #f8fafc;
            --text-muted: #94a3b8;
        }
        body {
            background-color: var(--primary-bg);
            color: var(--text-light);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
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
            box-shadow: 0 0 0 0.25rem rgba(56, 189, 248, 0.25);
        }
        .table {
            color: var(--text-light);
            border-color: var(--border-color);
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
        .badge-status {
            font-size: 0.85rem;
            padding: 6px 12px;
        }
        .btn-accent {
            background-color: #0284c7;
            color: #fff;
            border: none;
        }
        .btn-accent:hover {
            background-color: #0369a1;
            color: #fff;
        }
    </style>
</head>
<body>

<?php if (!$is_auth): ?>
<!-- TELA DE LOGIN -->
<div class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">
    <div class="card p-4 shadow" style="max-width: 420px; width: 100%;">
        <div class="text-center mb-4">
            <i class="fa-solid fa-cloud-arrow-up fa-3x text-info mb-3"></i>
            <h4 class="fw-bold">OnliBackup PBS Manager</h4>
            <p class="text-muted small">Gerenciamento de Proxmox Backup Servers</p>
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
            <button type="submit" class="btn btn-accent w-100 py-2 fw-semibold">
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

<!-- TELA PRINCIPAL DO GERENCIADOR -->
<nav class="navbar navbar-expand-lg navbar-dark px-4 py-3">
    <div class="container-fluid">
        <a class="navbar-brand d-flex align-items-center" href="index.php">
            <i class="fa-solid fa-cloud-arrow-up text-info fs-3 me-2"></i>
            <div>
                <span class="fw-bold fs-5 text-white">OnliBackup</span>
                <span class="badge bg-primary ms-2" style="font-size: 0.75rem;">PBS Manager</span>
            </div>
        </a>
        <div class="d-flex align-items-center gap-3">
            <a href="/bareos-webui/" target="_blank" class="btn btn-outline-info btn-sm">
                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i> Abrir Bareos WebUI
            </a>
            <button class="btn btn-outline-danger btn-sm" onclick="logout()">
                <i class="fa-solid fa-right-from-bracket me-1"></i> Sair
            </button>
        </div>
    </div>
</nav>

<div class="container-fluid px-4 py-4">
    <!-- CARDS DE MÉTRICAS -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Servidores PBS</div>
                        <h3 class="fw-bold mb-0" id="metricTotal">0</h3>
                    </div>
                    <i class="fa-solid fa-server fa-2x text-primary"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Servidores Ativos</div>
                        <h3 class="fw-bold mb-0 text-success" id="metricActive">0</h3>
                    </div>
                    <i class="fa-solid fa-circle-check fa-2x text-success"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">Staging Local (sda1)</div>
                        <h3 class="fw-bold mb-0 text-info">90 GB <span class="fs-6 text-muted">livres</span></h3>
                    </div>
                    <i class="fa-solid fa-hard-drive fa-2x text-info"></i>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small">PBS Client Version</div>
                        <h3 class="fw-bold mb-0 text-warning">4.2.6</h3>
                    </div>
                    <i class="fa-solid fa-shield-halved fa-2x text-warning"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- BARRA DE AÇÕES -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-network-wired me-2 text-info"></i>Proxmox Backup Servers Configurados</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-light btn-sm" onclick="showLogsModal()">
                <i class="fa-solid fa-file-lines me-1"></i> Ver Logs de Sincronismo
            </button>
            <button class="btn btn-warning btn-sm text-dark fw-semibold" onclick="triggerManualSync()">
                <i class="fa-solid fa-rotate me-1"></i> Sincronizar Agora
            </button>
            <button class="btn btn-accent btn-sm fw-semibold" onclick="openServerModal()">
                <i class="fa-solid fa-plus me-1"></i> Adicionar Servidor PBS
            </button>
        </div>
    </div>

    <!-- TABELA DE SERVIDORES -->
    <div class="card shadow-sm mb-4">
        <div class="table-responsive">
            <table class="table table-dark table-hover align-middle mb-0">
                <thead class="table-secondary">
                    <tr>
                        <th class="ps-4">Servidor / Nome Amigável</th>
                        <th>Destino (Host:Porta:Datastore)</th>
                        <th>Autenticação</th>
                        <th>Fingerprint TLS</th>
                        <th>Ativo</th>
                        <th class="text-end pe-4">Ações</th>
                    </tr>
                </thead>
                <tbody id="serversTableBody">
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">Carregando servidores...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SEÇÃO DE INFORMAÇÕES ARQUITETURA -->
    <div class="card p-4 shadow-sm">
        <h6 class="fw-bold text-info mb-2"><i class="fa-solid fa-circle-info me-2"></i>Como Funciona a Orquestração</h6>
        <p class="text-muted small mb-1">
            • O <strong>Bareos Director</strong> executa os backups dos clientes Windows (HikCentral) e armazena os volumes temporários em <code>/backup/storage</code>.<br>
            • Ao concluir o job com sucesso, o hook <code>RunScript</code> chama <code>/usr/local/bin/sync-to-pbs.sh</code>.<br>
            • O script exporta o catálogo PostgreSQL e executa o <strong>proxmox-backup-client</strong> para cada servidor PBS marcado como <strong>Ativo</strong> acima, enviando snapshots deduplicados pela porta <strong>HTTPS :8007</strong> para os respectivos Datastores remotos.
        </p>
    </div>
</div>

<!-- MODAL ADICIONAR / EDITAR SERVIDOR -->
<div class="modal fade" id="serverModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold" id="serverModalTitle"><i class="fa-solid fa-server me-2 text-info"></i>Configurar Servidor PBS</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="serverForm">
                <div class="modal-body">
                    <input type="hidden" id="srvId">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Nome Amigável do Servidor *</label>
                            <input type="text" id="srvName" class="form-control" placeholder="ex: PBS Nuvem Principal (10 TB)" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Porta *</label>
                            <input type="number" id="srvPort" class="form-control" value="8007" required>
                        </div>
                        <div class="col-md-7">
                            <label class="form-label small fw-semibold">Host / IP / FQDN *</label>
                            <input type="text" id="srvHost" class="form-control" placeholder="ex: pbs.seudominio.com.br ou 172.x.x.x" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small fw-semibold">Nome do Datastore *</label>
                            <input type="text" id="srvDatastore" class="form-control" placeholder="ex: datastore-10tb" required>
                        </div>

                        <div class="col-12 mt-3">
                            <label class="form-label small fw-semibold">Método de Autenticação *</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="authType" id="authTypeToken" value="token" checked onchange="toggleAuthFields()">
                                    <label class="form-check-label" for="authTypeToken">
                                        <strong>API Token</strong> <span class="badge bg-success ms-1">Recomendado</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="authType" id="authTypeUser" value="user" onchange="toggleAuthFields()">
                                    <label class="form-check-label" for="authTypeUser">
                                        Usuário e Senha tradicional
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold" id="lblUser">Usuário / Realm *</label>
                            <input type="text" id="srvUser" class="form-control" placeholder="ex: backup@pbs" required>
                        </div>
                        <div class="col-md-6" id="divTokenId">
                            <label class="form-label small fw-semibold">Token ID *</label>
                            <input type="text" id="srvTokenId" class="form-control" placeholder="ex: bareos">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-semibold" id="lblSecret">Token Secret / Senha *</label>
                            <div class="input-group">
                                <input type="password" id="srvSecret" class="form-control" placeholder="Chave de autenticação" required>
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePassVisibility('srvSecret')">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label small fw-semibold">Fingerprint TLS (SHA-256)</label>
                            <input type="text" id="srvFingerprint" class="form-control" placeholder="ex: 12:34:56:78:90:ab:cd:...">
                            <div class="form-text text-muted">
                                Copie no Proxmox Backup Server em <strong>Dashboard &gt; Show Fingerprint</strong> para validar o certificado SSL.
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" id="srvEnabled" checked>
                                <label class="form-check-label" for="srvEnabled">Ativar sincronização automática para este servidor</label>
                            </div>
                        </div>
                    </div>

                    <!-- ÁREA DE RESULTADO DE TESTE -->
                    <div id="testResultBox" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer border-secondary d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-info" onclick="testModalServer()">
                        <i class="fa-solid fa-plug me-1"></i> Testar Conexão Agora
                    </button>
                    <div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-accent" id="btnSaveServer">Salvar Configuração</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL SNAPSHOTS -->
<div class="modal fade" id="snapshotsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-boxes-stacked me-2 text-info"></i>Snapshots no Datastore Remoto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="snapshotsLoading" class="text-center py-4">
                    <div class="spinner-border text-info" role="status"></div>
                    <div class="text-muted mt-2">Consultando snapshots no PBS...</div>
                </div>
                <div id="snapshotsContent" class="d-none">
                    <div class="table-responsive">
                        <table class="table table-dark table-striped align-middle small mb-0">
                            <thead>
                                <tr>
                                    <th>Backup ID / Tipo</th>
                                    <th>Data / Hora do Snapshot</th>
                                    <th>Tamanho</th>
                                    <th>Arquivos</th>
                                </tr>
                            </thead>
                            <tbody id="snapshotsTableBody"></tbody>
                        </table>
                    </div>
                </div>
                <div id="snapshotsError" class="alert alert-danger d-none"></div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL LOGS -->
<div class="modal fade" id="logsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header border-secondary">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-terminal me-2 text-success"></i>Logs de Sincronismo (/var/log/bareos/pbs-sync.log)</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="terminal-log" id="logsContent">Carregando logs...</div>
            </div>
            <div class="modal-footer border-secondary">
                <button type="button" class="btn btn-outline-light btn-sm" onclick="fetchLogs()">
                    <i class="fa-solid fa-arrows-rotate me-1"></i> Atualizar Agora
                </button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let serversCache = [];
let serverModalInstance = null;
let snapshotsModalInstance = null;
let logsModalInstance = null;
let logPollTimer = null;

document.addEventListener('DOMContentLoaded', () => {
    serverModalInstance = new bootstrap.Modal(document.getElementById('serverModal'));
    snapshotsModalInstance = new bootstrap.Modal(document.getElementById('snapshotsModal'));
    logsModalInstance = new bootstrap.Modal(document.getElementById('logsModal'));
    loadServers();
});

async function loadServers() {
    try {
        const res = await fetch('api.php?action=get_servers');
        const data = await res.json();
        if (data.success) {
            serversCache = data.servers || [];
            renderServers(serversCache);
        }
    } catch(e) {
        console.error("Erro ao carregar servidores", e);
    }
}

function renderServers(servers) {
    const tbody = document.getElementById('serversTableBody');
    document.getElementById('metricTotal').textContent = servers.length;
    document.getElementById('metricActive').textContent = servers.filter(s => s.enabled).length;

    if (servers.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center py-5">
                    <i class="fa-solid fa-cloud-arrow-up fa-3x text-muted mb-3 d-block"></i>
                    <h6 class="fw-bold">Nenhum servidor PBS configurado</h6>
                    <p class="text-muted small mb-3">Adicione o seu PBS Nuvem de 10 TB para habilitar o envio dos backups.</p>
                    <button class="btn btn-accent btn-sm" onclick="openServerModal()">
                        <i class="fa-solid fa-plus me-1"></i> Adicionar Servidor Agora
                    </button>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = servers.map(s => {
        const repoStr = `${s.host}:${s.port} &gt; <strong>${s.datastore}</strong>`;
        const authBadge = s.auth_type === 'token' 
            ? `<span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-key me-1"></i>API Token (${s.user}!${s.token_id})</span>`
            : `<span class="badge bg-secondary"><i class="fa-solid fa-user me-1"></i>Usuário (${s.user})</span>`;
        const fpShort = s.fingerprint ? `<code>${s.fingerprint.substring(0, 14)}...</code>` : '<span class="text-muted small">Não informado</span>';

        return `
            <tr>
                <td class="ps-4">
                    <div class="fw-bold">${s.name}</div>
                    <div class="text-muted small">${s.id}</div>
                </td>
                <td>${repoStr}</td>
                <td>${authBadge}</td>
                <td>${fpShort}</td>
                <td>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" ${s.enabled ? 'checked' : ''} onchange="toggleServer('${s.id}')">
                    </div>
                </td>
                <td class="text-end pe-4">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-info" title="Testar Conexão" onclick="testServer('${s.id}')">
                            <i class="fa-solid fa-plug"></i>
                        </button>
                        <button class="btn btn-outline-light" title="Ver Snapshots no Datastore" onclick="viewSnapshots('${s.id}')">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </button>
                        <button class="btn btn-outline-primary" title="Editar" onclick="editServer('${s.id}')">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <button class="btn btn-outline-danger" title="Excluir" onclick="deleteServer('${s.id}', '${s.name}')">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function openServerModal(server = null) {
    document.getElementById('serverForm').reset();
    document.getElementById('testResultBox').classList.add('d-none');
    document.getElementById('testResultBox').innerHTML = '';

    if (server) {
        document.getElementById('serverModalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square me-2 text-info"></i>Editar Servidor PBS';
        document.getElementById('srvId').value = server.id;
        document.getElementById('srvName').value = server.name;
        document.getElementById('srvPort').value = server.port || 8007;
        document.getElementById('srvHost').value = server.host;
        document.getElementById('srvDatastore').value = server.datastore;
        if (server.auth_type === 'user') {
            document.getElementById('authTypeUser').checked = true;
        } else {
            document.getElementById('authTypeToken').checked = true;
        }
        document.getElementById('srvUser').value = server.user;
        document.getElementById('srvTokenId').value = server.token_id || '';
        document.getElementById('srvSecret').value = server.has_secret ? '••••••••••••' : '';
        document.getElementById('srvFingerprint').value = server.fingerprint || '';
        document.getElementById('srvEnabled').checked = server.enabled;
    } else {
        document.getElementById('serverModalTitle').innerHTML = '<i class="fa-solid fa-plus me-2 text-info"></i>Adicionar Servidor PBS';
        document.getElementById('srvId').value = '';
        document.getElementById('srvPort').value = 8007;
        document.getElementById('authTypeToken').checked = true;
        document.getElementById('srvEnabled').checked = true;
    }
    toggleAuthFields();
    serverModalInstance.show();
}

function toggleAuthFields() {
    const isToken = document.getElementById('authTypeToken').checked;
    document.getElementById('divTokenId').style.display = isToken ? 'block' : 'none';
    document.getElementById('lblUser').textContent = isToken ? 'Usuário do Token (ex: backup@pbs) *' : 'Usuário do PBS (ex: root@pam ou backup@pbs) *';
    document.getElementById('lblSecret').textContent = isToken ? 'Secret do API Token *' : 'Senha do Usuário *';
}

function togglePassVisibility(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

function editServer(id) {
    const server = serversCache.find(s => s.id === id);
    if (server) {
        openServerModal(server);
    }
}

document.getElementById('serverForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    const btn = document.getElementById('btnSaveServer');
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'save_server');
    formData.append('id', document.getElementById('srvId').value);
    formData.append('name', document.getElementById('srvName').value);
    formData.append('host', document.getElementById('srvHost').value);
    formData.append('port', document.getElementById('srvPort').value);
    formData.append('datastore', document.getElementById('srvDatastore').value);
    formData.append('auth_type', document.querySelector('input[name="authType"]:checked').value);
    formData.append('user', document.getElementById('srvUser').value);
    formData.append('token_id', document.getElementById('srvTokenId').value);
    formData.append('secret', document.getElementById('srvSecret').value);
    formData.append('fingerprint', document.getElementById('srvFingerprint').value);
    formData.append('enabled', document.getElementById('srvEnabled').checked ? '1' : '0');

    try {
        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            serverModalInstance.hide();
            loadServers();
        } else {
            alert("Erro ao salvar: " + data.error);
        }
    } catch(err) {
        alert("Falha na comunicação com o servidor.");
    } finally {
        btn.disabled = false;
    }
});

async function toggleServer(id) {
    const formData = new FormData();
    formData.append('action', 'toggle_server');
    formData.append('id', id);
    await fetch('api.php', { method: 'POST', body: formData });
    loadServers();
}

async function deleteServer(id, name) {
    if (!confirm(`Deseja realmente remover o servidor "${name}"?`)) return;
    const formData = new FormData();
    formData.append('action', 'delete_server');
    formData.append('id', id);
    const res = await fetch('api.php', { method: 'POST', body: formData });
    const data = await res.json();
    if (data.success) {
        loadServers();
    } else {
        alert(data.error);
    }
}

async function testServer(id) {
    const formData = new FormData();
    formData.append('action', 'test_connection');
    formData.append('id', id);
    
    // Alerta temporário
    const statusToast = document.createElement('div');
    statusToast.className = 'position-fixed bottom-0 end-0 p-3';
    statusToast.style.zIndex = '9999';
    statusToast.innerHTML = `
        <div class="toast show bg-dark text-white border-secondary" role="alert">
            <div class="toast-body d-flex align-items-center">
                <div class="spinner-border spinner-border-sm text-info me-2"></div>
                Testando conectividade com o PBS...
            </div>
        </div>
    `;
    document.body.appendChild(statusToast);

    try {
        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        statusToast.remove();
        if (data.success) {
            alert(`✅ CONEXÃO ESTABELECIDA COM SUCESSO!\n\nRepositório: ${data.repo}\nLatência: ${data.latency_ms} ms\n\nResposta do PBS:\n${JSON.stringify(data.status || data.raw, null, 2)}`);
        } else {
            alert(`❌ FALHA AO CONECTAR COM O PBS:\n\nRepositório: ${data.repo}\nLatência: ${data.latency_ms} ms\n\nErro:\n${data.error}`);
        }
    } catch(e) {
        statusToast.remove();
        alert("Erro na requisição de teste.");
    }
}

async function testModalServer() {
    const box = document.getElementById('testResultBox');
    box.classList.remove('d-none');
    box.innerHTML = '<div class="alert alert-info py-2 small mb-0"><i class="fa-solid fa-spinner fa-spin me-2"></i>Testando comunicação com o PBS...</div>';

    const formData = new FormData();
    formData.append('action', 'test_connection');
    formData.append('id', document.getElementById('srvId').value);
    formData.append('host', document.getElementById('srvHost').value);
    formData.append('port', document.getElementById('srvPort').value);
    formData.append('datastore', document.getElementById('srvDatastore').value);
    formData.append('auth_type', document.querySelector('input[name="authType"]:checked').value);
    formData.append('user', document.getElementById('srvUser').value);
    formData.append('token_id', document.getElementById('srvTokenId').value);
    formData.append('secret', document.getElementById('srvSecret').value);
    formData.append('fingerprint', document.getElementById('srvFingerprint').value);

    try {
        const res = await fetch('api.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
            box.innerHTML = `<div class="alert alert-success py-2 small mb-0">
                <strong><i class="fa-solid fa-circle-check me-1"></i> Sucesso!</strong> Conexão estabelecida (${data.latency_ms} ms).
            </div>`;
        } else {
            box.innerHTML = `<div class="alert alert-danger py-2 small mb-0">
                <strong><i class="fa-solid fa-triangle-exclamation me-1"></i> Erro:</strong> ${data.error}
            </div>`;
        }
    } catch(err) {
        box.innerHTML = '<div class="alert alert-danger py-2 small mb-0">Falha ao testar conexão.</div>';
    }
}

async function viewSnapshots(id) {
    document.getElementById('snapshotsLoading').classList.remove('d-none');
    document.getElementById('snapshotsContent').classList.add('d-none');
    document.getElementById('snapshotsError').classList.add('d-none');
    snapshotsModalInstance.show();

    try {
        const res = await fetch(`api.php?action=get_snapshots&id=${id}`);
        const data = await res.json();
        document.getElementById('snapshotsLoading').classList.add('d-none');
        if (data.success && Array.isArray(data.snapshots)) {
            const tbody = document.getElementById('snapshotsTableBody');
            if (data.snapshots.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-muted">Nenhum snapshot encontrado neste datastore.</td></tr>';
            } else {
                tbody.innerHTML = data.snapshots.map(s => {
                    const dateStr = s['backup-time'] ? new Date(s['backup-time'] * 1000).toLocaleString('pt-BR') : '-';
                    const sizeStr = s.size ? (s.size / (1024*1024*1024)).toFixed(2) + ' GB' : '-';
                    const filesStr = (s.files || []).map(f => f.filename).join(', ') || '-';
                    return `
                        <tr>
                            <td><strong>${s['backup-type']}/${s['backup-id']}</strong></td>
                            <td>${dateStr}</td>
                            <td>${sizeStr}</td>
                            <td><code>${filesStr}</code></td>
                        </tr>
                    `;
                }).join('');
            }
            document.getElementById('snapshotsContent').classList.remove('d-none');
        } else {
            document.getElementById('snapshotsError').textContent = data.error || 'Erro ao consultar snapshots';
            document.getElementById('snapshotsError').classList.remove('d-none');
        }
    } catch(err) {
        document.getElementById('snapshotsLoading').classList.add('d-none');
        document.getElementById('snapshotsError').textContent = 'Falha de comunicação.';
        document.getElementById('snapshotsError').classList.remove('d-none');
    }
}

async function triggerManualSync() {
    if (!confirm("Deseja iniciar a sincronização imediata dos backups para todos os servidores PBS ativos?")) return;
    try {
        const res = await fetch('api.php?action=trigger_sync', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
            alert(data.message);
            showLogsModal();
        } else {
            alert(data.error);
        }
    } catch(err) {
        alert("Falha ao disparar sincronização.");
    }
}

function showLogsModal() {
    logsModalInstance.show();
    fetchLogs();
    if (logPollTimer) clearInterval(logPollTimer);
    logPollTimer = setInterval(fetchLogs, 3000);
}

document.getElementById('logsModal').addEventListener('hidden.bs.modal', () => {
    if (logPollTimer) {
        clearInterval(logPollTimer);
        logPollTimer = null;
    }
});

async function fetchLogs() {
    try {
        const res = await fetch('api.php?action=get_logs');
        const data = await res.json();
        if (data.success) {
            const el = document.getElementById('logsContent');
            el.textContent = data.logs || 'Sem logs disponíveis.';
            el.scrollTop = el.scrollHeight;
        }
    } catch(e) {}
}

async function logout() {
    await fetch('api.php?action=logout');
    location.reload();
}
</script>
</body>
</html>
