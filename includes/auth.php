<?php
/**
 * Authentication Middleware Check
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$root_path = isset($root_path) ? $root_path : '';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page
    header("Location: " . $root_path . "login.php");
    exit();
}
?>
