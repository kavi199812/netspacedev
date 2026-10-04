<?php
require_once __DIR__ . '/config/db.php';
setCorsHeaders();

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Single project by slug
    if (!empty($_GET['slug'])) {
        $slug = trim($_GET['slug']);
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        $project = $stmt->fetch();

        if ($project) {
            $project['technologies_list'] = array_filter(array_map('trim', explode(',', $project['technologies'] ?? '')));
            sendJsonResponse(['success' => true, 'data' => $project]);
        } else {
            sendJsonResponse(['success' => false, 'error' => 'Project not found'], 404);
        }
    }

    // Single project by ID
    if (!empty($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $project = $stmt->fetch();

        if ($project) {
            $project['technologies_list'] = array_filter(array_map('trim', explode(',', $project['technologies'] ?? '')));
            sendJsonResponse(['success' => true, 'data' => $project]);
        } else {
            sendJsonResponse(['success' => false, 'error' => 'Project not found'], 404);
        }
    }

    // List projects with optional filters
    $sql = "SELECT * FROM projects WHERE 1=1";
    $params = [];

    if (isset($_GET['featured']) && $_GET['featured'] === '1') {
        $sql .= " AND is_featured = 1";
    }

    if (!empty($_GET['category'])) {
        $sql .= " AND category = ?";
        $params[] = trim($_GET['category']);
    }

    $sql .= " ORDER BY is_featured DESC, created_at DESC";

    if (!empty($_GET['limit'])) {
        $limit = (int)$_GET['limit'];
        $sql .= " LIMIT " . max(1, min(100, $limit));
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $projects = $stmt->fetchAll();

    foreach ($projects as &$p) {
        $p['technologies_list'] = array_filter(array_map('trim', explode(',', $p['technologies'] ?? '')));
    }

    sendJsonResponse([
        'success' => true,
        'count'   => count($projects),
        'data'    => $projects
    ]);
}

sendJsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
