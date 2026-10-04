<?php
$pageTitle = 'Security & Settings';
require_once __DIR__ . '/header.php';

$error = '';
$adminId = $_SESSION['admin_id'] ?? 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        $currentPin = trim($_POST['current_pin'] ?? '');
        $newPin     = trim($_POST['new_pin'] ?? '');
        $confirmPin = trim($_POST['confirm_pin'] ?? '');

        // Verify current pin
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

// Fetch current admin info
$stmt = $pdo->prepare("SELECT username, email, name, pin_code, created_at FROM admins WHERE id = ? LIMIT 1");
$stmt->execute([$adminId]);
$admin = $stmt->fetch();
?>

<div class="page-head">
    <div>
        <h1 class="page-title">Security & PIN Settings</h1>
        <p class="page-subtitle">Configure your 4-digit access code and console settings.</p>
    </div>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-card">
    <h2 style="font-size: 1.15rem; font-weight: 600; margin-bottom: 1rem; color: var(--text-primary);">Change 4-Digit Security PIN</h2>
    <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 1.5rem;">
        Current active PIN is: <strong style="color: var(--accent); letter-spacing: 0.15em;"><?= htmlspecialchars($admin['pin_code'] ?? '1234') ?></strong>
    </p>

    <form method="POST" action="settings.php">
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
