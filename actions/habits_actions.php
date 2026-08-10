<?php
require_once __DIR__ . '/../includes/auth_check.php';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$today = date('Y-m-d');

if ($action === 'add_habit') {
    $stmt = $pdo->prepare("INSERT INTO habits (user_id, habit_name, why_it_matters, icon) VALUES (?,?,?,?)");
    $stmt->execute([$current_user_id, $_POST['habit_name'], $_POST['why_it_matters'], $_POST['icon'] ?: '⭐']);

} elseif ($action === 'update_habit') {
    $stmt = $pdo->prepare("UPDATE habits SET habit_name=?, why_it_matters=?, icon=? WHERE id=? AND user_id=?");
    $stmt->execute([$_POST['habit_name'], $_POST['why_it_matters'], $_POST['icon'] ?: '⭐', $_POST['id'], $current_user_id]);

} elseif ($action === 'delete_habit') {
    $stmt = $pdo->prepare("DELETE FROM habits WHERE id=? AND user_id=?");
    $stmt->execute([$_GET['id'], $current_user_id]);

} elseif ($action === 'toggle_today') {
    $habitId = (int)$_POST['habit_id'];
    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM habits WHERE id=? AND user_id=?");
    $stmt->execute([$habitId, $current_user_id]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("SELECT id FROM habit_logs WHERE habit_id=? AND log_date=?");
        $stmt->execute([$habitId, $today]);
        if ($stmt->fetch()) {
            $del = $pdo->prepare("DELETE FROM habit_logs WHERE habit_id=? AND log_date=?");
            $del->execute([$habitId, $today]);
        } else {
            $ins = $pdo->prepare("INSERT INTO habit_logs (habit_id, log_date, completed) VALUES (?,?,1)");
            $ins->execute([$habitId, $today]);
        }
    }
}

header("Location: ../habits.php");
exit;