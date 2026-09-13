<?php
/**
 * Edit Student Profile
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin', 'Staff']);

$page_title = "Edit Student Profile";
$header_title = "Update Student";
$current_page = "students";

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($student_id <= 0) {
    header("Location: index.php");
    exit();
}

$error = '';

// Fetch all classes for the dropdown
$classes_stmt = $conn->prepare("SELECT id, class_name, section FROM classes ORDER BY class_name ASC");
$classes_stmt->execute();
$classes_list = $classes_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$classes_stmt->close();

// Fetch current student details using prepared statement
$stmt = $conn->prepare("SELECT * FROM students WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roll_no = trim($_POST['roll_no'] ?? '');
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $gender = $_POST['gender'] ?? 'Male';
    $dob = $_POST['dob'] ?? '';
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $class_id = intval($_POST['class_id'] ?? 0);
    $parent_name = trim($_POST['parent_name'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $status = $_POST['status'] ?? 'Active';

    if (empty($roll_no) || empty($first_name) || empty($last_name) || empty($dob) || empty($class_id) || empty($parent_name) || empty($parent_phone)) {
        $error = 'Please fill in all mandatory fields marked with an asterisk (*).';
    } else {
        $update_stmt = $conn->prepare("UPDATE students SET 
            roll_no = ?, 
            first_name = ?, 
            last_name = ?, 
            gender = ?, 
            dob = ?, 
            email = ?, 
            phone = ?, 
            address = ?, 
            class_id = ?, 
            parent_name = ?, 
            parent_phone = ?, 
            status = ? 
            WHERE id = ?");
        
        if ($update_stmt) {
            $update_stmt->bind_param("ssssssssisssi", 
                $roll_no, 
                $first_name, 
                $last_name, 
                $gender, 
                $dob, 
                $email, 
                $phone, 
                $address, 
                $class_id, 
                $parent_name, 
                $parent_phone, 
                $status,
                $student_id
            );

            if ($update_stmt->execute()) {
                $update_stmt->close();
                header("Location: index.php?msg=updated");
                exit();
            } else {
                $error = 'Failed to update student: ' . $update_stmt->error;
                $update_stmt->close();
            }
        } else {
            $error = 'Failed to prepare query: ' . $conn->error;
        }
    }
}

include "../includes/header.php";
?>

<div class="card" style="max-width: 900px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit Student: <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Students</a>
    </div>

    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="edit.php?id=<?php echo $student_id; ?>" method="POST">
            <h4 style="font-size: 14px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em; margin-bottom: 16px;">1. Basic Details</h4>
            
            <div class="form-grid" style="margin-bottom: 24px;">
                <div class="form-group">
                    <label class="form-label">Roll / Admission Number <span class="required">*</span></label>
                    <input type="text" name="roll_no" class="form-control" required value="<?php echo htmlspecialchars($_POST['roll_no'] ?? $student['roll_no']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Class / Grade <span class="required">*</span></label>
                    <select name="class_id" class="form-control" required>
                        <?php foreach ($classes_list as $cls): ?>
                            <option value="<?php echo $cls['id']; ?>" <?php echo (($student['class_id'] == $cls['id'])) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cls['class_name'] . ' - Section ' . $cls['section']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">First Name <span class="required">*</span></label>
                    <input type="text" name="first_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['first_name'] ?? $student['first_name']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Last Name <span class="required">*</span></label>
                    <input type="text" name="last_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['last_name'] ?? $student['last_name']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Gender <span class="required">*</span></label>
                    <select name="gender" class="form-control" required>
                        <option value="Male" <?php echo ($student['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                        <option value="Female" <?php echo ($student['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                        <option value="Other" <?php echo ($student['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Date of Birth <span class="required">*</span></label>
                    <input type="date" name="dob" class="form-control" required value="<?php echo htmlspecialchars($_POST['dob'] ?? $student['dob']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="Active" <?php echo ($student['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                        <option value="Inactive" <?php echo ($student['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive</option>
                        <option value="Graduated" <?php echo ($student['status'] === 'Graduated') ? 'selected' : ''; ?>>Graduated</option>
                        <option value="Suspended" <?php echo ($student['status'] === 'Suspended') ? 'selected' : ''; ?>>Suspended</option>
                    </select>
                </div>
            </div>

            <h4 style="font-size: 14px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.05em; margin-bottom: 16px;">2. Contact & Guardian Info</h4>
            
            <div class="form-grid" style="margin-bottom: 24px;">
                <div class="form-group">
                    <label class="form-label">Parent / Guardian Name <span class="required">*</span></label>
                    <input type="text" name="parent_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['parent_name'] ?? $student['parent_name']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Parent Phone <span class="required">*</span></label>
                    <input type="text" name="parent_phone" class="form-control" required value="<?php echo htmlspecialchars($_POST['parent_phone'] ?? $student['parent_phone']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Student Email</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($_POST['email'] ?? $student['email']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Student Phone</label>
                    <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($_POST['phone'] ?? $student['phone']); ?>">
                </div>

                <div class="form-group col-span-2">
                    <label class="form-label">Residential Address</label>
                    <textarea name="address" class="form-control"><?php echo htmlspecialchars($_POST['address'] ?? $student['address']); ?></textarea>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
