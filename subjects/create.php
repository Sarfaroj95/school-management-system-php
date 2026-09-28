<?php
/**
 * Add New Subject
 * Restricted to Super Admin and Admin only.
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin']);

$page_title   = "Add New Subject";
$header_title = "Create Subject";
$current_page = "subjects";

$error = '';

// Fetch all classes for dropdown
$c_stmt = $conn->prepare("SELECT id, class_name, section FROM classes ORDER BY class_name ASC, section ASC");
$c_stmt->execute();
$classes = $c_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$c_stmt->close();

// Fetch active teachers for dropdown
$t_stmt = $conn->prepare("SELECT id, name FROM teachers WHERE status = 'Active' ORDER BY name ASC");
$t_stmt->execute();
$teachers = $t_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$t_stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_name = trim($_POST['subject_name'] ?? '');
    $subject_code = strtoupper(trim($_POST['subject_code'] ?? ''));
    $class_id     = !empty($_POST['class_id'])   ? intval($_POST['class_id'])   : null;
    $teacher_id   = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;

    if (empty($subject_name) || empty($subject_code)) {
        $error = 'Subject name and subject code are required.';
    } elseif ($class_id === null) {
        $error = 'Please assign this subject to a class.';
    } else {
        // Check for duplicate code in same class
        $dup = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ? AND class_id = ? LIMIT 1");
        $dup->bind_param("si", $subject_code, $class_id);
        $dup->execute();
        $dup_row = $dup->get_result()->fetch_assoc();
        $dup->close();

        if ($dup_row) {
            $error = 'A subject with this code already exists for the selected class.';
        } else {
            $stmt = $conn->prepare("INSERT INTO subjects (subject_name, subject_code, class_id, teacher_id) VALUES (?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssii", $subject_name, $subject_code, $class_id, $teacher_id);
                if ($stmt->execute()) {
                    $stmt->close();
                    header("Location: index.php?msg=created");
                    exit();
                } else {
                    $error = 'Failed to create subject: ' . $stmt->error;
                    $stmt->close();
                }
            } else {
                $error = 'Query error: ' . $conn->error;
            }
        }
    }
}

include "../includes/header.php";
?>

<div class="card" style="max-width:680px; margin:0 auto;">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color:#6366f1;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            Add New Subject
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Subjects</a>
    </div>

    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="create.php" method="POST" id="form-create-subject">
            <div class="form-grid">

                <div class="form-group">
                    <label class="form-label" for="subject_name">
                        Subject Name <span class="required">*</span>
                    </label>
                    <input type="text"
                           id="subject_name"
                           name="subject_name"
                           class="form-control"
                           placeholder="e.g. Mathematics, Physics, English"
                           required
                           value="<?php echo htmlspecialchars($_POST['subject_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="subject_code">
                        Subject Code <span class="required">*</span>
                    </label>
                    <input type="text"
                           id="subject_code"
                           name="subject_code"
                           class="form-control"
                           placeholder="e.g. MATH-10, PHY-09"
                           required
                           style="text-transform:uppercase;"
                           value="<?php echo htmlspecialchars($_POST['subject_code'] ?? ''); ?>">
                    <small style="color:var(--text-muted); font-size:12px; margin-top:4px; display:block;">Must be unique within the selected class.</small>
                </div>

                <div class="form-group col-span-2">
                    <label class="form-label" for="class_id">
                        Assign to Class <span class="required">*</span>
                    </label>
                    <select id="class_id" name="class_id" class="form-control" required>
                        <option value="">-- Select Class / Grade --</option>
                        <?php foreach ($classes as $cls): ?>
                            <option value="<?php echo $cls['id']; ?>"
                                <?php echo (isset($_POST['class_id']) && $_POST['class_id'] == $cls['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cls['class_name'] . ' - Section ' . $cls['section']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group col-span-2">
                    <label class="form-label" for="teacher_id">Assign Subject Teacher</label>
                    <select id="teacher_id" name="teacher_id" class="form-control">
                        <option value="">-- Select Subject Teacher (Optional) --</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?php echo $t['id']; ?>"
                                <?php echo (isset($_POST['teacher_id']) && $_POST['teacher_id'] == $t['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <!-- Info note -->
            <div style="background:rgba(99,102,241,0.08); border:1px solid rgba(99,102,241,0.2); border-radius:10px; padding:14px 16px; margin-top:8px; margin-bottom:4px;">
                <p style="font-size:13px; color:var(--text-secondary); margin:0; display:flex; gap:8px; align-items:flex-start;">
                    <span style="font-size:16px; line-height:1;">ℹ️</span>
                    <span>Each subject is associated with one class. You can assign a teacher now or leave it unassigned and update later. Subject codes must be unique per class.</span>
                </p>
            </div>

            <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:24px;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" id="btn-save-subject">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16" style="margin-right:6px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Create Subject
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Auto-uppercase the subject code as user types
document.getElementById('subject_code').addEventListener('input', function () {
    this.value = this.value.toUpperCase();
});
</script>

<?php include "../includes/footer.php"; ?>
