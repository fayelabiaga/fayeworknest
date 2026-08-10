<?php
// Expects $pageTitle to be set by the including page
require_once __DIR__ . '/../config/db.php';

$unread_count = 0;
if (isset($current_user_id)) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$current_user_id]);
    $unread_count = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) . ' - ' : '' ?>Faye WorkNest</title>
<link rel="stylesheet" href="css/style.css">
<script>
// Apply saved theme before paint to avoid a flash of the wrong theme
(function(){
    var t = localStorage.getItem('fwn_theme');
    if (t === 'dark') document.documentElement.setAttribute('data-theme', 'dark');
})();
</script>
</head>
<body>
<div class="app-shell">
