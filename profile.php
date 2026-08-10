<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Profile";
$message = '';

$stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$current_user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $stmt = $pdo->prepare("UPDATE users SET name=?, email=? WHERE id=?");
        $stmt->execute([$_POST['name'], $_POST['email'], $current_user_id]);
        $_SESSION['user_name'] = $_POST['name'];
        $message = "Profile updated successfully.";
        $user['name'] = $_POST['name'];
        $user['email'] = $_POST['email'];
    }
}

// Quick stats
$stats = [];
$stats['projects'] = $pdo->prepare("SELECT COUNT(*) FROM personal_projects WHERE user_id=?");
$stats['projects']->execute([$current_user_id]);
$stats['skills'] = $pdo->prepare("SELECT COUNT(*) FROM skills WHERE user_id=?");
$stats['skills']->execute([$current_user_id]);
$stats['tasks_done'] = $pdo->prepare("SELECT COUNT(*) FROM school_activities WHERE user_id=? AND status='Done'");
$stats['tasks_done']->execute([$current_user_id]);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="page-header"><h1>👤 Profile</h1><div class="sub">Manage your account and see your stats.</div></div>
<?php if ($message): ?><div class="success-msg" style="max-width:500px;"><?= htmlspecialchars($message) ?></div><?php endif; ?>

<div class="widget-grid" style="grid-template-columns:1fr 1fr;">
    <div class="card">
        <h3>👤 Account Info</h3>
        <div class="topbar-avatar" style="width:60px;height:60px;font-size:1.5rem;margin-bottom:14px;"><?= strtoupper(substr($user['name'],0,1)) ?></div>
        <form method="POST">
            <input type="hidden" name="action" value="update_profile">
            <div class="form-group"><label>Full Name</label><input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required></div>
            <div class="form-group"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required></div>
            <div class="meta" style="margin-bottom:10px;">Member since <?= date('F Y', strtotime($user['created_at'])) ?></div>
            <button type="submit" class="btn">Save Changes</button>
        </form>
    </div>

    <div class="card">
        <h3>📊 Quick Stats</h3>
        <ul>
            <li>Total Projects: <?= $stats['projects']->fetchColumn() ?></li>
            <li>Skills Tracked: <?= $stats['skills']->fetchColumn() ?></li>
            <li>School Activities Completed: <?= $stats['tasks_done']->fetchColumn() ?></li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
