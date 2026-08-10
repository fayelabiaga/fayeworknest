<?php
ob_start();
require_once __DIR__ . '/../includes/auth_check.php';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$skillId = (int)($_POST['skill_id'] ?? $_GET['skill_id'] ?? 0);
$redirect = '../skill_plan.php?id=' . $skillId;

// Verify the skill belongs to this user before any write
$stmt = $pdo->prepare("SELECT id FROM skills WHERE id=? AND user_id=?");
$stmt->execute([$skillId, $current_user_id]);
$ownsSkill = (bool)$stmt->fetch();

if ($ownsSkill && $action === 'add_week') {
    $stmt = $pdo->prepare("INSERT INTO skill_plan_weeks (skill_id, week_number, topic, tasks, target_date, status, progress) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$skillId, $_POST['week_number'] ?: 1, $_POST['topic'], $_POST['tasks'], $_POST['target_date'] ?: null, $_POST['status'], $_POST['progress'] ?: 0]);

} elseif ($ownsSkill && $action === 'update_week') {
    $stmt = $pdo->prepare("UPDATE skill_plan_weeks SET week_number=?, topic=?, tasks=?, target_date=?, status=?, progress=? WHERE id=? AND skill_id=?");
    $stmt->execute([$_POST['week_number'] ?: 1, $_POST['topic'], $_POST['tasks'], $_POST['target_date'] ?: null, $_POST['status'], $_POST['progress'] ?: 0, $_POST['id'], $skillId]);

} elseif ($ownsSkill && $action === 'delete_week') {
    $stmt = $pdo->prepare("DELETE FROM skill_plan_weeks WHERE id=? AND skill_id=?");
    $stmt->execute([$_GET['week_id'], $skillId]);

} elseif ($ownsSkill && $action === 'add_milestone') {
    $stmt = $pdo->prepare("INSERT INTO skill_milestones (skill_id, milestone_name, milestone_date, status) VALUES (?,?,?,?)");
    $stmt->execute([$skillId, $_POST['milestone_name'], $_POST['milestone_date'] ?: null, $_POST['status']]);

} elseif ($ownsSkill && $action === 'toggle_milestone') {
    $stmt = $pdo->prepare("UPDATE skill_milestones SET status=? WHERE id=? AND skill_id=?");
    $stmt->execute([$_POST['status'], $_POST['id'], $skillId]);

} elseif ($ownsSkill && $action === 'delete_milestone') {
    $stmt = $pdo->prepare("DELETE FROM skill_milestones WHERE id=? AND skill_id=?");
    $stmt->execute([$_GET['milestone_id'], $skillId]);

} elseif ($ownsSkill && $action === 'update_notes') {
    $stmt = $pdo->prepare("UPDATE skills SET notes=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['notes'], $skillId, $current_user_id]);

} elseif ($ownsSkill && $action === 'update_schedule') {
    $stmt = $pdo->prepare("UPDATE skills SET study_schedule=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['study_schedule'], $skillId, $current_user_id]);

} elseif ($ownsSkill && $action === 'add_skill_todo') {
    $stmt = $pdo->prepare("INSERT INTO skill_todos (skill_id, task_name) VALUES (?,?)");
    $stmt->execute([$skillId, $_POST['task_name']]);

} elseif ($ownsSkill && $action === 'toggle_skill_todo') {
    $completedAt = $_POST['is_done'] == 1 ? date('Y-m-d H:i:s') : null;
    $stmt = $pdo->prepare("UPDATE skill_todos SET is_done=?, completed_at=? WHERE id=? AND skill_id=?");
    $stmt->execute([$_POST['is_done'], $completedAt, $_POST['id'], $skillId]);

} elseif ($ownsSkill && $action === 'delete_skill_todo') {
    $stmt = $pdo->prepare("DELETE FROM skill_todos WHERE id=? AND skill_id=?");
    $stmt->execute([$_GET['todo_id'], $skillId]);

} elseif ($ownsSkill && $action === 'add_skill_resource') {
    $stmt = $pdo->prepare("INSERT INTO learning_resources (user_id, skill_id, title, category, resource_type, difficulty, estimated_time, status, link) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$current_user_id, $skillId, $_POST['title'], $_POST['category'], $_POST['resource_type'], $_POST['difficulty'], $_POST['estimated_time'], $_POST['status'], $_POST['link'] ?: null]);

} elseif ($action === 'delete_skill_resource') {
    $stmt = $pdo->prepare("DELETE FROM learning_resources WHERE id=? AND user_id=?");
    $stmt->execute([$_GET['res_id'], $current_user_id]);
}

ob_end_clean();
$tabParam = isset($_POST['plan_tab']) ? '&tab=' . urlencode($_POST['plan_tab']) : (isset($_GET['plan_tab']) ? '&tab=' . urlencode($_GET['plan_tab']) : '');
header("Location: $redirect$tabParam");
exit;