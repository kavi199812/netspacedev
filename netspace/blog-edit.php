<?php
require_once __DIR__ . '/header.php';

$id = (int)($_GET['id'] ?? 0);
$isEditing = $id > 0;
$pageTitle = $isEditing ? 'Edit Article' : 'Write New Article';

$blog = [
    'title'       => '',
    'slug'        => '',
    'excerpt'     => '',
    'content'     => '',
    'category'    => 'Architecture',
    'author'      => $_SESSION['admin_name'] ?? 'NetSpace Engineering',
    'tags'        => 'Web, Performance, Cloud',
    'status'      => 'published',
    'read_time'   => '5 min read',
    'cover_image' => '',
];

if ($isEditing) {
    $stmt = $pdo->prepare("SELECT * FROM blogs WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $blog = $found;
    } else {
        setFlash('error', 'Article not found.');
        header('Location: blogs.php');
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $title       = trim($_POST['title'] ?? '');
        $slug        = trim($_POST['slug'] ?? '');
        $excerpt     = trim($_POST['excerpt'] ?? '');
        $content     = trim($_POST['content'] ?? '');
        $category    = trim($_POST['category'] ?? 'Architecture');
        $author      = trim($_POST['author'] ?? 'NetSpace Engineering');
        $tags        = trim($_POST['tags'] ?? '');
        $status      = ($_POST['status'] ?? 'published') === 'draft' ? 'draft' : 'published';
        $readTime    = trim($_POST['read_time'] ?? '5 min read');
        $coverImage  = trim($_POST['existing_cover_image'] ?? '');

        if (empty($slug)) {
            $slug = generateSlug($title);
        } else {
            $slug = generateSlug($slug);
        }

        // Handle Image Upload if provided
        if (!empty($_FILES['cover_file']['name'])) {
            try {
                $uploadedUrl = handleFileUpload($_FILES['cover_file'], 'blogs');
                if ($uploadedUrl) {
                    $coverImage = $uploadedUrl;
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } elseif (!empty($_POST['custom_cover_image'])) {
            $coverImage = trim($_POST['custom_cover_image']);
        }

        if (empty($title) || empty($excerpt) || empty($content)) {
            $error = 'Title, summary excerpt, and content are required.';
        }

        if (empty($error)) {
            // Check slug uniqueness
            $checkSql = "SELECT id FROM blogs WHERE slug = ?" . ($isEditing ? " AND id != ?" : "");
            $checkStmt = $pdo->prepare($checkSql);
            $checkParams = [$slug];
            if ($isEditing) $checkParams[] = $id;
            $checkStmt->execute($checkParams);

            if ($checkStmt->fetch()) {
                $slug .= '-' . time();
            }

            if ($isEditing) {
                $updateSql = "UPDATE blogs SET 
                    title = ?, slug = ?, excerpt = ?, content = ?, category = ?, 
                    author = ?, tags = ?, status = ?, read_time = ?, cover_image = ? 
                    WHERE id = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    $title, $slug, $excerpt, $content, $category,
                    $author, $tags, $status, $readTime, $coverImage, $id
                ]);
                setFlash('success', 'Article updated successfully.');
            } else {
                $insertSql = "INSERT INTO blogs 
                    (title, slug, excerpt, content, category, author, tags, status, read_time, cover_image) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    $title, $slug, $excerpt, $content, $category,
                    $author, $tags, $status, $readTime, $coverImage
                ]);
                setFlash('success', 'Article published successfully.');
            }

            header('Location: blogs.php');
            exit;
        }
    }
}
?>

<div class="page-head">
    <div>
        <h1 class="page-title"><?= $isEditing ? 'Edit Article' : 'Write New Article' ?></h1>
        <p class="page-subtitle">Craft technical blogs and architecture deep dives for your tech audience.</p>
    </div>
    <div>
        <a href="blogs.php" class="btn btn-secondary">← Back to Articles</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card">
    <form method="POST" action="blog-edit.php<?= $isEditing ? '?id=' . $id : '' ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="existing_cover_image" value="<?= htmlspecialchars($blog['cover_image'] ?? '') ?>">

        <div class="form-group">
            <label class="form-label" for="title">Article Title *</label>
            <input type="text" id="title" name="title" class="form-control" required value="<?= htmlspecialchars($blog['title']) ?>" placeholder="e.g. Scaling Microservices with Go and Kubernetes">
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label" for="slug">URL Slug (leave blank to auto-generate)</label>
                <input type="text" id="slug" name="slug" class="form-control" value="<?= htmlspecialchars($blog['slug']) ?>" placeholder="e.g. scaling-microservices">
                <p class="form-help">Will be accessed at <code>/blog/{slug}</code></p>
            </div>

            <div class="form-group">
                <label class="form-label" for="category">Category</label>
                <select id="category" name="category" class="form-control">
                    <?php
                    $categories = ['Architecture', 'Web Architecture', 'Backend Engineering', 'Cloud & DevOps', 'Mobile Development', 'AI & Automation'];
                    foreach ($categories as $cat):
                    ?>
                        <option value="<?= $cat ?>" <?= ($blog['category'] === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label" for="author">Author Name</label>
                <input type="text" id="author" name="author" class="form-control" value="<?= htmlspecialchars($blog['author']) ?>">
            </div>

            <div class="form-group">
                <label class="form-label" for="read_time">Estimated Read Time</label>
                <input type="text" id="read_time" name="read_time" class="form-control" value="<?= htmlspecialchars($blog['read_time']) ?>" placeholder="5 min read">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label" for="tags">Tags (comma-separated)</label>
                <input type="text" id="tags" name="tags" class="form-control" value="<?= htmlspecialchars($blog['tags']) ?>" placeholder="Astro, Jamstack, Node.js, Performance">
            </div>

            <div class="form-group">
                <label class="form-label" for="status">Publication Status</label>
                <select id="status" name="status" class="form-control">
                    <option value="published" <?= $blog['status'] === 'published' ? 'selected' : '' ?>>Published (Live on Website)</option>
                    <option value="draft" <?= $blog['status'] === 'draft' ? 'selected' : '' ?>>Draft (Saved Privately)</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Article Cover Image</label>
            <?php if (!empty($blog['cover_image'])): ?>
                <div style="margin-bottom: 0.75rem; display: flex; align-items: center; gap: 1rem;">
                    <img src="<?= htmlspecialchars($blog['cover_image']) ?>" alt="Cover Preview" style="width: 120px; height: 75px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                    <div style="font-size: 0.85rem; color: var(--text-muted);">Current cover preview</div>
                </div>
            <?php endif; ?>
            <input type="file" id="cover_file" name="cover_file" class="form-control" accept="image/*">
            <p class="form-help">Upload an image file or specify external image URL below.</p>
            <input type="text" name="custom_cover_image" class="form-control" style="margin-top: 0.5rem;" placeholder="Or paste external image URL (https://...)" value="<?= (str_starts_with($blog['cover_image'] ?? '', 'http') ? htmlspecialchars($blog['cover_image']) : '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="excerpt">Short Summary / Excerpt *</label>
            <textarea id="excerpt" name="excerpt" class="form-control" style="min-height: 80px;" required placeholder="Brief summary of this article shown on blog cards and search listings."><?= htmlspecialchars($blog['excerpt']) ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label" for="content">Full Article Content (Markdown or HTML supported) *</label>
            <textarea id="content" name="content" class="form-control" style="min-height: 250px; font-family: var(--font-mono); font-size: 0.9rem;" required placeholder="Write your article paragraphs here..."><?= htmlspecialchars($blog['content']) ?></textarea>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary">
                <?= $isEditing ? 'Save Changes' : 'Publish Article' ?>
            </button>
            <a href="blogs.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
