<?php
/**
 * AJAX Endpoint: Persist User Theme Mode (Dark/Light) in Database
 * Supported for all user roles (Super Admin, Admin, Staff, Teacher, Student)
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include_once __DIR__ . '/../connection.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

$theme = trim($_POST['theme'] ?? $_GET['theme'] ?? '');
if (empty($theme)) {
    $json_input = json_decode(file_get_contents('php://input'), true);
    if (is_array($json_input) && isset($json_input['theme'])) {
        $theme = trim($json_input['theme']);
    }
}

// Ensure theme is strictly 'light' or 'dark' (defaulting to 'dark')
$theme = ($theme === 'light') ? 'light' : 'dark';

$user_id = intval($_SESSION['user_id']);
$role = $_SESSION['role'] ?? 'Admin';
$updated = false;

// 1. Teacher Update
if (isset($_SESSION['teacher_id']) || $role === 'Teacher') {
    $t_id = intval($_SESSION['teacher_id'] ?? $user_id);
    $stmt = $conn->prepare("UPDATE teachers SET theme_mode = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $theme, $t_id);
        $stmt->execute();
        $updated = ($stmt->affected_rows >= 0);
        $stmt->close();
    }
} 
// 2. Student Update
elseif (isset($_SESSION['student_id']) || $role === 'Student') {
    $s_id = intval($_SESSION['student_id'] ?? $user_id);
    $stmt = $conn->prepare("UPDATE students SET theme_mode = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $theme, $s_id);
        $stmt->execute();
        $updated = ($stmt->affected_rows >= 0);
        $stmt->close();
    }
} 
// 3. Admin / Staff / Super Admin Update
else {
    $stmt = $conn->prepare("UPDATE admins SET theme_mode = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $theme, $user_id);
        $stmt->execute();
        $updated = ($stmt->affected_rows >= 0);
        $stmt->close();
    }
    
    // Also sync to legacy table if present
    $check_leg = @mysqli_query($conn, "SHOW TABLES LIKE 'admin_login'");
    if ($check_leg && mysqli_num_rows($check_leg) > 0) {
        $stmt_leg = $conn->prepare("UPDATE admin_login SET theme_mode = ? WHERE id = ?");
        if ($stmt_leg) {
            $stmt_leg->bind_param("si", $theme, $user_id);
            $stmt_leg->execute();
            $stmt_leg->close();
        }
    }
}

// Update active session
$_SESSION['theme_mode'] = $theme;

echo json_encode([
    'success' => true,
    'theme' => $theme,
    'user_id' => $user_id,
    'role' => $role
]);
exit();
