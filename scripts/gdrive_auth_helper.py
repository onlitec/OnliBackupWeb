#!/usr/bin/env python3
import sys
import os
import time
import json
import re
import signal
import subprocess
import urllib.request
import urllib.parse

STATE_FILE = "/tmp/gdrive_oauth_state.json"
RCLONE_CONF = "/etc/rclone/rclone.conf"
TIMEOUT_SECS = 300  # 5 minutos

def get_state():
    if os.path.exists(STATE_FILE):
        try:
            with open(STATE_FILE, "r") as f:
                return json.load(f)
        except Exception:
            pass
    return None

def is_pid_running(pid):
    try:
        os.kill(pid, 0)
        return True
    except OSError:
        return False

def action_status():
    state = get_state()
    if not state:
        print(json.dumps({"active": False}))
        return
    
    if time.time() - state.get("start_time", 0) > TIMEOUT_SECS:
        action_cancel()
        print(json.dumps({"active": False, "error": "Tempo limite de 5 minutos expirado"}))
        return
    
    status = state.get("status")
    if status == "waiting":
        pid = state.get("pid")
        if not pid or not is_pid_running(pid):
            if os.path.exists("/tmp/gdrive_token.tmp"):
                try:
                    with open("/tmp/gdrive_token.tmp") as f:
                        token_data = json.load(f)
                    print(json.dumps({"active": False, "status": "completed", "token": token_data}))
                    return
                except Exception:
                    pass
            print(json.dumps({"active": False, "error": "Processo de autorização encerrou inesperadamente"}))
            return
        print(json.dumps({
            "active": True,
            "status": "waiting",
            "auth_url": state.get("auth_url"),
            "elapsed_secs": int(time.time() - state.get("start_time", 0))
        }))
    elif status == "completed":
        print(json.dumps({"active": False, "status": "completed", "token": state.get("token")}))
    else:
        print(json.dumps({"active": False, "status": status, "error": state.get("error")}))

def action_start(team_drive=""):
    action_cancel(quiet=True)
    
    if os.path.exists("/tmp/gdrive_token.tmp"):
        try:
            os.remove("/tmp/gdrive_token.tmp")
        except OSError:
            pass

    # Save team_drive preference
    if os.path.exists("/tmp/gdrive_team_drive.tmp"):
        try:
            os.remove("/tmp/gdrive_team_drive.tmp")
        except OSError:
            pass
    if team_drive:
        with open("/tmp/gdrive_team_drive.tmp", "w") as f:
            f.write(team_drive.strip())

    worker_cmd = [sys.executable, os.path.abspath(__file__), "_worker"]
    subprocess.Popen(worker_cmd, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL, start_new_session=True)
    
    for _ in range(30):
        time.sleep(0.2)
        state = get_state()
        if state and state.get("auth_url"):
            print(json.dumps({"success": True, "auth_url": state["auth_url"]}))
            return
        if state and state.get("error"):
            print(json.dumps({"success": False, "error": state["error"]}))
            return
            
    print(json.dumps({"success": False, "error": "Tempo limite ao inicializar o daemon de autorização"}))

def action_worker():
    p = subprocess.Popen(['rclone', 'authorize', 'drive', '--auth-no-open-browser'],
                         stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
    
    local_link = None
    for _ in range(25):
        line = p.stderr.readline()
        if not line:
            time.sleep(0.2)
            continue
        m = re.search(r'(http://127\.0\.0\.1:53682/auth\?state=\S+)', line)
        if m:
            local_link = m.group(1)
            break
            
    if not local_link:
        with open(STATE_FILE, "w") as f:
            json.dump({"status": "error", "error": "Não foi possível obter o link de autorização do rclone"}, f)
        p.terminate()
        return

    google_url = None
    try:
        class NoRedirect(urllib.request.HTTPRedirectHandler):
            def redirect_request(self, req, fp, code, msg, headers, newurl):
                nonlocal google_url
                google_url = newurl
                return None
        opener = urllib.request.build_opener(NoRedirect)
        req = urllib.request.Request(local_link, headers={'User-Agent': 'Mozilla/5.0'})
        try:
            opener.open(req)
        except Exception:
            pass
    except Exception:
        pass
        
    auth_url = google_url if google_url else local_link
    
    with open(STATE_FILE, "w") as f:
        json.dump({
            "status": "waiting",
            "pid": p.pid,
            "auth_url": auth_url,
            "local_link": local_link,
            "start_time": time.time()
        }, f)
        
    stdout, stderr = p.communicate()
    
    token_json = None
    for line in stdout.splitlines():
        line = line.strip()
        if line.startswith("{") and "access_token" in line:
            try:
                token_json = json.loads(line)
                break
            except Exception:
                pass
                
    if token_json:
        team_drive = ""
        if os.path.exists("/tmp/gdrive_team_drive.tmp"):
            try:
                with open("/tmp/gdrive_team_drive.tmp") as f:
                    team_drive = f.read().strip()
            except Exception:
                pass
        apply_token(token_json, team_drive)
        with open(STATE_FILE, "w") as f:
            json.dump({"status": "completed", "token": token_json}, f)
        with open("/tmp/gdrive_token.tmp", "w") as f:
            json.dump(token_json, f)
    else:
        err_msg = stderr.strip() if stderr else "Falha ao obter token de acesso do Google."
        with open(STATE_FILE, "w") as f:
            json.dump({"status": "error", "error": err_msg}, f)

def apply_token(token_data, team_drive=""):
    os.makedirs("/etc/rclone", mode=0o775, exist_ok=True)
    conf = "[gdrive]\n"
    conf += "type = drive\n"
    conf += "scope = drive\n"
    conf += f"token = {json.dumps(token_data)}\n"
    if team_drive:
        conf += f"team_drive = {team_drive}\n"
    with open(RCLONE_CONF, "w") as f:
        f.write(conf)
    os.chmod(RCLONE_CONF, 0o664)

def action_relay(raw_input):
    state = get_state()
    query = ""
    clean_input = raw_input.strip()
    
    if "state=" in clean_input and "code=" in clean_input:
        if "?" in clean_input:
            query = clean_input.split("?", 1)[1]
        else:
            query = clean_input
    elif "code=" in clean_input:
        code_match = re.search(r'code=([^&\s]+)', clean_input)
        code = code_match.group(1) if code_match else clean_input
        s_val = ""
        if state and state.get("local_link"):
            m = re.search(r'state=([^&\s]+)', state["local_link"])
            if m:
                s_val = m.group(1)
        query = f"state={s_val}&code={code}"
    else:
        code = clean_input
        s_val = ""
        if state and state.get("local_link"):
            m = re.search(r'state=([^&\s]+)', state["local_link"])
            if m:
                s_val = m.group(1)
        query = f"state={s_val}&code={code}"

    relay_url = f"http://127.0.0.1:53682/?{query}"
    try:
        req = urllib.request.Request(relay_url, headers={'User-Agent': 'Mozilla/5.0'})
        with urllib.request.urlopen(req, timeout=5) as resp:
            content = resp.read().decode()
            print(json.dumps({"success": True, "message": "Callback retransmitido com sucesso para o rclone"}))
            return
    except Exception as e:
        print(json.dumps({"success": False, "error": f"Erro no relay do callback: {str(e)}"}))

def action_cancel(quiet=False):
    state = get_state()
    if state and state.get("pid"):
        try:
            os.kill(state["pid"], signal.SIGTERM)
        except OSError:
            pass
    try:
        subprocess.run(["pkill", "-f", "rclone authorize drive"], check=False)
    except Exception:
        pass
    if os.path.exists(STATE_FILE):
        try:
            os.remove(STATE_FILE)
        except OSError:
            pass
    if os.path.exists("/tmp/gdrive_token.tmp"):
        try:
            os.remove("/tmp/gdrive_token.tmp")
        except OSError:
            pass
    if os.path.exists("/tmp/gdrive_team_drive.tmp"):
        try:
            os.remove("/tmp/gdrive_team_drive.tmp")
        except OSError:
            pass
    if not quiet:
        print(json.dumps({"success": True}))

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Ação não especificada"}))
        sys.exit(1)
        
    cmd = sys.argv[1]
    if cmd == "start":
        td = sys.argv[2] if len(sys.argv) > 2 else ""
        action_start(td)
    elif cmd == "status":
        action_status()
    elif cmd == "relay":
        param = " ".join(sys.argv[2:]) if len(sys.argv) > 2 else ""
        action_relay(param)
    elif cmd == "cancel":
        action_cancel()
    elif cmd == "_worker":
        action_worker()
    else:
        print(json.dumps({"error": f"Ação desconhecida: {cmd}"}))
        sys.exit(1)
