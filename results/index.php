<?php
/**
 * Exam Results & Grade Book
 * Uses centralized connection with relative include "../connection.php"
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$page_title = "Exam Results & Grades";
$header_title = "Exams & Results";
$current_page = "results";

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
        <a href="create.php" class="btn btn-primary btn-sm">+ Record New Marks</a>
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
                    <th>Score / Max</th>
                    <th>Percentage</th>
                    <th>Grade</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($marks_list)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            No marks records found. Click "+ Record New Marks" to add one.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($marks_list as $row): 
                        $pct = ($row['max_marks'] > 0) ? round(($row['marks_obtained'] / $row['max_marks']) * 100, 1) : 0;
                    ?>
                        <tr>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($row['roll_no']); ?></span></td>
                            <td style="font-weight: 600; color: #ffffff;">
                                <?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['class_name'] . ' - ' . $row['section']); ?></td>
                            <td><?php echo htmlspecialchars($row['subject_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['exam_name']); ?></td>
                            <td><strong><?php echo $row['marks_obtained']; ?></strong> / <?php echo $row['max_marks']; ?></td>
                            <td><?php echo $pct; ?>%</td>
                            <td>
                                <span class="badge badge-purple" style="font-weight: 800;"><?php echo htmlspecialchars($row['grade']); ?></span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm btn-delete-confirm" data-name="marks entry for <?php echo htmlspecialchars($row['first_name']); ?>">Delete</a>
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
