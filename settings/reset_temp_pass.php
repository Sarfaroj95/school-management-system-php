<?php
/**
 * 1-Click Temporary Password Generator & Security Reset
 */
$root_path = '../';
include $root_path . "connection.php";
include $root_path . "includes/auth.php";

require_role(['Super Admin', 'Admin']);

$page_title = "Temporary Password Generated";
$header_title = "User Security & Credential Reset";
$current_page = "settings";

$type = $_GET['type'] ?? 'admin';
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php?error=" . urlencode("Invalid user ID."));
    exit();
}

$user_identifier = '';
$user_name = '';
$user_role = '';
$new_temp_pass = generate_temp_password(9);
$hash = password_hash($new_temp_pass, PASSWORD_BCRYPT);
$success = false;

if ($type === 'admin') {
    $stmt = $conn->prepare("SELECT username, full_name, role FROM admins WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($u = $res->fetch_assoc()) {
        // Enforce Super Admin security restriction
        if ($u['role'] === 'Super Admin' && !has_role('Super Admin')) {
            $stmt->close();
            header("Location: index.php?tab=staff&error=" . urlencode("Security restriction: Only a Super Admin can reset credentials for a Super Admin account."));
            exit();
        }

        // Enforce Admin restriction: Admins can only reset their own account or staff members
        if ($u['role'] === 'Admin' && !has_role('Super Admin') && $id != ($_SESSION['user_id'] ?? 0)) {
            $stmt->close();
            header("Location: index.php?tab=staff&error=" . urlencode("Security restriction: Administrators can only reset credentials for their own account or staff members."));
            exit();
        }

        $user_identifier = $u['username'];
        $user_name = $u['full_name'];
        $user_role = $u['role'];

        $upd = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $upd->bind_param("si", $hash, $id);
        $success = $upd->execute();
        $upd->close();
    }
    $stmt->close();
} elseif ($type === 'teacher') {
    $stmt = $conn->prepare("SELECT emp_id, name FROM teachers WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($t = $res->fetch_assoc()) {
        $user_identifier = $t['emp_id'];
        $user_name = $t['name'];
        $user_role = 'Teacher';

        $upd = $conn->prepare("UPDATE teachers SET password = ? WHERE id = ?");
        $upd->bind_param("si", $hash, $id);
        $success = $upd->execute();
        $upd->close();
    }
    $stmt->close();
} elseif ($type === 'student') {
    $stmt = $conn->prepare("SELECT roll_no, first_name, last_name FROM students WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($s = $res->fetch_assoc()) {
        $user_identifier = $s['roll_no'];
        $user_name = $s['first_name'] . ' ' . $s['last_name'];
        $user_role = 'Student';

        $upd = $conn->prepare("UPDATE students SET password = ? WHERE id = ?");
        $upd->bind_param("si", $hash, $id);
        $success = $upd->execute();
        $upd->close();
    }
    $stmt->close();
}

if (!$success) {
    header("Location: index.php?error=" . urlencode("Failed to generate temporary password for user."));
    exit();
}

include $root_path . "includes/header.php";
?>

<div style="max-width: 650px; margin: 30px auto;">
    <div class="card" style="border: 1px solid #10b981; background: rgba(16, 185, 129, 0.05); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);">
        <div class="card-header" style="border-color: rgba(16, 185, 129, 0.2); text-align: center; display: block; padding: 24px;">
            <div style="width: 54px; height: 54px; background: rgba(16, 185, 129, 0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px auto; font-size: 24px;">
                🔑
            </div>
            <h2 style="font-size: 20px; font-weight: 800; color: #34d399; margin-bottom: 4px;">Temporary Password Generated</h2>
            <p style="font-size: 13px; color: var(--text-secondary);">Credentials updated for <strong><?php echo htmlspecialchars($user_name); ?></strong></p>
        </div>

        <div class="card-body" style="padding: 28px;">
            <div style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; margin-bottom: 24px;">
                <div style="display: flex; flex-direction: column; gap: 14px;">
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Account Holder & Role</div>
                        <div style="font-size: 15px; font-weight: 700; color: var(--text-primary); margin-top: 2px;">
                            <?php echo htmlspecialchars($user_name); ?> · <span style="color: #38bdf8;"><?php echo htmlspecialchars($user_role); ?></span>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Unique Login ID / Username</div>
                        <div style="font-size: 18px; font-weight: 800; font-family: monospace; color: #38bdf8; margin-top: 2px;" id="dispUsername">
                            <?php echo htmlspecialchars($user_identifier); ?>
                        </div>
                    </div>
                    <div>
                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">New Temporary Password</div>
                        <div style="font-size: 20px; font-weight: 800; font-family: monospace; color: #fbbf24; margin-top: 2px;" id="dispPassword">
                            <?php echo htmlspecialchars($new_temp_pass); ?>
                        </div>
                    </div>
                </div>
            </div>

            <p style="font-size: 12px; color: var(--text-muted); margin-bottom: 22px; text-align: center;">
                ⚡ The user can now use these credentials on the login screen. They can update this password after logging in from their profile menu.
            </p>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                <button type="button" class="btn btn-primary" onclick="copyCredentials()" style="padding: 12px; font-weight: 700;">
                    📋 Copy Temporary Credentials to Clipboard
                </button>
                <a href="index.php?tab=<?php echo ($type === 'teacher' ? 'teachers' : ($type === 'student' ? 'students' : 'staff')); ?>" class="btn btn-secondary">
                    ← Return to User Management
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function copyCredentials() {
    var user = document.getElementById('dispUsername').innerText;
    var pass = document.getElementById('dispPassword').innerText;
    var text = "School SMS Portal - Password Reset\nUsername/ID: " + user + "\nTemporary Password: " + pass + "\nLogin URL: " + window.location.origin + "<?php echo $root_path; ?>login.php";
    
    navigator.clipboard.writeText(text).then(function() {
        alert("Credentials copied to clipboard:\n\n" + text);
    }).catch(function() {
        prompt("Copy credentials manually:", text);
    });
}
</script>

<?php include $root_path . "includes/footer.php"; ?>
