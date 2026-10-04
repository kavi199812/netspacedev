<?php
$pageTitle = 'Dashboard Overview';
require_once __DIR__ . '/header.php';

// Fetch Statistics
$totalProjects = (int)$pdo->query("SELECT COUNT(*) FROM projects")->fetchColumn();
$featuredProjects = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE is_featured = 1")->fetchColumn();
$totalBlogs = (int)$pdo->query("SELECT COUNT(*) FROM blogs")->fetchColumn();
$totalMessages = (int)$pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn();

// Fetch Recent Inquiries
$recentMessages = $pdo->query("SELECT * FROM messages ORDER BY created_at DESC LIMIT 5")->fetchAll();

// Fetch Recent Projects
$recentProjects = $pdo->query("SELECT * FROM projects ORDER BY created_at DESC LIMIT 4")->fetchAll();
?>

<div class="page-head">
    <div>
        <h1 class="page-title">Welcome, <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></h1>
        <p class="page-subtitle">Manage software projects, engineering articles, and client inquiries.</p>
    </div>
    <div style="display: flex; gap: 0.75rem;">
        <a href="project-edit.php" class="btn btn-primary">+ New Project</a>
        <a href="blog-edit.php" class="btn btn-secondary">+ Write Article</a>
    </div>
</div>

<!-- Metrics Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-title">Portfolio Projects</div>
        <div class="stat-value"><?= $totalProjects ?></div>
        <div class="stat-desc"><?= $featuredProjects ?> featured on home showcase</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Published Articles</div>
        <div class="stat-value"><?= $totalBlogs ?></div>
        <div class="stat-desc">Tech insights & engineering blog</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">Client Inquiries</div>
        <div class="stat-value"><?= $totalMessages ?></div>
        <div class="stat-desc"><?= $unreadCount ?> new unread messages</div>
    </div>
    <div class="stat-card">
        <div class="stat-title">System Status</div>
        <div class="stat-value" style="color: var(--success); font-size: 1.5rem; margin-top: 0.8rem;">● Online</div>
        <div class="stat-desc">PHP 8.4 + MySQL <code>netspace</code></div>
    </div>
</div>

<!-- Recent Projects Showcase -->
<div style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-size: 1.15rem; font-weight: 600;">Recent Portfolio Projects</h2>
        <a href="projects.php" style="font-size: 0.85rem;">View All Projects →</a>
    </div>

    <div class="card-table">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th style="width: 70px;">Preview</th>
                        <th>Title</th>
                        <th>Category</th>
                        <th>Technologies</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentProjects)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 2rem;">No projects yet. Click "+ New Project" to add one.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentProjects as $p): ?>
                            <tr>
                                <td>
                                    <img src="<?= htmlspecialchars($p['image_url'] ?: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=150&q=80') ?>" alt="" class="thumb-preview">
                                </td>
                                <td>
                                    <strong style="color: var(--text-primary);"><?= htmlspecialchars($p['title']) ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">Slug: /<?= htmlspecialchars($p['slug']) ?></div>
                                </td>
                                <td><span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted);"><?= htmlspecialchars($p['category']) ?></span></td>
                                <td style="font-size: 0.8rem; color: var(--text-muted);"><?= htmlspecialchars($p['technologies']) ?></td>
                                <td>
                                    <?php if ($p['is_featured']): ?>
                                        <span class="badge badge-featured">Featured</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted);">Standard</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="project-edit.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Recent Messages -->
<div>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-size: 1.15rem; font-weight: 600;">Recent Client Inquiries</h2>
        <a href="messages.php" style="font-size: 0.85rem;">View All Inquiries →</a>
    </div>

    <div class="card-table">
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Sender</th>
                        <th>Subject</th>
                        <th>Date</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentMessages)): ?>
                        <tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 2rem;">No inquiries received yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentMessages as $m): ?>
                            <tr>
                                <td>
                                    <?php if (!$m['is_read']): ?>
                                        <span class="badge badge-unread">New</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted);">Read</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong style="color: var(--text-primary);"><?= htmlspecialchars($m['name']) ?></strong>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($m['email']) ?></div>
                                </td>
                                <td><?= htmlspecialchars($m['subject']) ?></td>
                                <td style="font-size: 0.8rem; color: var(--text-muted);"><?= date('M j, Y g:i A', strtotime($m['created_at'])) ?></td>
                                <td style="text-align: right;">
                                    <a href="messages.php#msg-<?= $m['id'] ?>" class="btn btn-secondary btn-sm">View Message</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
