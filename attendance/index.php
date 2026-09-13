<?php
/**
 * Daily Attendance Register & Student Personal Attendance View
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$page_title = has_role('Student') ? "My Attendance Records" : "Attendance Register";
$header_title = has_role('Student') ? "My Attendance" : "Daily Attendance";
$current_page = "attendance";

$msg = '';

if (has_role('Student')) {
    // ========================================================
    // STUDENT VIEW: My Attendance History & Statistics
    // ========================================================
    $student_id = intval($_SESSION['student_id'] ?? 0);

    // Fetch student overall statistics
    $present_count = 0;
    $absent_count = 0;
    $late_count = 0;
    $excused_count = 0;
    $total_sessions = 0;

    $stat_stmt = $conn->prepare("SELECT status, COUNT(*) as cnt FROM attendance WHERE student_id = ? GROUP BY status");
    if ($stat_stmt) {
        $stat_stmt->bind_param("i", $student_id);
        $stat_stmt->execute();
        $stat_res = $stat_stmt->get_result();
        while ($row = $stat_res->fetch_assoc()) {
            $total_sessions += $row['cnt'];
            if ($row['status'] === 'Present') $present_count = $row['cnt'];
            elseif ($row['status'] === 'Absent') $absent_count = $row['cnt'];
            elseif ($row['status'] === 'Late') $late_count = $row['cnt'];
            elseif ($row['status'] === 'Excused') $excused_count = $row['cnt'];
        }
        $stat_stmt->close();
    }
    $att_percentage = $total_sessions > 0 ? round(($present_count / $total_sessions) * 100, 1) : 100;

    // Fetch recent log records
    $logs = [];
    $log_stmt = $conn->prepare("SELECT a.*, c.class_name, c.section 
                               FROM attendance a 
                               JOIN classes c ON a.class_id = c.id 
                               WHERE a.student_id = ? 
                               ORDER BY a.attendance_date DESC");
    if ($log_stmt) {
        $log_stmt->bind_param("i", $student_id);
        $log_stmt->execute();
        $logs = $log_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $log_stmt->close();
    }

    include "../includes/header.php";
    ?>

    <!-- Student Attendance Metric Cards -->
    <div class="grid-stats">
        <div class="stat-card emerald">
            <div class="stat-header">
                <span class="stat-label">Attendance Rate</span>
                <div class="stat-icon">📊</div>
            </div>
            <div class="stat-value"><?php echo $att_percentage; ?>%</div>
            <div class="stat-footer"><?php echo $present_count; ?> of <?php echo $total_sessions; ?> total class days</div>
        </div>

        <div class="stat-card indigo">
            <div class="stat-header">
                <span class="stat-label">Days Present</span>
                <div class="stat-icon">✅</div>
            </div>
            <div class="stat-value"><?php echo $present_count; ?></div>
            <div class="stat-footer">Recorded Present</div>
        </div>

        <div class="stat-card amber">
            <div class="stat-header">
                <span class="stat-label">Late / Excused</span>
                <div class="stat-icon">⏱️</div>
            </div>
            <div class="stat-value"><?php echo ($late_count + $excused_count); ?></div>
            <div class="stat-footer"><?php echo $late_count; ?> Late, <?php echo $excused_count; ?> Excused</div>
        </div>

        <div class="stat-card sky">
            <div class="stat-header">
                <span class="stat-label">Days Absent</span>
                <div class="stat-icon">⚠️</div>
            </div>
            <div class="stat-value"><?php echo $absent_count; ?></div>
            <div class="stat-footer">Recorded Absences</div>
        </div>
    </div>

    <!-- Attendance History Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                Complete Attendance Logs (<?php echo count($logs); ?> Days)
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Class / Section</th>
                        <th>Status</th>
                        <th>Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                No attendance entries recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td style="font-weight: 600; color: #fff;">
                                    <?php echo date('D, M d, Y', strtotime($log['attendance_date'])); ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['class_name'] . ' - ' . $log['section']); ?></td>
                                <td>
                                    <?php 
                                        $badge_class = 'badge-success';
                                        if ($log['status'] === 'Absent') $badge_class = 'badge-danger';
                                        elseif ($log['status'] === 'Late') $badge_class = 'badge-warning';
                                        elseif ($log['status'] === 'Excused') $badge_class = 'badge-info';
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($log['status']); ?></span>
                                </td>
                                <td style="color: var(--text-secondary); font-size: 13px;">
                                    <?php echo !empty($log['remarks']) ? htmlspecialchars($log['remarks']) : '—'; ?>
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
// ADMIN / STAFF / TEACHER: Class Register & Mark Attendance
// ========================================================
require_role(['Super Admin', 'Admin', 'Staff', 'Teacher']);

// Fetch classes
$classes_stmt = $conn->prepare("SELECT id, class_name, section FROM classes ORDER BY class_name ASC");
$classes_stmt->execute();
$classes = $classes_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$classes_stmt->close();

$selected_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : ($classes[0]['id'] ?? 0);
$selected_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_attendance'])) {
    if (!can_manage_attendance()) {
        die('Unauthorized');
    }

    $att_data = $_POST['attendance'] ?? [];
    $remarks_data = $_POST['remarks'] ?? [];
    $submitted_class_id = intval($_POST['class_id'] ?? 0);
    $submitted_date = $_POST['date'] ?? date('Y-m-d');

    if ($submitted_class_id > 0 && !empty($att_data)) {
        // Prepared statement with ON DUPLICATE KEY UPDATE
        $upsert_stmt = $conn->prepare("INSERT INTO attendance (student_id, class_id, attendance_date, status, remarks) 
                                       VALUES (?, ?, ?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE status = VALUES(status), remarks = VALUES(remarks)");
        
        foreach ($att_data as $s_id => $status) {
            $s_id = intval($s_id);
            $remark = trim($remarks_data[$s_id] ?? '');
            
            $upsert_stmt->bind_param("iisss", $s_id, $submitted_class_id, $submitted_date, $status, $remark);
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
        <?php if (has_role(['Super Admin', 'Admin', 'Staff'])): ?>
            <a href="report.php" class="btn btn-secondary btn-sm">View Attendance Report &rarr;</a>
        <?php endif; ?>
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

    <form method="POST" action="">
        <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">
        <input type="hidden" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Student Name</th>
                        <th>Gender</th>
                        <th style="text-align: center;">Attendance Status</th>
                        <th>Remarks (Optional)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                No active students found in this class.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $stu): ?>
                            <?php $curr = $stu['current_status'] ?? 'Present'; ?>
                            <tr>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($stu['roll_no']); ?></span></td>
                                <td style="font-weight: 600; color: #ffffff;">
                                    <?php echo htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']); ?>
                                </td>
                                <td><?php echo htmlspecialchars($stu['gender']); ?></td>
                                <td style="text-align: center;">
                                    <div style="display: inline-flex; gap: 12px; align-items: center;">
                                        <label style="cursor: pointer; display: flex; align-items: center; gap: 4px; font-size: 13px; color: #34d399;">
                                            <input type="radio" name="attendance[<?php echo $stu['id']; ?>]" value="Present" <?php echo ($curr === 'Present') ? 'checked' : ''; ?>>
                                            Present
                                        </label>
                                        <label style="cursor: pointer; display: flex; align-items: center; gap: 4px; font-size: 13px; color: #f87171;">
                                            <input type="radio" name="attendance[<?php echo $stu['id']; ?>]" value="Absent" <?php echo ($curr === 'Absent') ? 'checked' : ''; ?>>
                                            Absent
                                        </label>
                                        <label style="cursor: pointer; display: flex; align-items: center; gap: 4px; font-size: 13px; color: #fbbf24;">
                                            <input type="radio" name="attendance[<?php echo $stu['id']; ?>]" value="Late" <?php echo ($curr === 'Late') ? 'checked' : ''; ?>>
                                            Late
                                        </label>
                                        <label style="cursor: pointer; display: flex; align-items: center; gap: 4px; font-size: 13px; color: #60a5fa;">
                                            <input type="radio" name="attendance[<?php echo $stu['id']; ?>]" value="Excused" <?php echo ($curr === 'Excused') ? 'checked' : ''; ?>>
                                            Excused
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="remarks[<?php echo $stu['id']; ?>]" class="form-control" style="padding: 4px 8px; font-size: 12px;" placeholder="Optional notes" value="<?php echo htmlspecialchars($stu['current_remarks'] ?? ''); ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($students) && can_manage_attendance()): ?>
            <div style="padding: 16px 24px; border-top: 1px solid var(--border-color); display: flex; justify-content: flex-end;">
                <button type="submit" name="save_attendance" class="btn btn-primary">
                    💾 Save Attendance Register
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php include "../includes/footer.php"; ?>
