<?php
/**
 * Exam Results & Grade Book
 * Uses centralized connection with relative include "../connection.php"
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$page_title = has_role('Student') ? "My Exam Results & Report Card" : "Exam Results & Grades";
$header_title = has_role('Student') ? "My Academic Results" : "Exams & Results";
$current_page = "results";

if (has_role('Student')) {
    // ========================================================
    // STUDENT VIEW: My Report Card & Grades
    // ========================================================
    $student_id = intval($_SESSION['student_id'] ?? 0);

    // Fetch marks for this student
    $stmt = $conn->prepare("SELECT m.*, sub.subject_name, sub.subject_code, c.class_name, c.section 
                            FROM marks m 
                            JOIN subjects sub ON m.subject_id = sub.id 
                            JOIN classes c ON sub.class_id = c.id 
                            WHERE m.student_id = ? 
                            ORDER BY m.id DESC");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $student_marks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Calculate analytics
    $total_exams = count($student_marks);
    $total_obtained = 0;
    $total_max = 0;
    $highest_score = 0;

    foreach ($student_marks as $mk) {
        $total_obtained += floatval($mk['marks_obtained']);
        $total_max += floatval($mk['max_marks']);
        if (floatval($mk['marks_obtained']) > $highest_score) {
            $highest_score = floatval($mk['marks_obtained']);
        }
    }

    $avg_percentage = $total_max > 0 ? round(($total_obtained / $total_max) * 100, 1) : 0;

    include "../includes/header.php";
    ?>

    <!-- Student Grades Summary Cards -->
    <div class="grid-stats">
        <div class="stat-card emerald">
            <div class="stat-header">
                <span class="stat-label">Average Score</span>
                <div class="stat-icon">📈</div>
            </div>
            <div class="stat-value"><?php echo $avg_percentage; ?>%</div>
            <div class="stat-footer">Cumulative overall score across subjects</div>
        </div>

        <div class="stat-card indigo">
            <div class="stat-header">
                <span class="stat-label">Exams Completed</span>
                <div class="stat-icon">📝</div>
            </div>
            <div class="stat-value"><?php echo $total_exams; ?></div>
            <div class="stat-footer">Evaluated assessments</div>
        </div>

        <div class="stat-card sky">
            <div class="stat-header">
                <span class="stat-label">Top Score</span>
                <div class="stat-icon">🏆</div>
            </div>
            <div class="stat-value"><?php echo $highest_score; ?></div>
            <div class="stat-footer">Highest single mark achieved</div>
        </div>

        <div class="stat-card amber">
            <div class="stat-header">
                <span class="stat-label">Academic Status</span>
                <div class="stat-icon">🎖️</div>
            </div>
            <div class="stat-value" style="font-size: 20px;">
                <?php 
                    if ($avg_percentage >= 90) echo 'A+ Excellent';
                    elseif ($avg_percentage >= 75) echo 'A Very Good';
                    elseif ($avg_percentage >= 60) echo 'B Satisfactory';
                    else echo 'Pass Standing';
                ?>
            </div>
            <div class="stat-footer">Official Grade Standing</div>
        </div>
    </div>

    <!-- Student Report Card Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                My Official Subject Grade Report (<?php echo count($student_marks); ?> Assessments)
            </div>
            <button onclick="window.print()" class="btn btn-secondary btn-sm">🖨️ Print Report Card</button>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Subject</th>
                        <th>Exam Title</th>
                        <th>Date</th>
                        <th>Marks Obtained</th>
                        <th>Max Marks</th>
                        <th>Grade</th>
                        <th>Remarks / Evaluation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($student_marks)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                No examination records posted for your profile yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($student_marks as $mk): ?>
                            <tr>
                                <td style="font-weight: 600; color: #fff;">
                                    <?php echo htmlspecialchars($mk['subject_name']); ?>
                                    <span style="font-size: 11px; color: var(--text-muted); display: block;"><?php echo htmlspecialchars($mk['subject_code']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($mk['exam_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($mk['exam_date'])); ?></td>
                                <td><strong style="color: #38bdf8; font-size: 15px;"><?php echo htmlspecialchars($mk['marks_obtained']); ?></strong></td>
                                <td><?php echo htmlspecialchars($mk['max_marks']); ?></td>
                                <td>
                                    <?php 
                                        $g_class = 'badge-success';
                                        if ($mk['grade'] === 'F') $g_class = 'badge-danger';
                                        elseif (in_array($mk['grade'], ['C', 'D'])) $g_class = 'badge-warning';
                                    ?>
                                    <span class="badge <?php echo $g_class; ?>"><?php echo htmlspecialchars($mk['grade']); ?></span>
                                </td>
                                <td style="font-size: 13px; color: var(--text-secondary);">
                                    <?php echo !empty($mk['remarks']) ? htmlspecialchars($mk['remarks']) : '—'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php 
    include "../includes/footer.php";
    exit();
} 

// ========================================================
// ADMIN / STAFF / TEACHER: Grade Book Overview
// ========================================================
require_role(['Super Admin', 'Admin', 'Staff', 'Teacher']);

$exam_filter = isset($_GET['exam']) ? trim($_GET['exam']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build prepared query
if (!empty($exam_filter) && !empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    $stmt = $conn->prepare("SELECT m.*, s.first_name, s.last_name, s.roll_no, c.class_name, c.section, sub.subject_name 
                            FROM marks m
                            JOIN students s ON m.student_id = s.id
                            JOIN subjects sub ON m.subject_id = sub.id
                            JOIN classes c ON sub.class_id = c.id
                            WHERE m.exam_name = ? AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.roll_no LIKE ? OR sub.subject_name LIKE ?)
                            ORDER BY m.id DESC");
    $stmt->bind_param("sssss", $exam_filter, $search_param, $search_param, $search_param, $search_param);
} elseif (!empty($exam_filter)) {
    $stmt = $conn->prepare("SELECT m.*, s.first_name, s.last_name, s.roll_no, c.class_name, c.section, sub.subject_name 
                            FROM marks m
                            JOIN students s ON m.student_id = s.id
                            JOIN subjects sub ON m.subject_id = sub.id
                            JOIN classes c ON sub.class_id = c.id
                            WHERE m.exam_name = ?
                            ORDER BY m.id DESC");
    $stmt->bind_param("s", $exam_filter);
} elseif (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    $stmt = $conn->prepare("SELECT m.*, s.first_name, s.last_name, s.roll_no, c.class_name, c.section, sub.subject_name 
                            FROM marks m
                            JOIN students s ON m.student_id = s.id
                            JOIN subjects sub ON m.subject_id = sub.id
                            JOIN classes c ON sub.class_id = c.id
                            WHERE (s.first_name LIKE ? OR s.last_name LIKE ? OR s.roll_no LIKE ? OR sub.subject_name LIKE ?)
                            ORDER BY m.id DESC");
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
} else {
    $stmt = $conn->prepare("SELECT m.*, s.first_name, s.last_name, s.roll_no, c.class_name, c.section, sub.subject_name 
                            FROM marks m
                            JOIN students s ON m.student_id = s.id
                            JOIN subjects sub ON m.subject_id = sub.id
                            JOIN classes c ON sub.class_id = c.id
                            ORDER BY m.id DESC");
}

$stmt->execute();
$marks_list = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include "../includes/header.php";
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Exam marks recorded successfully!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
    <div class="alert alert-success">Exam marks updated successfully!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Exam record deleted successfully!</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Exam Marks & Grade Book (<?php echo count($marks_list); ?>)
        </div>
        <?php if (can_manage_results()): ?>
            <a href="create.php" class="btn btn-primary btn-sm">+ Record New Marks</a>
        <?php endif; ?>
    </div>

    <!-- Filter & Search Toolbar -->
    <div style="padding: 16px 24px; border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.15);">
        <form method="GET" action="index.php" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <input type="text" name="search" class="search-input" placeholder="Search by student or subject..." value="<?php echo htmlspecialchars($search_query); ?>">
            
            <select name="exam" class="form-control" style="width: auto;">
                <option value="">All Examinations</option>
                <option value="Midterm Exam" <?php echo ($exam_filter === 'Midterm Exam') ? 'selected' : ''; ?>>Midterm Exam</option>
                <option value="Final Term Exam" <?php echo ($exam_filter === 'Final Term Exam') ? 'selected' : ''; ?>>Final Term Exam</option>
                <option value="Unit Test 1" <?php echo ($exam_filter === 'Unit Test 1') ? 'selected' : ''; ?>>Unit Test 1</option>
                <option value="Unit Test 2" <?php echo ($exam_filter === 'Unit Test 2') ? 'selected' : ''; ?>>Unit Test 2</option>
            </select>

            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <?php if (!empty($search_query) || !empty($exam_filter)): ?>
                <a href="index.php" class="btn btn-secondary btn-sm" style="color: var(--danger);">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Roll No</th>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Subject</th>
                    <th>Exam</th>
                    <th>Marks</th>
                    <th>Grade</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($marks_list)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            No mark records found matching criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($marks_list as $mk): ?>
                        <tr>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($mk['roll_no']); ?></span></td>
                            <td style="font-weight: 600; color: #ffffff;">
                                <?php echo htmlspecialchars($mk['first_name'] . ' ' . $mk['last_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($mk['class_name'] . ' ' . $mk['section']); ?></td>
                            <td><span class="badge badge-purple"><?php echo htmlspecialchars($mk['subject_name']); ?></span></td>
                            <td><?php echo htmlspecialchars($mk['exam_name']); ?></td>
                            <td style="font-weight: 700; color: #38bdf8;">
                                <?php echo htmlspecialchars($mk['marks_obtained']) . ' / ' . htmlspecialchars($mk['max_marks']); ?>
                            </td>
                            <td>
                                <span class="badge <?php echo ($mk['grade'] === 'F') ? 'badge-danger' : 'badge-success'; ?>">
                                    <?php echo htmlspecialchars($mk['grade']); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <?php if (can_manage_results()): ?>
                                        <a href="edit.php?id=<?php echo $mk['id']; ?>" class="btn btn-secondary btn-sm" title="Edit Marks">Edit</a>
                                    <?php endif; ?>
                                    <?php if (can_delete()): ?>
                                        <a href="delete.php?id=<?php echo $mk['id']; ?>" class="btn btn-danger btn-sm btn-delete-confirm" data-name="Mark record #<?php echo $mk['id']; ?>" title="Delete Record">Delete</a>
                                    <?php endif; ?>
                                    <?php if (!can_manage_results() && !can_delete()): ?>
                                        <span style="color: var(--text-muted); font-size: 12px;">View Only</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
