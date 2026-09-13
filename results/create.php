<?php
/**
 * Record New Exam Marks
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin', 'Teacher']);

$page_title = "Record Exam Marks";
$header_title = "Record Marks";
$current_page = "results";

$error = '';

// Fetch active students
$s_stmt = $conn->prepare("SELECT s.id, s.roll_no, s.first_name, s.last_name, c.class_name, c.section 
                          FROM students s 
                          JOIN classes c ON s.class_id = c.id 
                          WHERE s.status = 'Active' 
                          ORDER BY c.class_name, s.roll_no ASC");
$s_stmt->execute();
$students = $s_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$s_stmt->close();

// Fetch subjects
$sub_stmt = $conn->prepare("SELECT id, subject_name, subject_code FROM subjects ORDER BY subject_name ASC");
$sub_stmt->execute();
$subjects = $sub_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$sub_stmt->close();

// Helper function to calculate letter grade
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
    $student_id = intval($_POST['student_id'] ?? 0);
    $subject_id = intval($_POST['subject_id'] ?? 0);
    $exam_name = trim($_POST['exam_name'] ?? '');
    $marks_obtained = floatval($_POST['marks_obtained'] ?? 0);
    $max_marks = floatval($_POST['max_marks'] ?? 100);
    $remarks = trim($_POST['remarks'] ?? '');
    $exam_date = $_POST['exam_date'] ?? date('Y-m-d');

    if ($student_id <= 0 || $subject_id <= 0 || empty($exam_name) || $max_marks <= 0) {
        $error = 'Please select student, subject, exam name, and valid maximum marks.';
    } else {
        $grade = compute_grade($marks_obtained, $max_marks);

        $stmt = $conn->prepare("INSERT INTO marks (student_id, subject_id, exam_name, marks_obtained, max_marks, grade, remarks, exam_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("iisddsss", $student_id, $subject_id, $exam_name, $marks_obtained, $max_marks, $grade, $remarks, $exam_date);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: index.php?msg=created");
                exit();
            } else {
                $error = 'Failed to save marks: ' . $stmt->error;
                $stmt->close();
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            Record Student Exam Marks
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Results</a>
    </div>

    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="create.php" method="POST">
            <div class="form-grid">
                <div class="form-group col-span-2">
                    <label class="form-label">Select Student <span class="required">*</span></label>
                    <select name="student_id" class="form-control" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo (isset($_POST['student_id']) && $_POST['student_id'] == $s['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['roll_no'] . ' - ' . $s['first_name'] . ' ' . $s['last_name'] . ' (' . $s['class_name'] . ' - ' . $s['section'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Subject <span class="required">*</span></label>
                    <select name="subject_id" class="form-control" required>
                        <option value="">-- Choose Subject --</option>
                        <?php foreach ($subjects as $sub): ?>
                            <option value="<?php echo $sub['id']; ?>" <?php echo (isset($_POST['subject_id']) && $_POST['subject_id'] == $sub['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($sub['subject_name'] . ' (' . $sub['subject_code'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Examination Name <span class="required">*</span></label>
                    <select name="exam_name" class="form-control" required>
                        <option value="Midterm Exam">Midterm Exam</option>
                        <option value="Final Term Exam">Final Term Exam</option>
                        <option value="Unit Test 1">Unit Test 1</option>
                        <option value="Unit Test 2">Unit Test 2</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Marks Obtained <span class="required">*</span></label>
                    <input type="number" step="0.5" name="marks_obtained" class="form-control" placeholder="e.g. 88.5" required value="<?php echo htmlspecialchars($_POST['marks_obtained'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Maximum Total Marks</label>
                    <input type="number" step="1" name="max_marks" class="form-control" value="<?php echo htmlspecialchars($_POST['max_marks'] ?? '100'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Exam Date</label>
                    <input type="date" name="exam_date" class="form-control" value="<?php echo htmlspecialchars($_POST['exam_date'] ?? date('Y-m-d')); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Teacher Remarks</label>
                    <input type="text" name="remarks" class="form-control" placeholder="e.g. Excellent presentation" value="<?php echo htmlspecialchars($_POST['remarks'] ?? ''); ?>">
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Exam Marks</button>
            </div>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
