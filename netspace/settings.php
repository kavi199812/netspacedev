<?php
$pageTitle = 'Settings & System Management';
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/logo_helper.php';
require_once __DIR__ . '/../database/migrator.php';

$error = '';
$adminId = $_SESSION['admin_id'] ?? 1;
$migrator = new NetSpaceMigrator($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token. Please refresh and try again.';
    } else {
        $formAction = $_POST['action'] ?? '';

        // Handle Database Migration
        if ($formAction === 'run_migrations') {
            $result = $migrator->runPendingMigrations();
            if ($result['success']) {
                $_SESSION['migration_logs'] = $result['logs'];
                setFlash('success', $result['message']);
            } else {
                $_SESSION['migration_logs'] = $result['logs'] ?? [];
                setFlash('error', $result['error'] ?? 'Migration failed.');
            }
            header('Location: settings.php#migrations');
            exit;
        }

        // Handle Logo Upload
        if ($formAction === 'upload_logo') {
            if (!isset($_FILES['logo_file']) || $_FILES['logo_file']['error'] === UPLOAD_ERR_NO_FILE) {
                $error = 'Please select a logo file before clicking upload.';
            } elseif ($_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
                $uploadErrors = [
                    UPLOAD_ERR_INI_SIZE   => 'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
                    UPLOAD_ERR_FORM_SIZE  => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.',
                    UPLOAD_ERR_PARTIAL    => 'The uploaded file was only partially uploaded.',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                    UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
                ];
                $error = $uploadErrors[$_FILES['logo_file']['error']] ?? 'File upload error code: ' . $_FILES['logo_file']['error'];
            } else {
                $file = $_FILES['logo_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['png', 'jpg', 'jpeg', 'webp', 'svg'];

                if (!in_array($ext, $allowed, true)) {
                    $error = 'Invalid file format. Allowed formats: PNG, JPG, WebP, SVG.';
                } elseif ($file['size'] > 8 * 1024 * 1024) {
                    $error = 'File size is too large (maximum 8MB allowed).';
                } else {
                    try {
                        processAndSaveLogo($file['tmp_name'], $ext);
                        setFlash('success', 'Company logo successfully uploaded and updated across all pages!');
                        header('Location: settings.php');
                        exit;
                    } catch (Exception $e) {
                        $error = 'Failed to process logo: ' . $e->getMessage();
                    }
                }
            }
        }

        // Handle PIN Change
        if ($formAction === 'change_pin') {
            $currentPin = trim($_POST['current_pin'] ?? '');
            $newPin     = trim($_POST['new_pin'] ?? '');
            $confirmPin = trim($_POST['confirm_pin'] ?? '');

            $stmt = $pdo->prepare("SELECT pin_code FROM admins WHERE id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $realPin = $stmt->fetchColumn();

            if ($currentPin !== $realPin) {
                $error = 'Current PIN is incorrect.';
            } elseif (strlen($newPin) !== 4 || !ctype_digit($newPin)) {
                $error = 'New PIN must be exactly 4 numeric digits.';
            } elseif ($newPin !== $confirmPin) {
                $error = 'New PIN and confirmation PIN do not match.';
            } else {
                $update = $pdo->prepare("UPDATE admins SET pin_code = ? WHERE id = ?");
                $update->execute([$newPin, $adminId]);
                setFlash('success', 'Security PIN successfully updated to ' . $newPin . '!');
                header('Location: settings.php');
                exit;
            }
        }
    }
}

// Fetch current admin info
$stmt = $pdo->prepare("SELECT username, email, name, pin_code, created_at FROM admins WHERE id = ? LIMIT 1");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();

$cacheBuster = time();
$logoWhiteExists = file_exists(__DIR__ . '/assets/logo-white.png');
$logoDarkExists  = file_exists(__DIR__ . '/assets/logo-dark.png');
$logoOrigExists  = file_exists(__DIR__ . '/assets/logo.png');

// Get migration details
$dbOverview = $migrator->getDatabaseOverview();
$lastMigrationLogs = $_SESSION['migration_logs'] ?? null;
unset($_SESSION['migration_logs']);
?>

<div class="page-head">
    <div>
        <h1 class="page-title">Settings & System Management</h1>
        <p class="page-subtitle">Manage company logo branding, database migrations, and 4-digit PIN security.</p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- 1. Database Migrations Card -->
<div class="form-card" id="migrations" style="margin-bottom: 2rem; border-color: <?= $dbOverview['total_pending'] > 0 ? 'var(--accent)' : 'var(--border)' ?>;">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem;">
        <div>
            <div style="display: flex; align-items: center; gap: 0.6rem; margin-bottom: 0.35rem;">
                <h2 style="font-size: 1.15rem; font-weight: 600; color: var(--text-primary); margin: 0;">
                    Database Schema & Migrations
                </h2>
                <?php if ($dbOverview['total_pending'] > 0): ?>
                    <span style="background: rgba(245, 158, 11, 0.15); color: #f59e0b; border: 1px solid rgba(245, 158, 11, 0.3); font-size: 0.72rem; font-weight: 600; padding: 2px 8px; border-radius: 999px;">
                        <?= $dbOverview['total_pending'] ?> Pending Migration<?= $dbOverview['total_pending'] > 1 ? 's' : '' ?>
                    </span>
                <?php else: ?>
                    <span style="background: rgba(16, 185, 129, 0.12); color: #10b981; border: 1px solid rgba(16, 185, 129, 0.3); font-size: 0.72rem; font-weight: 600; padding: 2px 8px; border-radius: 999px;">
                        ✓ Schema Up to Date
                    </span>
                <?php endif; ?>
            </div>
            <p style="color: var(--text-muted); font-size: 0.88rem; margin: 0;">
                Apply database table schema changes, new columns, and index updates seamlessly without opening phpMyAdmin.
            </p>
        </div>

        <form method="POST" action="settings.php" onsubmit="return confirm('Do you want to run all pending database migrations now?');">
            <input type="hidden" name="action" value="run_migrations">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 0.5rem; <?= $dbOverview['total_pending'] > 0 ? 'box-shadow: 0 0 15px rgba(255,255,255,0.15);' : '' ?>">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
                <span>Migrate Database</span>
            </button>
        </form>
    </div>

    <!-- Quick Stats Grid -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem; margin: 1.25rem 0; background: rgba(0,0,0,0.25); border: 1px solid var(--border); border-radius: var(--radius-md); padding: 1rem;">
        <div>
            <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Database</div>
            <div style="font-weight: 600; color: var(--text-primary); margin-top: 0.25rem; font-family: monospace; font-size: 0.95rem;"><?= htmlspecialchars($dbOverview['database']) ?></div>
        </div>
        <div>
            <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Tables</div>
            <div style="font-weight: 600; color: var(--text-primary); margin-top: 0.25rem; font-size: 0.95rem;"><?= $dbOverview['total_tables'] ?> active tables</div>
        </div>
        <div>
            <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Applied Migrations</div>
            <div style="font-weight: 600; color: #10b981; margin-top: 0.25rem; font-size: 0.95rem;"><?= $dbOverview['total_applied'] ?> applied</div>
        </div>
        <div>
            <div style="font-size: 0.72rem; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em;">Pending Status</div>
            <div style="font-weight: 600; color: <?= $dbOverview['total_pending'] > 0 ? '#f59e0b' : 'var(--text-muted)' ?>; margin-top: 0.25rem; font-size: 0.95rem;">
                <?= $dbOverview['total_pending'] ?> pending
            </div>
        </div>
    </div>

    <!-- Recent Execution Log (if any) -->
    <?php if (!empty($lastMigrationLogs)): ?>
        <div style="margin: 1rem 0;">
            <div style="font-size: 0.78rem; font-weight: 600; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase;">
                Execution Output
            </div>
            <pre style="background: #080b0f; border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0.85rem; color: #7ee787; font-family: monospace; font-size: 0.82rem; overflow-x: auto; margin: 0; line-height: 1.5;"><?php foreach ($lastMigrationLogs as $log): ?><?= htmlspecialchars($log) . "\n" ?><?php endforeach; ?></pre>
        </div>
    <?php endif; ?>

    <!-- Pending & Applied Files Detail -->
    <details style="margin-top: 1rem; border-top: 1px solid var(--border); padding-top: 0.75rem;">
        <summary style="cursor: pointer; font-size: 0.84rem; color: var(--text-muted); user-select: none;">
            View Detailed Migration Files & Database Tables
        </summary>
        <div style="margin-top: 0.75rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1rem;">
            <!-- Migration Files -->
            <div>
                <div style="font-size: 0.76rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem;">
                    Migration History
                </div>
                <div style="background: rgba(0,0,0,0.15); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0.5rem; font-size: 0.8rem;">
                    <?php if (empty($dbOverview['applied_migrations']) && empty($dbOverview['pending_migrations'])): ?>
                        <div style="color: var(--text-muted); padding: 0.3rem;">No migration files found in database/migrations/</div>
                    <?php endif; ?>

                    <?php foreach ($dbOverview['applied_migrations'] as $app): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.35rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.04); font-family: monospace;">
                            <span style="color: #7ee787;">✓ <?= htmlspecialchars($app['migration']) ?></span>
                            <span style="color: var(--text-muted); font-size: 0.72rem;">Batch #<?= $app['batch'] ?></span>
                        </div>
                    <?php endforeach; ?>

                    <?php foreach ($dbOverview['pending_migrations'] as $pend): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.35rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.04); font-family: monospace;">
                            <span style="color: #f59e0b;">⏳ <?= htmlspecialchars($pend) ?></span>
                            <span style="color: #f59e0b; font-size: 0.72rem; font-weight: 600;">Pending</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Current Database Tables -->
            <div>
                <div style="font-size: 0.76rem; font-weight: 600; text-transform: uppercase; color: var(--text-muted); margin-bottom: 0.5rem;">
                    Active Tables in Database
                </div>
                <div style="background: rgba(0,0,0,0.15); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 0.5rem; font-size: 0.8rem; max-height: 180px; overflow-y: auto;">
                    <?php foreach ($dbOverview['tables'] as $tbl): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 0.35rem 0.5rem; border-bottom: 1px solid rgba(255,255,255,0.04); font-family: monospace;">
                            <span style="color: var(--text-primary);"><?= htmlspecialchars($tbl['name']) ?></span>
                            <span style="color: var(--text-muted); font-size: 0.72rem;"><?= $tbl['rows'] ?> records</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </details>
</div>

<!-- 2. Company Logo Upload Card -->
<div class="form-card" style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-primary);">
        Company Logo Branding
    </h2>
    <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.5rem;">
        Upload your official company logo. It will automatically update across the website header, hero avatar, favicon, and admin console.
    </p>

    <!-- Logo Previews -->
    <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 1.5rem; align-items: center;">
        <div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.05em;">Dark Mode Preview</div>
            <div style="width: 110px; height: 110px; background-color: #0c0f14; border: 1px solid var(--border); border-radius: var(--radius-md); display: grid; place-items: center; padding: 12px;">
                <?php if ($logoWhiteExists): ?>
                    <img src="assets/logo-white.png?v=<?= $cacheBuster ?>" alt="Dark Preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                <?php else: ?>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">No Preview</span>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.05em;">Light Mode Preview</div>
            <div style="width: 110px; height: 110px; background-color: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-md); display: grid; place-items: center; padding: 12px;">
                <?php if ($logoDarkExists): ?>
                    <img src="assets/logo-dark.png?v=<?= $cacheBuster ?>" alt="Light Preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                <?php else: ?>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">No Preview</span>
                <?php endif; ?>
            </div>
        </div>

        <div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.05em;">Original Upload</div>
            <div style="width: 110px; height: 110px; background-color: #12161f; border: 1px solid var(--border); border-radius: var(--radius-md); display: grid; place-items: center; padding: 12px;">
                <?php if ($logoOrigExists): ?>
                    <img src="assets/logo.png?v=<?= $cacheBuster ?>" alt="Original Upload" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                <?php else: ?>
                    <span style="font-size: 0.75rem; color: var(--text-muted);">No Preview</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Upload Form -->
    <form method="POST" action="settings.php" enctype="multipart/form-data">
        <input type="hidden" name="action" value="upload_logo">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

        <div class="form-group">
            <label class="form-label" for="logo_file">Select New Logo Image</label>
            <input type="file" id="logo_file" name="logo_file" class="form-control" accept="image/png, image/jpeg, image/webp, image/svg+xml" required>
            <p class="form-help">Supported formats: PNG, JPG, WebP, SVG. Recommended square image (e.g. 512 x 512 px or higher).</p>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">
            Upload & Update Logo
        </button>
    </form>
</div>

<!-- 3. Security PIN Card -->
<div class="form-card">
    <h2 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-primary);">
        Change 4-Digit Security PIN
    </h2>
    <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.5rem;">
        Current active PIN is: <strong style="color: var(--accent); letter-spacing: 0.15em;"><?= htmlspecialchars($admin['pin_code'] ?? '1234') ?></strong>
    </p>

    <form method="POST" action="settings.php">
        <input type="hidden" name="action" value="change_pin">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

        <div class="form-group">
            <label class="form-label" for="current_pin">Current 4-Digit PIN</label>
            <input type="password" maxlength="4" inputmode="numeric" id="current_pin" name="current_pin" class="form-control" style="max-width: 200px; font-size: 1.2rem; letter-spacing: 0.2em;" required placeholder="••••">
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label class="form-label" for="new_pin">New 4-Digit PIN</label>
                <input type="password" maxlength="4" inputmode="numeric" id="new_pin" name="new_pin" class="form-control" style="max-width: 200px; font-size: 1.2rem; letter-spacing: 0.2em;" required placeholder="••••">
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_pin">Confirm New PIN</label>
                <input type="password" maxlength="4" inputmode="numeric" id="confirm_pin" name="confirm_pin" class="form-control" style="max-width: 200px; font-size: 1.2rem; letter-spacing: 0.2em;" required placeholder="••••">
            </div>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 1rem;">
            Update Security PIN
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
