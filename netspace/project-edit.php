<?php
require_once __DIR__ . '/header.php';

$id = (int)($_GET['id'] ?? 0);
$isEditing = $id > 0;
$pageTitle = $isEditing ? 'Edit Project' : 'Add New Project';

$project = [
    'title'        => '',
    'slug'         => '',
    'summary'      => '',
    'description'  => '',
    'category'     => 'Web Development',
    'technologies' => 'React, Node.js, MySQL',
    'client_name'  => '',
    'image_url'    => '',
    'live_url'     => '',
    'github_url'   => '',
    'is_featured'  => 0,
];

if ($isEditing) {
    $stmt = $pdo->prepare("SELECT * FROM projects WHERE id = ? LIMIT 1");
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) {
        $project = $found;
    } else {
        setFlash('error', 'Project not found.');
        header('Location: projects.php');
        exit;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please try again.';
    } else {
        $title        = trim($_POST['title'] ?? '');
        $slug         = trim($_POST['slug'] ?? '');
        $summary      = trim($_POST['summary'] ?? '');
        $description  = trim($_POST['description'] ?? '');
        $category     = trim($_POST['category'] ?? 'Web Development');
        $technologies = trim($_POST['technologies'] ?? '');
        $client_name  = trim($_POST['client_name'] ?? '');
        $live_url     = trim($_POST['live_url'] ?? '');
        $github_url   = trim($_POST['github_url'] ?? '');
        $is_featured  = isset($_POST['is_featured']) ? 1 : 0;
        $imageUrl     = trim($_POST['existing_image_url'] ?? '');

        if (empty($slug)) {
            $slug = generateSlug($title);
        } else {
            $slug = generateSlug($slug);
        }

        // Handle Image Upload if provided
        if (!empty($_FILES['image_file']['name'])) {
            try {
                $uploadedUrl = handleFileUpload($_FILES['image_file'], 'projects');
                if ($uploadedUrl) {
                    $imageUrl = $uploadedUrl;
                }
            } catch (Exception $e) {
                $error = $e->getMessage();
            }
        } elseif (!empty($_POST['custom_image_url'])) {
            $imageUrl = trim($_POST['custom_image_url']);
        }

        if (empty($title) || empty($summary)) {
            $error = 'Project title and summary are required.';
        }

        if (empty($error)) {
            // Check slug uniqueness
            $checkSql = "SELECT id FROM projects WHERE slug = ?" . ($isEditing ? " AND id != ?" : "");
            $checkStmt = $pdo->prepare($checkSql);
            $checkParams = [$slug];
            if ($isEditing) $checkParams[] = $id;
            $checkStmt->execute($checkParams);

            if ($checkStmt->fetch()) {
                $slug .= '-' . time();
            }

            if ($isEditing) {
                $updateSql = "UPDATE projects SET 
                    title = ?, slug = ?, summary = ?, description = ?, category = ?, 
                    technologies = ?, client_name = ?, image_url = ?, live_url = ?, 
                    github_url = ?, is_featured = ?
                    WHERE id = ?";
                $updateStmt = $pdo->prepare($updateSql);
                $updateStmt->execute([
                    $title, $slug, $summary, $description, $category,
                    $technologies, $client_name, $imageUrl, $live_url,
                    $github_url, $is_featured, $id
                ]);
                setFlash('success', 'Project updated successfully.');
            } else {
                $insertSql = "INSERT INTO projects 
                    (title, slug, summary, description, category, technologies, client_name, image_url, live_url, github_url, is_featured) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $insertStmt = $pdo->prepare($insertSql);
                $insertStmt->execute([
                    $title, $slug, $summary, $description, $category,
                    $technologies, $client_name, $imageUrl, $live_url,
                    $github_url, $is_featured
                ]);
                setFlash('success', 'Project created successfully.');
            }

            header('Location: projects.php');
            exit;
        }
    }
}
?>

<div class="page-head">
    <div>
        <h1 class="page-title"><?= $isEditing ? 'Edit Project' : 'Create New Project' ?></h1>
        <p class="page-subtitle">Add details, tech stack, and screenshots for your software portfolio.</p>
    </div>
    <div>
        <a href="projects.php" class="btn btn-secondary">← Back to Projects</a>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card">
    <form method="POST" action="project-edit.php<?= $isEditing ? '?id=' . $id : '' ?>" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
        <input type="hidden" name="existing_image_url" value="<?= htmlspecialchars($project['image_url'] ?? '') ?>">

        <div class="form-group">
            <label class="form-label" for="title">Project Title *</label>
            <input type="text" id="title" name="title" class="form-control" required value="<?= htmlspecialchars($project['title']) ?>" placeholder="e.g. Real-Time Logistics Platform">
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label" for="slug">URL Slug (leave blank to auto-generate)</label>
                <input type="text" id="slug" name="slug" class="form-control" value="<?= htmlspecialchars($project['slug']) ?>" placeholder="e.g. logistics-platform">
                <p class="form-help">Will be accessed at <code>/projects/{slug}</code></p>
            </div>

            <div class="form-group">
                <label class="form-label" for="category">Category</label>
                <select id="category" name="category" class="form-control">
                    <?php
                    $categories = ['Web Development', 'Mobile App', 'Enterprise Software', 'Cloud & DevOps', 'AI & Machine Learning', 'UI/UX & Design'];
                    foreach ($categories as $cat):
                    ?>
                        <option value="<?= $cat ?>" <?= ($project['category'] === $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label" for="technologies">Technologies (comma-separated)</label>
                <input type="text" id="technologies" name="technologies" class="form-control" value="<?= htmlspecialchars($project['technologies']) ?>" placeholder="React, Node.js, PostgreSQL, Docker">
            </div>

            <div class="form-group">
                <label class="form-label" for="client_name">Client / Company Name</label>
                <input type="text" id="client_name" name="client_name" class="form-control" value="<?= htmlspecialchars($project['client_name']) ?>" placeholder="e.g. Apex Global Corp">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label" for="live_url">Live Demo / Website URL</label>
                <input type="url" id="live_url" name="live_url" class="form-control" value="<?= htmlspecialchars($project['live_url']) ?>" placeholder="https://example.com">
            </div>

            <div class="form-group">
                <label class="form-label" for="github_url">Repository URL (optional)</label>
                <input type="url" id="github_url" name="github_url" class="form-control" value="<?= htmlspecialchars($project['github_url']) ?>" placeholder="https://github.com/...">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Project Cover Image</label>
            <?php if (!empty($project['image_url'])): ?>
                <div style="margin-bottom: 0.75rem; display: flex; align-items: center; gap: 1rem;">
                    <img src="<?= htmlspecialchars($project['image_url']) ?>" alt="Current Cover" style="width: 120px; height: 75px; object-fit: cover; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                    <div style="font-size: 0.85rem; color: var(--text-muted);">Current image preview</div>
                </div>
            <?php endif; ?>
            <input type="file" id="image_file" name="image_file" class="form-control" accept="image/*">
            <p class="form-help">Upload an image (JPG, PNG, WebP) or enter an external image URL below.</p>
            <input type="text" name="custom_image_url" class="form-control" style="margin-top: 0.5rem;" placeholder="Or paste external image URL (https://...)" value="<?= (str_starts_with($project['image_url'] ?? '', 'http') ? htmlspecialchars($project['image_url']) : '') ?>">
        </div>

        <div class="form-group">
            <label class="form-label" for="summary">Short Summary *</label>
            <textarea id="summary" name="summary" class="form-control" style="min-height: 80px;" required placeholder="Brief 1-2 sentence overview of the project shown on project cards."><?= htmlspecialchars($project['summary']) ?></textarea>
        </div>

        <div class="form-group">
            <label class="form-label" for="description">Full Project Description & Architecture</label>
            <textarea id="description" name="description" class="form-control" style="min-height: 180px;" placeholder="Detailed breakdown of challenges, system architecture, solutions, and impact..."><?= htmlspecialchars($project['description']) ?></textarea>
        </div>

        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="is_featured" value="1" <?= $project['is_featured'] ? 'checked' : '' ?>>
                <span>Feature this project on the homepage showcase</span>
            </label>
        </div>

        <div style="margin-top: 2rem; display: flex; gap: 1rem;">
            <button type="submit" class="btn btn-primary">
                <?= $isEditing ? 'Save Changes' : 'Publish Project' ?>
            </button>
            <a href="projects.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
