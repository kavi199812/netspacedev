<?php
$pageTitle = 'Settings & Logo Branding';
require_once __DIR__ . '/header.php';

$error = '';
$adminId = $_SESSION['admin_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $formAction = $_POST['action'] ?? '';

        // Handle Logo Upload
        if ($formAction === 'upload_logo') {
            if (empty($_FILES['logo_file']['name']) || $_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
                $error = 'Please select a valid logo file to upload.';
            } else {
                $file = $_FILES['logo_file'];
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['png', 'jpg', 'jpeg', 'webp', 'svg'];

                if (!in_array($ext, $allowed, true)) {
                    $error = 'Allowed logo formats: PNG, JPG, WebP, SVG.';
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $error = 'Logo file size cannot exceed 5MB.';
                } else {
                    $targetDir = __DIR__ . '/../public';
                    $targetLogo = $targetDir . '/logo.png';
                    
                    if (move_uploaded_file($file['tmp_name'], $targetLogo)) {
                        // Automatically generate transparent white & dark variants if GD is available
                        if (extension_loaded('gd') && $ext !== 'svg') {
                            $src = @imagecreatefromstring(file_get_contents($targetLogo));
                            if ($src) {
                                $w = imagesx($src);
                                $h = imagesy($src);

                                $darkLogo = imagecreatetruecolor($w, $h);
                                imagealphablending($darkLogo, false);
                                imagesavealpha($darkLogo, true);
                                $trans = imagecolorallocatealpha($darkLogo, 0, 0, 0, 127);
                                imagefill($darkLogo, 0, 0, $trans);

                                $lightLogo = imagecreatetruecolor($w, $h);
                                imagealphablending($lightLogo, false);
                                imagesavealpha($lightLogo, true);
                                imagefill($lightLogo, 0, 0, $trans);

                                for ($x = 0; $x < $w; $x++) {
                                    for ($y = 0; $y < $h; $y++) {
                                        $rgb = imagecolorat($src, $x, $y);
                                        $r = ($rgb >> 16) & 0xFF;
                                        $g = ($rgb >> 8) & 0xFF;
                                        $b = $rgb & 0xFF;
                                        $bright = ($r + $g + $b) / 3;

                                        if ($bright < 210) {
                                            $alpha = (int)(($bright / 210) * 127);
                                            $wCol = imagecolorallocatealpha($darkLogo, 240, 246, 252, $alpha);
                                            imagesetpixel($darkLogo, $x, $y, $wCol);

                                            $bCol = imagecolorallocatealpha($lightLogo, 15, 23, 42, $alpha);
                                            imagesetpixel($lightLogo, $x, $y, $bCol);
                                        }
                                    }
                                }
                                imagepng($darkLogo, $targetDir . '/logo-white.png');
                                imagepng($lightLogo, $targetDir . '/logo-dark.png');
                            }
                        }

                        // Also update uploads folder copy
                        @copy($targetLogo, $targetDir . '/uploads/logo.png');

                        setFlash('success', 'Company logo updated successfully!');
                        header('Location: settings.php');
                        exit;
                    } else {
                        $error = 'Failed to save uploaded logo file.';
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
?>

<div class="page-head">
    <div>
        <h1 class="page-title">Settings & Logo Branding</h1>
        <p class="page-subtitle">Manage company logo, brand assets, and 4-digit PIN access.</p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- 1. Company Logo Upload Card -->
<div class="form-card" style="margin-bottom: 2rem;">
    <h2 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 0.5rem; color: var(--text-primary);">
        Company Logo
    </h2>
    <p style="color: var(--text-muted); font-size: 0.88rem; margin-bottom: 1.5rem;">
        Upload your official company logo. It will be automatically updated across the website header, hero avatar, favicon, and admin console.
    </p>

    <!-- Logo Previews -->
    <div style="display: flex; flex-wrap: wrap; gap: 1.5rem; margin-bottom: 1.5rem; align-items: center;">
        <div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.05em;">Dark Mode Preview</div>
            <div style="width: 110px; height: 110px; background-color: #0c0f14; border: 1px solid var(--border); border-radius: var(--radius-md); display: grid; place-items: center; padding: 12px;">
                <img src="../logo-white.png?v=<?= time() ?>" alt="Logo Dark Preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">
            </div>
        </div>

        <div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.05em;">Light Mode Preview</div>
            <div style="width: 110px; height: 110px; background-color: #ffffff; border: 1px solid var(--border); border-radius: var(--radius-md); display: grid; place-items: center; padding: 12px;">
                <img src="../logo-dark.png?v=<?= time() ?>" alt="Logo Light Preview" style="max-width: 100%; max-height: 100%; object-fit: contain;">
            </div>
        </div>

        <div>
            <div style="font-size: 0.75rem; color: var(--text-muted); margin-bottom: 0.4rem; text-transform: uppercase; letter-spacing: 0.05em;">Original Upload</div>
            <div style="width: 110px; height: 110px; background-color: #12161f; border: 1px solid var(--border); border-radius: var(--radius-md); display: grid; place-items: center; padding: 12px;">
                <img src="../logo.png?v=<?= time() ?>" alt="Logo Original" style="max-width: 100%; max-height: 100%; object-fit: contain;">
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
            <p class="form-help">Supported formats: PNG, JPG, WebP, SVG. Recommended minimum dimensions: 512 x 512 px.</p>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-top: 0.5rem;">
            Upload & Update Logo
        </button>
    </form>
</div>

<!-- 2. Security PIN Card -->
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
