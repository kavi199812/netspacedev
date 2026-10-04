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

        .pin-keypad {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            max-width: 260px;
            margin: 20px auto 0;
        }

        .key-btn {
            background: var(--bg-main);
            border: 1px solid var(--border);
            color: var(--text-primary);
            font-size: 1.25rem;
            font-weight: 600;
            padding: 14px;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
        }

        .key-btn:hover {
            background: var(--bg-card-hover);
            border-color: #484f58;
            transform: translateY(-1px);
        }

        .key-btn:active {
            transform: translateY(1px);
            background: var(--border);
        }

        .key-btn.backspace {
            font-size: 0.95rem;
            color: var(--text-muted);
        }
    </style>
</head>
<body class="login-wrap">

<div class="login-card" style="text-align: center; max-width: 380px;">
    <div class="login-header" style="margin-bottom: 1.2rem;">
        <div class="brand-logo-icon" style="margin: 0 auto 12px; width: 48px; height: 48px; font-size: 1.4rem;">N</div>
        <h1>Enter Security PIN</h1>
        <p>Enter 4-digit code to access NetSpace Console</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-error" style="text-align: left; padding: 0.6rem 0.9rem; font-size: 0.88rem;">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php" id="pinForm">
        <input type="hidden" name="pin" id="fullPin" value="">

        <div class="pin-wrapper">
            <input type="password" inputmode="numeric" maxlength="1" class="pin-digit" id="d1" autofocus required autocomplete="off">
            <input type="password" inputmode="numeric" maxlength="1" class="pin-digit" id="d2" required autocomplete="off">
            <input type="password" inputmode="numeric" maxlength="1" class="pin-digit" id="d3" required autocomplete="off">
            <input type="password" inputmode="numeric" maxlength="1" class="pin-digit" id="d4" required autocomplete="off">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 0.75rem; font-size: 0.95rem;">
            Unlock Console ➔
        </button>

        <!-- On-Screen Numeric Keypad -->
        <div class="pin-keypad">
            <button type="button" class="key-btn" onclick="pressKey('1')">1</button>
            <button type="button" class="key-btn" onclick="pressKey('2')">2</button>
            <button type="button" class="key-btn" onclick="pressKey('3')">3</button>
            <button type="button" class="key-btn" onclick="pressKey('4')">4</button>
            <button type="button" class="key-btn" onclick="pressKey('5')">5</button>
            <button type="button" class="key-btn" onclick="pressKey('6')">6</button>
            <button type="button" class="key-btn" onclick="pressKey('7')">7</button>
            <button type="button" class="key-btn" onclick="pressKey('8')">8</button>
            <button type="button" class="key-btn" onclick="pressKey('9')">9</button>
            <button type="button" class="key-btn backspace" onclick="pressClear()">CLR</button>
            <button type="button" class="key-btn" onclick="pressKey('0')">0</button>
            <button type="button" class="key-btn backspace" onclick="pressBackspace()">⌫</button>
        </div>

        <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 1.5rem;">
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

function pressKey(num) {
    for (let i = 0; i < inputs.length; i++) {
        if (!inputs[i].value) {
            inputs[i].value = num;
            if (i < inputs.length - 1) {
                inputs[i + 1].focus();
            }
            updateHiddenPin();
            break;
        }
    }
}

function pressBackspace() {
    for (let i = inputs.length - 1; i >= 0; i--) {
        if (inputs[i].value) {
            inputs[i].value = '';
            inputs[i].focus();
            updateHiddenPin();
            break;
        }
    }
}

function pressClear() {
    inputs.forEach(i => i.value = '');
    inputs[0].focus();
    updateHiddenPin();
}
</script>

</body>
</html>
