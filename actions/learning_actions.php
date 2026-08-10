<?php
ob_start();
require_once __DIR__ . '/../includes/auth_check.php';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$tab = $_POST['tab'] ?? $_GET['tab'] ?? 'skills';

if ($action === 'add_skill') {
    $stmt = $pdo->prepare("INSERT INTO skills (user_id, skill_name, description, progress, status, level) VALUES (?,?,?,?,?,?)");
    $stmt->execute([$current_user_id, $_POST['skill_name'], $_POST['description'], $_POST['progress'] ?: 0, $_POST['status'], $_POST['level']]);
} elseif ($action === 'update_skill_notes') {
    $stmt = $pdo->prepare("UPDATE skills SET notes=?, progress=?, status=?, level=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['notes'], $_POST['progress'] ?: 0, $_POST['status'], $_POST['level'], $_POST['id'], $current_user_id]);
} elseif ($action === 'delete_skill') {
    $stmt = $pdo->prepare("DELETE FROM skills WHERE id=? AND user_id=?");
    $stmt->execute([$_GET['id'], $current_user_id]);

} elseif ($action === 'add_skill_todo') {
    $stmt = $pdo->prepare("SELECT id FROM skills WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['skill_id'], $current_user_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO skill_todos (skill_id, task_name) VALUES (?,?)");
        $stmt->execute([$_POST['skill_id'], $_POST['task_name']]);
    }
} elseif ($action === 'toggle_skill_todo') {
    $completedAt = $_POST['is_done'] == 1 ? date('Y-m-d H:i:s') : null;
    $stmt = $pdo->prepare("UPDATE skill_todos t JOIN skills s ON t.skill_id=s.id SET t.is_done=?, t.completed_at=? WHERE t.id=? AND s.user_id=?");
    $stmt->execute([$_POST['is_done'], $completedAt, $_POST['id'], $current_user_id]);
} elseif ($action === 'delete_skill_todo') {
    $stmt = $pdo->prepare("DELETE t FROM skill_todos t JOIN skills s ON t.skill_id=s.id WHERE t.id=? AND s.user_id=?");
    $stmt->execute([$_GET['id'], $current_user_id]);

} elseif ($action === 'add_resource') {
    $stmt = $pdo->prepare("INSERT INTO learning_resources (user_id, title, category, resource_type, difficulty, estimated_time, status, link) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$current_user_id, $_POST['title'], $_POST['category'], $_POST['resource_type'], $_POST['difficulty'], $_POST['estimated_time'], $_POST['status'], $_POST['link'] ?: null]);
} elseif ($action === 'toggle_resource') {
    $stmt = $pdo->prepare("UPDATE learning_resources SET status=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['status'], $_POST['id'], $current_user_id]);
} elseif ($action === 'delete_resource') {
    $stmt = $pdo->prepare("DELETE FROM learning_resources WHERE id=? AND user_id=?");
    $stmt->execute([$_GET['id'], $current_user_id]);
}

ob_end_clean();
header("Location: ../learning.php?tab=" . urlencode($tab));
exit;