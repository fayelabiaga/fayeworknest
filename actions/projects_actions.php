<?php
ob_start();
require_once __DIR__ . '/../includes/auth_check.php';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect = '../personal_projects.php';

if ($action === 'add_project') {
    $stmt = $pdo->prepare("INSERT INTO personal_projects (user_id, project_name, description, status, progress, start_date, deadline) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$current_user_id, $_POST['project_name'], $_POST['description'], $_POST['status'], $_POST['progress'] ?: 0, $_POST['start_date'] ?: null, $_POST['deadline'] ?: null]);
} elseif ($action === 'update_project') {
    $stmt = $pdo->prepare("UPDATE personal_projects SET project_name=?, description=?, status=?, progress=?, start_date=?, deadline=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['project_name'], $_POST['description'], $_POST['status'], $_POST['progress'] ?: 0, $_POST['start_date'] ?: null, $_POST['deadline'] ?: null, $_POST['id'], $current_user_id]);
    $redirect = '../project_view.php?id=' . (int)$_POST['id'];
} elseif ($action === 'delete_project') {
    $stmt = $pdo->prepare("DELETE FROM personal_projects WHERE id=? AND user_id=?");
    $stmt->execute([$_GET['id'], $current_user_id]);

} elseif ($action === 'add_todo') {
    $stmt = $pdo->prepare("SELECT id FROM personal_projects WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['project_id'], $current_user_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO project_todos (project_id, task_name) VALUES (?,?)");
        $stmt->execute([$_POST['project_id'], $_POST['task_name']]);
    }
    $redirect = ($_POST['return'] ?? '') === 'list' ? '../personal_projects.php' : '../project_view.php?id=' . (int)$_POST['project_id'];
} elseif ($action === 'toggle_todo') {
    $stmt = $pdo->prepare("UPDATE project_todos t JOIN personal_projects p ON t.project_id=p.id SET t.is_done=? WHERE t.id=? AND p.user_id=?");
    $stmt->execute([$_POST['is_done'], $_POST['id'], $current_user_id]);
    $redirect = ($_POST['return'] ?? '') === 'list' ? '../personal_projects.php' : '../project_view.php?id=' . (int)$_POST['project_id'];
} elseif ($action === 'delete_todo') {
    $stmt = $pdo->prepare("DELETE t FROM project_todos t JOIN personal_projects p ON t.project_id=p.id WHERE t.id=? AND p.user_id=?");
    $stmt->execute([$_GET['id'], $current_user_id]);
    $redirect = ($_GET['return'] ?? '') === 'list' ? '../personal_projects.php' : '../project_view.php?id=' . (int)$_GET['project_id'];

// ---------- GOALS ----------
} elseif ($action === 'add_goal') {
    $stmt = $pdo->prepare("INSERT INTO project_goals (user_id, category, goal_name, description, target_date, progress, status, priority) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$current_user_id, $_POST['category'], $_POST['goal_name'], $_POST['description'], $_POST['target_date'] ?: null, $_POST['progress'] ?: 0, $_POST['status'], $_POST['priority']]);
    $redirect = '../project_goals.php';
} elseif ($action === 'update_goal') {
    $stmt = $pdo->prepare("UPDATE project_goals SET category=?, goal_name=?, description=?, target_date=?, progress=?, status=?, priority=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['category'], $_POST['goal_name'], $_POST['description'], $_POST['target_date'] ?: null, $_POST['progress'] ?: 0, $_POST['status'], $_POST['priority'], $_POST['id'], $current_user_id]);
    $redirect = '../project_goals.php';
} elseif ($action === 'delete_goal') {
    $stmt = $pdo->prepare("DELETE FROM project_goals WHERE id=? AND user_id=?");
    $stmt->execute([$_GET['id'], $current_user_id]);
    $redirect = '../project_goals.php';

} elseif ($action === 'add_goal_todo') {
    $stmt = $pdo->prepare("SELECT id FROM project_goals WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['goal_id'], $current_user_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO goal_todos (goal_id, task_name) VALUES (?,?)");
        $stmt->execute([$_POST['goal_id'], $_POST['task_name']]);
    }
    $redirect = '../project_goals.php';
} elseif ($action === 'toggle_goal_todo') {
    $stmt = $pdo->prepare("UPDATE goal_todos t JOIN project_goals g ON t.goal_id=g.id SET t.is_done=? WHERE t.id=? AND g.user_id=?");
    $stmt->execute([$_POST['is_done'], $_POST['id'], $current_user_id]);
    $redirect = '../project_goals.php';
} elseif ($action === 'delete_goal_todo') {
    $stmt = $pdo->prepare("DELETE t FROM goal_todos t JOIN project_goals g ON t.goal_id=g.id WHERE t.id=? AND g.user_id=?");
    $stmt->execute([$_GET['id'], $current_user_id]);
    $redirect = '../project_goals.php';

// ---------- MILESTONES ----------
} elseif ($action === 'add_milestone') {
    $stmt = $pdo->prepare("INSERT INTO project_milestones (user_id, milestone_name, milestone_date, status) VALUES (?,?,?,?)");
    $stmt->execute([$current_user_id, $_POST['milestone_name'], $_POST['milestone_date'] ?: null, $_POST['status']]);
    $redirect = '../project_goals.php';
} elseif ($action === 'toggle_milestone') {
    $stmt = $pdo->prepare("UPDATE project_milestones SET status=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['status'], $_POST['id'], $current_user_id]);
    $redirect = '../project_goals.php';
} elseif ($action === 'delete_milestone') {
    $stmt = $pdo->prepare("DELETE FROM project_milestones WHERE id=? AND user_id=?");
    $stmt->execute([$_GET['id'], $current_user_id]);
    $redirect = '../project_goals.php';
}

ob_end_clean();
header("Location: $redirect");
exit;