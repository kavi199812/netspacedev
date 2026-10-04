<?php
require_once __DIR__ . '/config.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = trim($_POST['pin'] ?? '');

    // Combine digit inputs if sent individually
    if (empty($pin) && isset($_POST['digit_1'])) {
        $pin = trim($_POST['digit_1']) . trim($_POST['digit_2']) . trim($_POST['digit_3']) . trim($_POST['digit_4']);
    }

    if (empty($pin)) {
        $error = 'Please enter your 4-digit PIN.';
    } elseif (strlen($pin) < 4) {
        $error = 'PIN must be 4 digits.';
    } else {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE pin_code = ? LIMIT 1");
        $stmt->execute([$pin]);
        $admin = $stmt->fetch();

        if ($admin) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_user'] = $admin['username'];
            $_SESSION['admin_name'] = $admin['name'];
            setFlash('success', 'Access granted! Welcome, ' . $admin['name']);
            header('Location: index.php');
            exit;
        } else {
            $error = 'Incorrect PIN code. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter PIN | NetSpace Admin Console</title>
    <link rel="stylesheet" href="assets/admin.css">
    <link rel="icon" type="image/svg+xml" href="../favicon.svg">
    <style>
        .pin-wrapper {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin: 24px 0;
        }

        .pin-digit {
            width: 58px;
            height: 64px;
            font-size: 28px;
            font-weight: 700;
            text-align: center;
            background: #090d16;
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            color: var(--accent);
            outline: none;
            transition: all 0.2s ease;
            box-shadow: inset 0 2px 4px rgba(0,0,0,0.5);
            -webkit-text-security: disc;
        }

        .pin-digit:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px var(--accent-glow);
            transform: scale(1.05);
        }

    </style>
</head>
<body class="login-wrap">

<div class="login-card" style="text-align: center; max-width: 360px;">
    <div class="login-header" style="margin-bottom: 1.5rem;">
        <div class="brand-logo-icon" style="margin: 0 auto 16px; width: 64px; height: 64px; background: rgba(255,255,255,0.06); border: 1px solid var(--border); padding: 10px; display: grid; place-items: center; border-radius: var(--radius-md);">
            <img src="assets/logo-white.png" alt="NetSpace" style="width: 100%; height: 100%; object-fit: contain;">
        </div>
        <h1>Enter Security PIN</h1>
        <p>Enter 4-digit code to access NetSpace Console</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error" style="text-align: left; padding: 0.6rem 0.9rem; font-size: 0.88rem; margin-bottom: 1.25rem;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="pinForm">
        <input type="hidden" name="pin" id="fullPin" value="">

        <div class="pin-wrapper" style="margin-bottom: 1.5rem;">
            <input type="password" inputmode="numeric" maxlength="1" class="pin-digit" id="d1" autofocus required autocomplete="off">
            <input type="password" inputmode="numeric" maxlength="1" class="pin-digit" id="d2" required autocomplete="off">
            <input type="password" inputmode="numeric" maxlength="1" class="pin-digit" id="d3" required autocomplete="off">
            <input type="password" inputmode="numeric" maxlength="1" class="pin-digit" id="d4" required autocomplete="off">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.85rem; font-size: 0.95rem; font-weight: 600;">
            Unlock Console ➔
        </button>

        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 1.5rem; margin-bottom: 0;">
            Default PIN: <strong style="color: var(--accent); letter-spacing: 0.1em;">1234</strong>
        </p>
    </form>
</div>

<script>
const inputs = [
    document.getElementById('d1'),
    document.getElementById('d2'),
    document.getElementById('d3'),
    document.getElementById('d4')
];
const fullPin = document.getElementById('fullPin');
const form = document.getElementById('pinForm');

function updateHiddenPin() {
    const code = inputs.map(i => i.value).join('');
    fullPin.value = code;
    if (code.length === 4) {
        form.submit();
    }
}

inputs.forEach((input, index) => {
    input.addEventListener('input', (e) => {
        // Only accept numbers
        input.value = input.value.replace(/[^0-9]/g, '');
        if (input.value) {
            if (index < inputs.length - 1) {
                inputs[index + 1].focus();
            }
        }
        updateHiddenPin();
    });

    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace' && !input.value && index > 0) {
            inputs[index - 1].focus();
            inputs[index - 1].value = '';
            updateHiddenPin();
        }
    });

    input.addEventListener('paste', (e) => {
        e.preventDefault();
        const pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
        if (pasted.length >= 4) {
            for (let i = 0; i < 4; i++) {
                inputs[i].value = pasted[i];
            }
            updateHiddenPin();
        }
    });
});
</script>

</body>
</html>
