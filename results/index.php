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

// Auto-align any legacy marks with the updated grading scale:
// 80-100: A (Very Good), 65-79: B (Good), 50-64: C (Satisfactory), 35-49: D (Average), Below 35: E (Not Satisfactory)
@mysqli_query($conn, "UPDATE marks SET grade = CASE 
    WHEN (marks_obtained / max_marks) * 100 >= 80 THEN 'A'
    WHEN (marks_obtained / max_marks) * 100 >= 65 THEN 'B'
    WHEN (marks_obtained / max_marks) * 100 >= 50 THEN 'C'
    WHEN (marks_obtained / max_marks) * 100 >= 35 THEN 'D'
    ELSE 'E'
END WHERE max_marks > 0 AND grade IN ('A+', 'B+', 'F')");

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
                    if ($avg_percentage >= 80) echo 'Grade A · Very Good';
                    elseif ($avg_percentage >= 65) echo 'Grade B · Good';
                    elseif ($avg_percentage >= 50) echo 'Grade C · Satisfactory';
                    elseif ($avg_percentage >= 35) echo 'Grade D · Average';
                    else echo 'Grade E · Not Satisfactory';
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
            <div style="display: flex; gap: 8px;">
                <a href="print.php?student_id=<?php echo $student_id; ?>" target="_blank" class="btn btn-secondary btn-sm">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="15" height="15" style="vertical-align: -2px; margin-right: 4px;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                    </svg>
                    Print Official Marksheet
                </a>
            </div>
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
                                <td style="font-weight: 600; color: var(--text-primary);">
                                    <?php echo htmlspecialchars($mk['subject_name']); ?>
                                    <span style="font-size: 11px; color: var(--text-muted); display: block;"><?php echo htmlspecialchars($mk['subject_code']); ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($mk['exam_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($mk['exam_date'])); ?></td>
                                <td><strong class="marks-val" style="font-size: 15px;"><?php echo htmlspecialchars($mk['marks_obtained']); ?></strong></td>
                                <td><?php echo htmlspecialchars($mk['max_marks']); ?></td>
                                <td>
                                    <?php 
                                        $g_class = 'badge-success';
                                        if (in_array($mk['grade'], ['E', 'F'])) $g_class = 'badge-danger';
                                        elseif ($mk['grade'] === 'D') $g_class = 'badge-warning';
                                        elseif ($mk['grade'] === 'C') $g_class = 'badge-info';
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

$class_filter = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$exam_filter = isset($_GET['exam']) ? trim($_GET['exam']) : '';
$grade_filter = isset($_GET['grade']) ? trim($_GET['grade']) : '';
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch all classes for the filter dropdown
$classes_res = mysqli_query($conn, "SELECT id, class_name, section FROM classes ORDER BY class_name, section ASC");
$all_classes = [];
if ($classes_res) {
    while ($cl = mysqli_fetch_assoc($classes_res)) {
        $all_classes[] = $cl;
    }
}

// Fetch all distinct exam names for the filter dropdown
$exams_res = mysqli_query($conn, "SELECT DISTINCT exam_name FROM marks ORDER BY exam_name ASC");
$all_exams = [];
if ($exams_res) {
    while ($ex = mysqli_fetch_assoc($exams_res)) {
        if (!empty($ex['exam_name'])) {
            $all_exams[] = $ex['exam_name'];
        }
    }
}
$default_exams = ['Midterm Exam', 'Final Term Exam', 'Unit Test 1', 'Unit Test 2'];
$exam_options = array_values(array_unique(array_merge($default_exams, $all_exams)));

// Build prepared query with dynamic filters
$where_clauses = [];
$params = [];
$types = '';

if ($class_filter > 0) {
    $where_clauses[] = "(c.id = ? OR s.class_id = ?)";
    $params[] = $class_filter;
    $params[] = $class_filter;
    $types .= 'ii';
}

if (!empty($exam_filter)) {
    $where_clauses[] = "m.exam_name = ?";
    $params[] = $exam_filter;
    $types .= 's';
}

if (!empty($grade_filter)) {
    $where_clauses[] = "m.grade = ?";
    $params[] = $grade_filter;
    $types .= 's';
}

if (!empty($search_query)) {
    $where_clauses[] = "(s.first_name LIKE ? OR s.last_name LIKE ? OR s.roll_no LIKE ? OR sub.subject_name LIKE ?)";
    $search_param = '%' . $search_query . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ssss';
}

$sql = "SELECT m.*, s.first_name, s.last_name, s.roll_no, c.class_name, c.section, sub.subject_name 
        FROM marks m
        JOIN students s ON m.student_id = s.id
        JOIN subjects sub ON m.subject_id = sub.id
        JOIN classes c ON s.class_id = c.id";

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY m.id DESC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
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
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <a href="final_print.php<?php echo ($class_filter > 0 ? '?class_id=' . $class_filter . (!empty($exam_filter) ? '&exam=' . urlencode($exam_filter) : '') : (!empty($exam_filter) ? '?exam=' . urlencode($exam_filter) : '')); ?>" target="_blank" class="btn btn-secondary btn-sm" title="Print Class-wise Merit Ranking & Tabulation Sheet" style="color: #38bdf8; border-color: rgba(56, 189, 248, 0.3);">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="15" height="15" style="vertical-align: -2px; margin-right: 4px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Final Print (Class Merit List)
            </a>
            <a href="print.php" class="btn btn-secondary btn-sm" title="Generate and Print Student Examination Marksheet">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="15" height="15" style="vertical-align: -2px; margin-right: 4px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print Student Result
            </a>
            <?php if (can_manage_results()): ?>
                <a href="create.php" class="btn btn-primary btn-sm">+ Record New Marks</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div class="table-filter-bar">
        <form method="GET" action="index.php" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <input type="text" name="search" class="search-input" placeholder="Search student, roll no, subject..." value="<?php echo htmlspecialchars($search_query); ?>" style="min-width: 200px;">
            
            <!-- Class / Grade Filter -->
            <select name="class_id" class="form-control" style="width: auto;">
                <option value="">All Classes / Grades</option>
                <?php foreach ($all_classes as $cl): ?>
                    <option value="<?php echo $cl['id']; ?>" <?php echo ($class_filter == $cl['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cl['class_name'] . ' (' . $cl['section'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Examination Filter -->
            <select name="exam" class="form-control" style="width: auto;">
                <option value="">All Examinations</option>
                <?php foreach ($exam_options as $opt): ?>
                    <option value="<?php echo htmlspecialchars($opt); ?>" <?php echo ($exam_filter === $opt) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($opt); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Letter Grade Filter -->
            <select name="grade" class="form-control" style="width: auto;">
                <option value="">All Letter Grades</option>
                <option value="A" <?php echo ($grade_filter === 'A') ? 'selected' : ''; ?>>Grade A (80-100 · Very Good)</option>
                <option value="B" <?php echo ($grade_filter === 'B') ? 'selected' : ''; ?>>Grade B (65-79 · Good)</option>
                <option value="C" <?php echo ($grade_filter === 'C') ? 'selected' : ''; ?>>Grade C (50-64 · Satisfactory)</option>
                <option value="D" <?php echo ($grade_filter === 'D') ? 'selected' : ''; ?>>Grade D (35-49 · Average)</option>
                <option value="E" <?php echo ($grade_filter === 'E') ? 'selected' : ''; ?>>Grade E (Below 35 · Not Satisfactory)</option>
            </select>

            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <?php if (!empty($search_query) || !empty($exam_filter) || $class_filter > 0 || !empty($grade_filter)): ?>
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
                            <td style="font-weight: 600; color: var(--text-primary);">
                                <?php echo htmlspecialchars($mk['first_name'] . ' ' . $mk['last_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($mk['class_name'] . ' ' . $mk['section']); ?></td>
                            <td><span class="badge badge-purple"><?php echo htmlspecialchars($mk['subject_name']); ?></span></td>
                            <td><?php echo htmlspecialchars($mk['exam_name']); ?></td>
                            <td class="marks-val">
                                <?php echo htmlspecialchars($mk['marks_obtained']) . ' / ' . htmlspecialchars($mk['max_marks']); ?>
                            </td>
                            <td>
                                <?php
                                    $badge_cls = 'badge-success';
                                    if (in_array($mk['grade'], ['E', 'F'])) $badge_cls = 'badge-danger';
                                    elseif ($mk['grade'] === 'D') $badge_cls = 'badge-warning';
                                    elseif ($mk['grade'] === 'C') $badge_cls = 'badge-info';
                                ?>
                                <span class="badge <?php echo $badge_cls; ?>">
                                    <?php echo htmlspecialchars($mk['grade']); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="print.php?student_id=<?php echo $mk['student_id']; ?>&exam=<?php echo urlencode($mk['exam_name']); ?>" target="_blank" class="btn btn-secondary btn-sm" title="Print Result Marksheet" style="color: #38bdf8;">
                                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="13" height="13" style="vertical-align: -2px; margin-right: 2px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                        Print
                                    </a>
                                    <?php if (can_manage_results()): ?>
                                        <a href="edit.php?id=<?php echo $mk['id']; ?>" class="btn btn-secondary btn-sm" title="Edit Marks">Edit</a>
                                    <?php endif; ?>
                                    <?php if (can_delete()): ?>
                                        <a href="delete.php?id=<?php echo $mk['id']; ?>" class="btn btn-danger btn-sm btn-delete-confirm" data-name="Marks for <?php echo htmlspecialchars($mk['first_name'] . ' ' . $mk['last_name'] . ' (' . $mk['subject_name'] . ')'); ?>" title="Delete Record">Delete</a>
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
