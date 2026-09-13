<?php
/**
 * Authentication & Role-Based Access Control (RBAC) Middleware
 * Supports roles: Super Admin, Admin, Staff, Teacher, Student
 */
if (!ob_get_level()) {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$root_path = isset($root_path) ? $root_path : '';

// 1. Core Login Check
if (!isset($_SESSION['user_id'])) {
    header("Location: " . $root_path . "login");
    echo '<script>window.location.href = "' . $root_path . 'login";</script>';
    exit();
}

// 2. Helper Functions for Role-Based Access Control
if (!function_exists('get_user_role')) {
    function get_user_role() {
        return $_SESSION['role'] ?? 'Admin';
    }
}

if (!function_exists('has_role')) {
    /**
     * Check if current user has any of the specified roles
     * @param array|string $roles Single role string or array of allowed roles
     * @return bool
     */
    function has_role($roles) {
        if (!isset($_SESSION['role'])) {
            return false;
        }
        $current_role = strtolower(trim($_SESSION['role']));
        if (is_string($roles)) {
            $roles = [$roles];
        }
        foreach ($roles as $role) {
            if (strtolower(trim($role)) === $current_role) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('require_role')) {
    /**
     * Enforce role requirement. Terminates execution with 403 Forbidden if not authorized.
     * @param array|string $roles
     */
    function require_role($roles) {
        global $root_path;
        if (!has_role($roles)) {
            http_response_code(403);
            $role_list = is_array($roles) ? implode(', ', $roles) : $roles;
            $current_role = htmlspecialchars(get_user_role());
            $dash_url = htmlspecialchars($root_path . 'index.php');
            
            echo '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>403 Forbidden - Access Restricted</title>
                <link rel="stylesheet" href="' . $root_path . 'assets/css/style.css">
                <style>
                    body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #0b0f19; color: #f8fafc; font-family: system-ui, -apple-system, sans-serif; margin: 0; padding: 20px; }
                    .forbidden-card { background: #111827; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 16px; padding: 40px; max-width: 500px; text-align: center; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
                    .forbidden-icon { width: 64px; height: 64px; background: rgba(239, 68, 68, 0.15); color: #ef4444; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto; font-size: 28px; }
                    h2 { margin: 0 0 12px 0; font-size: 22px; color: #fff; }
                    p { color: #94a3b8; font-size: 14px; line-height: 1.6; margin: 0 0 24px 0; }
                    .badge-role { display: inline-block; background: #1f2937; border: 1px solid #374151; padding: 4px 12px; border-radius: 6px; font-size: 12px; color: #f59e0b; margin-bottom: 20px; }
                    .btn-back { display: inline-block; background: #3b82f6; color: #fff; text-decoration: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; }
                    .btn-back:hover { background: #2563eb; }
                </style>
            </head>
            <body>
                <div class="forbidden-card">
                    <div class="forbidden-icon">🔒</div>
                    <h2>Access Restricted (403)</h2>
                    <div class="badge-role">Current Role: ' . $current_role . '</div>
                    <p>You do not have administrative permission to access this action or page. This area requires one of the following roles: <strong>' . htmlspecialchars($role_list) . '</strong>.</p>
                    <a href="' . $dash_url . '" class="btn-back">Return to Dashboard</a>
                </div>
            </body>
            </html>';
            exit();
        }
    }
}

// 3. Granular Action Check Helpers
if (!function_exists('can_delete')) {
    function can_delete() {
        return has_role(['Super Admin', 'Admin']);
    }
}

if (!function_exists('can_manage_teachers')) {
    function can_manage_teachers() {
        return has_role(['Super Admin', 'Admin']);
    }
}

if (!function_exists('can_manage_classes')) {
    function can_manage_classes() {
        return has_role(['Super Admin', 'Admin']);
    }
}

if (!function_exists('can_manage_students')) {
    function can_manage_students() {
        return has_role(['Super Admin', 'Admin', 'Staff']);
    }
}

if (!function_exists('can_manage_attendance')) {
    function can_manage_attendance() {
        return has_role(['Super Admin', 'Admin', 'Staff', 'Teacher']);
    }
}

if (!function_exists('can_manage_results')) {
    function can_manage_results() {
        return has_role(['Super Admin', 'Admin', 'Teacher']);
    }
}

if (!function_exists('can_manage_library')) {
    function can_manage_library() {
        return has_role(['Super Admin', 'Admin', 'Staff']);
    }
}

if (!function_exists('can_manage_notices')) {
    function can_manage_notices() {
        return has_role(['Super Admin', 'Admin', 'Staff']);
    }
}
?>
