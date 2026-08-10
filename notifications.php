<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Notifications";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'mark_read') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?");
        $stmt->execute([$_POST['id'], $current_user_id]);
    } elseif ($action === 'mark_all_read') {
        $stmt = $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?");
        $stmt->execute([$current_user_id]);
    } elseif ($action === 'generate') {
        // Auto-generate notifications from upcoming deadlines (2 days out) that don't already have one
        $in2days = date('Y-m-d', strtotime('+2 days'));
        $stmt = $pdo->prepare("SELECT assignment_name AS n FROM school_assignments WHERE user_id=? AND deadline=? AND status!='Done'");
        $stmt->execute([$current_user_id, $in2days]);
        foreach ($stmt->fetchAll() as $r) {
            $msg = $r['n'] . " is due in 2 days";
            $chk = $pdo->prepare("SELECT id FROM notifications WHERE user_id=? AND message=?");
            $chk->execute([$current_user_id, $msg]);
            if (!$chk->fetch()) {
                $ins = $pdo->prepare("INSERT INTO notifications (user_id, message) VALUES (?,?)");
                $ins->execute([$current_user_id, $msg]);
            }
        }
    }
    header("Location: notifications.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY is_read ASC, created_at DESC");
$stmt->execute([$current_user_id]);
$notifs = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="page-header"><h1>🔔 Notifications</h1><div class="sub">Stay on top of what needs your attention.</div></div>
<div class="toolbar">
    <form method="POST"><input type="hidden" name="action" value="generate"><button type="submit" class="btn btn-secondary btn-small">🔄 Check for New Reminders</button></form>
    <form method="POST"><input type="hidden" name="action" value="mark_all_read"><button type="submit" class="btn btn-small">Mark All as Read</button></form>
</div>

<div class="card" style="max-width:600px;">
    <?php if (!$notifs): ?><div class="empty-state">You're all caught up! 🎉</div><?php endif; ?>
    <?php foreach ($notifs as $n): ?>
        <li style="list-style:none;display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border);<?= $n['is_read']?'opacity:0.55;':'' ?>">
            <span>🔔 <?= htmlspecialchars($n['message']) ?> <span class="meta" style="color:var(--muted);font-size:0.75rem;">— <?= date('M j, g:i A', strtotime($n['created_at'])) ?></span></span>
            <?php if (!$n['is_read']): ?>
            <form method="POST"><input type="hidden" name="action" value="mark_read"><input type="hidden" name="id" value="<?= $n['id'] ?>"><button type="submit" class="btn btn-small btn-secondary">Mark Read</button></form>
            <?php endif; ?>
        </li>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
