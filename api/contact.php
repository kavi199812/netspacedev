<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/mail.php';
setCorsHeaders();

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

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
        $toEmail     = MAIL_TO_ADDRESS;
        $htmlBody    = buildContactEmailHtml($name, $email, $subject, $message, $messageId);
        $fullSubject = "[NetSpace Inquiry #" . $messageId . "] " . $subject;

        $mailResult = sendNetSpaceEmail($toEmail, $fullSubject, $htmlBody, $email, $name);

        sendJsonResponse([
            'success'     => true,
            'message'     => 'Thank you! Your message has been received. Our engineering team will contact you shortly.',
            'message_id'  => $messageId,
            'mail_status' => $mailResult['success'] ? 'sent' : 'failed',
            'mail_driver' => $mailResult['driver']
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
 * Builds responsive dark-themed HTML email content matching NetSpace Dev branding
 */
function buildContactEmailHtml(
    string $name,
    string $email,
    string $subject,
    string $message,
    int $messageId
): string {
    $dateTime  = date('Y-m-d H:i:s T');
    $clientIp  = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';

    $escapedName    = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
    $escapedEmail   = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
    $escapedSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
    $escapedMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

    $replyMailto = "mailto:{$escapedEmail}?subject=" . rawurlencode("Re: {$subject} - NetSpace Dev");

    return <<<HTML
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
}
