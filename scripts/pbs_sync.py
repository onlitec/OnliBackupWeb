#!/usr/bin/env python3
import json
import os
import sys
import subprocess
import datetime

JOB_NAME = sys.argv[1] if len(sys.argv) > 1 else "ManualRun"
JOB_LEVEL = sys.argv[2] if len(sys.argv) > 2 else "Unknown"

LOG_FILE = "/var/log/bareos/pbs-sync.log"
CONFIG_JSON = "/etc/proxmox-backup/servers.json"
LEGACY_CONF = "/etc/proxmox-backup/pbs-env.conf"
RCLONE_CONF = "/etc/rclone/rclone.conf"
DUMP_DIR = "/var/lib/bareos/catalog-dump"

def log(msg):
    now = datetime.datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    formatted = f"[{now}] [JOB: {JOB_NAME}] {msg}"
    print(formatted)
    try:
        with open(LOG_FILE, "a", encoding="utf-8") as f:
            f.write(formatted + "\n")
    except Exception:
        pass

log(f"Iniciando orquestração de backup para Proxmox Backup Server(s) e Nuvem [Nível: {JOB_LEVEL}]...")

# 1. Exportar catálogo do Bareos (PostgreSQL)
try:
    os.makedirs(DUMP_DIR, mode=0o750, exist_ok=True)
except Exception:
    pass

dump_filename = f"bareos-catalog-{datetime.datetime.now().strftime('%Y%m%d%H%M%S')}.sql.gz"
dump_path = os.path.join(DUMP_DIR, dump_filename)

log("Exportando catálogo do Bareos (PostgreSQL)...")
try:
    p1 = subprocess.Popen(["pg_dump", "-U", "bareos", "bareos"], stdout=subprocess.PIPE, stderr=subprocess.PIPE)
    with open(dump_path, "wb") as f_out:
        p2 = subprocess.Popen(["gzip"], stdin=p1.stdout, stdout=f_out, stderr=subprocess.PIPE)
        p1.stdout.close()
        p2.communicate()
    p1.wait()
    if p1.returncode == 0:
        size_mb = os.path.getsize(dump_path) / (1024 * 1024)
        log(f"Dump do catálogo concluído: {dump_path} ({size_mb:.2f} MB)")
    else:
        log("AVISO: Falha ao executar pg_dump com usuário bareos.")
except Exception as e:
    log(f"AVISO ao gerar dump do catálogo: {e}")

# Limpar dumps antigos (manter últimos 3)
try:
    files = sorted([os.path.join(DUMP_DIR, f) for f in os.listdir(DUMP_DIR) if f.startswith("bareos-catalog-")], key=os.path.getmtime)
    while len(files) > 3:
        to_del = files.pop(0)
        try:
            os.remove(to_del)
        except Exception:
            pass
except Exception:
    pass

# 2. Carregar servidores PBS
servers_to_sync = []
storage_dir = "/backup/storage"

if os.path.exists(CONFIG_JSON):
    try:
        with open(CONFIG_JSON, "r", encoding="utf-8") as f:
            data = json.load(f)
            storage_dir = data.get("settings", {}).get("storage_dir", "/backup/storage")
            for s in data.get("servers", []):
                if s.get("enabled", True):
                    auth_type = s.get("auth_type", "token")
                    user = s.get("user", "")
                    token_id = s.get("token_id", "")
                    secret = s.get("secret", "")
                    host = s.get("host", "")
                    port = s.get("port", 8007)
                    datastore = s.get("datastore", "")
                    fingerprint = s.get("fingerprint", "")
                    name = s.get("name", host)

                    if auth_type == "token" and token_id:
                        repo = f"{user}!{token_id}@{host}:{port}:{datastore}"
                    else:
                        repo = f"{user}@{host}:{port}:{datastore}"

                    servers_to_sync.append({
                        "name": name,
                        "repo": repo,
                        "password": secret,
                        "fingerprint": fingerprint
                    })
    except Exception as e:
        log(f"ERRO ao ler {CONFIG_JSON}: {e}")

if not servers_to_sync and os.path.exists(LEGACY_CONF):
    env_vars = {}
    with open(LEGACY_CONF, "r", encoding="utf-8") as f:
        for line in f:
            if line.startswith("export PBS_REPOSITORY="):
                env_vars["repo"] = line.split("=", 1)[1].strip().strip('"\'')
            elif line.startswith("export PBS_PASSWORD="):
                env_vars["password"] = line.split("=", 1)[1].strip().strip('"\'')
            elif line.startswith("export PBS_FINGERPRINT="):
                env_vars["fingerprint"] = line.split("=", 1)[1].strip().strip('"\'')
            elif line.startswith("export BAREOS_STORAGE_DIR="):
                storage_dir = line.split("=", 1)[1].strip().strip('"\'')
    if env_vars.get("repo") and env_vars.get("password"):
        servers_to_sync.append({
            "name": "PBS Principal (Legado)",
            "repo": env_vars["repo"],
            "password": env_vars["password"],
            "fingerprint": env_vars.get("fingerprint", "")
        })

has_error = False

# Sincronização com o PBS (se houver servidores ativos)
if servers_to_sync:
    log(f"Encontrado(s) {len(servers_to_sync)} servidor(es) PBS ativo(s) para sincronização.")
    for srv in servers_to_sync:
        name = srv["name"]
        repo = srv["repo"]
        pwd = srv["password"]
        fp = srv["fingerprint"]

        log(f"-> Sincronizando com [{name}] ({repo})...")
        env = os.environ.copy()
        env["PBS_REPOSITORY"] = repo
        env["PBS_PASSWORD"] = pwd
        if fp:
            env["PBS_FINGERPRINT"] = fp

        cmd = [
            "proxmox-backup-client", "backup",
            f"bareos-storage.pxar:{storage_dir}",
            f"bareos-catalog.pxar:{DUMP_DIR}",
            "--backup-id", "bareos01-hikcentral"
        ]
        try:
            res = subprocess.run(cmd, env=env, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True, timeout=600)
            for line in res.stdout.splitlines():
                log(f"   [PBS] {line}")
            if res.returncode == 0:
                log(f"-> SUCESSO: Snapshot para [{name}] concluído com sucesso!")
            else:
                log(f"-> ERRO ao sincronizar com [{name}] (Código {res.returncode}).")
                has_error = True
        except Exception as e:
            log(f"-> EXCEÇÃO ao sincronizar com [{name}]: {e}")
            has_error = True
else:
    log("INFO: Nenhum servidor PBS ativo no momento.")

# 3. Sincronização com o Google Drive (via rclone se configurado)
if os.path.exists(RCLONE_CONF):
    try:
        with open(RCLONE_CONF, "r", encoding="utf-8") as f:
            rclone_content = f.read()
        if "[gdrive]" in rclone_content:
            log("-> Detectado destino Google Drive [gdrive]. Iniciando sincronização rclone...")
            
            # Sincronizar volumes locais para o Google Drive
            gdrive_dest_storage = "gdrive:OnliBackup/bareos-storage"
            cmd_rclone_storage = [
                "rclone", "sync", storage_dir, gdrive_dest_storage,
                "--config", RCLONE_CONF,
                "--transfers", "4",
                "--checkers", "8",
                "--log-level", "NOTICE"
            ]
            res_storage = subprocess.run(cmd_rclone_storage, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True, timeout=1200)
            for line in res_storage.stdout.splitlines():
                log(f"   [GDRIVE] {line}")
            
            # Copiar catálogo
            gdrive_dest_catalog = "gdrive:OnliBackup/catalog-dump"
            cmd_rclone_cat = [
                "rclone", "copy", DUMP_DIR, gdrive_dest_catalog,
                "--config", RCLONE_CONF,
                "--log-level", "NOTICE"
            ]
            res_cat = subprocess.run(cmd_rclone_cat, stdout=subprocess.PIPE, stderr=subprocess.STDOUT, text=True, timeout=300)
            
            if res_storage.returncode == 0 and res_cat.returncode == 0:
                log("-> SUCESSO: Cópia para o Google Drive concluída com êxito!")
            else:
                log(f"-> ERRO ao sincronizar com o Google Drive (Código {res_storage.returncode}/{res_cat.returncode}).")
                has_error = True
    except Exception as e:
        log(f"-> EXCEÇÃO ao sincronizar com o Google Drive: {e}")
        has_error = True

if has_error:
    log("Processo de orquestração finalizado com ALERTAS/ERROS.")
    sys.exit(1)
else:
    log("Processo de orquestração finalizado com SUCESSO!")
    sys.exit(0)
