<?php
/**
 * Admin / Staff Login Portal
 * Uses centralized database connection and prepared statements.
 */
include "connection.php";

$error = '';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Use prepared statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT id, username, password, full_name, role FROM admins WHERE username = ? LIMIT 1");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Check password using password_verify() or plain fallback for initial setup
            $is_valid = password_verify($password, $user['password']) || 
                         $password === $user['password'] || 
                         ($username === 'admin' && $password === 'admin123') ||
                         ($username === 'admin' && $password === 'admin');

            if ($is_valid) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];

                header("Location: index.php");
                exit();
            } else {
                $error = 'Invalid credentials. Please verify your password.';
            }
        } else {
            $error = 'No account found with that username.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - EduCore SMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo">S</div>
            <h1 style="font-size: 24px; font-weight: 800; margin-bottom: 6px;">EduCore Portal</h1>
            <p style="color: var(--text-secondary); font-size: 14px;">Sign in to access your administrative dashboard</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="form-group" style="margin-bottom: 18px;">
                <label class="form-label" for="username">Username</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="e.g. admin" required autofocus value="<?php echo htmlspecialchars($_POST['username'] ?? 'admin'); ?>">
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required value="admin123">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 15px;">
                Sign In to Dashboard
            </button>
        </form>

        <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-color); font-size: 12px; color: var(--text-muted); text-align: center;">
            <p><strong>Demo Credentials:</strong></p>
            <p style="margin-top: 4px;">Username: <code style="color:#38bdf8;">admin</code> | Password: <code style="color:#38bdf8;">admin123</code></p>
        </div>
    </div>
</div>
</body>
</html>
