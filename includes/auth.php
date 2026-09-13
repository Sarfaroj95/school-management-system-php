<?php
/**
 * Authentication Middleware Check
 */
if (!ob_get_level()) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$root_path = isset($root_path) ? $root_path : '';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to clean /login path
    header("Location: " . $root_path . "login");
    echo '<script>window.location.href = "' . $root_path . 'login";</script>';
    exit();
}
?>
