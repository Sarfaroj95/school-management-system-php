<?php
/**
 * User Edit & Profile Management
 * Edit user information and assign new temporary passwords
 */
$root_path = '../';
include $root_path . "connection.php";
include $root_path . "includes/auth.php";

require_role(['Super Admin', 'Admin']);

$page_title = "Edit User Account";
$header_title = "Update User & Security Credentials";
$current_page = "settings";

$type = $_GET['type'] ?? 'admin';
$id = intval($_GET['id'] ?? 0);

$user = null;
$error = '';
$msg = '';

// Classes for student edit
$classes_res = mysqli_query($conn, "SELECT id, class_name, section FROM classes ORDER BY id ASC");
$classes = [];
if ($classes_res) {
    while ($c = mysqli_fetch_assoc($classes_res)) {
        $classes[] = $c;
    }
}

// Fetch user data
if ($type === 'admin') {
    $stmt = $conn->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} elseif ($type === 'teacher') {
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} elseif ($type === 'student') {
    $stmt = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

if (!$user) {
    header("Location: index.php?error=" . urlencode("User not found or invalid ID."));
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $new_temp_pass = trim($_POST['new_temp_pass'] ?? '');

    if (empty($full_name)) {
        $error = "Full Name is required.";
    } else {
        if ($type === 'admin') {
            $role = trim($_POST['role'] ?? 'Staff');
            $username = trim($_POST['username'] ?? $user['username']);

            if (!empty($new_temp_pass)) {
                $hash = password_hash($new_temp_pass, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE admins SET username = ?, full_name = ?, email = ?, role = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssssi", $username, $full_name, $email, $role, $hash, $id);
            } else {
                $stmt = $conn->prepare("UPDATE admins SET username = ?, full_name = ?, email = ?, role = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $username, $full_name, $email, $role, $id);
            }
            if ($stmt->execute()) {
                header("Location: index.php?tab=staff&msg=" . urlencode("User {$full_name} successfully updated."));
                exit();
            } else {
                $error = "Error updating user: " . $conn->error;
            }
            $stmt->close();
        } elseif ($type === 'teacher') {
            $emp_id = trim($_POST['emp_id'] ?? $user['emp_id']);
            $specialization = trim($_POST['subject_specialization'] ?? '');
            $status = trim($_POST['status'] ?? 'Active');
            $salary = floatval($_POST['salary'] ?? 0.0);

            if (!empty($new_temp_pass)) {
                $hash = password_hash($new_temp_pass, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE teachers SET emp_id = ?, name = ?, email = ?, phone = ?, subject_specialization = ?, status = ?, salary = ?, password = ? WHERE id = ?");
                $stmt->bind_param("ssssssdsi", $emp_id, $full_name, $email, $phone, $specialization, $status, $salary, $hash, $id);
            } else {
                $stmt = $conn->prepare("UPDATE teachers SET emp_id = ?, name = ?, email = ?, phone = ?, subject_specialization = ?, status = ?, salary = ? WHERE id = ?");
                $stmt->bind_param("ssssssdi", $emp_id, $full_name, $email, $phone, $specialization, $status, $salary, $id);
            }
            if ($stmt->execute()) {
                header("Location: index.php?tab=teachers&msg=" . urlencode("Teacher {$full_name} successfully updated."));
                exit();
            } else {
                $error = "Error updating teacher: " . $conn->error;
            }
            $stmt->close();
        } elseif ($type === 'student') {
            $roll_no = trim($_POST['roll_no'] ?? $user['roll_no']);
            $class_id = intval($_POST['class_id'] ?? $user['class_id']);
            $status = trim($_POST['status'] ?? 'Active');
            $parent_name = trim($_POST['parent_name'] ?? '');
            $parent_phone = trim($_POST['parent_phone'] ?? '');

            $parts = explode(' ', $full_name, 2);
            $first_name = $parts[0];
            $last_name = $parts[1] ?? '';

            if (!empty($new_temp_pass)) {
                $hash = password_hash($new_temp_pass, PASSWORD_BCRYPT);
                $stmt = $conn->prepare("UPDATE students SET roll_no = ?, first_name = ?, last_name = ?, email = ?, phone = ?, class_id = ?, status = ?, parent_name = ?, parent_phone = ?, password = ? WHERE id = ?");
                $stmt->bind_param("sssssissssi", $roll_no, $first_name, $last_name, $email, $phone, $class_id, $status, $parent_name, $parent_phone, $hash, $id);
            } else {
                $stmt = $conn->prepare("UPDATE students SET roll_no = ?, first_name = ?, last_name = ?, email = ?, phone = ?, class_id = ?, status = ?, parent_name = ?, parent_phone = ? WHERE id = ?");
                $stmt->bind_param("sssssisssi", $roll_no, $first_name, $last_name, $email, $phone, $class_id, $status, $parent_name, $parent_phone, $id);
            }
            if ($stmt->execute()) {
                header("Location: index.php?tab=students&msg=" . urlencode("Student {$full_name} successfully updated."));
                exit();
            } else {
                $error = "Error updating student: " . $conn->error;
            }
            $stmt->close();
        }
    }
}

include $root_path . "includes/header.php";
?>

<div style="max-width: 800px; margin: 0 auto;">
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
        <div>
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 4px;">Edit Account Information</h2>
            <p style="font-size: 13px; color: var(--text-secondary);">Modify account attributes, roles, and security credentials.</p>
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">
            ← Return to User List
        </a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>Account Details (ID: <?php echo htmlspecialchars($user['username'] ?? $user['emp_id'] ?? $user['roll_no'] ?? $id); ?>)</span>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="user_edit.php?type=<?php echo urlencode($type); ?>&id=<?php echo $id; ?>">
                <div class="form-grid">

                    <!-- Admin / Staff specific fields -->
                    <?php if ($type === 'admin'): ?>
                        <div class="form-group col-span-2">
                            <label class="form-label" for="full_name">Full Name <span class="required">*</span></label>
                            <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="username">Username / ID <span class="required">*</span></label>
                            <input type="text" id="username" name="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="role">Role Permission Level</label>
                            <select id="role" name="role" class="form-control">
                                <option value="Super Admin" <?php echo ($user['role'] === 'Super Admin') ? 'selected' : ''; ?>>Super Admin</option>
                                <option value="Admin" <?php echo ($user['role'] === 'Admin') ? 'selected' : ''; ?>>Admin</option>
                                <option value="Staff" <?php echo ($user['role'] === 'Staff') ? 'selected' : ''; ?>>Staff</option>
                            </select>
                        </div>
                        <div class="form-group col-span-2">
                            <label class="form-label" for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                    <?php endif; ?>

                    <!-- Teacher specific fields -->
                    <?php if ($type === 'teacher'): ?>
                        <div class="form-group col-span-2">
                            <label class="form-label" for="full_name">Faculty Full Name <span class="required">*</span></label>
                            <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="emp_id">Employee ID <span class="required">*</span></label>
                            <input type="text" id="emp_id" name="emp_id" class="form-control" value="<?php echo htmlspecialchars($user['emp_id']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="status">Account Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="Active" <?php echo ($user['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="On Leave" <?php echo ($user['status'] === 'On Leave') ? 'selected' : ''; ?>>On Leave</option>
                                <option value="Resigned" <?php echo ($user['status'] === 'Resigned') ? 'selected' : ''; ?>>Resigned</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="subject_specialization">Subject Specialization</label>
                            <input type="text" id="subject_specialization" name="subject_specialization" class="form-control" value="<?php echo htmlspecialchars($user['subject_specialization']); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="salary">Monthly Salary</label>
                            <input type="number" step="0.01" id="salary" name="salary" class="form-control" value="<?php echo htmlspecialchars($user['salary']); ?>">
                        </div>
                    <?php endif; ?>

                    <!-- Student specific fields -->
                    <?php if ($type === 'student'): ?>
                        <div class="form-group col-span-2">
                            <label class="form-label" for="full_name">Student Full Name <span class="required">*</span></label>
                            <input type="text" id="full_name" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="roll_no">Roll Number <span class="required">*</span></label>
                            <input type="text" id="roll_no" name="roll_no" class="form-control" value="<?php echo htmlspecialchars($user['roll_no']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="class_id">Enrolled Class</label>
                            <select id="class_id" name="class_id" class="form-control">
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?php echo $c['id']; ?>" <?php echo ($user['class_id'] == $c['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($c['class_name'] . ' - ' . $c['section']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="status">Status</label>
                            <select id="status" name="status" class="form-control">
                                <option value="Active" <?php echo ($user['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo ($user['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                                <option value="Graduated" <?php echo ($user['status'] === 'Graduated') ? 'selected' : ''; ?>>Graduated</option>
                                <option value="Suspended" <?php echo ($user['status'] === 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="email">Student Email</label>
                            <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="parent_name">Parent / Guardian</label>
                            <input type="text" id="parent_name" name="parent_name" class="form-control" value="<?php echo htmlspecialchars($user['parent_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="parent_phone">Parent Phone</label>
                            <input type="text" id="parent_phone" name="parent_phone" class="form-control" value="<?php echo htmlspecialchars($user['parent_phone'] ?? ''); ?>">
                        </div>
                    <?php endif; ?>

                    <!-- Reset Temporary Password field -->
                    <div class="form-group col-span-2" style="background: rgba(245, 158, 11, 0.06); border: 1px solid rgba(245, 158, 11, 0.2); border-radius: 8px; padding: 14px; margin-top: 10px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <label class="form-label" for="new_temp_pass" style="color: #fbbf24; margin: 0;">
                                🔑 Set New Temporary Password (Optional)
                            </label>
                            <span style="font-size: 11px; color: #38bdf8; cursor: pointer;" onclick="genPass()">⚡ Auto-Generate</span>
                        </div>
                        <input type="text" id="new_temp_pass" name="new_temp_pass" class="form-control" placeholder="Leave empty to keep existing password" style="font-family: monospace;">
                        <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px; display: block;">
                            If entered, this will overwrite the user's password with this new temporary password.
                        </span>
                    </div>

                </div>

                <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
                        💾 Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function genPass() {
    var chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$';
    var pass = '';
    for (var i = 0; i < 9; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('new_temp_pass').value = pass;
}
</script>

<?php include $root_path . "includes/footer.php"; ?>
