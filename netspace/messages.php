<?php
$pageTitle = 'Client Inquiries';
require_once __DIR__ . '/header.php';

// Handle Actions (mark_read, delete)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['id'] ?? 0);

        if ($action === 'delete') {
            $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Inquiry deleted.');
        } elseif ($action === 'toggle_read') {
            $stmt = $pdo->prepare("UPDATE messages SET is_read = 1 - is_read WHERE id = ?");
            $stmt->execute([$id]);
            setFlash('success', 'Status updated.');
        } elseif ($action === 'mark_all_read') {
            $pdo->query("UPDATE messages SET is_read = 1");
            setFlash('success', 'All inquiries marked as read.');
        }
    }
    header('Location: messages.php');
    exit;
}

$messages = $pdo->query("SELECT * FROM messages ORDER BY is_read ASC, created_at DESC")->fetchAll();
?>

<div class="page-head">
    <div>
        <h1 class="page-title">Client Inquiries</h1>
        <p class="page-subtitle">Messages sent through your website contact form.</p>
    </div>
    <div>
        <?php if (!empty($messages)): ?>
            <form method="POST" action="messages.php" style="display: inline;">
                <input type="hidden" name="action" value="mark_all_read">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <button type="submit" class="btn btn-secondary btn-sm">Mark All as Read</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card-table">
    <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">Status</th>
                    <th>Sender & Email</th>
                    <th>Subject</th>
                    <th>Message Details</th>
                    <th>Date</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($messages)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 3rem;">
                            No inquiries yet. Incoming inquiries from <code>/contact</code> will appear here.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($messages as $m): ?>
                        <tr id="msg-<?= $m['id'] ?>" style="<?= !$m['is_read'] ? 'background-color: rgba(56, 189, 248, 0.03);' : '' ?>">
                            <td>
                                <?php if (!$m['is_read']): ?>
                                    <span class="badge badge-unread">New</span>
                                <?php else: ?>
                                    <span class="badge" style="background: rgba(255,255,255,0.05); color: var(--text-muted);">Read</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color: var(--text-primary); font-size: 0.95rem;"><?= htmlspecialchars($m['name']) ?></strong>
                                <div>
                                    <a href="mailto:<?= htmlspecialchars($m['email']) ?>" style="font-size: 0.8rem; color: var(--accent);">
                                        <?= htmlspecialchars($m['email']) ?> ✉
                                    </a>
                                </div>
                            </td>
                            <td style="font-weight: 600; color: var(--text-primary); max-width: 180px;">
                                <?= htmlspecialchars($m['subject']) ?>
                            </td>
                            <td style="max-width: 350px;">
                                <div style="font-size: 0.88rem; color: #c9d1d9; white-space: pre-wrap; word-break: break-word; background: rgba(0,0,0,0.2); padding: 0.6rem 0.8rem; border-radius: var(--radius-sm); border: 1px solid var(--border);">
                                    <?= htmlspecialchars($m['message']) ?>
                                </div>
                            </td>
                            <td style="font-size: 0.8rem; color: var(--text-muted); white-space: nowrap;">
                                <?= date('M j, Y', strtotime($m['created_at'])) ?><br>
                                <span style="font-size: 0.72rem;"><?= date('g:i A', strtotime($m['created_at'])) ?></span>
                            </td>
                            <td style="text-align: right; white-space: nowrap;">
                                <div style="display: inline-flex; gap: 0.4rem;">
                                    <form method="POST" action="messages.php" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_read">
                                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
                                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm" title="Toggle Read Status">
                                            <?= $m['is_read'] ? 'Mark Unread' : 'Mark Read' ?>
                                        </button>
                                    </form>
                                    <form method="POST" action="messages.php" onsubmit="return confirm('Delete this inquiry?');" style="display: inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $m['id'] ?>">
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
