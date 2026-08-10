<?php
ob_start();
require_once __DIR__ . '/../includes/auth_check.php';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$tab = $_POST['tab'] ?? $_GET['tab'] ?? 'activities';

if ($action === 'add_activity') {
    $stmt = $pdo->prepare("INSERT INTO research_activities (user_id, activity_name, description, assigned_to, status, priority, progress, start_date, deadline) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$current_user_id, $_POST['activity_name'], $_POST['description'], $_POST['assigned_to'] ?? null, $_POST['status'], $_POST['priority'] ?: 'Medium', $_POST['progress'] ?: 0, $_POST['start_date'] ?: null, $_POST['deadline'] ?: null]);
} elseif ($action === 'update_activity') {
    $stmt = $pdo->prepare("UPDATE research_activities SET activity_name=?, description=?, assigned_to=?, status=?, priority=?, progress=?, start_date=?, deadline=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['activity_name'], $_POST['description'], $_POST['assigned_to'], $_POST['status'], $_POST['priority'] ?: 'Medium', $_POST['progress'] ?: 0, $_POST['start_date'] ?: null, $_POST['deadline'] ?: null, $_POST['id'], $current_user_id]);
} elseif ($action === 'update_activity_status') {
    $stmt = $pdo->prepare("UPDATE research_activities SET status=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['status'], $_POST['id'], $current_user_id]);
} elseif ($action === 'delete_activity') {
    $stmt = $pdo->prepare("DELETE FROM research_activities WHERE id=? AND user_id=?");
    $stmt->execute([$_GET['id'], $current_user_id]);

} elseif ($action === 'add_deadline') {
    $stmt = $pdo->prepare("INSERT INTO research_deadlines (user_id, deadline_name, description, deadline_date, status, reminder, priority) VALUES (?,?,?,?,?,?,?)");
    $stmt->execute([$current_user_id, $_POST['deadline_name'], $_POST['description'], $_POST['deadline_date'] ?: null, $_POST['status'], $_POST['reminder'], $_POST['priority']]);
} elseif ($action === 'toggle_deadline') {
    $stmt = $pdo->prepare("UPDATE research_deadlines SET status=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['status'], $_POST['id'], $current_user_id]);
} elseif ($action === 'update_deadline') {
    $stmt = $pdo->prepare("UPDATE research_deadlines SET deadline_name=?, description=?, deadline_date=?, status=?, reminder=?, priority=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['deadline_name'], $_POST['description'], $_POST['deadline_date'] ?: null, $_POST['status'], $_POST['reminder'], $_POST['priority'], $_POST['id'], $current_user_id]);
} elseif ($action === 'delete_deadline') {
    $stmt = $pdo->prepare("DELETE FROM research_deadlines WHERE id=? AND user_id=?");
    $stmt->execute([$_GET['id'], $current_user_id]);

} elseif ($action === 'add_document') {
    $filePath = null;
    if (!empty($_FILES['file']['name'])) {
        $filename = time() . '_' . basename($_FILES['file']['name']);
        $dest = __DIR__ . '/../uploads/' . $filename;
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            $filePath = 'uploads/' . $filename;
        }
    }
    $stmt = $pdo->prepare("INSERT INTO research_documents (user_id, title, category, file_path) VALUES (?,?,?,?)");
    $stmt->execute([$current_user_id, $_POST['title'], $_POST['category'], $filePath]);
} elseif ($action === 'delete_document') {
    $stmt = $pdo->prepare("DELETE FROM research_documents WHERE id=? AND user_id=?");
    $stmt->execute([$_GET['id'], $current_user_id]);

} elseif ($action === 'save_progress') {
    // Upsert progress stages
    $stages = $_POST['stage_name'] ?? [];
    $progresses = $_POST['stage_progress'] ?? [];
    $ids = $_POST['stage_id'] ?? [];
    foreach ($stages as $i => $name) {
        if (trim($name) === '') continue;
        $prog = (int)($progresses[$i] ?? 0);
        $id = $ids[$i] ?? '';
        if ($id) {
            $stmt = $pdo->prepare("UPDATE research_progress SET stage_name=?, progress=? WHERE id=? AND user_id=?");
            $stmt->execute([$name, $prog, $id, $current_user_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO research_progress (user_id, stage_name, progress) VALUES (?,?,?)");
            $stmt->execute([$current_user_id, $name, $prog]);
        }
    }
} elseif ($action === 'delete_progress') {
    $stmt = $pdo->prepare("DELETE FROM research_progress WHERE id=? AND user_id=?");
    $stmt->execute([$_GET['id'], $current_user_id]);
}

ob_end_clean();
header("Location: ../research_works.php?tab=" . urlencode($tab));
exit;