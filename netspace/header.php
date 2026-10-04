<?php
// NetSpace Admin - Header Component
require_once __DIR__ . '/config.php';
requireLogin();

$pdo = getDBConnection();
// Unread messages count
$unreadCount = (int)$pdo->query("SELECT COUNT(*) FROM messages WHERE is_read = 0")->fetchColumn();
$currentScript = basename($_SERVER['PHP_SELF']);
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin Dashboard') ?> | NetSpace Console</title>
    <link rel="stylesheet" href="assets/admin.css">
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
</head>
<body>

<header class="admin-header">
    <div class="admin-header-inner">
        <a href="index.php" class="brand-badge">
            <span class="brand-logo-icon">N</span>
            <span>NetSpace <span style="font-weight: 400; color: var(--accent); font-size: 0.9rem;">Studio</span></span>
        </a>

        <ul class="nav-links">
            <li>
                <a href="index.php" class="nav-link <?= $currentScript === 'index.php' ? 'active' : '' ?>">
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="projects.php" class="nav-link <?= in_array($currentScript, ['projects.php', 'project-edit.php']) ? 'active' : '' ?>">
                    <span>Projects</span>
                </a>
            </li>
            <li>
                <a href="blogs.php" class="nav-link <?= in_array($currentScript, ['blogs.php', 'blog-edit.php']) ? 'active' : '' ?>">
                    <span>Blog Posts</span>
                </a>
            </li>
            <li>
                <a href="messages.php" class="nav-link <?= $currentScript === 'messages.php' ? 'active' : '' ?>">
                    <span>Inquiries</span>
                    <?php if ($unreadCount > 0): ?>
                        <span class="badge-count"><?= $unreadCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="settings.php" class="nav-link <?= $currentScript === 'settings.php' ? 'active' : '' ?>">
                    <span>PIN Settings</span>
                </a>
            </li>
        </ul>

        <div class="user-actions">
            <a href="http://localhost:4321/" target="_blank" class="btn-view-site" title="Preview Astro Frontend">
                <span>View Site ↗</span>
            </a>
            <a href="logout.php" class="btn-logout" onclick="return confirm('Are you sure you want to log out?');">
                <span>Logout</span>
            </a>
        </div>
    </div>
</header>

<main class="admin-container">
    <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] === 'error' ? 'error' : 'success' ?>">
            <?= htmlspecialchars($flash['message']) ?>
        </div>
    <?php endif; ?>
