<?php
// ✅ Make sure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ✅ Block access if user is not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php'); // 👈 Adjust path as needed
    exit;
}