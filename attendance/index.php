<?php
/**
 * Daily Attendance Register
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$page_title = "Attendance Register";
$header_title = "Daily Attendance";
$current_page = "attendance";

// Fetch classes
$classes_stmt = $conn->prepare("SELECT id, class_name, section FROM classes ORDER BY class_name ASC");
$classes_stmt->execute();
$classes = $classes_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$classes_stmt->close();

$selected_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : ($classes[0]['id'] ?? 0);
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$msg = '';

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    $att_data = $_POST['attendance'] ?? [];
    $remarks_data = $_POST['remarks'] ?? [];
    $submitted_class_id = intval($_POST['class_id'] ?? 0);
    $submitted_date = $_POST['date'] ?? date('Y-m-d');

    if ($submitted_class_id > 0 && !empty($att_data)) {
        // Prepared statement with ON DUPLICATE KEY UPDATE
        $upsert_stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, attendance_date, status, remarks) 
                                       VALUES (?, ?, ?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks)");
        
        foreach ($att_data as $student_id => $status) {
            $student_id = intval($student_id);
            $remark = trim($remarks_data[$student_id] ?? '');
            
            $upsert_stmt->bind_param("iisss", $student_id, $submitted_class_id, $submitted_date, $status, $remark);
            $upsert_stmt->execute();
        }
        $upsert_stmt->close();
        $msg = 'Attendance recorded successfully!';
    }
}

// Fetch students for the selected class along with their existing attendance for this date
$students_query = "SELECT s.id, s.roll_no, s.first_name, s.last_name, s.gender,
                   a.status AS current_status, a.remarks AS current_remarks
                   FROM students s
                   LEFT JOIN attendance a ON s.id = a.student_id AND a.attendance_date = ?
                   WHERE s.class_id = ? AND s.status = 'Active'
                   ORDER BY s.roll_no ASC";
$s_stmt = $conn->prepare($students_query);
$s_stmt->bind_param("si", $selected_date, $selected_class_id);
$s_stmt->execute();
$students = $s_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$s_stmt->close();

include "../includes/header.php";
?>

<?php if (!empty($msg)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            Attendance Register
        </div>
        <a href="report.php" class="btn btn-secondary btn-sm">View Attendance Report &rarr;</a>
    </div>

    <!-- Class & Date Selector Form -->
    <div style="padding: 16px 24px; border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.15);">
        <form method="GET" action="index.php" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <label class="form-label" style="margin: 0;">Class:</label>
                <select name="class_id" class="form-control" style="width: auto;" onchange="this.form.submit()">
                    <?php foreach ($classes as $c): ?>
                        <option value="<?php echo $c['id']; ?>" <?php echo ($selected_class_id == $c['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($c['class_name'] . ' - Section ' . $c['section']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; align-items: center; gap: 8px;">
                <label class="form-label" style="margin: 0;">Date:</label>
                <input type="date" name="date" class="form-control" style="width: auto;" value="<?php echo htmlspecialchars($selected_date); ?>" onchange="this.form.submit()">
            </div>

            <button type="submit" class="btn btn-secondary btn-sm">Load Register</button>
        </form>
    </div>

    <?php if (empty($students)): ?>
        <div style="padding: 40px; text-align: center; color: var(--text-muted);">
            No active students enrolled in this class.
        </div>
    <?php else: ?>
        <form action="index.php?class_id=<?php echo $selected_class_id; ?>&date=<?php echo urlencode($selected_date); ?>" method="POST">
            <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">
            <input type="hidden" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">

            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Roll No</th>
                            <th>Student Name</th>
                            <th>Gender</th>
                            <th>Status Marking</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $stu): 
                            $curr = $stu['current_status'] ?? 'Present';
                        ?>
                            <tr>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($stu['roll_no']); ?></span></td>
                                <td style="font-weight: 600; color: #ffffff;">
                                    <?php echo htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($stu['gender']); ?></td>
                                <td>
                                    <div style="display: flex; gap: 14px; align-items: center;">
                                        <label style="display: flex; align-items: center; gap: 4px; cursor: pointer; color: #34d399; font-size: 13px;">
                                            <input type="radio" name="attendance[<?php echo $stu['id']; ?>]" value="Present" <?php echo ($curr === 'Present') ? 'checked' : ''; ?>>
                                            Present
                                        </label>

                                        <label style="display: flex; align-items: center; gap: 4px; cursor: pointer; color: #f87171; font-size: 13px;">
                                            <input type="radio" name="attendance[<?php echo $stu['id']; ?>]" value="Absent" <?php echo ($curr === 'Absent') ? 'checked' : ''; ?>>
                                            Absent
                                        </label>

                                        <label style="display: flex; align-items: center; gap: 4px; cursor: pointer; color: #fbbf24; font-size: 13px;">
                                            <input type="radio" name="attendance[<?php echo $stu['id']; ?>]" value="Late" <?php echo ($curr === 'Late') ? 'checked' : ''; ?>>
                                            Late
                                        </label>

                                        <label style="display: flex; align-items: center; gap: 4px; cursor: pointer; color: #38bdf8; font-size: 13px;">
                                            <input type="radio" name="attendance[<?php echo $stu['id']; ?>]" value="Excused" <?php echo ($curr === 'Excused') ? 'checked' : ''; ?>>
                                            Excused
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="remarks[<?php echo $stu['id']; ?>]" class="form-control" style="padding: 6px 10px; font-size: 13px;" placeholder="Optional notes" value="<?php echo htmlspecialchars($stu['current_remarks'] ?? ''); ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="padding: 20px 24px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end; gap: 12px; background: rgba(0,0,0,0.1);">
                <button type="submit" name="save_attendance" class="btn btn-primary">
                    Save Attendance Register
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<?php include "../includes/footer.php"; ?>
