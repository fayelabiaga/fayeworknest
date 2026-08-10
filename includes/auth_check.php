<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';

if (!isset($_SESSION['user_id'])) {
    // No login system — auto-use the single default account, creating it on first run.
    $stmt = $pdo->query("SELECT * FROM users ORDER BY id ASC LIMIT 1");
    $user = $stmt->fetch();
    if (!$user) {
        $hash = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->execute(['Faye', 'faye@worknest.local', $hash]);
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['user_name'] = 'Faye';
    } else {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
    }
}
$current_user_id = $_SESSION['user_id'];
$current_user_name = $_SESSION['user_name'] ?? 'User';
