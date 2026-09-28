# OnliBackupWeb 🚀

Interface Web e Orquestrador Central de Backup unificando **Bareos** e **Proxmox Backup Server (PBS)** no Debian 13.

## 📌 Arquitetura

```
                  INTERNET / VPN
                       │
                       │
┌──────────────────────▼──────────────────────┐
│       WINDOWS SERVER - HIKCENTRAL           │
│                                              │
│       Bareos File Daemon (FD)               │
│       VSS (Volume Shadow Copy) Habilitado    │
└──────────────────────┬───────────────────────┘
                       │
                       │ TLS / 9102
                       ▼
┌──────────────────────────────────────────────┐
│              BAREOS01 (Debian 13)            │
│                                              │
│  - Bareos Director (25.1.2) :9101            │
│  - Bareos Catalog (PostgreSQL 17) :5432      │
│  - Bareos Storage Daemon :9103               │
│  - OnliBackup Web Portal (/ & /pbs/)         │
│  - WebUI: http://<ip>/bareos-webui/          │
│                                              │
│  ORQUESTRAÇÃO PÓS-JOB (RunScript)            │
│             │                                │
│             ▼                                │
│      proxmox-backup-client (4.2.6)           │
│      sync-to-pbs.sh / pbs_sync.py            │
└──────────────────────┬───────────────────────┘
                       │
                       │ HTTPS / 8007
                       ▼
┌──────────────────────────────────────────────┐
│                 PBS NUVEM                    │
│                                              │
│       Datastore existente (10 TB)            │
│       - Deduplicação em nível de blocos      │
│       - Compressão ZSTD                      │
│       - Criptografia em trânsito e repouso   │
└──────────────────────────────────────────────┘
```

## 🛠️ Recursos Implementados

* **Portal Central OnliBackup (`/`):** Hub de acesso unificado para o Bareos WebUI e o PBS Manager.
* **PBS Nuvem Manager (`/pbs/`):**
  * Suporte a múltiplos servidores Proxmox Backup Server (Nuvem 10 TB, Local, DR).
  * Teste de conectividade e latência em tempo real com o Datastore remoto via API.
  * Visualizador de snapshots remotos direto na interface web.
  * Disparo manual de sincronização para todos os servidores PBS ativos.
  * Visualização de logs em tempo real (`/var/log/bareos/pbs-sync.log`).
* **Integração Total com Bareos:**
  * Recurso de Storage `PBS-Cloud-Target` visível no Bareos WebUI.
  * Job oficial `SyncToPBSCloud` para envio sob demanda ou agendado.
  * Injeção de logs detalhados do `proxmox-backup-client` no catálogo do Bareos.
  * Botão de navegação rápida `☁ PBS Nuvem` no navbar do Bareos WebUI.

## 🚀 Estrutura de Arquivos

* `/var/www/html/index.php`: Portal unificado inicial.
* `/var/www/html/pbs/index.php`: Interface responsiva do PBS Manager.
* `/var/www/html/pbs/api.php`: Backend REST para gerenciamento dos servidores PBS.
* `scripts/sync-to-pbs.sh`: Wrapper de execução chamado pelo Bareos `RunScript`.
* `scripts/pbs_sync.py`: Motor Python com suporte a múltiplos destinos PBS.
* `config/bareos/`: Modelos de configuração do Bareos Director, Storage e Clientes.

---
Desenvolvido por **Onlitec**
