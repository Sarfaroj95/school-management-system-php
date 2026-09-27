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
                                <td style="font-weight: 600; color: var(--text-primary);">
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

<?php
// Find currently selected class
$selected_class_obj = null;
foreach ($classes as $c) {
    if ($c['id'] == $selected_class_id) {
        $selected_class_obj = $c;
        break;
    }
}
$selected_class_title = $selected_class_obj ? $selected_class_obj['class_name'] : 'Select Class';
$selected_class_sec = $selected_class_obj ? ('Section ' . $selected_class_obj['section']) : '';
?>

<style>
/* ==========================================================
   Attendance Module Specific Custom Controls (Scoped)
   ========================================================== */
.attendance-filter-bar {
    padding: 18px 0px;
    border-bottom: 1px solid var(--border-color);
    background: linear-gradient(180deg, rgba(15, 23, 42, 0.85) 0%, rgba(11, 15, 25, 0.95) 100%);
    backdrop-filter: blur(16px);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    position: relative;
    z-index: 50;
}

.attendance-filters {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
}

/* ==========================================================
   1. Custom Attendance Class Dropdown Widget (Custom UI)
   ========================================================== */
.custom-att-dropdown {
    position: relative;
    display: inline-block;
    user-select: none;
}

.custom-dropdown-btn,
.custom-calendar-btn {
    height: 32px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    background: linear-gradient(145deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.95) 100%);
    border: 1px solid rgba(255, 255, 255, 0.14);
    border-radius: 8px;
    padding: 0 10px 0 7px;
    color: #f8fafc;
    cursor: pointer;
    font-family: inherit;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.08);
    box-sizing: border-box;
    text-align: left;
}

.custom-dropdown-btn:hover,
.custom-calendar-btn:hover {
    border-color: rgba(99, 102, 241, 0.5);
    background: linear-gradient(145deg, rgba(37, 51, 74, 0.95) 0%, rgba(18, 28, 48, 0.98) 100%);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35), 0 0 10px rgba(99, 102, 241, 0.2);
    transform: translateY(-1px);
}

.custom-dropdown-btn.open,
.custom-calendar-btn.open {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3), 0 6px 18px rgba(0, 0, 0, 0.4);
}

.custom-dropdown-icon,
.custom-calendar-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 20px;
    height: 20px;
    border-radius: 5px;
    flex-shrink: 0;
}

.custom-dropdown-icon {
    background: rgba(99, 102, 241, 0.18);
    color: #818cf8;
    border: 1px solid rgba(99, 102, 241, 0.3);
}

.custom-calendar-icon {
    background: rgba(16, 185, 129, 0.15);
    color: #34d399;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.class-title,
.date-day-str {
    font-size: 12.5px;
    font-weight: 700;
    color: #ffffff;
    letter-spacing: -0.2px;
    white-space: nowrap;
}

.section-pill {
    font-size: 10px;
    font-weight: 700;
    color: #a5b4fc;
    background: rgba(99, 102, 241, 0.18);
    border: 1px solid rgba(99, 102, 241, 0.3);
    padding: 1px 5px;
    border-radius: 4px;
    white-space: nowrap;
}

.today-indicator-pill {
    background: rgba(16, 185, 129, 0.2);
    color: #34d399;
    font-size: 9px;
    padding: 1px 5px;
    border-radius: 4px;
    font-weight: 700;
    text-transform: uppercase;
    border: 1px solid rgba(16, 185, 129, 0.3);
}

.custom-dropdown-chevron,
.custom-calendar-chevron {
    color: #94a3b8;
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    flex-shrink: 0;
    margin-left: 2px;
}

.custom-dropdown-btn.open .custom-dropdown-chevron,
.custom-calendar-btn.open .custom-calendar-chevron {
    transform: rotate(180deg);
    color: #818cf8;
}

/* Dropdown Menu Popover */
.custom-dropdown-menu {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    width: 280px;
    background: rgba(15, 23, 42, 0.98);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 14px;
    padding: 8px;
    box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.7), 0 0 20px rgba(99, 102, 241, 0.2);
    backdrop-filter: blur(20px);
    z-index: 1000;
    opacity: 0;
    transform: translateY(8px) scale(0.97);
    pointer-events: none;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

.custom-dropdown-menu.show {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: auto;
}

.custom-dropdown-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 10px 10px 10px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
    margin-bottom: 6px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    color: #818cf8;
}

.custom-dropdown-header .count-pill {
    background: rgba(99, 102, 241, 0.15);
    color: #a5b4fc;
    padding: 2px 7px;
    border-radius: 6px;
    font-size: 10px;
}

.custom-dropdown-list {
    max-height: 250px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
}

.custom-dropdown-list::-webkit-scrollbar {
    width: 5px;
}
.custom-dropdown-list::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.2);
    border-radius: 4px;
}

.custom-dropdown-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 12px;
    border-radius: 9px;
    cursor: pointer;
    transition: all 0.15s ease;
    color: #e2e8f0;
    margin-bottom: 3px;
    border: 1px solid transparent;
}

.custom-dropdown-item:hover {
    background: rgba(99, 102, 241, 0.15);
    border-color: rgba(99, 102, 241, 0.3);
    color: #ffffff;
    transform: translateX(2px);
}

.custom-dropdown-item.selected {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.28) 0%, rgba(79, 70, 229, 0.22) 100%);
    border-color: rgba(99, 102, 241, 0.5);
    color: #ffffff;
    font-weight: 700;
}

.custom-dropdown-item .item-main {
    display: flex;
    align-items: center;
    gap: 10px;
}

.custom-dropdown-item .item-icon {
    font-size: 14px;
}

.custom-dropdown-item .item-name {
    font-size: 13.5px;
}

.custom-dropdown-item .item-sec {
    font-size: 11px;
    background: rgba(255, 255, 255, 0.08);
    color: #94a3b8;
    padding: 2px 6px;
    border-radius: 5px;
    font-weight: 600;
}

.custom-dropdown-item.selected .item-sec {
    background: rgba(99, 102, 241, 0.35);
    color: #c7d2fe;
}

.custom-dropdown-item .item-check {
    color: #34d399;
    font-weight: 900;
    font-size: 14px;
}

/* ==========================================================
   2. Custom Attendance Calendar Widget (Custom UI)
   ========================================================== */
.custom-att-calendar {
    position: relative;
    display: inline-block;
    user-select: none;
}

/* Custom Calendar Popover Widget */
.custom-calendar-popover {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    width: 320px;
    background: rgba(15, 23, 42, 0.98);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8), 0 0 25px rgba(99, 102, 241, 0.25);
    backdrop-filter: blur(24px);
    z-index: 1000;
    opacity: 0;
    transform: translateY(8px) scale(0.97);
    pointer-events: none;
    transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}

.custom-calendar-popover.show {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: auto;
}

.cal-nav-bar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

.cal-month-year-label {
    font-size: 14.5px;
    font-weight: 800;
    color: #ffffff;
    letter-spacing: -0.3px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.cal-nav-btn {
    background: rgba(255, 255, 255, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #e2e8f0;
    width: 28px;
    height: 28px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.15s ease;
}

.cal-nav-btn:hover {
    background: rgba(99, 102, 241, 0.25);
    border-color: rgba(99, 102, 241, 0.4);
    color: #ffffff;
    transform: scale(1.08);
}

.cal-weekdays-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    text-align: center;
    font-size: 11px;
    font-weight: 700;
    color: #64748b;
    text-transform: uppercase;
    margin-bottom: 8px;
    padding-bottom: 6px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.cal-weekdays-grid span:nth-child(1),
.cal-weekdays-grid span:nth-child(7) {
    color: #e06c75;
}

.cal-days-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 4px;
}

.cal-day-cell {
    aspect-ratio: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12.5px;
    font-weight: 600;
    border-radius: 8px;
    cursor: pointer;
    color: #cbd5e1;
    transition: all 0.15s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    border: 1px solid transparent;
}

.cal-day-cell:hover:not(.out-of-month):not(.selected-day) {
    background: rgba(99, 102, 241, 0.18);
    border-color: rgba(99, 102, 241, 0.35);
    color: #ffffff;
    transform: scale(1.1);
    z-index: 2;
}

.cal-day-cell.out-of-month {
    color: #475569;
    opacity: 0.35;
    cursor: pointer;
}
.cal-day-cell.out-of-month:hover {
    opacity: 0.7;
}

.cal-day-cell.today-day:not(.selected-day) {
    border-color: rgba(16, 185, 129, 0.6);
    color: #34d399;
    font-weight: 800;
    background: rgba(16, 185, 129, 0.1);
}

.cal-day-cell.today-day::after {
    content: '';
    position: absolute;
    bottom: 3px;
    width: 4px;
    height: 4px;
    border-radius: 50%;
    background: #34d399;
    box-shadow: 0 0 6px #34d399;
}

.cal-day-cell.selected-day {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: #ffffff;
    font-weight: 800;
    box-shadow: 0 0 14px rgba(99, 102, 241, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.2);
    border-color: #818cf8;
    transform: scale(1.05);
}

.cal-day-cell.selected-day.today-day::after {
    background: #ffffff;
    box-shadow: 0 0 4px #ffffff;
}

.cal-quick-shortcuts {
    margin-top: 14px;
    padding-top: 10px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
    display: flex;
    justify-content: space-between;
    gap: 6px;
}

.cal-quick-btn {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #cbd5e1;
    font-size: 11px;
    font-weight: 700;
    padding: 5px 9px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    font-family: inherit;
    flex: 1;
    text-align: center;
}

.cal-quick-btn:hover {
    background: rgba(99, 102, 241, 0.2);
    border-color: rgba(99, 102, 241, 0.4);
    color: #ffffff;
    transform: translateY(-1px);
}

/* Date Steppers */
.date-steppers {
    height: 32px;
    box-sizing: border-box;
    display: inline-flex;
    align-items: center;
    gap: 2px;
    background: rgba(15, 23, 42, 0.6);
    padding: 2px;
    border-radius: 8px;
    border: 1px solid rgba(255, 255, 255, 0.08);
}

.date-stepper-btn {
    height: 26px;
    line-height: 26px;
    background: transparent;
    border: 1px solid transparent;
    color: var(--text-secondary);
    padding: 0 9px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.18s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    box-sizing: border-box;
}

.date-stepper-btn:hover {
    background: rgba(255, 255, 255, 0.08);
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.15);
}

.date-stepper-btn.active {
    background: rgba(16, 185, 129, 0.18);
    color: #34d399;
    border-color: rgba(16, 185, 129, 0.35);
}

/* ==========================================================
   Modern Segmented Radio Check Buttons (Scoped to Attendance)
   ========================================================== */
.att-radio-group {
    display: inline-flex;
    gap: 5px;
    background: rgba(11, 15, 25, 0.85);
    padding: 4px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.3);
}

.att-radio-label {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 7px 13px;
    border-radius: 7px;
    font-size: 12px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
    border: 1px solid transparent;
    color: var(--text-muted);
    background: transparent;
}

.att-radio-label input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
    width: 0;
    height: 0;
}

.att-radio-indicator {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: currentColor;
    opacity: 0.35;
    transition: all 0.2s ease;
}

.att-radio-label:hover {
    transform: translateY(-1px);
}

.att-radio-label:active {
    transform: scale(0.96);
}

/* Present State */
.att-radio-label.opt-present:hover {
    color: #34d399;
    background: rgba(16, 185, 129, 0.08);
}
.att-radio-label.opt-present.active,
.att-radio-label.opt-present:has(input:checked) {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.25) 0%, rgba(5, 150, 105, 0.18) 100%);
    border-color: rgba(16, 185, 129, 0.55);
    color: #34d399;
    box-shadow: 0 0 14px rgba(16, 185, 129, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.1);
    text-shadow: 0 0 8px rgba(16, 185, 129, 0.35);
}
.att-radio-label.opt-present.active .att-radio-indicator,
.att-radio-label.opt-present:has(input:checked) .att-radio-indicator {
    opacity: 1;
    box-shadow: 0 0 6px #34d399;
}

/* Absent State */
.att-radio-label.opt-absent:hover {
    color: #f87171;
    background: rgba(239, 68, 68, 0.08);
}
.att-radio-label.opt-absent.active,
.att-radio-label.opt-absent:has(input:checked) {
    background: linear-gradient(135deg, rgba(239, 68, 68, 0.25) 0%, rgba(225, 29, 72, 0.18) 100%);
    border-color: rgba(239, 68, 68, 0.55);
    color: #f87171;
    box-shadow: 0 0 14px rgba(239, 68, 68, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.1);
    text-shadow: 0 0 8px rgba(239, 68, 68, 0.35);
}
.att-radio-label.opt-absent.active .att-radio-indicator,
.att-radio-label.opt-absent:has(input:checked) .att-radio-indicator {
    opacity: 1;
    box-shadow: 0 0 6px #f87171;
}

/* Late State */
.att-radio-label.opt-late:hover {
    color: #fbbf24;
    background: rgba(245, 158, 11, 0.08);
}
.att-radio-label.opt-late.active,
.att-radio-label.opt-late:has(input:checked) {
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.25) 0%, rgba(217, 119, 6, 0.18) 100%);
    border-color: rgba(245, 158, 11, 0.55);
    color: #fbbf24;
    box-shadow: 0 0 14px rgba(245, 158, 11, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.1);
    text-shadow: 0 0 8px rgba(245, 158, 11, 0.35);
}
.att-radio-label.opt-late.active .att-radio-indicator,
.att-radio-label.opt-late:has(input:checked) .att-radio-indicator {
    opacity: 1;
    box-shadow: 0 0 6px #fbbf24;
}

/* Excused State */
.att-radio-label.opt-excused:hover {
    color: #38bdf8;
    background: rgba(14, 165, 233, 0.08);
}
.att-radio-label.opt-excused.active,
.att-radio-label.opt-excused:has(input:checked) {
    background: linear-gradient(135deg, rgba(14, 165, 233, 0.25) 0%, rgba(2, 132, 199, 0.18) 100%);
    border-color: rgba(14, 165, 233, 0.55);
    color: #38bdf8;
    box-shadow: 0 0 14px rgba(14, 165, 233, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.1);
    text-shadow: 0 0 8px rgba(14, 165, 233, 0.35);
}
.att-radio-label.opt-excused.active .att-radio-indicator,
.att-radio-label.opt-excused:has(input:checked) .att-radio-indicator {
    opacity: 1;
    box-shadow: 0 0 6px #38bdf8;
}

/* Quick Batch Action Buttons */
.quick-batch-btn {
    height: 32px;
    box-sizing: border-box;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid var(--border-color);
    color: #e2e8f0;
    font-size: 11.5px;
    font-weight: 700;
    padding: 0 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.18s ease;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.quick-batch-btn:hover {
    background: rgba(255, 255, 255, 0.09);
    transform: translateY(-1px);
}

.quick-batch-btn:active {
    transform: scale(0.96);
}

.live-stat-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.live-stat-badge.present { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }
.live-stat-badge.absent { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
.live-stat-badge.late { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
.live-stat-badge.excused { background: rgba(14, 165, 233, 0.15); color: #38bdf8; border: 1px solid rgba(14, 165, 233, 0.3); }
</style>

<?php if (!empty($msg)): ?>
    <div class="alert alert-success">
        <div style="display: flex; align-items: center; gap: 8px;">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
            </svg>
            Daily Attendance Register
        </div>
        
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <div id="liveAttStats" style="display: inline-flex; gap: 8px; flex-wrap: wrap;">
                <!-- Dynamically filled via JavaScript -->
            </div>
            <?php if (has_role(['Super Admin', 'Admin', 'Staff'])): ?>
                <a href="report.php" class="btn btn-secondary btn-sm">📊 Attendance Analytics &rarr;</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Custom Class & Calendar Filter Bar -->
    <div class="attendance-filter-bar">
        <form method="GET" action="index.php" id="attendanceFilterForm" style="padding: 0px 24px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; width: 100%; justify-content: space-between;">
            <input type="hidden" name="date" id="hiddenDate" value="<?php echo htmlspecialchars($selected_date); ?>">

            <div class="attendance-filters">
                <!-- 1. ATTENDANCE GRADE / CLASS DROPDOWN -->
                <div style="min-width: 210px; max-width: 270px;">
                    <select name="class_id" id="attendanceClassSelect" onchange="document.getElementById('attendanceFilterForm').submit()" style="width: 100%;">
                        <?php foreach ($classes as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo ($selected_class_id == $c['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($c['class_name'] . ' - Section ' . $c['section']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 2. CUSTOM CALENDAR DATE PICKER -->
                <div class="custom-att-calendar" id="calendarPicker">
                    <button type="button" class="custom-calendar-btn" id="calendarPickerBtn" onclick="toggleCalendar(event)">
                        <span class="custom-calendar-icon">
                            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </span>
                        <span class="date-day-str"><?php echo date('D, M d, Y', strtotime($selected_date)); ?></span>
                        <?php if ($selected_date === date('Y-m-d')): ?>
                            <span class="today-indicator-pill">Today</span>
                        <?php endif; ?>
                        <svg class="custom-calendar-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="14" height="14">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    
                    <div class="custom-calendar-popover" id="calendarPopover" onclick="event.stopPropagation()">
                        <div class="cal-nav-bar">
                            <button type="button" class="cal-nav-btn" onclick="navigateCalMonth(-1)" title="Previous Month">◀</button>
                            <div class="cal-month-year-label" id="calMonthYearLabel">
                                <!-- Rendered dynamically via JS -->
                            </div>
                            <button type="button" class="cal-nav-btn" onclick="navigateCalMonth(1)" title="Next Month">▶</button>
                        </div>
                        
                        <div class="cal-weekdays-grid">
                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                        </div>
                        
                        <div class="cal-days-grid" id="calDaysGrid">
                            <!-- Rendered dynamically via JS -->
                        </div>
                        
                        <div class="cal-quick-shortcuts">
                            <button type="button" class="cal-quick-btn" onclick="selectDateQuick('<?php echo date('Y-m-d'); ?>')">⭐ Today</button>
                            <button type="button" class="cal-quick-btn" onclick="selectDateQuick('<?php echo date('Y-m-d', strtotime('-1 day')); ?>')">◀ Yesterday</button>
                            <button type="button" class="cal-quick-btn" onclick="selectDateQuick('<?php echo date('Y-m-d', strtotime('-7 day')); ?>')">◀ -7 Days</button>
                        </div>
                    </div>
                </div>

                <!-- Date Steppers -->
                <?php
                    $prev_day = date('Y-m-d', strtotime($selected_date . ' -1 day'));
                    $next_day = date('Y-m-d', strtotime($selected_date . ' +1 day'));
                    $today_date = date('Y-m-d');
                    $is_today = ($selected_date === $today_date);
                ?>
                <div class="date-steppers">
                    <a href="index.php?class_id=<?php echo $selected_class_id; ?>&date=<?php echo $prev_day; ?>" class="date-stepper-btn" title="Previous Day">
                        ◀ Prev
                    </a>
                    <a href="index.php?class_id=<?php echo $selected_class_id; ?>&date=<?php echo $today_date; ?>" class="date-stepper-btn <?php echo $is_today ? 'active' : ''; ?>" title="Jump to Today">
                        📅 Today
                    </a>
                    <a href="index.php?class_id=<?php echo $selected_class_id; ?>&date=<?php echo $next_day; ?>" class="date-stepper-btn" title="Next Day">
                        Next ▶
                    </a>
                </div>
            </div>

            <!-- Quick Batch Fill Buttons -->
            <?php if (!empty($students) && can_manage_attendance()): ?>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <span style="font-size: 11px; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px;">Quick Fill:</span>
                    <button type="button" class="quick-batch-btn" onclick="batchMark('Present')" style="color: #34d399; border-color: rgba(16, 185, 129, 0.35);">
                        ✓ All Present
                    </button>
                    <button type="button" class="quick-batch-btn" onclick="batchMark('Absent')" style="color: #f87171; border-color: rgba(239, 68, 68, 0.35);">
                        ✕ All Absent
                    </button>
            <?php endif; ?>
        </form>
    </div>

    <form method="POST" action="" style="width: 100% !important; margin: 0 !important; padding: 0 !important;">
        <input type="hidden" name="class_id" value="<?php echo $selected_class_id; ?>">
        <input type="hidden" name="date" value="<?php echo htmlspecialchars($selected_date); ?>">

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Roll No</th>
                        <th>Student Name</th>
                        <th style="width: 90px;">Gender</th>
                        <th style="text-align: center; min-width: 350px;">Attendance Status</th>
                        <th>Remarks / Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                No active students found enrolled in this class.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $stu): ?>
                            <?php $curr = $stu['current_status'] ?? 'Present'; ?>
                            <tr>
                                <td>
                                    <span class="badge badge-info" style="font-family: monospace;">
                                        <?php echo htmlspecialchars($stu['roll_no']); ?>
                                    </span>
                                </td>
                                <td style="font-weight: 600; color: var(--text-primary);">
                                    <?php echo htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']); ?>
                                </td>
                                <td style="color: var(--text-secondary); font-size: 13px;">
                                    <?php echo htmlspecialchars($stu['gender']); ?>
                                </td>
                                <td style="text-align: center;">
                                    <!-- Modern Theme-Based Segmented Radio Checks (Attendance Only) -->
                                    <div class="att-radio-group">
                                        <label class="att-radio-label opt-present <?php echo ($curr === 'Present') ? 'active' : ''; ?>">
                                            <input type="radio" name="attendance[<?php echo $stu['id']; ?>]" value="Present" <?php echo ($curr === 'Present') ? 'checked' : ''; ?> onchange="updateRadioTheme(this)">
                                            <span class="att-radio-indicator"></span>
                                            <span>✓ Present</span>
                                        </label>
                                        <label class="att-radio-label opt-absent <?php echo ($curr === 'Absent') ? 'active' : ''; ?>">
                                            <input type="radio" name="attendance[<?php echo $stu['id']; ?>]" value="Absent" <?php echo ($curr === 'Absent') ? 'checked' : ''; ?> onchange="updateRadioTheme(this)">
                                            <span class="att-radio-indicator"></span>
                                            <span>✕ Absent</span>
                                        </label>
                                        <label class="att-radio-label opt-late <?php echo ($curr === 'Late') ? 'active' : ''; ?>">
                                            <input type="radio" name="attendance[<?php echo $stu['id']; ?>]" value="Late" <?php echo ($curr === 'Late') ? 'checked' : ''; ?> onchange="updateRadioTheme(this)">
                                            <span class="att-radio-indicator"></span>
                                            <span>⏱ Late</span>
                                        </label>
                                        <label class="att-radio-label opt-excused <?php echo ($curr === 'Excused') ? 'active' : ''; ?>">
                                            <input type="radio" name="attendance[<?php echo $stu['id']; ?>]" value="Excused" <?php echo ($curr === 'Excused') ? 'checked' : ''; ?> onchange="updateRadioTheme(this)">
                                            <span class="att-radio-indicator"></span>
                                            <span>📄 Excused</span>
                                        </label>
                                    </div>
                                </td>
                                <td>
                                    <input type="text" name="remarks[<?php echo $stu['id']; ?>]" class="form-control" style="padding: 6px 10px; font-size: 12.5px;" placeholder="Optional notes" value="<?php echo htmlspecialchars($stu['current_remarks'] ?? ''); ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($students) && can_manage_attendance()): ?>
            <div style="padding: 14px 24px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: var(--bg-surface-elevated); flex-wrap: wrap; gap: 12px;">
                <div style="font-size: 13px; color: var(--text-secondary);">
                    Class: <strong><?php echo htmlspecialchars($classes[array_search($selected_class_id, array_column($classes, 'id'))]['class_name'] ?? ''); ?></strong> · Date: <strong><?php echo date('D, M d, Y', strtotime($selected_date)); ?></strong>
                </div>
                <button type="submit" name="save_attendance" class="btn btn-primary" style="padding: 10px 24px; font-weight: 700; box-shadow: 0 4px 14px rgba(79, 70, 229, 0.4);">
                    💾 Save Attendance Register
                </button>
            </div>
        <?php endif; ?>
    </form>
</div>

<script>
// Attendance Custom Calendar Popover Logic
var activeSelectedDate = "<?php echo $selected_date; ?>";
var calDateObj = new Date(activeSelectedDate + 'T00:00:00');
var calViewYear = calDateObj.getFullYear();
var calViewMonth = calDateObj.getMonth();

var monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

function toggleCalendar(e) {
    e.stopPropagation();
    var btn = document.getElementById('calendarPickerBtn');
    var popover = document.getElementById('calendarPopover');
    var isOpen = popover.classList.contains('show');
    
    closeAllPopovers();
    
    if (!isOpen) {
        renderCalendar(calViewYear, calViewMonth);
        popover.classList.add('show');
        btn.classList.add('open');
    }
}

function navigateCalMonth(delta) {
    calViewMonth += delta;
    if (calViewMonth > 11) {
        calViewMonth = 0;
        calViewYear++;
    } else if (calViewMonth < 0) {
        calViewMonth = 11;
        calViewYear--;
    }
    renderCalendar(calViewYear, calViewMonth);
}

function renderCalendar(year, month) {
    var labelEl = document.getElementById('calMonthYearLabel');
    if (labelEl) {
        labelEl.innerHTML = '<span>' + monthNames[month] + '</span> <span style="color:#818cf8;">' + year + '</span>';
    }
    
    var grid = document.getElementById('calDaysGrid');
    if (!grid) return;
    grid.innerHTML = '';
    
    var firstDayIndex = new Date(year, month, 1).getDay(); // 0 = Sun
    var lastDayDate = new Date(year, month + 1, 0).getDate();
    var prevLastDayDate = new Date(year, month, 0).getDate();
    
    var todayStr = new Date().toISOString().split('T')[0];
    
    // Previous Month Days
    for (var i = firstDayIndex; i > 0; i--) {
        var dayNum = prevLastDayDate - i + 1;
        var prevMonth = month - 1;
        var prevYear = year;
        if (prevMonth < 0) { prevMonth = 11; prevYear--; }
        var dateStr = formatDateStr(prevYear, prevMonth, dayNum);
        
        var cell = createDayCell(dayNum, dateStr, true, todayStr);
        grid.appendChild(cell);
    }
    
    // Current Month Days
    for (var d = 1; d <= lastDayDate; d++) {
        var dateStr = formatDateStr(year, month, d);
        var cell = createDayCell(d, dateStr, false, todayStr);
        grid.appendChild(cell);
    }
    
    // Next Month Days
    var totalCells = firstDayIndex + lastDayDate;
    var nextDays = (totalCells <= 35 ? 35 : 42) - totalCells;
    for (var n = 1; n <= nextDays; n++) {
        var nextMonth = month + 1;
        var nextYear = year;
        if (nextMonth > 11) { nextMonth = 0; nextYear++; }
        var dateStr = formatDateStr(nextYear, nextMonth, n);
        
        var cell = createDayCell(n, dateStr, true, todayStr);
        grid.appendChild(cell);
    }
}

function formatDateStr(y, m, d) {
    var mm = (m + 1) < 10 ? '0' + (m + 1) : (m + 1);
    var dd = d < 10 ? '0' + d : d;
    return y + '-' + mm + '-' + dd;
}

function createDayCell(dayNum, dateStr, isOutOfMonth, todayStr) {
    var cell = document.createElement('div');
    cell.className = 'cal-day-cell';
    if (isOutOfMonth) cell.classList.add('out-of-month');
    if (dateStr === todayStr) cell.classList.add('today-day');
    if (dateStr === activeSelectedDate) cell.classList.add('selected-day');
    
    cell.innerText = dayNum;
    cell.title = dateStr;
    cell.onclick = function(e) {
        e.stopPropagation();
        selectDateQuick(dateStr);
    };
    return cell;
}

function selectDateQuick(dateStr) {
    document.getElementById('hiddenDate').value = dateStr;
    document.getElementById('attendanceFilterForm').submit();
}

function closeAllPopovers() {
    var calPopover = document.getElementById('calendarPopover');
    var calBtn = document.getElementById('calendarPickerBtn');
    if (calPopover) calPopover.classList.remove('show');
    if (calBtn) calBtn.classList.remove('open');
}

// Global click & escape handler
document.addEventListener('click', function() {
    closeAllPopovers();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAllPopovers();
    }
});

function updateRadioTheme(input) {
    var group = input.closest('.att-radio-group');
    group.querySelectorAll('.att-radio-label').forEach(function(lbl) {
        lbl.classList.remove('active');
    });
    input.closest('.att-radio-label').classList.add('active');
    calcLiveStats();
}

function batchMark(status) {
    document.querySelectorAll('.att-radio-group').forEach(function(group) {
        var targetRadio = group.querySelector('input[value="' + status + '"]');
        if (targetRadio) {
            targetRadio.checked = true;
            group.querySelectorAll('.att-radio-label').forEach(function(lbl) {
                lbl.classList.remove('active');
            });
            targetRadio.closest('.att-radio-label').classList.add('active');
        }
    });
    calcLiveStats();
}

function calcLiveStats() {
    var p = document.querySelectorAll('input[type="radio"][value="Present"]:checked').length;
    var a = document.querySelectorAll('input[type="radio"][value="Absent"]:checked').length;
    var l = document.querySelectorAll('input[type="radio"][value="Late"]:checked').length;
    var e = document.querySelectorAll('input[type="radio"][value="Excused"]:checked').length;
    
    var container = document.getElementById('liveAttStats');
    if (container) {
        container.innerHTML = '<span class="live-stat-badge present">🟢 ' + p + ' Present</span>' +
                              '<span class="live-stat-badge absent">🔴 ' + a + ' Absent</span>' +
                              '<span class="live-stat-badge late">🟡 ' + l + ' Late</span>' +
                              '<span class="live-stat-badge excused">🔵 ' + e + ' Excused</span>';
    }
}

// Initial live count calculation
document.addEventListener('DOMContentLoaded', calcLiveStats);
</script>

<?php include "../includes/footer.php"; ?>
