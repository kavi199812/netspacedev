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
        $stmt = $pdo->prepare("INSERT INTO messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $message]);

        sendJsonResponse([
            'success' => true,
            'message' => 'Thank you! Your message has been received. Our engineering team will contact you shortly.'
        ], 201);
    } catch (PDOException $e) {
        sendJsonResponse([
            'success' => false,
            'error'   => 'Failed to save message. Please try again later.'
        ], 500);
    }
}

sendJsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
