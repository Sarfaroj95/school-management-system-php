<?php
/**
 * Multi-Role User Creation Portal
 * Generates unique usernames, assigns roles, creates temporary passwords
 */
$root_path = '../';
include $root_path . "connection.php";
include $root_path . "includes/auth.php";

require_role(['Super Admin', 'Admin']);

$page_title = "Create New User";
$header_title = "User Provisioning & Credential Generator";
$current_page = "settings";

$selected_role = trim($_GET['role'] ?? 'Staff');
if (!in_array($selected_role, ['Admin', 'Staff', 'Teacher', 'Student'])) {
    $selected_role = 'Staff';
}

$suggested_username = generate_unique_username($selected_role, $conn);
$suggested_temp_pass = generate_temp_password(9);

$created_user = null;
$error = '';

// Fetch classes for student assignment
$classes_res = mysqli_query($conn, "SELECT id, class_name, section FROM classes ORDER BY id ASC");
$classes = [];
if ($classes_res) {
    while ($c = mysqli_fetch_assoc($classes_res)) {
        $classes[] = $c;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = trim($_POST['role'] ?? 'Staff');
    $username = trim($_POST['username'] ?? '');
    $temp_pass = trim($_POST['temp_pass'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($username) || empty($temp_pass) || empty($full_name)) {
        $error = "Please fill in the full name, username/ID, and temporary password.";
    } else {
        $hash = password_hash($temp_pass, PASSWORD_BCRYPT);

        if ($role === 'Admin' || $role === 'Staff') {
            // Check username duplicate
            $chk = $conn->prepare("SELECT id FROM admins WHERE username = ? LIMIT 1");
            $chk->bind_param("s", $username);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $error = "Username '$username' is already in use. Please choose or generate another.";
            } else {
                $stmt = $conn->prepare("INSERT INTO admins (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("sssss", $username, $hash, $full_name, $email, $role);
                if ($stmt->execute()) {
                    $created_user = [
                        'id' => $stmt->insert_id,
                        'name' => $full_name,
                        'username' => $username,
                        'temp_pass' => $temp_pass,
                        'role' => $role,
                        'email' => $email
                    ];
                } else {
                    $error = "Database error: " . $conn->error;
                }
                $stmt->close();
            }
            $chk->close();
        } elseif ($role === 'Teacher') {
            $specialization = trim($_POST['specialization'] ?? 'General');
            $qualification = trim($_POST['qualification'] ?? 'B.Ed / Master Degree');
            $salary = floatval($_POST['salary'] ?? 3500.00);

            // Check employee ID duplicate
            $chk = $conn->prepare("SELECT id FROM teachers WHERE emp_id = ? LIMIT 1");
            $chk->bind_param("s", $username);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $error = "Employee ID '$username' already exists.";
            } else {
                $stmt = $conn->prepare("INSERT INTO teachers (emp_id, name, email, password, phone, qualification, subject_specialization, joining_date, salary, status) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, 'Active')");
                $stmt->bind_param("sssssssd", $username, $full_name, $email, $hash, $phone, $qualification, $specialization, $salary);
                if ($stmt->execute()) {
                    $created_user = [
                        'id' => $stmt->insert_id,
                        'name' => $full_name,
                        'username' => $username,
                        'temp_pass' => $temp_pass,
                        'role' => 'Teacher',
                        'email' => $email
                    ];
                } else {
                    $error = "Database error: " . $conn->error;
                }
                $stmt->close();
            }
            $chk->close();
        } elseif ($role === 'Student') {
            $class_id = intval($_POST['class_id'] ?? 1);
            $gender = trim($_POST['gender'] ?? 'Male');
            $dob = !empty($_POST['dob']) ? trim($_POST['dob']) : '2012-01-01';
            $parent_name = trim($_POST['parent_name'] ?? 'Parent / Guardian');
            $parent_phone = trim($_POST['parent_phone'] ?? $phone);
            $address = trim($_POST['address'] ?? 'Campus Residence');

            // Split full name into first and last
            $parts = explode(' ', $full_name, 2);
            $first_name = $parts[0];
            $last_name = $parts[1] ?? 'Student';

            // Check roll no duplicate
            $chk = $conn->prepare("SELECT id FROM students WHERE roll_no = ? LIMIT 1");
            $chk->bind_param("s", $username);
            $chk->execute();
            if ($chk->get_result()->num_rows > 0) {
                $error = "Roll Number '$username' already exists.";
            } else {
                $stmt = $conn->prepare("INSERT INTO students (roll_no, first_name, last_name, gender, dob, email, password, phone, address, class_id, admission_date, parent_name, parent_phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, 'Active')");
                $stmt->bind_param("sssssssssisss", $username, $first_name, $last_name, $gender, $dob, $email, $hash, $phone, $address, $class_id, $parent_name, $parent_phone);
                if ($stmt->execute()) {
                    $created_user = [
                        'id' => $stmt->insert_id,
                        'name' => $full_name,
                        'username' => $username,
                        'temp_pass' => $temp_pass,
                        'role' => 'Student',
                        'email' => $email
                    ];
                } else {
                    $error = "Database error: " . $conn->error;
                }
                $stmt->close();
            }
            $chk->close();
        }
    }
}

include $root_path . "includes/header.php";
?>

<div style="max-width: 820px; margin: 0 auto;">
    
    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px;">
        <div>
            <h2 style="font-size: 20px; font-weight: 700; margin-bottom: 4px;">Provision New Account</h2>
            <p style="font-size: 13px; color: var(--text-secondary);">Assign unique identifier, temporary password, and initial role section access.</p>
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">
            ← Return to Settings
        </a>
    </div>

    <?php if ($created_user): ?>
        <!-- Success Credential Card with 1-Click Copy -->
        <div class="card" style="border: 1px solid #10b981; background: rgba(16, 185, 129, 0.08); margin-bottom: 28px;">
            <div class="card-header" style="border-color: rgba(16, 185, 129, 0.2);">
                <div class="card-title" style="color: #34d399;">
                    <span>🎉 User Account Successfully Provisioned</span>
                </div>
            </div>
            <div class="card-body">
                <p style="font-size: 14px; margin-bottom: 16px; color: #e2e8f0;">
                    Please securely provide these login credentials to <strong><?php echo htmlspecialchars($created_user['name']); ?></strong>. They can sign in on the main portal and update their password.
                </p>

                <div style="background: #0b0f19; border: 1px solid rgba(255,255,255,0.1); border-radius: 10px; padding: 20px; margin-bottom: 18px;">
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                        <div>
                            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Role Assigned</div>
                            <div style="font-size: 15px; font-weight: 700; color: #f8fafc; margin-top: 2px;">
                                <?php echo htmlspecialchars($created_user['role']); ?>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Unique Username / ID</div>
                            <div style="font-size: 16px; font-weight: 800; font-family: monospace; color: #38bdf8; margin-top: 2px;" id="dispUsername">
                                <?php echo htmlspecialchars($created_user['username']); ?>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase; font-weight: 700;">Temporary Password</div>
                            <div style="font-size: 16px; font-weight: 800; font-family: monospace; color: #fbbf24; margin-top: 2px;" id="dispPassword">
                                <?php echo htmlspecialchars($created_user['temp_pass']); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                    <button type="button" class="btn btn-primary" onclick="copyCredentials()">
                        📋 Copy Credentials to Clipboard
                    </button>
                    <a href="user_create.php?role=<?php echo urlencode($created_user['role']); ?>" class="btn btn-secondary">
                        + Add Another <?php echo htmlspecialchars($created_user['role']); ?>
                    </a>
                    <a href="index.php" class="btn btn-secondary">
                        Return to User List
                    </a>
                </div>
            </div>
        </div>

        <script>
        function copyCredentials() {
            var user = document.getElementById('dispUsername').innerText;
            var pass = document.getElementById('dispPassword').innerText;
            var text = "School SMS Portal Credentials\nUsername: " + user + "\nTemporary Password: " + pass + "\nLogin URL: " + window.location.origin + "<?php echo $root_path; ?>login.php";
            
            navigator.clipboard.writeText(text).then(function() {
                alert("Credentials copied to clipboard:\n\n" + text);
            }).catch(function() {
                prompt("Copy credentials manually:", text);
            });
        }
        </script>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    <?php endif; ?>

    <!-- User Provision Form -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <span>Account Specifications & Role</span>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="user_create.php" id="createForm">
                
                <!-- Role Selection -->
                <div class="form-group" style="margin-bottom: 20px;">
                    <label class="form-label">Select Account Role Level <span class="required">*</span></label>
                    <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px;">
                        <label class="role-chip <?php echo ($selected_role === 'Staff') ? 'active' : ''; ?>" style="border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 8px; cursor: pointer;">
                            <input type="radio" name="role" value="Staff" <?php echo ($selected_role === 'Staff') ? 'checked' : ''; ?> onchange="window.location.href='user_create.php?role=Staff'" style="display: none;">
                            💼 Staff Member
                        </label>
                        <label class="role-chip <?php echo ($selected_role === 'Teacher') ? 'active' : ''; ?>" style="border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 8px; cursor: pointer;">
                            <input type="radio" name="role" value="Teacher" <?php echo ($selected_role === 'Teacher') ? 'checked' : ''; ?> onchange="window.location.href='user_create.php?role=Teacher'" style="display: none;">
                            👨‍🏫 Teacher / Faculty
                        </label>
                        <label class="role-chip <?php echo ($selected_role === 'Student') ? 'active' : ''; ?>" style="border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 8px; cursor: pointer;">
                            <input type="radio" name="role" value="Student" <?php echo ($selected_role === 'Student') ? 'checked' : ''; ?> onchange="window.location.href='user_create.php?role=Student'" style="display: none;">
                            🎓 Student
                        </label>
                        <label class="role-chip <?php echo ($selected_role === 'Admin') ? 'active' : ''; ?>" style="border: 1px solid var(--border-color); padding: 8px 16px; border-radius: 8px; cursor: pointer;">
                            <input type="radio" name="role" value="Admin" <?php echo ($selected_role === 'Admin') ? 'checked' : ''; ?> onchange="window.location.href='user_create.php?role=Admin'" style="display: none;">
                            🛡️ Administrator
                        </label>
                    </div>
                </div>

                <div class="form-grid">
                    <!-- Full Name -->
                    <div class="form-group col-span-2">
                        <label class="form-label" for="full_name">Full Name <span class="required">*</span></label>
                        <input type="text" id="full_name" name="full_name" class="form-control" placeholder="e.g. Eleanor Vance" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                    </div>

                    <!-- Unique Identifier (Auto Generated) -->
                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <label class="form-label" for="username">
                                <?php echo ($selected_role === 'Teacher') ? 'Employee ID (Emp ID)' : (($selected_role === 'Student') ? 'Roll Number' : 'Unique Username'); ?>
                                <span class="required">*</span>
                            </label>
                            <span style="font-size: 11px; color: #38bdf8; cursor: pointer;" onclick="regenerateUsername()">⚡ Auto-Generate</span>
                        </div>
                        <input type="text" id="username" name="username" class="form-control" style="font-family: monospace; font-weight: 700; color: #38bdf8;" value="<?php echo htmlspecialchars($_POST['username'] ?? $suggested_username); ?>" required>
                    </div>

                    <!-- Temporary Password (Auto Generated) -->
                    <div class="form-group">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <label class="form-label" for="temp_pass">Temporary Password <span class="required">*</span></label>
                            <span style="font-size: 11px; color: #fbbf24; cursor: pointer;" onclick="regeneratePassword()">⚡ Randomize</span>
                        </div>
                        <input type="text" id="temp_pass" name="temp_pass" class="form-control" style="font-family: monospace; font-weight: 700; color: #fbbf24;" value="<?php echo htmlspecialchars($_POST['temp_pass'] ?? $suggested_temp_pass); ?>" required>
                    </div>

                    <!-- Email Address -->
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" placeholder="user@schoolsms.edu" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                    </div>

                    <!-- Phone -->
                    <div class="form-group">
                        <label class="form-label" for="phone">Contact Phone</label>
                        <input type="text" id="phone" name="phone" class="form-control" placeholder="+1 (555) 000-0000" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                    </div>

                    <!-- Role-Specific Fields: Teacher -->
                    <?php if ($selected_role === 'Teacher'): ?>
                        <div class="form-group">
                            <label class="form-label" for="specialization">Subject / Specialization</label>
                            <input type="text" id="specialization" name="specialization" class="form-control" placeholder="e.g. Science / Mathematics" value="<?php echo htmlspecialchars($_POST['specialization'] ?? 'Mathematics'); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="qualification">Academic Qualification</label>
                            <input type="text" id="qualification" name="qualification" class="form-control" placeholder="e.g. M.Sc, B.Ed" value="<?php echo htmlspecialchars($_POST['qualification'] ?? 'B.Ed, M.Sc'); ?>">
                        </div>
                    <?php endif; ?>

                    <!-- Role-Specific Fields: Student -->
                    <?php if ($selected_role === 'Student'): ?>
                        <div class="form-group">
                            <label class="form-label" for="class_id">Assigned Class & Section <span class="required">*</span></label>
                            <select id="class_id" name="class_id" class="form-control" required>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?php echo $c['id']; ?>">
                                        <?php echo htmlspecialchars($c['class_name'] . ' - ' . $c['section']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="gender">Gender</label>
                            <select id="gender" name="gender" class="form-control">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="parent_name">Parent / Guardian Name</label>
                            <input type="text" id="parent_name" name="parent_name" class="form-control" placeholder="e.g. Arthur Vance" value="<?php echo htmlspecialchars($_POST['parent_name'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob" class="form-control" value="2012-05-15">
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center;">
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700;">
                        ✓ Provision Account & Save Credentials
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function regeneratePassword() {
    var chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$';
    var pass = '';
    for (var i = 0; i < 9; i++) {
        pass += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('temp_pass').value = pass;
}

function regenerateUsername() {
    var role = '<?php echo $selected_role; ?>';
    var prefix = 'STF';
    if (role === 'Teacher') prefix = 'EMP';
    else if (role === 'Student') prefix = 'STD';
    else if (role === 'Admin') prefix = 'ADM';
    
    var num = Math.floor(100 + Math.random() * 900);
    document.getElementById('username').value = prefix + '-' + num;
}
</script>

<?php include $root_path . "includes/footer.php"; ?>
