<?php
/**
 * Add New Teacher Form
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin']);

$page_title = "Add New Teacher";
$header_title = "Faculty Registration";
$current_page = "teachers";

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_id = trim($_POST['emp_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $raw_pass = trim($_POST['password'] ?? 'teacher123');
    if (empty($raw_pass)) $raw_pass = 'teacher123';
    $hashed_pass = password_hash($raw_pass, PASSWORD_DEFAULT);
    $phone = trim($_POST['phone'] ?? '');
    $qualification = trim($_POST['qualification'] ?? '');
    $subject_specialization = trim($_POST['subject_specialization'] ?? '');
    $joining_date = $_POST['joining_date'] ?? date('Y-m-d');
    $salary = floatval($_POST['salary'] ?? 0.00);
    $status = $_POST['status'] ?? 'Active';

    if (empty($emp_id) || empty($name) || empty($email) || empty($phone) || empty($qualification) || empty($subject_specialization)) {
        $error = 'Please fill in all mandatory fields marked with an asterisk (*).';
    } else {
        $stmt = $conn->prepare("INSERT INTO teachers (emp_id, name, email, password, phone, qualification, subject_specialization, joining_date, salary, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("ssssssssds", $emp_id, $name, $email, $hashed_pass, $phone, $qualification, $subject_specialization, $joining_date, $salary, $status);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: index.php?msg=created");
                exit();
            } else {
                $error = 'Failed to register teacher: ' . $stmt->error;
                $stmt->close();
            }
        } else {
            $error = 'Database query preparation failed: ' . $conn->error;
        }
    }
}

$auto_emp = 'EMP' . rand(110, 999);

include "../includes/header.php";
?>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
            </svg>
            New Teacher / Faculty Registration
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Faculty</a>
    </div>

    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="create.php" method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Employee ID <span class="required">*</span></label>
                    <input type="text" name="emp_id" class="form-control" required value="<?php echo htmlspecialchars($_POST['emp_id'] ?? $auto_emp); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Full Name <span class="required">*</span></label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Dr. Jane Smith" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address <span class="required">*</span></label>
                    <input type="email" name="email" class="form-control" placeholder="e.g. j.smith@schoolsms.edu" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Phone Number <span class="required">*</span></label>
                    <input type="text" name="phone" class="form-control" placeholder="e.g. +1 (555) 0123" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Qualification <span class="required">*</span></label>
                    <input type="text" name="qualification" class="form-control" placeholder="e.g. M.Sc, Ph.D, B.Ed" required value="<?php echo htmlspecialchars($_POST['qualification'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Subject Specialization <span class="required">*</span></label>
                    <input type="text" name="subject_specialization" class="form-control" placeholder="e.g. Mathematics, Physics" required value="<?php echo htmlspecialchars($_POST['subject_specialization'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Joining Date</label>
                    <input type="date" name="joining_date" class="form-control" value="<?php echo htmlspecialchars($_POST['joining_date'] ?? date('Y-m-d')); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Monthly Salary ($)</label>
                    <input type="number" step="0.01" name="salary" class="form-control" placeholder="4500.00" value="<?php echo htmlspecialchars($_POST['salary'] ?? '4200.00'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Employment Status</label>
                    <select name="status" class="form-control">
                        <option value="Active">Active</option>
                        <option value="On Leave">On Leave</option>
                        <option value="Resigned">Resigned</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Register Faculty Member</button>
            </div>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
