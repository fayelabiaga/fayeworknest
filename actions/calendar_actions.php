<?php
require_once __DIR__ . '/../includes/auth_check.php';
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$month = $_POST['month'] ?? $_GET['month'] ?? date('n');
$year = $_POST['year'] ?? $_GET['year'] ?? date('Y');

if ($action === 'add_item') {
    $stmt = $pdo->prepare("INSERT INTO calendar_items (user_id, title, item_type, item_date) VALUES (?,?,?,?)");
    $stmt->execute([$current_user_id, $_POST['title'], $_POST['item_type'], $_POST['item_date']]);
} elseif ($action === 'delete_item') {
    $stmt = $pdo->prepare("DELETE FROM calendar_items WHERE id=? AND user_id=?");
    $stmt->execute([$_GET['id'], $current_user_id]);
}

header("Location: ../calendar.php?month=$month&year=$year");
exit;
