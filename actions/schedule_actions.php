<?php
ob_start();
require_once __DIR__ . '/../includes/auth_check.php';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$semId = (int)($_POST['semester_id'] ?? $_GET['semester_id'] ?? 0);

if ($action === 'add_semester') {
    $stmt = $pdo->prepare("INSERT INTO semesters (user_id, semester_name, is_current) VALUES (?,?,0)");
    $stmt->execute([$current_user_id, $_POST['semester_name']]);
    $newId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("UPDATE semesters SET is_current = (id = ?) WHERE user_id = ?");
    $stmt->execute([$newId, $current_user_id]);
    $semId = $newId;

} elseif ($action === 'switch_semester') {
    $stmt = $pdo->prepare("SELECT id FROM semesters WHERE id=? AND user_id=?");
    $stmt->execute([$semId, $current_user_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE semesters SET is_current = (id = ?) WHERE user_id = ?");
        $stmt->execute([$semId, $current_user_id]);
    }

} elseif ($action === 'delete_semester') {
    $stmt = $pdo->prepare("DELETE FROM semesters WHERE id=? AND user_id=?");
    $stmt->execute([$semId, $current_user_id]);
    $semId = 0;

} elseif ($action === 'add_class') {
    $stmt = $pdo->prepare("SELECT id FROM semesters WHERE id=? AND user_id=?");
    $stmt->execute([$semId, $current_user_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO class_schedule (semester_id, code, remarks, subject, class_type, schedule_time, days, room, units, section, details) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$semId, $_POST['code'], $_POST['remarks'], $_POST['subject'], $_POST['class_type'], $_POST['schedule_time'], $_POST['days'], $_POST['room'], $_POST['units'] !== '' ? $_POST['units'] : null, $_POST['section'], $_POST['details']]);
    }

} elseif ($action === 'update_class') {
    $stmt = $pdo->prepare("UPDATE class_schedule cs JOIN semesters s ON cs.semester_id = s.id
        SET cs.code=?, cs.remarks=?, cs.subject=?, cs.class_type=?, cs.schedule_time=?, cs.days=?, cs.room=?, cs.units=?, cs.section=?, cs.details=?
        WHERE cs.id=? AND s.user_id=?");
    $stmt->execute([$_POST['code'], $_POST['remarks'], $_POST['subject'], $_POST['class_type'], $_POST['schedule_time'], $_POST['days'], $_POST['room'], $_POST['units'] !== '' ? $_POST['units'] : null, $_POST['section'], $_POST['details'], $_POST['id'], $current_user_id]);

} elseif ($action === 'delete_class') {
    $stmt = $pdo->prepare("DELETE cs FROM class_schedule cs JOIN semesters s ON cs.semester_id = s.id WHERE cs.id=? AND s.user_id=?");
    $stmt->execute([$_GET['id'], $current_user_id]);
    $semId = (int)($_GET['semester_id'] ?? 0);
}

ob_end_clean();
header("Location: ../school_works.php?tab=schedule" . ($semId ? "&semester_id=$semId" : ""));
exit;