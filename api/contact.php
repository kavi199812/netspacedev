<?php
require_once __DIR__ . '/config/db.php';
setCorsHeaders();

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    // Read input from JSON or standard POST form data
    $rawInput = file_get_contents('php://input');
    $data = json_decode($rawInput, true);

    if (!$data) {
        $data = $_POST;
    }

    $name    = trim($data['name'] ?? '');
    $email   = trim($data['email'] ?? '');
    $subject = trim($data['subject'] ?? 'Project Inquiry via NetSpace Website');
    $message = trim($data['message'] ?? '');

    // Validation
    if (empty($name) || empty($email) || empty($message)) {
        sendJsonResponse([
            'success' => false,
            'error'   => 'Please provide your name, email, and message.'
        ], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendJsonResponse([
            'success' => false,
            'error'   => 'Please enter a valid email address.'
        ], 400);
    }

    try {
        // 1. Store message in MySQL database for permanent record
        $stmt = $pdo->prepare("INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message]);
        $messageId = (int)$pdo->lastInsertId();

        // 2. Dispatch Email notification to hello@netspacedev.com
        $toEmail = getenv('CONTACT_TO_EMAIL') ?: 'hello@netspacedev.com';
        $fromEmail = getenv('CONTACT_FROM_EMAIL') ?: 'no-reply@netspacedev.com';

        $emailSent = sendContactNotificationEmail(
            $toEmail,
            $fromEmail,
            $name,
            $email,
            $subject,
            $message,
            $messageId
        );

        sendJsonResponse([
            'success'    => true,
            'message'    => 'Thank you! Your message has been received. Our engineering team will contact you shortly.',
            'message_id' => $messageId,
            'email_sent' => $emailSent
        ], 201);
    } catch (PDOException $e) {
        sendJsonResponse([
            'success' => false,
            'error'   => 'Failed to save message. Please try again later.'
        ], 500);
    }
}

sendJsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);

/**
 * Sends a modern, responsive HTML notification email to hello@netspacedev.com
 */
function sendContactNotificationEmail(
    string $toEmail,
    string $fromEmail,
    string $name,
    string $email,
    string $subject,
    string $message,
    int $messageId
): bool {
    // Sanitize headers to prevent header injection attacks
    $safeName    = trim(str_replace(["\r", "\n", "%0a", "%0d"], '', $name));
    $safeEmail   = trim(str_replace(["\r", "\n", "%0a", "%0d"], '', $email));
    $safeSubject = trim(str_replace(["\r", "\n", "%0a", "%0d"], '', $subject));

    $fullSubject = "[NetSpace Inquiry #" . $messageId . "] " . $safeSubject;
    $encodedSubject = '=?UTF-8?B?' . base64_encode($fullSubject) . '?=';

    $dateTime  = date('Y-m-d H:i:s T');
    $clientIp  = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $userAgent = htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');

    $escapedName    = htmlspecialchars($safeName, ENT_QUOTES, 'UTF-8');
    $escapedEmail   = htmlspecialchars($safeEmail, ENT_QUOTES, 'UTF-8');
    $escapedSubject = htmlspecialchars($safeSubject, ENT_QUOTES, 'UTF-8');
    $escapedMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

    $replyMailto = "mailto:{$escapedEmail}?subject=" . rawurlencode("Re: {$safeSubject} - NetSpace Dev");

    // Modern HTML Email Template styled in NetSpace Dev theme
    $htmlBody = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{$escapedSubject}</title>
  <style>
    body { margin: 0; padding: 0; background-color: #0c0f14; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #e6edf3; }
    .email-container { max-width: 600px; margin: 30px auto; background-color: #12161f; border: 1px solid #21262d; border-radius: 12px; overflow: hidden; }
    .email-header { background-color: #0c0f14; padding: 24px 30px; border-bottom: 1px solid #21262d; }
    .brand-title { font-size: 20px; font-weight: 700; color: #f0f6fc; margin: 0; }
    .brand-badge { display: inline-block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.08em; background-color: #1c2331; color: #38bdf8; padding: 3px 8px; border-radius: 4px; margin-top: 6px; }
    .email-body { padding: 30px; }
    .inquiry-meta-table { width: 100%; border-collapse: collapse; margin-bottom: 24px; font-size: 14px; }
    .inquiry-meta-table td { padding: 9px 12px; border-bottom: 1px solid #1e2430; }
    .inquiry-meta-table .meta-label { width: 130px; color: #8b949e; font-weight: 600; }
    .inquiry-meta-table .meta-value { color: #f0f6fc; }
    .message-card { background-color: #161b24; border: 1px solid #28303d; border-left: 4px solid #38bdf8; border-radius: 8px; padding: 18px 20px; margin: 20px 0 26px 0; }
    .message-title { font-size: 13px; font-weight: 700; color: #8b949e; text-transform: uppercase; letter-spacing: 0.05em; margin: 0 0 10px 0; }
    .message-text { font-size: 15px; line-height: 1.65; color: #e6edf3; margin: 0; }
    .action-row { text-align: center; margin: 28px 0 12px 0; }
    .reply-btn { display: inline-block; background-color: #38bdf8; color: #0c0f14 !important; font-size: 14px; font-weight: 700; text-decoration: none; padding: 12px 26px; border-radius: 6px; }
    .email-footer { background-color: #0c0f14; padding: 18px 30px; border-top: 1px solid #21262d; font-size: 12px; color: #8b949e; text-align: center; }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="email-header">
      <h1 class="brand-title">NetSpace Dev</h1>
      <span class="brand-badge">New Website Inquiry #{$messageId}</span>
    </div>
    <div class="email-body">
      <table class="inquiry-meta-table">
        <tr>
          <td class="meta-label">Client Name</td>
          <td class="meta-value"><strong>{$escapedName}</strong></td>
        </tr>
        <tr>
          <td class="meta-label">Email Address</td>
          <td class="meta-value"><a href="mailto:{$escapedEmail}" style="color: #38bdf8; text-decoration: none;">{$escapedEmail}</a></td>
        </tr>
        <tr>
          <td class="meta-label">Subject</td>
          <td class="meta-value">{$escapedSubject}</td>
        </tr>
        <tr>
          <td class="meta-label">Received At</td>
          <td class="meta-value">{$dateTime}</td>
        </tr>
        <tr>
          <td class="meta-label">Client IP</td>
          <td class="meta-value" style="font-family: monospace; font-size: 12px; color: #8b949e;">{$clientIp}</td>
        </tr>
      </table>

      <div class="message-card">
        <div class="message-title">Message Content</div>
        <p class="message-text">{$escapedMessage}</p>
      </div>

      <div class="action-row">
        <a href="{$replyMailto}" class="reply-btn">Reply Directly to {$escapedName}</a>
      </div>
    </div>
    <div class="email-footer">
      This notification was automatically sent from the contact form on <a href="https://netspacedev.com" style="color: #8b949e; text-decoration: underline;">netspacedev.com</a>.<br>
      NetSpace Global (Pvt) Ltd &bull; hello@netspacedev.com
    </div>
  </div>
</body>
</html>
HTML;

    // Email headers
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: NetSpace Dev <' . $fromEmail . '>',
        'Reply-To: ' . $safeName . ' <' . $safeEmail . '>',
        'X-Mailer: PHP/' . phpversion(),
        'X-Priority: 1 (Highest)',
        'Importance: High'
    ];

    $headersStr = implode("\r\n", $headers);

    // Attempt to send email via standard PHP mail()
    // Suppress warnings in case local dev environment lacks sendmail/MTA
    $sent = @mail($toEmail, $encodedSubject, $htmlBody, $headersStr, "-f" . $fromEmail);

    if (!$sent) {
        // Fallback without -f flag if restricted by server configuration
        $sent = @mail($toEmail, $encodedSubject, $htmlBody, $headersStr);
    }

    return (bool)$sent;
}
