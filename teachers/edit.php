<?php
/**
 * Edit Teacher Profile
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin']);

$page_title = "Edit Teacher Details";
$header_title = "Update Faculty";
$current_page = "teachers";

$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($teacher_id <= 0) {
    header("Location: index.php");
    exit();
}

$error = '';

// Fetch teacher details
$stmt = $conn->prepare("SELECT * FROM teachers WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$teacher) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_id = trim($_POST['emp_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $subject_specialization = trim($_POST['subject_specialization'] ?? '');
    $joining_date = $_POST['joining_date'] ?? date('Y-m-d');
    $salary = floatval($_POST['salary'] ?? 0.00);
    $status = $_POST['status'] ?? 'Active';

    if (empty($emp_id) || empty($name) || empty($email) || empty($phone) || empty($qualification) || empty($subject_specialization)) {
        $error = 'Please fill in all mandatory fields marked with an asterisk (*).';
    } else {
        $update_stmt = $conn->prepare("UPDATE teachers SET emp_id = ?, name = ?, email = ?, phone = ?, qualification = ?, subject_specialization = ?, joining_date = ?, salary = ?, status = ? WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("sssssssdsi", $emp_id, $name, $email, $phone, $qualification, $subject_specialization, $joining_date, $salary, $status, $teacher_id);
            if ($update_stmt->execute()) {
                $update_stmt->close();
                header("Location: index.php?msg=updated");
                exit();
            } else {
                $error = 'Failed to update teacher: ' . $update_stmt->error;
                $update_stmt->close();
            }
        } else {
            $error = 'Query preparation failed: ' . $conn->error;
        }
    }
}

include "../includes/header.php";
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit Faculty: <?php echo htmlspecialchars($teacher['name']); ?>
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Faculty</a>
    </div>

    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="edit.php?id=<?php echo $teacher_id; ?>" method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Employee ID <span class="required">*</span></label>
                    <input type="text" name="emp_id" class="form-control" required value="<?php echo htmlspecialchars($_POST['emp_id'] ?? $teacher['emp_id']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Full Name <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?php echo htmlspecialchars($_POST['name'] ?? $teacher['name']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($_POST['email'] ?? $teacher['email']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number <span class="required">*</span></label>
                    <input type="text" name="phone" class="form-control" required value="<?php echo htmlspecialchars($_POST['phone'] ?? $teacher['phone']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Qualification <span class="required">*</span></label>
                    <input type="text" name="qualification" class="form-control" required value="<?php echo htmlspecialchars($_POST['qualification'] ?? $teacher['qualification']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Subject Specialization <span class="required">*</span></label>
                    <input type="text" name="subject_specialization" class="form-control" required value="<?php echo htmlspecialchars($_POST['subject_specialization'] ?? $teacher['subject_specialization']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control" value="<?php echo htmlspecialchars($_POST['joining_date'] ?? $teacher['joining_date']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Monthly Salary ($)</label>
                    <input type="number" step="0.01" name="salary" class="form-control" value="<?php echo htmlspecialchars($_POST['salary'] ?? $teacher['salary']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Employment Status</label>
                    <select name="status" class="form-control">
                        <option value="Active" <?php echo ($teacher['status'] === 'Active') ? 'selected' : ''; ?>>Active</option>
                        <option value="On Leave" <?php echo ($teacher['status'] === 'On Leave') ? 'selected' : ''; ?>>On Leave</option>
                        <option value="Resigned" <?php echo ($teacher['status'] === 'Resigned') ? 'selected' : ''; ?>>Resigned</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
