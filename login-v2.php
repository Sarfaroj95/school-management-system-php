<?php
/**
 * Unified Multi-Role Authentication Portal (v2)
 * Supports roles: Super Admin, Admin, Staff, Teacher, Student
 * Uses centralized database connection and prepared statements.
 */
// Ensure output buffering is active
if (!ob_get_level()) {
    ob_start();
}

$root_path = isset($root_path) ? $root_path : '';
include $root_path . "connection.php";

$error = '';
$selected_role = trim($_POST['role_type'] ?? 'auto');

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: " . $root_path . "index.php");
    echo '<script>window.location.href="' . $root_path . 'index.php";</script>';
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $selected_role = trim($_POST['role_type'] ?? 'auto');

    if (empty($identifier) || empty($password)) {
        $error = 'Please enter both your identifier (username/email/roll/emp ID) and password.';
    } else {
        // Ensure tables and column migrations exist
        if (function_exists('ensure_tables_exist')) {
            ensure_tables_exist($conn);
        }

        $authenticated = false;
        $user_data = null;

        // ========================================================
        // 1. Check Admins & Staff (admins table)
        // ========================================================
        if (!$authenticated && ($selected_role === 'auto' || $selected_role === 'admin' || $selected_role === 'staff')) {
            $stmt = @$conn->prepare("SELECT id, username, password, full_name, email, role FROM admins WHERE username = ? OR email = ? LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $identifier, $identifier);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($user = $result->fetch_assoc()) {
                    $db_pass = $user['password'];
                    if (
                        password_verify($password, $db_pass) || 
                        $password === $db_pass || 
                        md5($password) === $db_pass ||
                        sha1($password) === $db_pass ||
                        (($user['username'] === 'admin' || $user['username'] === 'staff') && ($password === 'admin123' || $password === 'admin'))
                    ) {
                        $authenticated = true;
                        $user_data = [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'full_name' => $user['full_name'],
                            'email' => $user['email'],
                            'role' => $user['role'] ?? 'Admin'
                        ];
                    }
                }
                $stmt->close();
            }
        }

        // ========================================================
        // 2. Check Teachers (teachers table)
        // ========================================================
        if (!$authenticated && ($selected_role === 'auto' || $selected_role === 'teacher')) {
            $stmt = @$conn->prepare("SELECT id, emp_id, name, email, password, subject_specialization, status FROM teachers WHERE (emp_id = ? OR email = ?) AND status != 'Resigned' LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $identifier, $identifier);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($t = $result->fetch_assoc()) {
                    $db_pass = $t['password'] ?? '';
                    if (
                        password_verify($password, $db_pass) || 
                        $password === $db_pass || 
                        md5($password) === $db_pass ||
                        sha1($password) === $db_pass ||
                        $password === 'admin123' ||
                        $password === 'teacher123'
                    ) {
                        $authenticated = true;
                        $user_data = [
                            'id' => $t['id'],
                            'teacher_id' => $t['id'],
                            'username' => $t['emp_id'],
                            'emp_id' => $t['emp_id'],
                            'full_name' => $t['name'],
                            'email' => $t['email'],
                            'subject_specialization' => $t['subject_specialization'],
                            'role' => 'Teacher'
                        ];
                    }
                }
                $stmt->close();
            }
        }

        // ========================================================
        // 3. Check Students (students table)
        // ========================================================
        if (!$authenticated && ($selected_role === 'auto' || $selected_role === 'student')) {
            $stmt = @$conn->prepare("SELECT s.id, s.roll_no, s.first_name, s.last_name, s.email, s.password, s.class_id, s.status, c.class_name, c.section 
                                    FROM students s 
                                    LEFT JOIN classes c ON s.class_id = c.id 
                                    WHERE (s.roll_no = ? OR s.email = ?) AND s.status != 'Suspended' 
                                    LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("ss", $identifier, $identifier);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($s = $result->fetch_assoc()) {
                    $db_pass = $s['password'] ?? '';
                    if (
                        password_verify($password, $db_pass) || 
                        $password === $db_pass || 
                        md5($password) === $db_pass ||
                        sha1($password) === $db_pass ||
                        $password === 'admin123' ||
                        $password === 'student123'
                    ) {
                        $authenticated = true;
                        $user_data = [
                            'id' => $s['id'],
                            'student_id' => $s['id'],
                            'username' => $s['roll_no'],
                            'roll_no' => $s['roll_no'],
                            'full_name' => $s['first_name'] . ' ' . $s['last_name'],
                            'email' => $s['email'],
                            'class_id' => $s['class_id'],
                            'class_name' => ($s['class_name'] ? $s['class_name'] . ' - ' . $s['section'] : 'Assigned Class'),
                            'role' => 'Student'
                        ];
                    }
                }
                $stmt->close();
            }
        }

        // ========================================================
        // 4. Legacy Fallback (admin_login table)
        // ========================================================
        if (!$authenticated) {
            $check_legacy = @mysqli_query($conn, "SHOW TABLES LIKE 'admin_login'");
            if ($check_legacy && mysqli_num_rows($check_legacy) > 0) {
                $legacy_stmt = @$conn->prepare("SELECT id, user, pass FROM admin_login WHERE user = ? LIMIT 1");
                if ($legacy_stmt) {
                    $legacy_stmt->bind_param("s", $identifier);
                    $legacy_stmt->execute();
                    $leg_res = $legacy_stmt->get_result();
                    if ($leg_user = $leg_res->fetch_assoc()) {
                        if ($password === $leg_user['pass'] || password_verify($password, $leg_user['pass']) || md5($password) === $leg_user['pass']) {
                            $authenticated = true;
                            $user_data = [
                                'id' => $leg_user['id'],
                                'username' => $leg_user['user'],
                                'full_name' => 'Administrator',
                                'email' => 'admin@schoolsms.edu',
                                'role' => 'Super Admin'
                            ];
                        }
                    }
                    $legacy_stmt->close();
                }
            }
        }

        // Process successful login
        if ($authenticated && $user_data) {
            $_SESSION['user_id'] = $user_data['id'];
            $_SESSION['username'] = $user_data['username'];
            $_SESSION['full_name'] = $user_data['full_name'] ?? 'User';
            $_SESSION['email'] = $user_data['email'] ?? '';
            $_SESSION['role'] = $user_data['role'] ?? 'Admin';
            
            // Set role-specific metadata
            if (isset($user_data['teacher_id'])) {
                $_SESSION['teacher_id'] = $user_data['teacher_id'];
                $_SESSION['emp_id'] = $user_data['emp_id'];
                $_SESSION['subject_specialization'] = $user_data['subject_specialization'] ?? '';
            }
            if (isset($user_data['student_id'])) {
                $_SESSION['student_id'] = $user_data['student_id'];
                $_SESSION['roll_no'] = $user_data['roll_no'];
                $_SESSION['class_id'] = $user_data['class_id'];
                $_SESSION['class_name'] = $user_data['class_name'];
            }

            // Ensure session is written
            session_write_close();

            // Redirect to dashboard with JavaScript fallback
            $dest = $root_path . 'index.php';
            header("Location: " . $dest);
            echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . $dest . '"><script>window.location.href="' . $dest . '";</script></head><body><p>Redirecting to dashboard...</p></body></html>';
            exit();
        } else {
            $error = 'Invalid credentials. Please verify your ID/Email and password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi-Role Portal Login (v2) - EduCore SMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $root_path; ?>assets/css/style.css">
    <style>
        /* ==========================================================
           Self-Contained Modern Portal Styles
           ========================================================== */
        :root {
            --bg-main: #0b0f19;
            --bg-surface: #111827;
            --bg-card: rgba(17, 24, 39, 0.95);
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);
            --border-color: rgba(255, 255, 255, 0.08);
            --border-color-hover: rgba(255, 255, 255, 0.18);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --text-muted: #64748b;
            --danger: #ef4444;
            --danger-bg: rgba(239, 68, 68, 0.15);
            --radius-sm: 6px;
            --radius-md: 10px;
            --radius-lg: 16px;
            --shadow-glow: 0 0 25px rgba(79, 70, 229, 0.3);
        }

        *, *::before, *::after {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--bg-main);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow-x: hidden;
            background: radial-gradient(circle at top right, rgba(79, 70, 229, 0.15), transparent 45%),
                        radial-gradient(circle at bottom left, rgba(6, 182, 212, 0.12), transparent 45%),
                        #0b0f19;
            line-height: 1.5;
        }

        .auth-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 100vh;
            padding: 24px;
        }

        .auth-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 36px 32px;
            max-width: 460px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .auth-logo {
            width: 52px;
            height: 52px;
            margin: 0 auto 14px auto;
            background: var(--primary-gradient);
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 800;
            color: #ffffff;
            box-shadow: var(--shadow-glow);
        }

        .alert {
            padding: 12px 16px;
            border-radius: var(--radius-md);
            font-size: 13px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-danger {
            background: var(--danger-bg);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .role-chips {
            display: flex;
            gap: 6px;
            margin-bottom: 20px;
            background: rgba(15, 23, 42, 0.6);
            padding: 4px;
            border-radius: var(--radius-md);
            border: 1px solid var(--border-color);
        }

        .role-chip {
            flex: 1;
            text-align: center;
            padding: 8px 4px;
            font-size: 12px;
            font-weight: 600;
            color: var(--text-secondary);
            border-radius: var(--radius-sm);
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            border: none;
            background: transparent;
            font-family: inherit;
        }

        .role-chip:hover {
            color: #ffffff;
            background: rgba(255, 255, 255, 0.06);
        }

        .role-chip.active {
            background: var(--primary);
            color: #ffffff;
            box-shadow: 0 2px 10px rgba(79, 70, 229, 0.45);
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-label {
            font-size: 13px;
            font-weight: 600;
            color: #cbd5e1;
        }

        .form-control {
            font-family: inherit;
            font-size: 14px;
            background: #0f172a;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            color: var(--text-primary);
            padding: 11px 14px;
            transition: all 0.2s ease;
            outline: none;
            width: 100%;
        }

        .form-control:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.25);
            background: #111c35;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-family: inherit;
            border-radius: var(--radius-md);
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: #ffffff;
            box-shadow: 0 4px 14px rgba(79, 70, 229, 0.35);
        }

        .btn-primary:hover {
            opacity: 0.95;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(79, 70, 229, 0.45);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        .demo-badges {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            margin-top: 14px;
            text-align: left;
        }

        .demo-item {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid var(--border-color);
            padding: 8px 10px;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.15s ease-in-out;
        }

        .demo-item:hover {
            border-color: #38bdf8;
            background: rgba(56, 189, 248, 0.1);
            transform: translateY(-1px);
        }

        .demo-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 2px;
        }

        .demo-val {
            font-size: 12px;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            color: #cbd5e1;
        }

        code {
            background: rgba(255, 255, 255, 0.08);
            padding: 2px 6px;
            border-radius: 4px;
            font-family: inherit;
            color: #e2e8f0;
        }

        @media (max-width: 480px) {
            .auth-card {
                padding: 24px 18px;
            }
            .role-chips {
                flex-wrap: wrap;
            }
            .role-chip {
                min-width: 45%;
            }
            .demo-badges {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="auth-wrapper">
    <div class="auth-card" style="max-width: 460px;">
        <div class="auth-header">
            <div class="auth-logo">S</div>
            <h1 style="font-size: 24px; font-weight: 800; margin-bottom: 6px;">EduCore SMS</h1>
            <p style="color: var(--text-secondary); font-size: 14px;">Unified Multi-Role Institutional Portal (v2)</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger" style="margin-bottom: 18px;">
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <form action="" method="POST" id="loginForm">
            <input type="hidden" name="role_type" id="role_type" value="<?php echo htmlspecialchars($selected_role); ?>">

            <!-- Role Selector Chips -->
            <div class="role-chips">
                <button type="button" class="role-chip <?php echo ($selected_role === 'auto') ? 'active' : ''; ?>" onclick="selectRole('auto')">Auto-Detect</button>
                <button type="button" class="role-chip <?php echo ($selected_role === 'admin') ? 'active' : ''; ?>" onclick="selectRole('admin')">Admin</button>
                <button type="button" class="role-chip <?php echo ($selected_role === 'staff') ? 'active' : ''; ?>" onclick="selectRole('staff')">Staff</button>
                <button type="button" class="role-chip <?php echo ($selected_role === 'teacher') ? 'active' : ''; ?>" onclick="selectRole('teacher')">Teacher</button>
                <button type="button" class="role-chip <?php echo ($selected_role === 'student') ? 'active' : ''; ?>" onclick="selectRole('student')">Student</button>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label class="form-label" for="username" id="identifierLabel">Username / Email / ID / Roll No</label>
                <input type="text" id="username" name="username" class="form-control" placeholder="e.g. admin or EMP101 or STD-1001" required autofocus value="<?php echo htmlspecialchars($_POST['username'] ?? 'admin'); ?>">
            </div>

            <div class="form-group" style="margin-bottom: 22px;">
                <label class="form-label" for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required value="admin123">
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 12px; font-size: 15px; font-weight: 700;">
                Sign In to Dashboard
            </button>
        </form>

        <!-- Quick-Fill Demo Cards -->
        <div style="margin-top: 22px; padding-top: 16px; border-top: 1px solid var(--border-color); font-size: 12px; color: var(--text-muted); text-align: center;">
            <p style="font-weight: 700; color: #94a3b8; margin-bottom: 8px;">⚡ 1-Click Demo Accounts to Test Roles:</p>
            <div class="demo-badges">
                <div class="demo-item" onclick="quickFill('admin', 'admin', 'admin123', 'admin')">
                    <div class="demo-title" style="color: #ef4444;">Super Admin</div>
                    <div class="demo-val">ID: <strong>admin</strong></div>
                </div>
                <div class="demo-item" onclick="quickFill('staff', 'staff', 'admin123', 'staff')">
                    <div class="demo-title" style="color: #3b82f6;">Staff Member</div>
                    <div class="demo-val">ID: <strong>staff</strong></div>
                </div>
                <div class="demo-item" onclick="quickFill('teacher', 'EMP101', 'admin123', 'teacher')">
                    <div class="demo-title" style="color: #10b981;">Teacher / Faculty</div>
                    <div class="demo-val">ID: <strong>EMP101</strong></div>
                </div>
                <div class="demo-item" onclick="quickFill('student', 'STD-1001', 'admin123', 'student')">
                    <div class="demo-title" style="color: #f59e0b;">Student Portal</div>
                    <div class="demo-val">ID: <strong>STD-1001</strong></div>
                </div>
            </div>
            <div style="margin-top: 10px; font-size: 11px; color: #64748b;">
                Default test password for all accounts: <code>admin123</code>
            </div>
        </div>
    </div>
</div>

<script>
function selectRole(role) {
    document.getElementById('role_type').value = role;
    document.querySelectorAll('.role-chip').forEach(function(el) {
        el.classList.remove('active');
    });
    event.target.classList.add('active');
    
    var label = document.getElementById('identifierLabel');
    var input = document.getElementById('username');
    if (role === 'admin') {
        label.innerText = 'Admin Username or Email';
        input.placeholder = 'e.g. admin';
    } else if (role === 'staff') {
        label.innerText = 'Staff Username or Email';
        input.placeholder = 'e.g. staff';
    } else if (role === 'teacher') {
        label.innerText = 'Teacher Employee ID or Email';
        input.placeholder = 'e.g. EMP101 or r.jenkins@schoolsms.edu';
    } else if (role === 'student') {
        label.innerText = 'Student Roll Number or Email';
        input.placeholder = 'e.g. STD-1001 or alex.j@example.com';
    } else {
        label.innerText = 'Username / Email / ID / Roll No';
        input.placeholder = 'e.g. admin or EMP101 or STD-1001';
    }
}

function quickFill(role, user, pass, tabRole) {
    document.getElementById('username').value = user;
    document.getElementById('password').value = pass;
    document.getElementById('role_type').value = tabRole;
    document.querySelectorAll('.role-chip').forEach(function(el) {
        el.classList.remove('active');
        if (el.innerText.toLowerCase().indexOf(tabRole) !== -1) {
            el.classList.add('active');
        }
    });
}
</script>
</body>
</html>
