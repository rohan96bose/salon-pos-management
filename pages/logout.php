<?php
require_once '../includes/db.php';

if (isset($_SESSION['user_session_id'])) {
    $stmt = $pdo->prepare("UPDATE user_sessions SET logout_time = NOW() WHERE id = ?");
    $stmt->execute([$_SESSION['user_session_id']]);
}

session_unset();
session_destroy();

header('Location: ../index.php'); // Adjust redirect path as needed
exit;
?>
