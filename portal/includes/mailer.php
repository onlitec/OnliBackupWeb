<?php
// includes/mailer.php - Cliente SMTP Nativo em PHP para Envio de E-mails

require_once __DIR__ . '/db.php';

class PortalMailer {
    private $host;
    private $port;
    private $secure; // 'tls', 'ssl' ou 'none'
    private $user;
    private $pass;
    private $fromEmail;
    private $fromName;
    private $timeout = 15;

    public function __construct() {
        $this->host = getSetting('smtp_host', '');
        $this->port = (int)getSetting('smtp_port', '587');
        $this->secure = strtolower(getSetting('smtp_secure', 'tls'));
        $this->user = getSetting('smtp_user', '');
        $this->pass = getSetting('smtp_pass', '');
        $this->fromEmail = getSetting('smtp_from_email', $this->user ?: 'noreply@onlitec.com.br');
        $this->fromName = getSetting('smtp_from_name', 'OnliBackup — Segurança & Auditoria');
    }

    public function isConfigured() {
        return !empty($this->host) && !empty($this->user) && !empty($this->pass);
    }

    public function send($to, $subject, $htmlBody, $textBody = '') {
        if (!$this->isConfigured()) {
            throw new Exception("Configurações de SMTP não estão preenchidas no painel administrativo.");
        }

        $hostPrefix = ($this->secure === 'ssl') ? 'ssl://' : '';
        $socket = @fsockopen($hostPrefix . $this->host, $this->port, $errno, $errstr, $this->timeout);
        if (!$socket) {
            throw new Exception("Falha ao conectar no servidor SMTP ({$this->host}:{$this->port}): $errstr ($errno)");
        }

        stream_set_timeout($socket, $this->timeout);

        $this->readResponse($socket, "220");
        $this->sendCommand($socket, "EHLO " . gethostname(), "250");

        if ($this->secure === 'tls') {
            $this->sendCommand($socket, "STARTTLS", "220");
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                fclose($socket);
                throw new Exception("Falha na negociação TLS criptografada com o servidor SMTP.");
            }
            $this->sendCommand($socket, "EHLO " . gethostname(), "250");
        }

        // Autenticação
        $this->sendCommand($socket, "AUTH LOGIN", "334");
        $this->sendCommand($socket, base64_encode($this->user), "334");
        $this->sendCommand($socket, base64_encode($this->pass), "235");

        // Envio Envelope
        $this->sendCommand($socket, "MAIL FROM: <" . $this->fromEmail . ">", "250");
        $this->sendCommand($socket, "RCPT TO: <" . $to . ">", "250");
        $this->sendCommand($socket, "DATA", "354");

        // Headers
        $boundary = "bnd_" . md5(uniqid(time()));
        $headers = [];
        $headers[] = "Date: " . date("r");
        $headers[] = "To: <$to>";
        $headers[] = "From: =?UTF-8?B?" . base64_encode($this->fromName) . "?= <" . $this->fromEmail . ">";
        $headers[] = "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"$boundary\"";
        $headers[] = "X-Mailer: OnliBackup-Portal-Mailer/1.0";

        $message = implode("\r\n", $headers) . "\r\n\r\n";
        
        // Corpo Texto Puro
        if (!empty($textBody)) {
            $message .= "--$boundary\r\n";
            $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $message .= chunk_split(base64_encode($textBody)) . "\r\n";
        }

        // Corpo HTML
        $message .= "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $message .= chunk_split(base64_encode($htmlBody)) . "\r\n";
        $message .= "--$boundary--\r\n";
        $message .= "\r\n.";

        $this->sendCommand($socket, $message, "250");
        $this->sendCommand($socket, "QUIT", "221");

        fclose($socket);
        return true;
    }

    private function sendCommand($socket, $cmd, $expectedCode) {
        fwrite($socket, $cmd . "\r\n");
        return $this->readResponse($socket, $expectedCode);
    }

    private function readResponse($socket, $expectedCode) {
        $response = "";
        while ($line = fgets($socket, 512)) {
            $response .= $line;
            if (substr($line, 3, 1) === " ") {
                break;
            }
        }
        $code = substr($response, 0, 3);
        if ($code !== $expectedCode) {
            throw new Exception("Erro SMTP (Esperado $expectedCode, recebido $code): $response");
        }
        return $response;
    }
}

function sendPasswordResetCode($toEmail, $recipientName, $code) {
    $mailer = new PortalMailer();
    $subject = "Seu Código de Recuperação de Senha: " . $code;

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 20px; }
            .card { max-width: 520px; margin: 0 auto; background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 32px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
            .logo { font-size: 20px; font-weight: bold; color: #0284c7; margin-bottom: 24px; text-align: center; }
            .title { font-size: 18px; font-weight: bold; color: #0f172a; margin-bottom: 12px; }
            .code-box { background: #f0f9ff; border: 2px dashed #0284c7; border-radius: 8px; text-align: center; padding: 18px; font-size: 32px; font-weight: 800; letter-spacing: 6px; color: #0369a1; margin: 24px 0; }
            .footer { font-size: 12px; color: #64748b; margin-top: 24px; border-top: 1px solid #f1f5f9; padding-top: 16px; text-align: center; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="logo">🛡️ OnliBackup — Central de Auditoria</div>
            <div class="title">Recuperação de Acesso à Conta</div>
            <p>Olá, <strong>' . htmlspecialchars($recipientName) . '</strong>.</p>
            <p>Recebemos uma solicitação de redefinição de senha para sua conta associada a este e-mail. Utilize o código de verificação abaixo:</p>
            
            <div class="code-box">' . htmlspecialchars($code) . '</div>

            <p style="font-size: 13px; color: #64748b;">
                ⏱️ Este código é válido por <strong>15 minutos</strong>.<br>
                Caso você não tenha solicitado esta alteração, ignore este e-mail por segurança.
            </p>

            <div class="footer">
                OnliBackup Corporativo • Infraestrutura de Backup e Nuvem • Onlitec
            </div>
        </div>
    </body>
    </html>
    ';

    $text = "Olá, " . $recipientName . ".\n\nSeu código de recuperação de senha do OnliBackup é: " . $code . "\n\nEste código expira em 15 minutos.\nSe você não solicitou, desconsidere.";

    return $mailer->send($toEmail, $subject, $html, $text);
}
