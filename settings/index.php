<?php
/**
 * Settings & Multi-Role User Management Portal
 * Strictly restricted to Super Admin and Admin users.
 */
$root_path = '../';
include $root_path . "connection.php";
include $root_path . "includes/auth.php";

// Enforce Admin-only access
require_role(['Super Admin', 'Admin']);

$page_title = "Settings & User Management";
$header_title = "System Settings & Multi-Role Users";
$current_page = "settings";

$active_tab = $_GET['tab'] ?? 'staff';
$search = trim($_GET['q'] ?? '');
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Handle System Settings Save
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_system_settings'])) {
    $keys = [
        'school_name' => trim($_POST['school_name'] ?? ''),
        'school_email' => trim($_POST['school_email'] ?? ''),
        'school_phone' => trim($_POST['school_phone'] ?? ''),
        'school_address' => trim($_POST['school_address'] ?? ''),
        'academic_year' => trim($_POST['academic_year'] ?? ''),
        'currency_symbol' => trim($_POST['currency_symbol'] ?? '$')
    ];

    foreach ($keys as $k => $v) {
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        if ($stmt) {
            $stmt->bind_param("sss", $k, $v, $v);
            $stmt->execute();
            $stmt->close();
        }
    }
    header("Location: index.php?tab=system&msg=" . urlencode("System settings successfully updated."));
    exit();
}

// 1. Fetch Admins & Staff
$admin_query = "SELECT * FROM admins";
if (!empty($search) && $active_tab === 'staff') {
    $esc = mysqli_real_escape_string($conn, $search);
    $admin_query .= " WHERE username LIKE '%$esc%' OR full_name LIKE '%$esc%' OR email LIKE '%$esc%'";
}
$admin_query .= " ORDER BY id ASC";
$admins_res = mysqli_query($conn, $admin_query);

// 2. Fetch Teachers
$teacher_query = "SELECT * FROM teachers";
if (!empty($search) && $active_tab === 'teachers') {
    $esc = mysqli_real_escape_string($conn, $search);
    $teacher_query .= " WHERE emp_id LIKE '%$esc%' OR name LIKE '%$esc%' OR email LIKE '%$esc%' OR subject_specialization LIKE '%$esc%'";
}
$teacher_query .= " ORDER BY id DESC";
$teachers_res = mysqli_query($conn, $teacher_query);

// 3. Fetch Students
$student_query = "SELECT s.*, c.class_name, c.section FROM students s LEFT JOIN classes c ON s.class_id = c.id";
if (!empty($search) && $active_tab === 'students') {
    $esc = mysqli_real_escape_string($conn, $search);
    $student_query .= " WHERE s.roll_no LIKE '%$esc%' OR s.first_name LIKE '%$esc%' OR s.last_name LIKE '%$esc%' OR s.email LIKE '%$esc%'";
}
$student_query .= " ORDER BY s.id DESC";
$students_res = mysqli_query($conn, $student_query);

// 4. Fetch System Settings
$settings_map = [];
$set_res = mysqli_query($conn, "SELECT setting_key, setting_value FROM system_settings");
if ($set_res) {
    while ($row = mysqli_fetch_assoc($set_res)) {
        $settings_map[$row['setting_key']] = $row['setting_value'];
    }
}

// Stats counts
$count_admins = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM admins WHERE role IN ('Super Admin', 'Admin')"));
$count_staff = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM admins WHERE role = 'Staff'"));
$count_teachers = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM teachers"));
$count_students = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM students"));

include $root_path . "includes/header.php";
?>

<!-- Alert Feedback -->
<?php if (!empty($msg)): ?>
    <div class="alert alert-success">
        <div style="display: flex; align-items: center; gap: 8px;">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <div style="display: flex; align-items: center; gap: 8px;">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    </div>
<?php endif; ?>

<!-- Quick Stat Cards -->
<div class="grid-stats">
    <div class="stat-card indigo">
        <div class="stat-header">
            <span class="stat-label">Admins & Staff</span>
            <div class="stat-icon">🛡️</div>
        </div>
        <div class="stat-value"><?php echo $count_admins + $count_staff; ?></div>
        <div class="stat-footer"><?php echo $count_admins; ?> Admins · <?php echo $count_staff; ?> Staff</div>
    </div>

    <div class="stat-card emerald">
        <div class="stat-header">
            <span class="stat-label">Faculty / Teachers</span>
            <div class="stat-icon">👨‍🏫</div>
        </div>
        <div class="stat-value"><?php echo $count_teachers; ?></div>
        <div class="stat-footer">Active Teaching Staff</div>
    </div>

    <div class="stat-card amber">
        <div class="stat-header">
            <span class="stat-label">Enrolled Students</span>
            <div class="stat-icon">🎓</div>
        </div>
        <div class="stat-value"><?php echo $count_students; ?></div>
        <div class="stat-footer">Unique Roll Numbers</div>
    </div>

    <div class="stat-card sky">
        <div class="stat-header">
            <span class="stat-label">Role Access Modes</span>
            <div class="stat-icon">🔐</div>
        </div>
        <div class="stat-value">5 Roles</div>
        <div class="stat-footer">Granular Section Controls</div>
    </div>
</div>

<!-- Main Card & Tabs -->
<div class="card">
    <div class="card-header" style="border-bottom: none; padding-bottom: 0;">
        <div style="display: flex; align-items: center; justify-content: space-between; width: 100%; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                <a href="index.php?tab=staff" class="btn <?php echo ($active_tab === 'staff') ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                    👥 Admins & Staff (<?php echo $count_admins + $count_staff; ?>)
                </a>
                <a href="index.php?tab=teachers" class="btn <?php echo ($active_tab === 'teachers') ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                    👨‍🏫 Teachers (<?php echo $count_teachers; ?>)
                </a>
                <a href="index.php?tab=students" class="btn <?php echo ($active_tab === 'students') ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                    🎓 Students (<?php echo $count_students; ?>)
                </a>
                <a href="index.php?tab=matrix" class="btn <?php echo ($active_tab === 'matrix') ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                    🔒 Section Access Matrix
                </a>
                <a href="index.php?tab=system" class="btn <?php echo ($active_tab === 'system') ? 'btn-primary' : 'btn-secondary'; ?> btn-sm">
                    ⚙️ General Settings
                </a>
            </div>

            <div style="display: flex; gap: 10px; align-items: center;">
                <?php if ($active_tab !== 'matrix' && $active_tab !== 'system'): ?>
                    <form method="GET" action="index.php" style="display: flex; gap: 8px;">
                        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
                        <input type="text" name="q" class="search-input" placeholder="Search user by ID, name..." value="<?php echo htmlspecialchars($search); ?>">
                        <?php if (!empty($search)): ?>
                            <a href="index.php?tab=<?php echo htmlspecialchars($active_tab); ?>" class="btn btn-secondary btn-sm" title="Clear Search">✕</a>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>

                <a href="db_export.php" class="btn btn-secondary btn-sm" title="Download Full SQL Database Backup">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export Database (.sql)
                </a>

                <a href="user_create.php<?php echo ($active_tab === 'teachers' ? '?role=Teacher' : ($active_tab === 'students' ? '?role=Student' : '?role=Staff')); ?>" class="btn btn-primary btn-sm">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add New User
                </a>
            </div>
        </div>
    </div>

    <div class="card-body" style="padding-top: 18px;">
        
        <!-- ======================================================== -->
        <!-- TAB 1: ADMINS & STAFF -->
        <!-- ======================================================== -->
        <?php if ($active_tab === 'staff'): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>User ID / Username</th>
                            <th>Full Name</th>
                            <th>Email Address</th>
                            <th>Role Level</th>
                            <th>Created Date</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($admins_res) === 0): ?>
                            <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No administrative users found.</td></tr>
                        <?php else: ?>
                            <?php while ($adm = mysqli_fetch_assoc($admins_res)): ?>
                                <tr>
                                    <td>
                                        <div style="font-family: monospace; font-weight: 700; color: #38bdf8;">
                                            <?php echo htmlspecialchars($adm['username']); ?>
                                        </div>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($adm['full_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($adm['email']); ?></td>
                                    <td>
                                        <?php if ($adm['role'] === 'Super Admin'): ?>
                                            <span class="badge badge-danger">Super Admin</span>
                                        <?php elseif ($adm['role'] === 'Staff'): ?>
                                            <span class="badge badge-purple">Staff Member</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">Admin</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size: 12px; color: var(--text-muted);"><?php echo date('M d, Y', strtotime($adm['created_at'] ?? 'now')); ?></td>
                                    <td style="text-align: right;">
                                        <div style="display: inline-flex; gap: 6px;">
                                            <a href="reset_temp_pass.php?type=admin&id=<?php echo $adm['id']; ?>" class="btn btn-secondary btn-sm" title="Generate Temporary Password" onclick="return confirm('Generate and display a new temporary password for user <?php echo addslashes($adm['username']); ?>?');">
                                                🔑 Temp Pass
                                            </a>
                                            <a href="user_edit.php?type=admin&id=<?php echo $adm['id']; ?>" class="btn btn-secondary btn-sm">
                                                ✏️ Edit
                                            </a>
                                            <?php if ($adm['id'] != ($_SESSION['user_id'] ?? 0)): ?>
                                                <a href="user_delete.php?type=admin&id=<?php echo $adm['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete user <?php echo addslashes($adm['username']); ?>?');">
                                                    🗑️
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <!-- ======================================================== -->
        <!-- TAB 2: TEACHERS -->
        <!-- ======================================================== -->
        <?php elseif ($active_tab === 'teachers'): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Unique Employee ID</th>
                            <th>Faculty Name</th>
                            <th>Email Address</th>
                            <th>Subject / Specialization</th>
                            <th>Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($teachers_res) === 0): ?>
                            <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No faculty members found.</td></tr>
                        <?php else: ?>
                            <?php while ($t = mysqli_fetch_assoc($teachers_res)): ?>
                                <tr>
                                    <td>
                                        <div style="font-family: monospace; font-weight: 700; color: #34d399;">
                                            <?php echo htmlspecialchars($t['emp_id']); ?>
                                        </div>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($t['name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($t['email']); ?></td>
                                    <td><?php echo htmlspecialchars($t['subject_specialization'] ?: 'General Faculty'); ?></td>
                                    <td>
                                        <span class="badge <?php echo ($t['status'] === 'Active') ? 'badge-success' : 'badge-warning'; ?>">
                                            <?php echo htmlspecialchars($t['status']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: inline-flex; gap: 6px;">
                                            <a href="reset_temp_pass.php?type=teacher&id=<?php echo $t['id']; ?>" class="btn btn-secondary btn-sm" title="Generate Temporary Password" onclick="return confirm('Generate a new temporary password for <?php echo addslashes($t['name']); ?>?');">
                                                🔑 Temp Pass
                                            </a>
                                            <a href="user_edit.php?type=teacher&id=<?php echo $t['id']; ?>" class="btn btn-secondary btn-sm">
                                                ✏️ Edit
                                            </a>
                                            <a href="user_delete.php?type=teacher&id=<?php echo $t['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete teacher <?php echo addslashes($t['name']); ?>?');">
                                                🗑️
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <!-- ======================================================== -->
        <!-- TAB 3: STUDENTS -->
        <!-- ======================================================== -->
        <?php elseif ($active_tab === 'students'): ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Unique Roll Number</th>
                            <th>Student Full Name</th>
                            <th>Enrolled Class</th>
                            <th>Parent / Guardian</th>
                            <th>Account Status</th>
                            <th style="text-align: right;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($students_res) === 0): ?>
                            <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">No students found.</td></tr>
                        <?php else: ?>
                            <?php while ($stu = mysqli_fetch_assoc($students_res)): ?>
                                <tr>
                                    <td>
                                        <div style="font-family: monospace; font-weight: 700; color: #fbbf24;">
                                            <?php echo htmlspecialchars($stu['roll_no']); ?>
                                        </div>
                                    </td>
                                    <td><strong><?php echo htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']); ?></strong></td>
                                    <td><?php echo htmlspecialchars(($stu['class_name'] ?? 'Class') . ' - ' . ($stu['section'] ?? 'A')); ?></td>
                                    <td><?php echo htmlspecialchars($stu['parent_name'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge <?php echo ($stu['status'] === 'Active') ? 'badge-success' : 'badge-danger'; ?>">
                                            <?php echo htmlspecialchars($stu['status']); ?>
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <div style="display: inline-flex; gap: 6px;">
                                            <a href="reset_temp_pass.php?type=student&id=<?php echo $stu['id']; ?>" class="btn btn-secondary btn-sm" title="Generate Temporary Password" onclick="return confirm('Generate temporary password for student <?php echo addslashes($stu['first_name']); ?>?');">
                                                🔑 Temp Pass
                                            </a>
                                            <a href="user_edit.php?type=student&id=<?php echo $stu['id']; ?>" class="btn btn-secondary btn-sm">
                                                ✏️ Edit
                                            </a>
                                            <a href="user_delete.php?type=student&id=<?php echo $stu['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete student <?php echo addslashes($stu['first_name']); ?>?');">
                                                🗑️
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        <!-- ======================================================== -->
        <!-- TAB 4: SECTION ACCESS & PERMISSIONS MATRIX -->
        <!-- ======================================================== -->
        <?php elseif ($active_tab === 'matrix'): ?>
            <?php $matrix = get_role_permissions_matrix(); ?>
            <div style="margin-bottom: 20px;">
                <h3 style="font-size: 16px; margin-bottom: 6px;">Institutional Role Section Access Controls</h3>
                <p style="font-size: 13px; color: var(--text-secondary);">
                    This matrix defines exactly which sections, dashboard features, and actions are accessible to each user role once they log in.
                </p>
            </div>

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Module / Section</th>
                            <th style="text-align: center;">Super Admin</th>
                            <th style="text-align: center;">Admin</th>
                            <th style="text-align: center;">Staff</th>
                            <th style="text-align: center;">Teacher</th>
                            <th style="text-align: center;">Student</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $sections = [
                            'dashboard'   => 'Dashboard & Analytics',
                            'students'    => 'Student Management / Roster',
                            'teachers'    => 'Teachers & Faculty Directory',
                            'classes'     => 'Classes & Schedules',
                            'attendance'  => 'Attendance Marking & Log',
                            'results'     => 'Exams, Marks & Report Cards',
                            'library'     => 'Library Catalog & Issue System',
                            'notices'     => 'Notice Board Announcements',
                            'reports'     => 'Institutional Reports & Export',
                            'settings'    => 'Settings & Multi-Role Users'
                        ];

                        foreach ($sections as $sec_key => $sec_title): 
                        ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($sec_title); ?></strong></td>
                                <?php foreach (['Super Admin', 'Admin', 'Staff', 'Teacher', 'Student'] as $role_name): 
                                    $has_access = $matrix[$role_name][$sec_key]['access'] ?? false;
                                    $actions = $matrix[$role_name][$sec_key]['actions'] ?? [];
                                ?>
                                    <td style="text-align: center;">
                                        <?php if ($has_access): ?>
                                            <span class="badge badge-success" style="font-size: 11px;">✓ Enabled</span>
                                            <div style="font-size: 10px; color: var(--text-muted); margin-top: 4px;">
                                                <?php echo implode(', ', $actions); ?>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge" style="background: rgba(239, 68, 68, 0.1); color: #f87171; font-size: 11px;">✕ Restricted</span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <!-- ======================================================== -->
        <!-- TAB 5: GENERAL SYSTEM SETTINGS -->
        <!-- ======================================================== -->
        <?php elseif ($active_tab === 'system'): ?>
            <form method="POST" action="index.php?tab=system">
                <div class="form-grid">
                    <div class="form-group col-span-2">
                        <label class="form-label" for="school_name">Institution / School Name <span class="required">*</span></label>
                        <input type="text" id="school_name" name="school_name" class="form-control" value="<?php echo htmlspecialchars($settings_map['school_name'] ?? 'EduCore Model International School'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="school_email">Official Contact Email <span class="required">*</span></label>
                        <input type="email" id="school_email" name="school_email" class="form-control" value="<?php echo htmlspecialchars($settings_map['school_email'] ?? 'contact@educore-sms.edu'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="school_phone">Contact Phone Number</label>
                        <input type="text" id="school_phone" name="school_phone" class="form-control" value="<?php echo htmlspecialchars($settings_map['school_phone'] ?? '+1 (555) 019-2834'); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="academic_year">Current Academic Session / Year</label>
                        <input type="text" id="academic_year" name="academic_year" class="form-control" value="<?php echo htmlspecialchars($settings_map['academic_year'] ?? '2026-2027'); ?>">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="currency_symbol">Currency Symbol</label>
                        <input type="text" id="currency_symbol" name="currency_symbol" class="form-control" value="<?php echo htmlspecialchars($settings_map['currency_symbol'] ?? '$'); ?>">
                    </div>

                    <div class="form-group col-span-full">
                        <label class="form-label" for="school_address">Campus Physical Address</label>
                        <textarea id="school_address" name="school_address" class="form-control" rows="3"><?php echo htmlspecialchars($settings_map['school_address'] ?? '100 University Avenue, Tech Park, Suite 400'); ?></textarea>
                    </div>
                </div>

                <div style="margin-top: 24px; padding-top: 18px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
                    <button type="submit" name="save_system_settings" class="btn btn-primary">
                        💾 Save Institutional Settings
                    </button>
                </div>
            </form>
        <?php endif; ?>

    </div>
</div>

<?php include $root_path . "includes/footer.php"; ?>
