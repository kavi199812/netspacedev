<?php
/**
 * NetSpace Dev - Lightweight Pure PHP SMTP Mailer
 * Zero external dependencies. Fully compatible with Hostinger Titan Mail & Standard SMTP.
 * Supports SSL (Port 465) and STARTTLS (Port 587).
 */

class NetSpaceMailer {
    private string $host;
    private int $port;
    private string $secure; // 'ssl', 'tls', or 'none'
    private string $username;
    private string $password;
    private int $timeout;

    public string $lastError = '';
    public array $debugLog = [];

    public function __construct(
        string $host = 'smtp.hostinger.com',
        int $port = 465,
        string $secure = 'ssl',
        string $username = '',
        string $password = '',
        int $timeout = 15
    ) {
        $this->host     = trim($host);
        $this->port     = $port;
        $this->secure   = strtolower(trim($secure));
        $this->username = trim($username);
        $this->password = trim($password);
        $this->timeout  = $timeout;
    }

    /**
     * Send an email via SMTP
     */
    public function send(
        string $toEmail,
        string $subject,
        string $htmlBody,
        string $fromEmail,
        string $fromName = 'NetSpace Dev',
        string $replyToEmail = '',
        string $replyToName = ''
    ): bool {
        $this->debugLog  = [];
        $this->lastError = '';

        // Determine socket protocol
        $socketProtocol = ($this->secure === 'ssl' || $this->port === 465) ? 'ssl://' : 'tcp://';
        $targetHost     = $socketProtocol . $this->host . ':' . $this->port;

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ]);

        $this->log("Connecting to {$targetHost} (Timeout: {$this->timeout}s)...");
        $socket = @stream_socket_client(
            $targetHost,
            $errno,
            $errstr,
            $this->timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            $this->lastError = "Connection failed to {$this->host}:{$this->port} - [{$errno}] {$errstr}";
            $this->log("ERROR: " . $this->lastError);
            return false;
        }

        stream_set_timeout($socket, $this->timeout);

        // Read initial server greeting (220)
        $response = $this->readResponse($socket);
        if (!$this->checkCode($response, 220)) {
            $this->closeAndFail($socket, "Invalid greeting from mail server: {$response}");
            return false;
        }

        // Send EHLO
        $clientDomain = !empty($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'netspacedev.com';
        $this->sendCommand($socket, "EHLO {$clientDomain}");
        $response = $this->readResponse($socket);
        if (!$this->checkCode($response, 250)) {
            $this->closeAndFail($socket, "EHLO rejected: {$response}");
            return false;
        }

        // STARTTLS upgrade if port 587 or tls mode
        if ($this->secure === 'tls' || ($this->port === 587 && strpos($response, 'STARTTLS') !== false)) {
            $this->sendCommand($socket, "STARTTLS");
            $response = $this->readResponse($socket);
            if (!$this->checkCode($response, 220)) {
                $this->closeAndFail($socket, "STARTTLS failed: {$response}");
                return false;
            }

            // Enable crypto
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                $this->closeAndFail($socket, "TLS handshake failed.");
                return false;
            }

            // Re-send EHLO after TLS negotiation
            $this->sendCommand($socket, "EHLO {$clientDomain}");
            $response = $this->readResponse($socket);
            if (!$this->checkCode($response, 250)) {
                $this->closeAndFail($socket, "EHLO post-TLS rejected: {$response}");
                return false;
            }
        }

        // Authenticate if credentials are provided
        if (!empty($this->username) && !empty($this->password)) {
            $this->sendCommand($socket, "AUTH LOGIN");
            $response = $this->readResponse($socket);
            if (!$this->checkCode($response, 334)) {
                $this->closeAndFail($socket, "AUTH LOGIN rejected: {$response}");
                return false;
            }

            $this->sendCommand($socket, base64_encode($this->username));
            $response = $this->readResponse($socket);
            if (!$this->checkCode($response, 334)) {
                $this->closeAndFail($socket, "Username rejected: {$response}");
                return false;
            }

            $this->sendCommand($socket, base64_encode($this->password));
            $response = $this->readResponse($socket);
            if (!$this->checkCode($response, 235)) {
                $this->closeAndFail($socket, "Authentication failed (Invalid password or account blocked): {$response}");
                return false;
            }
            $this->log("Authenticated successfully as {$this->username}");
        }

        // MAIL FROM
        $this->sendCommand($socket, "MAIL FROM:<{$fromEmail}>");
        $response = $this->readResponse($socket);
        if (!$this->checkCode($response, 250)) {
            $this->closeAndFail($socket, "MAIL FROM rejected: {$response}");
            return false;
        }

        // RCPT TO
        $this->sendCommand($socket, "RCPT TO:<{$toEmail}>");
        $response = $this->readResponse($socket);
        if (!$this->checkCode($response, 250)) {
            $this->closeAndFail($socket, "RCPT TO rejected: {$response}");
            return false;
        }

        // DATA
        $this->sendCommand($socket, "DATA");
        $response = $this->readResponse($socket);
        if (!$this->checkCode($response, 354)) {
            $this->closeAndFail($socket, "DATA command rejected: {$response}");
            return false;
        }

        // Build RFC-compliant headers & message
        $encodedSubject  = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedFromName = '=?UTF-8?B?' . base64_encode($fromName) . '?=';
        $messageId       = '<' . bin2hex(random_bytes(16)) . '@' . $clientDomain . '>';
        $dateHeader      = date('r');

        $headers   = [];
        $headers[] = "Date: {$dateHeader}";
        $headers[] = "Message-ID: {$messageId}";
        $headers[] = "From: {$encodedFromName} <{$fromEmail}>";
        $headers[] = "To: <{$toEmail}>";

        if (!empty($replyToEmail)) {
            $encodedReplyName = !empty($replyToName) ? '=?UTF-8?B?' . base64_encode($replyToName) . '?=' : $replyToEmail;
            $headers[] = "Reply-To: {$encodedReplyName} <{$replyToEmail}>";
        }

        $headers[] = "Subject: {$encodedSubject}";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: text/html; charset=UTF-8";
        $headers[] = "Content-Transfer-Encoding: base64";
        $headers[] = "X-Mailer: NetSpace-SMTP-Mailer/1.0";

        // Dot-stuffing and base64 encoding body
        $body = chunk_split(base64_encode($htmlBody));

        $emailData = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";

        // Send payload
        fwrite($socket, $emailData . "\r\n");
        $response = $this->readResponse($socket);
        if (!$this->checkCode($response, 250)) {
            $this->closeAndFail($socket, "Failed to submit email content: {$response}");
            return false;
        }

        // QUIT
        $this->sendCommand($socket, "QUIT");
        fclose($socket);

        $this->log("Email successfully dispatched to {$toEmail}!");
        return true;
    }

    private function sendCommand($socket, string $command): void {
        $logCommand = str_starts_with($command, 'AUTH') ? 'AUTH ***' : $command;
        // Obscure base64 password in debug log
        if (strlen($command) > 0 && !str_contains($command, ' ') && !str_starts_with($command, 'EHLO')) {
            $this->log(">>> [AUTH CREDENTIAL]");
        } else {
            $this->log(">>> {$logCommand}");
        }
        fwrite($socket, $command . "\r\n");
    }

    private function readResponse($socket): string {
        $response = '';
        while (!feof($socket)) {
            $line = fgets($socket, 512);
            if ($line === false) {
                break;
            }
            $response .= $line;
            // Check if end of multiline response (e.g. "250-..." vs "250 ...")
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $this->log("<<< " . trim($response));
        return trim($response);
    }

    private function checkCode(string $response, int $expectedCode): bool {
        return str_starts_with($response, (string)$expectedCode);
    }

    private function closeAndFail($socket, string $errorMessage): void {
        $this->lastError = $errorMessage;
        $this->log("ERROR: {$errorMessage}");
        @fwrite($socket, "QUIT\r\n");
        @fclose($socket);
    }

    private function log(string $msg): void {
        $this->debugLog[] = date('[Y-m-d H:i:s] ') . $msg;
    }
}
