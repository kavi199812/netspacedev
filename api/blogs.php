<?php
require_once __DIR__ . '/config/db.php';
setCorsHeaders();

$pdo = getDBConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Single blog by slug
    if (!empty($_GET['slug'])) {
        $slug = trim($_GET['slug']);
        $stmt = $pdo->prepare("SELECT * FROM blogs WHERE slug = ? AND status = 'published' LIMIT 1");
        $stmt->execute([$slug]);
        $blog = $stmt->fetch();

        if ($blog) {
            $blog['tags_list'] = array_filter(array_map('trim', explode(',', $blog['tags'] ?? '')));
            sendJsonResponse(['success' => true, 'data' => $blog]);
        } else {
            sendJsonResponse(['success' => false, 'error' => 'Article not found'], 404);
        }
    }

    // Single blog by ID
    if (!empty($_GET['id'])) {
        $id = (int)$_GET['id'];
        $stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $blog = $stmt->fetch();

        if ($blog) {
            $blog['tags_list'] = array_filter(array_map('trim', explode(',', $blog['tags'] ?? '')));
            sendJsonResponse(['success' => true, 'data' => $blog]);
        } else {
            sendJsonResponse(['success' => false, 'error' => 'Article not found'], 404);
        }
    }

    // List published blogs
    $sql = "SELECT id, title, slug, excerpt, cover_image, author, category, tags, read_time, created_at, updated_at 
            FROM blogs 
            WHERE status = 'published' 
            ORDER BY created_at DESC";

    if (!empty($_GET['limit'])) {
        $limit = (int)$_GET['limit'];
        $sql .= " LIMIT " . max(1, min(100, $limit));
    }

    $stmt = $pdo->query($sql);
    $blogs = $stmt->fetchAll();

    foreach ($blogs as &$b) {
        $b['tags_list'] = array_filter(array_map('trim', explode(',', $b['tags'] ?? '')));
    }

    sendJsonResponse([
        'success' => true,
        'count'   => count($blogs),
        'data'    => $blogs
    ]);
}

sendJsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
