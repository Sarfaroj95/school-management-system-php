<?php
/**
 * User Logout Handler
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session values
$_SESSION = array();

// Destroy session cookie if present
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect to clean login path
$root_path = isset($root_path) ? $root_path : '';
header("Location: " . $root_path . "login");
echo '<script>window.location.href = "' . $root_path . 'login";</script>';
exit();
?>