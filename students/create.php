<?php
/**
 * New Student Admission Form
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin', 'Staff']);

$page_title = "New Student Admission";
$header_title = "Student Admission";
$current_page = "students";

$error = '';

// Fetch all classes for the dropdown
$classes_stmt = $conn->prepare("SELECT id, class_name, section FROM classes ORDER BY class_name ASC");
$classes_stmt->execute();
$classes_list = $classes_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$classes_stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_no = trim($_POST['roll_no'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? 'Male';
    $dob = $_POST['dob'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $raw_pass = trim($_POST['password'] ?? 'student123');
    if (empty($raw_pass)) $raw_pass = 'student123';
    $hashed_pass = password_hash($raw_pass, PASSWORD_DEFAULT);
    $phone = trim($_POST['phone'] ?? '');
    $class_id = intval($_POST['class_id'] ?? 0);
    $admission_date = $_POST['admission_date'] ?? date('Y-m-d');
    $parent_name = trim($_POST['parent_name'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    if (empty($roll_no) || empty($first_name) || empty($last_name) || empty($dob) || empty($class_id) || empty($parent_name) || empty($parent_phone)) {
        $error = 'Please fill in all mandatory fields marked with an asterisk (*).';
    } else {
        // Insert student using prepared statement
        $insert_stmt = $conn->prepare("INSERT INTO students 
            (roll_no, first_name, last_name, gender, dob, email, password, phone, address, class_id, admission_date, parent_name, parent_phone, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        if ($insert_stmt) {
            $insert_stmt->bind_param("sssssssssissss", 
                $roll_no, 
                $first_name, 
                $last_name, 
                $gender, 
                $dob, 
                $email,
                $hashed_pass,
                $phone, 
                $address, 
                $class_id, 
                $admission_date, 
                $parent_name, 
                $parent_phone, 
                $status
            );

            if ($insert_stmt->execute()) {
                $insert_stmt->close();
                header("Location: index.php?msg=created");
                exit();
            } else {
                $error = 'Database insertion failed: ' . $insert_stmt->error;
                $insert_stmt->close();
            }
        } else {
            $error = 'Failed to prepare database query: ' . $conn->error;
        }
    }
}

// Generate an auto roll number suggestion
$auto_roll = 'STD-' . rand(1050, 9999);

include "../includes/header.php";
?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            Student Admission Registration
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Students</a>
    </div>

    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="create.php" method="POST">
            <h4 style="font-size: 14px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em; margin-bottom: 16px;">1. Basic Details</h4>
            
            <div class="form-grid" style="margin-bottom: 24px;">
                <div class="form-group">
                    <label class="form-label">Roll / Admission Number <span class="required">*</span></label>
                    <input type="text" name="roll_no" class="form-control" required value="<?php echo htmlspecialchars($_POST['roll_no'] ?? $auto_roll); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Target Class / Grade <span class="required">*</span></label>
                    <select name="class_id" class="form-control" required>
                        <option value="">-- Select Class --</option>
                        <?php foreach ($classes_list as $cls): ?>
                            <option value="<?php echo $cls['id']; ?>" <?php echo (isset($_POST['class_id']) && $_POST['class_id'] == $cls['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cls['class_name'] . ' - Section ' . $cls['section']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">First Name <span class="required">*</span></label>
                    <input type="text" name="first_name" class="form-control" placeholder="e.g. Jordan" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Last Name <span class="required">*</span></label>
                    <input type="text" name="last_name" class="form-control" placeholder="e.g. Vance" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Gender <span class="required">*</span></label>
                    <select name="gender" class="form-control" required>
                        <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Date of Birth <span class="required">*</span></label>
                    <input type="date" name="dob" class="form-control" required value="<?php echo htmlspecialchars($_POST['dob'] ?? '2012-01-01'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Admission Date</label>
                    <input type="date" name="admission_date" class="form-control" value="<?php echo htmlspecialchars($_POST['admission_date'] ?? date('Y-m-d')); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Enrollment Status</label>
                    <select name="status" class="form-control">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Suspended">Suspended</option>
                    </select>
                </div>
            </div>

            <h4 style="font-size: 14px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em; margin-bottom: 16px;">2. Contact & Guardian Info</h4>
            
            <div class="form-grid" style="margin-bottom: 24px;">
                <div class="form-group">
                    <label class="form-label">Parent / Guardian Name <span class="required">*</span></label>
                    <input type="text" name="parent_name" class="form-control" placeholder="e.g. Samuel Vance" required value="<?php echo htmlspecialchars($_POST['parent_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Parent Phone <span class="required">*</span></label>
                    <input type="text" name="parent_phone" class="form-control" placeholder="e.g. +1 555-0199" required value="<?php echo htmlspecialchars($_POST['parent_phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Student Email</label>
                    <input type="email" name="email" class="form-control" placeholder="e.g. student@example.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Student Phone</label>
                    <input type="text" name="phone" class="form-control" placeholder="e.g. +1 555-0100" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>

                <div class="form-group col-span-2">
                    <label class="form-label">Residential Address</label>
                    <textarea name="address" class="form-control" placeholder="Street address, city, state, zip..."><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Complete Admission</button>
            </div>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
