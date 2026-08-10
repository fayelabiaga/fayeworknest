<?php
ob_start();
require_once __DIR__ . '/../includes/auth_check.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$tab = $_POST['tab'] ?? $_GET['tab'] ?? 'activities';

// ---------- ACTIVITIES ----------
if ($action === 'add_activity') {
    $stmt = $pdo->prepare("INSERT INTO school_activities (user_id, activity_name, subject, description, activity_date, activity_time, priority, status) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$current_user_id, $_POST['activity_name'], $_POST['subject'], $_POST['description'], $_POST['activity_date'] ?: null, $_POST['activity_time'] ?: null, $_POST['priority'], $_POST['status']]);
} elseif ($action === 'toggle_activity') {
    $stmt = $pdo->prepare("UPDATE school_activities SET status = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['status'], $_POST['id'], $current_user_id]);
} elseif ($action === 'update_activity') {
    $stmt = $pdo->prepare("UPDATE school_activities SET activity_name=?, subject=?, description=?, activity_date=?, activity_time=?, priority=?, status=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['activity_name'], $_POST['subject'], $_POST['description'], $_POST['activity_date'] ?: null, $_POST['activity_time'] ?: null, $_POST['priority'], $_POST['status'], $_POST['id'], $current_user_id]);
} elseif ($action === 'delete_activity') {
    $stmt = $pdo->prepare("DELETE FROM school_activities WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $current_user_id]);

// ---------- ASSIGNMENTS ----------
} elseif ($action === 'add_assignment') {
    $stmt = $pdo->prepare("INSERT INTO school_assignments (user_id, assignment_name, subject, description, teacher, deadline, priority, status, notes) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmt->execute([$current_user_id, $_POST['assignment_name'], $_POST['subject'], $_POST['description'], $_POST['teacher'], $_POST['deadline'] ?: null, $_POST['priority'], $_POST['status'], $_POST['notes']]);
} elseif ($action === 'update_assignment_status') {
    $stmt = $pdo->prepare("UPDATE school_assignments SET status = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['status'], $_POST['id'], $current_user_id]);
} elseif ($action === 'update_assignment') {
    $stmt = $pdo->prepare("UPDATE school_assignments SET assignment_name=?, subject=?, description=?, teacher=?, deadline=?, priority=?, status=?, notes=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['assignment_name'], $_POST['subject'], $_POST['description'], $_POST['teacher'], $_POST['deadline'] ?: null, $_POST['priority'], $_POST['status'], $_POST['notes'], $_POST['id'], $current_user_id]);
} elseif ($action === 'delete_assignment') {
    $stmt = $pdo->prepare("DELETE FROM school_assignments WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $current_user_id]);

// ---------- EVENTS ----------
} elseif ($action === 'add_event') {
    $stmt = $pdo->prepare("INSERT INTO school_events (user_id, event_name, description, venue, event_date, event_time, organizer, status) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$current_user_id, $_POST['event_name'], $_POST['description'], $_POST['venue'], $_POST['event_date'] ?: null, $_POST['event_time'] ?: null, $_POST['organizer'], $_POST['status']]);
} elseif ($action === 'update_event') {
    $stmt = $pdo->prepare("UPDATE school_events SET event_name=?, description=?, venue=?, event_date=?, event_time=?, organizer=?, status=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['event_name'], $_POST['description'], $_POST['venue'], $_POST['event_date'] ?: null, $_POST['event_time'] ?: null, $_POST['organizer'], $_POST['status'], $_POST['id'], $current_user_id]);
} elseif ($action === 'delete_event') {
    $stmt = $pdo->prepare("DELETE FROM school_events WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $current_user_id]);

// ---------- GANAPS (school appointments/errands) ----------
} elseif ($action === 'add_ganap') {
    $stmt = $pdo->prepare("INSERT INTO school_ganaps (user_id, ganap_name, purpose, office, ganap_date, ganap_time, priority, status) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$current_user_id, $_POST['ganap_name'], $_POST['purpose'], $_POST['office'], $_POST['ganap_date'] ?: null, $_POST['ganap_time'] ?: null, $_POST['priority'], $_POST['status']]);
} elseif ($action === 'toggle_ganap') {
    $stmt = $pdo->prepare("UPDATE school_ganaps SET status = ? WHERE id = ? AND user_id = ?");
    $stmt->execute([$_POST['status'], $_POST['id'], $current_user_id]);
} elseif ($action === 'update_ganap') {
    $stmt = $pdo->prepare("UPDATE school_ganaps SET ganap_name=?, purpose=?, office=?, ganap_date=?, ganap_time=?, priority=?, status=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['ganap_name'], $_POST['purpose'], $_POST['office'], $_POST['ganap_date'] ?: null, $_POST['ganap_time'] ?: null, $_POST['priority'], $_POST['status'], $_POST['id'], $current_user_id]);
} elseif ($action === 'delete_ganap') {
    $stmt = $pdo->prepare("DELETE FROM school_ganaps WHERE id = ? AND user_id = ?");
    $stmt->execute([$_GET['id'], $current_user_id]);
}

ob_end_clean();
header("Location: ../school_works.php?tab=" . urlencode($tab));
exit;