<?php
/**
 * NetSpace Dev - Mail Diagnostics & Test Endpoint
 * Allows testing email dispatch directly to verify Hostinger SMTP & Titan Mail settings.
 */

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/mail.php';
setCorsHeaders();

$action = $_GET['action'] ?? 'status';

if ($action === 'status') {
    $hasPassword = !empty(MAIL_SMTP_PASS);
    $maskedPass = $hasPassword ? substr(MAIL_SMTP_PASS, 0, 2) . '••••' . substr(MAIL_SMTP_PASS, -2) : '(Not Set)';

    $logFile = __DIR__ . '/logs/mail.log';
    $recentLogs = [];
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $recentLogs = array_slice($lines, -15);
    }

    sendJsonResponse([
        'status' => 'ready',
        'config' => [
            'smtp_host'       => MAIL_SMTP_HOST,
            'smtp_port'       => MAIL_SMTP_PORT,
            'smtp_secure'     => MAIL_SMTP_SECURE,
            'smtp_user'       => MAIL_SMTP_USER,
            'smtp_pass_set'   => $hasPassword,
            'smtp_pass_hint'  => $maskedPass,
            'to_address'      => MAIL_TO_ADDRESS,
            'from_address'    => MAIL_FROM_ADDRESS,
            'from_name'       => MAIL_FROM_NAME,
        ],
        'recent_logs' => $recentLogs
    ]);
}

if ($action === 'send_test') {
    $to = $_GET['to'] ?? MAIL_TO_ADDRESS;
    $testSubject = "[NetSpace Dev] SMTP Test Verification (" . date('H:i:s') . ")";
    $testHtml = <<<HTML
<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #0c0f14; color: #e6edf3; padding: 20px; }
    .box { max-width: 520px; margin: 0 auto; background: #12161f; border: 1px solid #21262d; border-radius: 10px; padding: 24px; }
    h2 { color: #38bdf8; margin-top: 0; }
    p { line-height: 1.6; color: #8b949e; }
    .success-badge { display: inline-block; background: rgba(63, 185, 80, 0.2); border: 1px solid #7ee787; color: #7ee787; padding: 4px 12px; border-radius: 4px; font-weight: bold; font-size: 13px; }
  </style>
</head>
<body>
  <div class="box">
    <div class="success-badge">✓ SMTP VERIFIED</div>
    <h2>NetSpace Dev Email Test Successful!</h2>
    <p>Your Hostinger Titan Mail / SMTP settings are functioning correctly. Inquiries submitted via the Contact Us form will be delivered to this mailbox reliably.</p>
    <p style="font-size: 12px; color: #64748b;">Timestamp: <?= date('Y-m-d H:i:s T') ?> | Host: <?= htmlspecialchars(MAIL_SMTP_HOST) ?></p>
  </div>
</body>
</html>
HTML;

    $result = sendNetSpaceEmail($to, $testSubject, $testHtml, MAIL_TO_ADDRESS, 'NetSpace System Test');

    sendJsonResponse([
        'success'  => $result['success'],
        'driver'   => $result['driver'],
        'error'    => $result['error'],
        'to_email' => $to,
        'message'  => $result['success']
            ? "Test email was successfully dispatched to {$to} via {$result['driver']}!"
            : "Failed to send test email: {$result['error']}"
    ], $result['success'] ? 200 : 500);
}
