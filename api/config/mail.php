<?php
/**
 * NetSpace Dev - Mail Configuration & Helper
 * Supports authenticated Hostinger SMTP (Port 465 SSL) and PHP mail() fallback.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/../utils/Mailer.php';

// SMTP Configuration (Can be customized via .env or Hostinger environment variables)
define('MAIL_SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.hostinger.com');
define('MAIL_SMTP_PORT', (int)(getenv('SMTP_PORT') ?: 465));
define('MAIL_SMTP_SECURE', getenv('SMTP_SECURE') ?: 'ssl');
define('MAIL_SMTP_USER', getenv('SMTP_USER') ?: 'hello@netspacedev.com');
define('MAIL_SMTP_PASS', getenv('SMTP_PASS') !== false ? getenv('SMTP_PASS') : '');

// Email destination & sender addresses
define('MAIL_TO_ADDRESS', getenv('CONTACT_TO_EMAIL') ?: 'hello@netspacedev.com');
define('MAIL_FROM_ADDRESS', getenv('CONTACT_FROM_EMAIL') ?: (MAIL_SMTP_USER ?: 'hello@netspacedev.com'));
define('MAIL_FROM_NAME', getenv('CONTACT_FROM_NAME') ?: 'NetSpace Dev');

/**
 * Dispatch an email with automatic SMTP and fallback logic
 */
function sendNetSpaceEmail(
    string $toEmail,
    string $subject,
    string $htmlBody,
    string $replyToEmail = '',
    string $replyToName = ''
): array {
    $fromEmail = MAIL_FROM_ADDRESS;
    $fromName  = MAIL_FROM_NAME;

    // 1. Try Authenticated SMTP if password or host is configured
    if (!empty(MAIL_SMTP_PASS)) {
        try {
            $mailer = new NetSpaceMailer(
                MAIL_SMTP_HOST,
                MAIL_SMTP_PORT,
                MAIL_SMTP_SECURE,
                MAIL_SMTP_USER,
                MAIL_SMTP_PASS,
                15
            );

            $sent = $mailer->send(
                $toEmail,
                $subject,
                $htmlBody,
                $fromEmail,
                $fromName,
                $replyToEmail,
                $replyToName
            );

            if ($sent) {
                logMailEvent("SMTP SUCCESS to {$toEmail}: {$subject}");
                return ['success' => true, 'driver' => 'smtp', 'error' => ''];
            } else {
                $errorMsg = $mailer->lastError ?: 'Unknown SMTP error';
                logMailEvent("SMTP FAILED to {$toEmail}: {$errorMsg}");
                // Fall back to PHP mail()
            }
        } catch (Throwable $e) {
            logMailEvent("SMTP EXCEPTION to {$toEmail}: " . $e->getMessage());
        }
    } else {
        logMailEvent("SMTP_PASS not set in .env. Attempting PHP mail() fallback.");
    }

    // 2. Fallback to PHP native mail()
    $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $fromName . ' <' . $fromEmail . '>',
        'Reply-To: ' . ($replyToName ? $replyToName . ' <' . $replyToEmail . '>' : $replyToEmail),
        'X-Mailer: PHP/' . phpversion()
    ];

    $headersStr = implode("\r\n", $headers);
    $mailSent = @mail($toEmail, $encodedSubject, $htmlBody, $headersStr, "-f" . $fromEmail);

    if (!$mailSent) {
        $mailSent = @mail($toEmail, $encodedSubject, $htmlBody, $headersStr);
    }

    if ($mailSent) {
        logMailEvent("PHP mail() SUCCESS to {$toEmail}: {$subject}");
        return ['success' => true, 'driver' => 'mail', 'error' => ''];
    }

    $lastErr = error_get_last();
    $errMsg  = $lastErr['message'] ?? 'PHP mail() function returned false. SMTP authentication is required.';
    logMailEvent("PHP mail() FAILED to {$toEmail}: {$errMsg}");

    return [
        'success' => false,
        'driver'  => 'mail',
        'error'   => $errMsg
    ];
}

/**
 * Log mail event to api/logs/mail.log for easy troubleshooting
 */
function logMailEvent(string $message): void {
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    $logFile = $logDir . '/mail.log';
    $entry = date('[Y-m-d H:i:s] ') . $message . "\n";
    @file_put_contents($logFile, $entry, FILE_APPEND);
}
