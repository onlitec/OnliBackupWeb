<?php
// includes/db.php - Conexões com SQLite (Portal) e PostgreSQL (Bareos)

function getPortalDB() {
    static $sqlite = null;
    if ($sqlite === null) {
        $dbPath = '/var/www/html/config/portal.db';
        $isNew = !file_exists($dbPath);
        
        $sqlite = new PDO("sqlite:" . $dbPath);
        $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sqlite->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // Criar tabelas se não existirem
        $sqlite->exec("
            CREATE TABLE IF NOT EXISTS portal_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                role TEXT CHECK(role IN ('admin', 'client')) DEFAULT 'client',
                is_active INTEGER DEFAULT 1,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS portal_client_permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                bareos_client_name TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(user_id) REFERENCES portal_users(id) ON DELETE CASCADE,
                UNIQUE(user_id, bareos_client_name)
            );

            CREATE TABLE IF NOT EXISTS portal_audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                user_email TEXT NOT NULL,
                action TEXT NOT NULL,
                details TEXT,
                ip_address TEXT,
                user_agent TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS portal_password_resets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL,
                code_hash TEXT NOT NULL,
                attempts INTEGER DEFAULT 0,
                expires_at DATETIME NOT NULL,
                used INTEGER DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            );

            CREATE TABLE IF NOT EXISTS portal_settings (
                setting_key TEXT PRIMARY KEY,
                setting_value TEXT
            );
        ");

        // Seed Admin Padrão se tabela estiver vazia
        $stmt = $sqlite->query("SELECT count(*) FROM portal_users");
        if ((int)$stmt->fetchColumn() === 0) {
            $adminPass = password_hash('$R74g20m@2080', PASSWORD_DEFAULT);
            $insert = $sqlite->prepare("INSERT INTO portal_users (name, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
            $insert->execute(['Administrador Onlitec', 'admin@onlitec.com.br', $adminPass]);
        }
    }
    return $sqlite;
}

function getBareosDB() {
    static $pg = null;
    if ($pg === null) {
        $pg = new PDO("pgsql:dbname=bareos");
        $pg->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pg->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }
    return $pg;
}

function getSetting($key, $default = '') {
    $db = getPortalDB();
    $stmt = $db->prepare("SELECT setting_value FROM portal_settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

function setSetting($key, $value) {
    $db = getPortalDB();
    $stmt = $db->prepare("INSERT INTO portal_settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value");
    return $stmt->execute([$key, $value]);
}
