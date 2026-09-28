<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

$RCLONE_CONF = '/etc/rclone/rclone.conf';
$SA_JSON_PATH = '/etc/rclone/service-account.json';
$LOG_FILE = '/var/log/bareos/pbs-sync.log';
$AUTH_PASS = '$R74g20m@2080';
$HELPER_BIN = '/usr/local/bin/gdrive_auth_helper.py';

function parse_last_json($output) {
    if (empty($output)) return null;
    $lines = array_filter(array_map('trim', explode("\n", $output)));
    while (!empty($lines)) {
        $candidate = array_pop($lines);
        $decoded = json_decode($candidate, true);
        if ($decoded !== null) {
            return $decoded;
        }
    }
    return null;
}

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

if (empty($_SESSION['authenticated'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

switch ($action) {
    case 'get_status':
        if (!file_exists($RCLONE_CONF)) {
            echo json_encode(['success' => true, 'connected' => false]);
            exit;
        }
        $conf_content = file_get_contents($RCLONE_CONF);
        if (strpos($conf_content, '[gdrive]') === false) {
            echo json_encode(['success' => true, 'connected' => false]);
            exit;
        }

        $auth_mode = (strpos($conf_content, 'service_account_file') !== false) ? 'service_account' : 'oauth';
        
        $cmd = "sudo /usr/bin/rclone about gdrive: --config " . escapeshellarg($RCLONE_CONF) . " --json 2>&1";
        $out = shell_exec($cmd);
        $quota = json_decode($out, true);

        if (is_array($quota)) {
            echo json_encode([
                'success' => true,
                'connected' => true,
                'auth_mode' => $auth_mode,
                'quota' => $quota
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'connected' => true,
                'auth_mode' => $auth_mode,
                'warning' => 'Conectado, mas falha ao ler quota: ' . trim($out)
            ]);
        }
        break;

    case 'start_browser_auth':
        $team_drive = trim($_POST['team_drive'] ?? $_GET['team_drive'] ?? '');
        $cmd = "sudo " . escapeshellcmd($HELPER_BIN) . " start " . escapeshellarg($team_drive) . " 2>&1";
        $out = shell_exec($cmd);
        $res = parse_last_json($out);
        if ($res && !empty($res['success'])) {
            echo json_encode($res);
        } else {
            echo json_encode([
                'success' => false,
                'error' => $res['error'] ?? 'Falha ao iniciar processo de autorização do Google: ' . trim($out)
            ]);
        }
        break;

    case 'check_browser_auth':
        $cmd = "sudo " . escapeshellcmd($HELPER_BIN) . " status 2>&1";
        $out = shell_exec($cmd);
        $res = parse_last_json($out);
        if ($res) {
            // Se concluiu, testar a quota imediatamente
            if (!empty($res['status']) && $res['status'] === 'completed') {
                $test_cmd = "sudo /usr/bin/rclone about gdrive: --config " . escapeshellarg($RCLONE_CONF) . " --json 2>&1";
                $test_out = shell_exec($test_cmd);
                $quota = json_decode($test_out, true);
                $res['quota'] = is_array($quota) ? $quota : null;
            }
            echo json_encode($res);
        } else {
            echo json_encode(['active' => false, 'error' => 'Falha ao checar status: ' . trim($out)]);
        }
        break;

    case 'relay_callback':
        $raw_input = trim($_POST['callback_input'] ?? '');
        if (empty($raw_input)) {
            echo json_encode(['success' => false, 'error' => 'URL ou código de callback não fornecido.']);
            exit;
        }
        $cmd = "sudo " . escapeshellcmd($HELPER_BIN) . " relay " . escapeshellarg($raw_input) . " 2>&1";
        $out = shell_exec($cmd);
        $res = parse_last_json($out);
        if ($res) {
            echo json_encode($res);
        } else {
            echo json_encode(['success' => false, 'error' => trim($out)]);
        }
        break;

    case 'cancel_browser_auth':
        $cmd = "sudo " . escapeshellcmd($HELPER_BIN) . " cancel 2>&1";
        $out = shell_exec($cmd);
        $res = parse_last_json($out);
        echo json_encode($res ?: ['success' => true]);
        break;

    case 'save_service_account':
        $sa_content = trim($_POST['sa_json'] ?? '');
        $team_drive = trim($_POST['team_drive'] ?? '');

        if (!empty($_FILES['sa_file']['tmp_name'])) {
            $sa_content = file_get_contents($_FILES['sa_file']['tmp_name']);
        }

        if (empty($sa_content)) {
            echo json_encode(['success' => false, 'error' => 'Arquivo ou conteúdo JSON da Service Account não fornecido.']);
            exit;
        }

        $json_test = json_decode($sa_content, true);
        if (!$json_test || empty($json_test['client_email'])) {
            echo json_encode(['success' => false, 'error' => 'O JSON fornecido não é uma chave de Conta de Serviço (Service Account) válida do Google Cloud.']);
            exit;
        }

        if (!is_dir('/etc/rclone')) {
            mkdir('/etc/rclone', 0775, true);
        }

        file_put_contents($SA_JSON_PATH, $sa_content);
        chmod($SA_JSON_PATH, 0660);

        $rclone_content = "[gdrive]\n";
        $rclone_content .= "type = drive\n";
        $rclone_content .= "scope = drive\n";
        $rclone_content .= "service_account_file = {$SA_JSON_PATH}\n";
        if (!empty($team_drive)) {
            $rclone_content .= "team_drive = {$team_drive}\n";
        }

        file_put_contents($RCLONE_CONF, $rclone_content);
        chmod($RCLONE_CONF, 0664);

        $test_cmd = "sudo /usr/bin/rclone about gdrive: --config " . escapeshellarg($RCLONE_CONF) . " --json 2>&1";
        $test_out = shell_exec($test_cmd);
        $test_quota = json_decode($test_out, true);

        if (is_array($test_quota)) {
            echo json_encode([
                'success' => true,
                'message' => 'Google Drive conectado com sucesso!',
                'quota' => $test_quota,
                'client_email' => $json_test['client_email']
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Chave salva, mas o Google rejeitou a conexão: ' . trim($test_out)
            ]);
        }
        break;

    case 'save_oauth_token':
        $token_raw = trim($_POST['token_json'] ?? '');
        $team_drive = trim($_POST['team_drive'] ?? '');

        if (empty($token_raw)) {
            echo json_encode(['success' => false, 'error' => 'Token OAuth não fornecido.']);
            exit;
        }

        $token_clean = trim($token_raw);

        if (!is_dir('/etc/rclone')) {
            mkdir('/etc/rclone', 0775, true);
        }

        $rclone_content = "[gdrive]\n";
        $rclone_content .= "type = drive\n";
        $rclone_content .= "scope = drive\n";
        $rclone_content .= "token = {$token_clean}\n";
        if (!empty($team_drive)) {
            $rclone_content .= "team_drive = {$team_drive}\n";
        }

        file_put_contents($RCLONE_CONF, $rclone_content);
        chmod($RCLONE_CONF, 0664);

        $test_cmd = "sudo /usr/bin/rclone about gdrive: --config " . escapeshellarg($RCLONE_CONF) . " --json 2>&1";
        $test_out = shell_exec($test_cmd);
        $test_quota = json_decode($test_out, true);

        if (is_array($test_quota)) {
            echo json_encode([
                'success' => true,
                'message' => 'Conta Google Drive conectada com sucesso!',
                'quota' => $test_quota
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Token salvo, mas o Google rejeitou a autenticação: ' . trim($test_out)
            ]);
        }
        break;

    case 'test_connection':
        if (!file_exists($RCLONE_CONF)) {
            echo json_encode(['success' => false, 'error' => 'Google Drive não configurado ainda.']);
            exit;
        }

        $start_time = microtime(true);
        $cmd = "sudo /usr/bin/rclone about gdrive: --config " . escapeshellarg($RCLONE_CONF) . " --json 2>&1";
        $out = shell_exec($cmd);
        $latency = round((microtime(true) - $start_time) * 1000);
        $quota = json_decode($out, true);

        if (is_array($quota)) {
            echo json_encode([
                'success' => true,
                'latency_ms' => $latency,
                'quota' => $quota
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'latency_ms' => $latency,
                'error' => trim($out)
            ]);
        }
        break;

    case 'list_files':
        if (!file_exists($RCLONE_CONF)) {
            echo json_encode(['success' => false, 'error' => 'Google Drive não configurado.']);
            exit;
        }
        $cmd = "sudo /usr/bin/rclone lsjson gdrive:OnliBackup --config " . escapeshellarg($RCLONE_CONF) . " --max-depth 2 2>&1";
        $out = shell_exec($cmd);
        $files = json_decode($out, true);
        if (is_array($files)) {
            echo json_encode(['success' => true, 'files' => $files]);
        } else {
            echo json_encode(['success' => true, 'files' => [], 'raw' => trim($out)]);
        }
        break;

    case 'trigger_sync':
        $cmd = "sudo /usr/local/bin/sync-to-pbs.sh WebTriggerGDrive Full > /dev/null 2>&1 &";
        shell_exec($cmd);
        echo json_encode(['success' => true, 'message' => 'Sincronização com o Google Drive iniciada em segundo plano!']);
        break;

    case 'disconnect':
        @unlink($RCLONE_CONF);
        @unlink($SA_JSON_PATH);
        echo json_encode(['success' => true, 'message' => 'Google Drive desconectado com sucesso.']);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Ação inválida']);
        break;
}
