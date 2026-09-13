<?php
/**
 * Edit Student Exam Marks
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$page_title = "Edit Exam Marks";
$header_title = "Update Marks";
$current_page = "results";

$mark_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($mark_id <= 0) {
    header("Location: index.php");
    exit();
}

$error = '';

// Fetch mark record with student and subject info
$stmt = $conn->prepare("SELECT m.*, s.first_name, s.last_name, s.roll_no, sub.subject_name 
                        FROM marks m 
                        JOIN students s ON m.student_id = s.id 
                        JOIN subjects sub ON m.subject_id = sub.id 
                        WHERE m.id = ? LIMIT 1");
$stmt->bind_param("i", $mark_id);
$stmt->execute();
$mark = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$mark) {
    header("Location: index.php");
    exit();
}

function compute_grade($obtained, $max) {
    if ($max <= 0) return 'N/A';
    $pct = ($obtained / $max) * 100;
    if ($pct >= 90) return 'A+';
    if ($pct >= 80) return 'A';
    if ($pct >= 70) return 'B+';
    if ($pct >= 60) return 'B';
    if ($pct >= 50) return 'C';
    if ($pct >= 40) return 'D';
    return 'F';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marks_obtained = floatval($_POST['marks_obtained'] ?? 0);
    $max_marks = floatval($_POST['max_marks'] ?? 100);
    $exam_name = trim($_POST['exam_name'] ?? '');
    $remarks = trim($_POST['remarks'] ?? '');
    $exam_date = $_POST['exam_date'] ?? date('Y-m-d');

    if (empty($exam_name) || $max_marks <= 0) {
        $error = 'Please provide a valid exam name and maximum marks.';
    } else {
        $grade = compute_grade($marks_obtained, $max_marks);

        $update_stmt = $conn->prepare("UPDATE marks SET marks_obtained = ?, max_marks = ?, grade = ?, exam_name = ?, remarks = ?, exam_date = ? WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("ddssssi", $marks_obtained, $max_marks, $grade, $exam_name, $remarks, $exam_date, $mark_id);
            if ($update_stmt->execute()) {
                $update_stmt->close();
                header("Location: index.php?msg=updated");
                exit();
            } else {
                $error = 'Update failed: ' . $update_stmt->error;
                $update_stmt->close();
            }
        } else {
            $error = 'Query preparation failed: ' . $conn->error;
        }
    }
}

include "../includes/header.php";
?>

<div class="card" style="max-width: 750px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit Marks: <?php echo htmlspecialchars($mark['first_name'] . ' ' . $mark['last_name'] . ' (' . $mark['subject_name'] . ')'); ?>
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Results</a>
    </div>

    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="edit.php?id=<?php echo $mark_id; ?>" method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Student</label>
                    <input type="text" class="form-control" disabled value="<?php echo htmlspecialchars($mark['roll_no'] . ' - ' . $mark['first_name'] . ' ' . $mark['last_name']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Subject</label>
                    <input type="text" class="form-control" disabled value="<?php echo htmlspecialchars($mark['subject_name']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Examination Name</label>
                    <input type="text" name="exam_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['exam_name'] ?? $mark['exam_name']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Marks Obtained</label>
                    <input type="number" step="0.5" name="marks_obtained" class="form-control" required value="<?php echo htmlspecialchars($_POST['marks_obtained'] ?? $mark['marks_obtained']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Maximum Total Marks</label>
                    <input type="number" step="1" name="max_marks" class="form-control" required value="<?php echo htmlspecialchars($_POST['max_marks'] ?? $mark['max_marks']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Exam Date</label>
                    <input type="date" name="exam_date" class="form-control" value="<?php echo htmlspecialchars($_POST['exam_date'] ?? $mark['exam_date']); ?>">
                </div>

                <div class="form-group col-span-2">
                    <label class="form-label">Teacher Remarks</label>
                    <input type="text" name="remarks" class="form-control" value="<?php echo htmlspecialchars($_POST['remarks'] ?? $mark['remarks']); ?>">
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
