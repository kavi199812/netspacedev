<?php
$pageTitle = 'Manage Projects';
require_once __DIR__ . '/header.php';

// Handle Delete Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        setFlash('success', 'Project deleted successfully.');
    } else {
        setFlash('error', 'Invalid security token.');
    }
    header('Location: projects.php');
    exit;
}

// Fetch all projects
$search = trim($_GET['search'] ?? '');
if (!empty($search)) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE title LIKE ? OR technologies LIKE ? OR category LIKE ? ORDER BY created_at DESC");
    $stmt->execute(["%$search%", "%$search%", "%$search%"]);
    $projects = $stmt->fetchAll();
} else {
    $projects = $pdo->query("SELECT * FROM projects ORDER BY is_featured DESC, created_at DESC")->fetchAll();
}
?>

<div class="page-head">
    <div>
        <h1 class="page-title">Portfolio Projects</h1>
        <p class="page-subtitle">Showcase your software engineering work, clients, and technical stack.</p>
    </div>
    <div>
        <a href="project-edit.php" class="btn btn-primary">+ Add New Project</a>
    </div>
</div>

<!-- Search Bar -->
<div style="margin-bottom: 1.5rem; display: flex; gap: 1rem; align-items: center;">
    <form method="GET" action="projects.php" style="display: flex; gap: 0.5rem; flex: 1; max-width: 450px;">
        <input type="text" name="search" class="form-control" placeholder="Search by title, technology, category..." value="<?= htmlspecialchars($search) ?>">
        <button type="submit" class="btn btn-secondary">Search</button>
        <?php if (!empty($search)): ?>
            <a href="projects.php" class="btn btn-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card-table">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 70px;">Thumbnail</th>
                    <th>Title & Slug</th>
                    <th>Category</th>
                    <th>Client</th>
                    <th>Tech Stack</th>
                    <th>Featured</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($projects)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 3rem;">
                            No projects found. <a href="project-edit.php" style="color: var(--accent);">Create your first project</a>.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($projects as $p): ?>
                        <tr>
                            <td>
                                <img src="<?= htmlspecialchars($p['image_url'] ?: 'https://images.unsplash.com/photo-1551288049-bebda4e38f71?auto=format&fit=crop&w=150&q=80') ?>" alt="" class="thumb-preview">
                            </td>
                            <td>
                                <strong style="color: var(--text-primary); font-size: 0.95rem;"><?= htmlspecialchars($p['title']) ?></strong>
                                <div style="font-size: 0.78rem; color: var(--text-muted); font-family: var(--font-mono);">
                                    /projects/<?= htmlspecialchars($p['slug']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted);">
                                    <?= htmlspecialchars($p['category']) ?>
                                </span>
                            </td>
                            <td style="color: var(--text-muted); font-size: 0.85rem;">
                                <?= htmlspecialchars($p['client_name'] ?? 'NetSpace') ?>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-muted); max-width: 200px;">
                                <?= htmlspecialchars($p['technologies']) ?>
                            </td>
                            <td>
                                <?php if ($p['is_featured']): ?>
                                    <span class="badge badge-featured">★ Featured</span>
                                <?php else: ?>
                                    <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted);">Standard</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 0.4rem;">
                                    <a href="project-edit.php?id=<?= $p['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <form method="POST" action="projects.php" onsubmit="return confirm('Are you sure you want to delete this project?');" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">
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
