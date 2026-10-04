<?php
$pageTitle = 'Manage Articles & Blogs';
require_once __DIR__ . '/header.php';

// Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM blogs WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Article deleted successfully.');
    } else {
        setFlash('error', 'Invalid security token.');
    }
    header('Location: blogs.php');
    exit;
}

// Fetch all blogs
$search = trim($_GET['search'] ?? '');
if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT * FROM blogs WHERE title LIKE ? OR tags LIKE ? OR category LIKE ? ORDER BY created_at DESC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    $blogs = $stmt->fetchAll();
} else {
    $blogs = $pdo->query("SELECT * FROM blogs ORDER BY created_at DESC")->fetchAll();
}
?>

<div class="page-head">
    <div>
        <h1 class="page-title">Engineering Articles & Blog</h1>
        <p class="page-subtitle">Publish tech articles, architecture breakdowns, and company updates.</p>
    </div>
    <div>
        <a href="blog-edit.php" class="btn btn-primary">+ Write New Article</a>
    </div>
</div>

<!-- Search Bar -->
<div style="margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center;">
    <form method="GET" action="blogs.php" style="display: flex; gap: 0.5rem; flex: 1; max-width: 450px;">
        <input type="text" name="search" class="form-control" placeholder="Search by title, tag, category..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-secondary">Search</button>
        <?php if (!empty($search)): ?>
            <a href="blogs.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card-table">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 70px;">Cover</th>
                    <th>Title & Slug</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($blogs)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem;">
                            No articles found. <a href="blog-edit.php" style="color: var(--accent);">Write your first article</a>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($blogs as $b): ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars($b['cover_image'] ?: 'https://images.unsplash.com/photo-1461749280684-dccba630e2f6?auto=format&fit=crop&w=150&q=80') ?>" alt="" class="thumb-preview">
                            </td>
                            <td>
                                <strong style="color: var(--text-primary); font-size: 0.95rem;"><?= htmlspecialchars($b['title']) ?></strong>
                                <div style="font-size: 0.78rem; color: var(--text-muted); font-family: var(--font-mono);">
                                    /blog/<?= htmlspecialchars($b['slug']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted);">
                                    <?= htmlspecialchars($b['category']) ?>
                                </span>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">
                                <?= htmlspecialchars($b['author']) ?>
                            </td>
                            <td>
                                <?php if ($b['status'] === 'published'): ?>
                                    <span class="badge badge-published">Published</span>
                                <?php else: ?>
                                    <span class="badge badge-draft">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-muted);">
                                <?= date('M j, Y', strtotime($b['created_at'])) ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.4rem;">
                                    <a href="blog-edit.php?id=<?= $b['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <form method="POST" action="blogs.php" onsubmit="return confirm('Are you sure you want to delete this article?');" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
