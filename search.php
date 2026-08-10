<?php
require_once __DIR__ . '/includes/auth_check.php';
$pageTitle = "Search";
$q = trim($_GET['q'] ?? '');
$results = [];

if ($q !== '') {
    $like = "%$q%";

    $stmt = $pdo->prepare("SELECT activity_name AS title, 'School Activity' AS type, 'school_works.php?tab=activities' AS link FROM school_activities WHERE user_id=? AND activity_name LIKE ?");
    $stmt->execute([$current_user_id, $like]); $results = array_merge($results, $stmt->fetchAll());

    $stmt = $pdo->prepare("SELECT assignment_name AS title, 'Assignment' AS type, 'school_works.php?tab=assignments' AS link FROM school_assignments WHERE user_id=? AND assignment_name LIKE ?");
    $stmt->execute([$current_user_id, $like]); $results = array_merge($results, $stmt->fetchAll());

    $stmt = $pdo->prepare("SELECT event_name AS title, 'Event' AS type, 'school_works.php?tab=events' AS link FROM school_events WHERE user_id=? AND event_name LIKE ?");
    $stmt->execute([$current_user_id, $like]); $results = array_merge($results, $stmt->fetchAll());

    $stmt = $pdo->prepare("SELECT activity_name AS title, 'Research Activity' AS type, 'research_works.php?tab=activities' AS link FROM research_activities WHERE user_id=? AND activity_name LIKE ?");
    $stmt->execute([$current_user_id, $like]); $results = array_merge($results, $stmt->fetchAll());

    $stmt = $pdo->prepare("SELECT project_name AS title, 'Personal Project' AS type, 'personal_projects.php' AS link FROM personal_projects WHERE user_id=? AND project_name LIKE ?");
    $stmt->execute([$current_user_id, $like]); $results = array_merge($results, $stmt->fetchAll());

    $stmt = $pdo->prepare("SELECT skill_name AS title, 'Skill' AS type, 'learning.php?tab=skills' AS link FROM skills WHERE user_id=? AND skill_name LIKE ?");
    $stmt->execute([$current_user_id, $like]); $results = array_merge($results, $stmt->fetchAll());
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>
<div class="page-header"><h1>🔍 Search results for "<?= htmlspecialchars($q) ?>"</h1></div>
<div class="item-list">
    <?php if (!$results): ?><div class="empty-state">No results found.</div><?php endif; ?>
    <?php foreach ($results as $r): ?>
        <a href="<?= $r['link'] ?>" class="item-card">
            <h4><?= htmlspecialchars($r['title']) ?></h4>
            <span class="badge badge-medium"><?= htmlspecialchars($r['type']) ?></span>
        </a>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
