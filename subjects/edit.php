<?php
/**
 * Edit / Update Subject
 * Restricted to Super Admin and Admin only.
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin']);

$page_title   = "Edit Subject";
$header_title = "Update Subject";
$current_page = "subjects";

$subject_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($subject_id <= 0) {
    header("Location: index.php");
    exit();
}

$error = '';

// Fetch subject record
$stmt = $conn->prepare("SELECT * FROM subjects WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $subject_id);
$stmt->execute();
$subject = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$subject) {
    header("Location: index.php");
    exit();
}

// Fetch all classes
$c_stmt = $conn->prepare("SELECT id, class_name, section FROM classes ORDER BY class_name ASC, section ASC");
$c_stmt->execute();
$classes = $c_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$c_stmt->close();

// Fetch active teachers
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
        // Duplicate code check (exclude current record)
        $dup = $conn->prepare("SELECT id FROM subjects WHERE subject_code = ? AND class_id = ? AND id != ? LIMIT 1");
        $dup->bind_param("sii", $subject_code, $class_id, $subject_id);
        $dup->execute();
        $dup_row = $dup->get_result()->fetch_assoc();
        $dup->close();

        if ($dup_row) {
            $error = 'A subject with this code already exists for the selected class.';
        } else {
            $upd = $conn->prepare("UPDATE subjects SET subject_name = ?, subject_code = ?, class_id = ?, teacher_id = ? WHERE id = ?");
            if ($upd) {
                $upd->bind_param("ssiii", $subject_name, $subject_code, $class_id, $teacher_id, $subject_id);
                if ($upd->execute()) {
                    $upd->close();
                    header("Location: index.php?msg=updated");
                    exit();
                } else {
                    $error = 'Update failed: ' . $upd->error;
                    $upd->close();
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Edit Subject: <span style="color:#a78bfa;"><?php echo htmlspecialchars($subject['subject_name']); ?></span>
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Subjects</a>
    </div>

    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="edit.php?id=<?php echo $subject_id; ?>" method="POST" id="form-edit-subject">
            <div class="form-grid">

                <div class="form-group">
                    <label class="form-label" for="subject_name">
                        Subject Name <span class="required">*</span>
                    </label>
                    <input type="text"
                           id="subject_name"
                           name="subject_name"
                           class="form-control"
                           required
                           placeholder="e.g. Mathematics"
                           value="<?php echo htmlspecialchars($_POST['subject_name'] ?? $subject['subject_name']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="subject_code">
                        Subject Code <span class="required">*</span>
                    </label>
                    <input type="text"
                           id="subject_code"
                           name="subject_code"
                           class="form-control"
                           required
                           style="text-transform:uppercase;"
                           placeholder="e.g. MATH-10"
                           value="<?php echo htmlspecialchars($_POST['subject_code'] ?? $subject['subject_code']); ?>">
                </div>

                <div class="form-group col-span-2">
                    <label class="form-label" for="class_id">
                        Assign to Class <span class="required">*</span>
                    </label>
                    <select id="class_id" name="class_id" class="form-control" required>
                        <option value="">-- Select Class / Grade --</option>
                        <?php
                        $sel_class = $_POST['class_id'] ?? $subject['class_id'];
                        foreach ($classes as $cls): ?>
                            <option value="<?php echo $cls['id']; ?>"
                                <?php echo ($sel_class == $cls['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cls['class_name'] . ' - Section ' . $cls['section']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group col-span-2">
                    <label class="form-label" for="teacher_id">Assign Subject Teacher</label>
                    <select id="teacher_id" name="teacher_id" class="form-control">
                        <option value="">-- No Teacher Assigned --</option>
                        <?php
                        $sel_teacher = $_POST['teacher_id'] ?? $subject['teacher_id'];
                        foreach ($teachers as $t): ?>
                            <option value="<?php echo $t['id']; ?>"
                                <?php echo ($sel_teacher == $t['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

            </div>

            <div style="display:flex; gap:12px; justify-content:flex-end; margin-top:24px;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary" id="btn-update-subject">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16" style="margin-right:6px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.getElementById('subject_code').addEventListener('input', function () {
    this.value = this.value.toUpperCase();
});
</script>

<?php include "../includes/footer.php"; ?>
