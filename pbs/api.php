<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$CONFIG_FILE = '/etc/proxmox-backup/servers.json';
$LEGACY_ENV_FILE = '/etc/proxmox-backup/pbs-env.conf';
$LOG_FILE = '/var/log/bareos/pbs-sync.log';
$AUTH_PASS = '$R74g20m@2080';

// Autenticação simples
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'login') {
    $pass = $_POST['password'] ?? '';
    if ($pass === $AUTH_PASS) {
        $_SESSION['authenticated'] = true;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Senha incorreta']);
    }
    exit;
}

if ($action === 'logout') {
    $_SESSION['authenticated'] = false;
    session_destroy();
    echo json_encode(['success' => true]);
    exit;
}

// Checar sessão
if (empty($_SESSION['authenticated'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

function load_config($file) {
    if (!file_exists($file)) {
        return [
            'servers' => [],
            'settings' => [
                'storage_dir' => '/backup/storage',
                'sync_catalog' => true
            ]
        ];
    }
    $content = file_get_contents($file);
    $data = json_decode($content, true);
    return is_array($data) ? $data : ['servers' => [], 'settings' => []];
}

function save_config($file, $data, $legacy_file) {
    if (!is_dir(dirname($file))) {
        mkdir(dirname($file), 0775, true);
    }
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $saved = file_put_contents($file, $json);
    @chmod($file, 0664);

    // Atualizar legacy env para o primeiro servidor ativo
    if ($legacy_file) {
        $first_active = null;
        foreach ($data['servers'] as $s) {
            if (!empty($s['enabled'])) {
                $first_active = $s;
                break;
            }
        }
        if ($first_active) {
            $user = $first_active['user'];
            $tid = $first_active['token_id'] ?? '';
            $host = $first_active['host'];
            $port = $first_active['port'] ?? 8007;
            $ds = $first_active['datastore'];
            $repo = (!empty($tid)) ? "{$user}!{$tid}@{$host}:{$port}:{$ds}" : "{$user}@{$host}:{$port}:{$ds}";
            $secret = $first_active['secret'];
            $fp = $first_active['fingerprint'] ?? '';
            $env_content = "# Auto-gerado pelo PBS Web Manager\n";
            $env_content .= "export PBS_REPOSITORY=\"{$repo}\"\n";
            $env_content .= "export PBS_PASSWORD=\"{$secret}\"\n";
            $env_content .= "export PBS_FINGERPRINT=\"{$fp}\"\n";
            $env_content .= "export BAREOS_STORAGE_DIR=\"/backup/storage\"\n";
            @file_put_contents($legacy_file, $env_content);
            @chmod($legacy_file, 0600);
        }
    }
    return $saved !== false;
}

switch ($action) {
    case 'get_servers':
        $config = load_config($CONFIG_FILE);
        // Mascarar senhas para retorno seguro
        $safe_servers = array_map(function($s) {
            $s['has_secret'] = !empty($s['secret']);
            $s['secret'] = !empty($s['secret']) ? '••••••••••••' : '';
            return $s;
        }, $config['servers']);
        echo json_encode([
            'success' => true,
            'servers' => $safe_servers,
            'settings' => $config['settings']
        ]);
        break;

    case 'save_server':
        $config = load_config($CONFIG_FILE);
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $host = trim($_POST['host'] ?? '');
        $port = intval($_POST['port'] ?? 8007);
        $datastore = trim($_POST['datastore'] ?? '');
        $auth_type = $_POST['auth_type'] ?? 'token';
        $user = trim($_POST['user'] ?? '');
        $token_id = trim($_POST['token_id'] ?? '');
        $secret = trim($_POST['secret'] ?? '');
        $fingerprint = trim($_POST['fingerprint'] ?? '');
        $enabled = isset($_POST['enabled']) ? ($_POST['enabled'] === '1' || $_POST['enabled'] === 'true') : true;

        if (empty($name) || empty($host) || empty($datastore) || empty($user)) {
            echo json_encode(['success' => false, 'error' => 'Preencha todos os campos obrigatórios (Nome, Host, Datastore, Usuário).']);
            exit;
        }

        $is_new = empty($id);
        if ($is_new) {
            $id = 'pbs-' . substr(md5(uniqid(rand(), true)), 0, 8);
        }

        $found = false;
        foreach ($config['servers'] as &$srv) {
            if ($srv['id'] === $id) {
                $srv['name'] = $name;
                $srv['host'] = $host;
                $srv['port'] = $port;
                $srv['datastore'] = $datastore;
                $srv['auth_type'] = $auth_type;
                $srv['user'] = $user;
                $srv['token_id'] = $token_id;
                if (!empty($secret) && $secret !== '••••••••••••') {
                    $srv['secret'] = $secret;
                }
                $srv['fingerprint'] = $fingerprint;
                $srv['enabled'] = $enabled;
                $srv['updated_at'] = date('Y-m-d H:i:s');
                $found = true;
                break;
            }
        }

        if (!$found) {
            $config['servers'][] = [
                'id' => $id,
                'name' => $name,
                'host' => $host,
                'port' => $port,
                'datastore' => $datastore,
                'auth_type' => $auth_type,
                'user' => $user,
                'token_id' => $token_id,
                'secret' => $secret,
                'fingerprint' => $fingerprint,
                'enabled' => $enabled,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }

        if (save_config($CONFIG_FILE, $config, $LEGACY_ENV_FILE)) {
            echo json_encode(['success' => true, 'message' => 'Servidor salvo com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erro ao salvar arquivo de configuração.']);
        }
        break;

    case 'delete_server':
        $id = $_POST['id'] ?? '';
        $config = load_config($CONFIG_FILE);
        $orig_count = count($config['servers']);
        $config['servers'] = array_values(array_filter($config['servers'], function($s) use ($id) {
            return $s['id'] !== $id;
        }));
        if (count($config['servers']) < $orig_count) {
            save_config($CONFIG_FILE, $config, $LEGACY_ENV_FILE);
            echo json_encode(['success' => true, 'message' => 'Servidor removido.']);
        } else {
            echo json_encode(['success' => false, 'error' => 'Servidor não encontrado.']);
        }
        break;

    case 'toggle_server':
        $id = $_POST['id'] ?? '';
        $config = load_config($CONFIG_FILE);
        foreach ($config['servers'] as &$srv) {
            if ($srv['id'] === $id) {
                $srv['enabled'] = !$srv['enabled'];
                break;
            }
        }
        save_config($CONFIG_FILE, $config, $LEGACY_ENV_FILE);
        echo json_encode(['success' => true]);
        break;

    case 'test_connection':
        $id = $_POST['id'] ?? '';
        $config = load_config($CONFIG_FILE);
        $target = null;

        // Se foi passado id existente
        if ($id) {
            foreach ($config['servers'] as $s) {
                if ($s['id'] === $id) {
                    $target = $s;
                    break;
                }
            }
        }

        // Se foram passados parâmetros temporários pelo modal
        if (!$target && !empty($_POST['host'])) {
            $target = [
                'name' => $_POST['name'] ?? 'Teste',
                'host' => trim($_POST['host']),
                'port' => intval($_POST['port'] ?? 8007),
                'datastore' => trim($_POST['datastore']),
                'auth_type' => $_POST['auth_type'] ?? 'token',
                'user' => trim($_POST['user']),
                'token_id' => trim($_POST['token_id'] ?? ''),
                'secret' => trim($_POST['secret'] ?? ''),
                'fingerprint' => trim($_POST['fingerprint'] ?? '')
            ];
        }

        if (!$target) {
            echo json_encode(['success' => false, 'error' => 'Dados de servidor incompletos para teste.']);
            exit;
        }

        $user = $target['user'];
        $tid = $target['token_id'] ?? '';
        $host = $target['host'];
        $port = $target['port'] ?? 8007;
        $ds = $target['datastore'];
        $repo = (!empty($tid)) ? "{$user}!{$tid}@{$host}:{$port}:{$ds}" : "{$user}@{$host}:{$port}:{$ds}";
        $secret = $target['secret'] ?? '';
        $fp = $target['fingerprint'] ?? '';

        // Montar comando sudo proxmox-backup-client status
        $env_exports = "export PBS_REPOSITORY=" . escapeshellarg($repo) . " && export PBS_PASSWORD=" . escapeshellarg($secret);
        if (!empty($fp)) {
            $env_exports .= " && export PBS_FINGERPRINT=" . escapeshellarg($fp);
        }

        $start_time = microtime(true);
        $cmd = "{$env_exports} && sudo -E /usr/bin/proxmox-backup-client status --output-format json 2>&1";
        $output = shell_exec($cmd);
        $duration = round((microtime(true) - $start_time) * 1000);

        $json_data = json_decode($output, true);
        if (is_array($json_data)) {
            // Sucesso
            echo json_encode([
                'success' => true,
                'latency_ms' => $duration,
                'repo' => $repo,
                'status' => $json_data,
                'raw' => $output
            ]);
        } else {
            // Tentar status texto comum
            $cmd_text = "{$env_exports} && sudo -E /usr/bin/proxmox-backup-client status 2>&1";
            $output_text = shell_exec($cmd_text);
            if (strpos($output_text, 'total') !== false || strpos($output_text, 'used') !== false) {
                echo json_encode([
                    'success' => true,
                    'latency_ms' => $duration,
                    'repo' => $repo,
                    'raw' => $output_text
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'latency_ms' => $duration,
                    'repo' => $repo,
                    'error' => trim($output_text ?: $output)
                ]);
            }
        }
        break;

    case 'get_snapshots':
        $id = $_GET['id'] ?? '';
        $config = load_config($CONFIG_FILE);
        $target = null;
        foreach ($config['servers'] as $s) {
            if ($s['id'] === $id) {
                $target = $s;
                break;
            }
        }
        if (!$target) {
            echo json_encode(['success' => false, 'error' => 'Servidor não encontrado.']);
            exit;
        }

        $user = $target['user'];
        $tid = $target['token_id'] ?? '';
        $host = $target['host'];
        $port = $target['port'] ?? 8007;
        $ds = $target['datastore'];
        $repo = (!empty($tid)) ? "{$user}!{$tid}@{$host}:{$port}:{$ds}" : "{$user}@{$host}:{$port}:{$ds}";
        $secret = $target['secret'] ?? '';
        $fp = $target['fingerprint'] ?? '';

        $env_exports = "export PBS_REPOSITORY=" . escapeshellarg($repo) . " && export PBS_PASSWORD=" . escapeshellarg($secret);
        if (!empty($fp)) {
            $env_exports .= " && export PBS_FINGERPRINT=" . escapeshellarg($fp);
        }

        $cmd = "{$env_exports} && sudo -E /usr/bin/proxmox-backup-client snapshot list --output-format json 2>&1";
        $output = shell_exec($cmd);
        $snapshots = json_decode($output, true);
        if (is_array($snapshots)) {
            echo json_encode(['success' => true, 'snapshots' => $snapshots]);
        } else {
            echo json_encode(['success' => false, 'error' => trim($output)]);
        }
        break;

    case 'trigger_sync':
        $cmd = "sudo /usr/local/bin/sync-to-pbs.sh WebManual Full > /dev/null 2>&1 &";
        shell_exec($cmd);
        echo json_encode(['success' => true, 'message' => 'Sincronização iniciada em segundo plano! Acompanhe os logs.']);
        break;

    case 'get_logs':
        if (!file_exists($LOG_FILE)) {
            echo json_encode(['success' => true, 'logs' => 'Nenhum log registrado ainda.']);
            exit;
        }
        $lines = escapeshellarg(100);
        $output = shell_exec("tail -n {$lines} " . escapeshellarg($LOG_FILE));
        echo json_encode(['success' => true, 'logs' => $output]);
        break;

    
    case 'get_bareos_status':
        $dir_active = trim(shell_exec("systemctl is-active bareos-dir 2>&1") ?? '') === 'active';
        $sd_active = trim(shell_exec("systemctl is-active bareos-sd 2>&1") ?? '') === 'active';
        $fd_active = trim(shell_exec("systemctl is-active bareos-fd 2>&1") ?? '') === 'active';
        $pg_active = trim(shell_exec("systemctl is-active postgresql 2>&1") ?? '') === 'active';
        
        $jobs_raw = shell_exec("echo 'list jobs' | sudo /usr/bin/bconsole 2>&1");
        
        echo json_encode([
            'success' => true,
            'services' => [
                'bareos_dir' => $dir_active,
                'bareos_sd' => $sd_active,
                'bareos_fd' => $fd_active,
                'postgresql' => $pg_active
            ],
            'jobs_output' => $jobs_raw
        ]);
        break;

    case 'run_bareos_job':
        $job_name = escapeshellarg($_POST['job_name'] ?? 'SyncToPBSCloud');
        $cmd = "echo "run job={$job_name} yes" | sudo /usr/bin/bconsole 2>&1";
        $out = shell_exec($cmd);
        echo json_encode(['success' => true, 'output' => $out]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Ação inválida']);
        break;
}
