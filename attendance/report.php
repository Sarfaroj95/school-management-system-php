<?php
/**
 * Attendance Summary & Monthly Mode Analysis Reports
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin', 'Staff', 'Teacher']);

$view = $_GET['view'] ?? (isset($_GET['class_id']) ? 'monthly' : 'overview');
if (!in_array($view, ['overview', 'monthly'])) {
    $view = 'overview';
}

$page_title = ($view === 'monthly') ? "Monthly Attendance Breakdown" : "Attendance Analytics & Class Performance";
$header_title = ($view === 'monthly') ? "Monthly Mode Analysis" : "Attendance Analytics";
$current_page = "attendance";

// Fetch overall attendance stats grouped by status
$stmt = $conn->prepare("SELECT status, COUNT(*) AS count FROM attendance GROUP BY status");
$stmt->execute();
$stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_logs = 0;
$counts = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Excused' => 0];
foreach ($stats as $s) {
    $counts[$s['status']] = $s['count'];
    $total_logs += $s['count'];
}

// Fetch class-wise attendance rates
$class_stmt = $conn->prepare("SELECT c.id, c.class_name, c.section, 
                             COUNT(a.id) AS total_records,
                             SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_records,
                             SUM(CASE WHEN a.status = 'Absent' THEN 1 ELSE 0 END) AS absent_records,
                             SUM(CASE WHEN a.status = 'Late' THEN 1 ELSE 0 END) AS late_records,
                             SUM(CASE WHEN a.status = 'Excused' THEN 1 ELSE 0 END) AS excused_records
                             FROM classes c
                             LEFT JOIN attendance a ON c.id = a.class_id
                             GROUP BY c.id
                             ORDER BY c.class_name ASC");
$class_stmt->execute();
$class_reports = $class_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$class_stmt->close();

// Fetch all classes for the dropdown selector
$classes_list_stmt = $conn->prepare("SELECT id, class_name, section FROM classes ORDER BY class_name ASC");
$classes_list_stmt->execute();
$all_classes = $classes_list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$classes_list_stmt->close();

// ========================================================
// Monthly Mode Analysis for Selected Class & Month
// ========================================================
$selected_class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : ($class_reports[0]['id'] ?? 0);
$selected_month = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month']) ? $_GET['month'] : date('Y-m');

$month_start = $selected_month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));
$month_label = date('F Y', strtotime($month_start));

$prev_month = date('Y-m', strtotime($month_start . ' -1 month'));
$next_month = date('Y-m', strtotime($month_start . ' +1 month'));
$current_month_str = date('Y-m');
$is_current_month = ($selected_month === $current_month_str);

// Selected Class details
$current_class = null;
foreach ($all_classes as $c) {
    if ($c['id'] == $selected_class_id) {
        $current_class = $c;
        break;
    }
}

// Fetch active students in this class
$monthly_students = [];
if ($selected_class_id > 0) {
    $stu_stmt = $conn->prepare("SELECT id, roll_no, first_name, last_name, gender 
                                FROM students 
                                WHERE class_id = ? AND status = 'Active' 
                                ORDER BY roll_no ASC");
    $stu_stmt->bind_param("i", $selected_class_id);
    $stu_stmt->execute();
    $students_res = $stu_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stu_stmt->close();

    // Fetch monthly attendance tallies per student for this month
    $monthly_att_stmt = $conn->prepare("SELECT student_id,
                                         COUNT(id) AS total_sessions,
                                         SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
                                         SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS absent_count,
                                         SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) AS late_count,
                                         SUM(CASE WHEN status = 'Excused' THEN 1 ELSE 0 END) AS excused_count
                                         FROM attendance
                                         WHERE class_id = ? AND attendance_date BETWEEN ? AND ?
                                         GROUP BY student_id");
    $monthly_att_stmt->bind_param("iss", $selected_class_id, $month_start, $month_end);
    $monthly_att_stmt->execute();
    $att_records = $monthly_att_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $monthly_att_stmt->close();

    $att_map = [];
    foreach ($att_records as $rec) {
        $att_map[$rec['student_id']] = $rec;
    }

    foreach ($students_res as $st) {
        $sid = $st['id'];
        $att = $att_map[$sid] ?? [
            'total_sessions' => 0,
            'present_count' => 0,
            'absent_count' => 0,
            'late_count' => 0,
            'excused_count' => 0
        ];

        $tot = intval($att['total_sessions']);
        $p = intval($att['present_count']);
        $a = intval($att['absent_count']);
        $l = intval($att['late_count']);
        $e = intval($att['excused_count']);
        $pct = $tot > 0 ? round(($p / $tot) * 100, 1) : 0;

        $monthly_students[] = array_merge($st, [
            'total_sessions' => $tot,
            'present_count' => $p,
            'absent_count' => $a,
            'late_count' => $l,
            'excused_count' => $e,
            'attendance_rate' => $pct
        ]);
    }
}

// Calculate class monthly totals
$class_total_present = 0;
$class_total_sessions = 0;
$top_performer = null;
$highest_rate = -1;

foreach ($monthly_students as $mst) {
    $class_total_present += $mst['present_count'];
    $class_total_sessions += $mst['total_sessions'];
    if ($mst['total_sessions'] > 0 && $mst['attendance_rate'] > $highest_rate) {
        $highest_rate = $mst['attendance_rate'];
        $top_performer = $mst['first_name'] . ' ' . $mst['last_name'] . ' (' . $mst['attendance_rate'] . '%)';
    }
}

$class_monthly_avg = $class_total_sessions > 0 ? round(($class_total_present / $class_total_sessions) * 100, 1) : 0;

// Unique school days logged in this month for this class
$days_logged_stmt = $conn->prepare("SELECT COUNT(DISTINCT attendance_date) AS unique_days 
                                    FROM attendance 
                                    WHERE class_id = ? AND attendance_date BETWEEN ? AND ?");
$days_logged_stmt->bind_param("iss", $selected_class_id, $month_start, $month_end);
$days_logged_stmt->execute();
$days_logged_res = $days_logged_stmt->get_result()->fetch_assoc();
$unique_recorded_days = $days_logged_res['unique_days'] ?? 0;
$days_logged_stmt->close();

include "../includes/header.php";
?>

<style>
/* Scoped Report & Monthly Mode Analysis Styles */
.col-roll-no {
    width: 80px !important;
    min-width: 80px !important;
    max-width: 80px !important;
    text-align: center;
}

.report-action-bar {
    margin-bottom: 24px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 14px;
}

/* Clickable Class Performance Row */
.clickable-class-row {
    cursor: pointer;
    transition: all 0.2s ease;
}
.clickable-class-row:hover {
    background: rgba(99, 102, 241, 0.12) !important;
    transform: translateY(-1px);
}
.clickable-class-row.active-row {
    background: rgba(99, 102, 241, 0.18) !important;
    box-shadow: inset 3px 0 0 #6366f1;
}

/* Custom Month Filter Bar */
.month-filter-bar {
    padding: 18px 24px;
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

/* ==========================================================
   Custom Dropdown Widget (Report Scoped)
   ========================================================== */
.custom-att-dropdown {
    position: relative;
    display: inline-block;
    user-select: none;
}

.custom-dropdown-btn,
.custom-monthpicker-btn {
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
.custom-monthpicker-btn:hover {
    border-color: rgba(99, 102, 241, 0.5);
    background: linear-gradient(145deg, rgba(37, 51, 74, 0.95) 0%, rgba(18, 28, 48, 0.98) 100%);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35), 0 0 10px rgba(99, 102, 241, 0.2);
    transform: translateY(-1px);
}

.custom-dropdown-btn.open,
.custom-monthpicker-btn.open {
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3), 0 6px 18px rgba(0, 0, 0, 0.4);
}

.custom-dropdown-icon,
.custom-monthpicker-icon {
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

.custom-monthpicker-icon {
    background: rgba(14, 165, 233, 0.15);
    color: #38bdf8;
    border: 1px solid rgba(14, 165, 233, 0.3);
}

.class-title,
.month-main-str {
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

.current-month-indicator-pill {
    background: rgba(14, 165, 233, 0.2);
    color: #38bdf8;
    font-size: 9px;
    padding: 1px 5px;
    border-radius: 4px;
    font-weight: 700;
    text-transform: uppercase;
    border: 1px solid rgba(14, 165, 233, 0.3);
    white-space: nowrap;
}

.custom-dropdown-chevron,
.custom-monthpicker-chevron {
    color: #94a3b8;
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    flex-shrink: 0;
    margin-left: 2px;
}

.custom-dropdown-btn.open .custom-dropdown-chevron,
.custom-monthpicker-btn.open .custom-monthpicker-chevron {
    transform: rotate(180deg);
    color: #818cf8;
}

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

.custom-dropdown-list {
    max-height: 250px;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
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
}

.custom-dropdown-item:hover {
    background: rgba(99, 102, 241, 0.15);
    color: #ffffff;
    transform: translateX(2px);
}

.custom-dropdown-item.selected {
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.28) 0%, rgba(79, 70, 229, 0.22) 100%);
    border: 1px solid rgba(99, 102, 241, 0.5);
    color: #ffffff;
    font-weight: 700;
}

.custom-dropdown-item .item-main {
    display: flex;
    align-items: center;
    gap: 10px;
}

.custom-dropdown-item .item-sec {
    font-size: 11px;
    background: rgba(255, 255, 255, 0.08);
    color: #94a3b8;
    padding: 2px 6px;
    border-radius: 5px;
}

.custom-dropdown-item.selected .item-sec {
    background: rgba(99, 102, 241, 0.35);
    color: #c7d2fe;
}

.custom-dropdown-item .item-check {
    color: #34d399;
    font-weight: 900;
}

/* ==========================================================
   Custom Month Picker Popover (Report Scoped)
   ========================================================== */
.custom-att-monthpicker {
    position: relative;
    display: inline-block;
    user-select: none;
}

/* Month Popover Card */
.custom-monthpicker-popover {
    position: absolute;
    top: calc(100% + 8px);
    left: 0;
    width: 290px;
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

.custom-monthpicker-popover.show {
    opacity: 1;
    transform: translateY(0) scale(1);
    pointer-events: auto;
}

.mp-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 14px;
}

.mp-year-label {
    font-size: 15px;
    font-weight: 800;
    color: #ffffff;
}

.mp-nav-btn {
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

.mp-nav-btn:hover {
    background: rgba(99, 102, 241, 0.25);
    border-color: rgba(99, 102, 241, 0.4);
    color: #ffffff;
    transform: scale(1.08);
}

.mp-months-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
}

.mp-month-cell {
    padding: 10px 6px;
    text-align: center;
    font-size: 12.5px;
    font-weight: 700;
    border-radius: 8px;
    cursor: pointer;
    color: #cbd5e1;
    background: rgba(255, 255, 255, 0.04);
    border: 1px solid rgba(255, 255, 255, 0.06);
    transition: all 0.15s ease;
}

.mp-month-cell:hover {
    background: rgba(99, 102, 241, 0.2);
    border-color: rgba(99, 102, 241, 0.4);
    color: #ffffff;
    transform: translateY(-1px);
}

.mp-month-cell.selected-month {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    color: #ffffff;
    border-color: #818cf8;
    box-shadow: 0 0 14px rgba(99, 102, 241, 0.6);
}

.mp-month-cell.current-month:not(.selected-month) {
    border-color: rgba(14, 165, 233, 0.6);
    color: #38bdf8;
}

.mp-quick-shortcuts {
    margin-top: 14px;
    padding-top: 10px;
    border-top: 1px solid rgba(255, 255, 255, 0.08);
}

.mp-quick-btn {
    width: 100%;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #cbd5e1;
    font-size: 11.5px;
    font-weight: 700;
    padding: 6px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.15s ease;
    text-align: center;
}

.mp-quick-btn:hover {
    background: rgba(99, 102, 241, 0.2);
    border-color: rgba(99, 102, 241, 0.4);
    color: #ffffff;
}

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
    background: rgba(99, 102, 241, 0.18);
    color: #818cf8;
    border-color: rgba(99, 102, 241, 0.35);
}

/* Tier Badges */
.tier-badge {
    padding: 4px 10px;
    border-radius: 6px;
    font-size: 11.5px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.tier-excellent { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.35); }
.tier-good { background: rgba(14, 165, 233, 0.15); color: #38bdf8; border: 1px solid rgba(14, 165, 233, 0.35); }
.tier-warning { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.35); }
.tier-critical { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.35); }

/* Progress Pill Bar */
.att-progress-track {
    flex: 1;
    height: 8px;
    background: rgba(255, 255, 255, 0.08);
    border-radius: 4px;
    overflow: hidden;
    max-width: 130px;
}
.att-progress-fill {
    height: 100%;
    border-radius: 4px;
    transition: width 0.4s ease;
}
</style>

<?php if ($view === 'overview'): ?>

    <div class="report-action-bar">
        <a href="index.php" class="btn btn-secondary btn-sm">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            &larr; Daily Attendance Register
        </a>

        <div style="font-size: 13px; color: var(--text-secondary);">
            Overall Total Logs: <strong><?php echo number_format($total_logs); ?></strong> entries
        </div>
    </div>

    <!-- Overall Metric Cards -->
    <div class="grid-stats">
        <div class="stat-card emerald">
            <div class="stat-header">
                <span class="stat-label">Total Present Logs</span>
                <div class="stat-icon">✅</div>
            </div>
            <div class="stat-value"><?php echo number_format($counts['Present']); ?></div>
            <div class="stat-footer"><?php echo $total_logs > 0 ? round(($counts['Present']/$total_logs)*100, 1) : 0; ?>% of all records</div>
        </div>

        <div class="stat-card rose">
            <div class="stat-header">
                <span class="stat-label">Absences Logged</span>
                <div class="stat-icon">✕</div>
            </div>
            <div class="stat-value"><?php echo number_format($counts['Absent']); ?></div>
            <div class="stat-footer"><?php echo $total_logs > 0 ? round(($counts['Absent']/$total_logs)*100, 1) : 0; ?>% unexcused</div>
        </div>

        <div class="stat-card amber">
            <div class="stat-header">
                <span class="stat-label">Late Arrivals</span>
                <div class="stat-icon">⏱️</div>
            </div>
            <div class="stat-value"><?php echo number_format($counts['Late']); ?></div>
            <div class="stat-footer"><?php echo number_format($counts['Late']); ?> tardiness instances</div>
        </div>

        <div class="stat-card sky">
            <div class="stat-header">
                <span class="stat-label">Excused Leaves</span>
                <div class="stat-icon">📄</div>
            </div>
            <div class="stat-value"><?php echo number_format($counts['Excused']); ?></div>
            <div class="stat-footer">Official approvals & medicals</div>
        </div>
    </div>

    <!-- ========================================================
         1. Class-Wise Attendance Performance Summary Table
         ======================================================== -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                Class-Wise Attendance Performance
            </div>
            <div style="font-size: 12.5px; color: var(--text-muted);">
                💡 Click on any class row or <strong>Monthly Breakdown</strong> to view dedicated student-by-student analytics
            </div>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Class Name</th>
                        <th>Section</th>
                        <th>Total Logged Days</th>
                        <th>Present Count</th>
                        <th>Attendance Percentage</th>
                        <th style="text-align: right;">Monthly Mode Analysis</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($class_reports)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                No class attendance logs found.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($class_reports as $cr): 
                            $pct = $cr['total_records'] > 0 ? round(($cr['present_records'] / $cr['total_records']) * 100, 1) : 0;
                            $bar_color = ($pct >= 85) ? '#10b981' : (($pct >= 70) ? '#0ea5e9' : (($pct >= 50) ? '#f59e0b' : '#ef4444'));
                        ?>
                            <tr class="clickable-class-row" onclick="window.location='report.php?view=monthly&class_id=<?php echo $cr['id']; ?>&month=<?php echo $selected_month; ?>'">
                                <td style="font-weight: 700; color: #ffffff;">
                                    🏫 <?php echo htmlspecialchars($cr['class_name']); ?>
                                </td>
                                <td><span class="badge badge-info"><?php echo htmlspecialchars($cr['section']); ?></span></td>
                                <td><?php echo number_format($cr['total_records']); ?></td>
                                <td><?php echo number_format($cr['present_records']); ?></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="att-progress-track">
                                            <div class="att-progress-fill" style="width: <?php echo $pct; ?>%; background: <?php echo $bar_color; ?>;"></div>
                                        </div>
                                        <span style="font-weight: 700; color: <?php echo $bar_color; ?>;"><?php echo $pct; ?>%</span>
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <a href="report.php?view=monthly&class_id=<?php echo $cr['id']; ?>&month=<?php echo $selected_month; ?>" class="btn btn-primary btn-sm">
                                        📊 Monthly Breakdown &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: /* Monthly Mode Analysis Dedicated View */ ?>

    <!-- Action Bar with Back to Class-Wise Attendance Performance Button -->
    <div class="report-action-bar">
        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <a href="report.php" class="btn btn-secondary btn-sm" title="Return to Class-Wise Attendance Performance Overview">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                &larr; Back to Class-Wise Attendance Performance
            </a>
            <a href="index.php?class_id=<?php echo $selected_class_id; ?>" class="btn btn-secondary btn-sm">
                📝 Daily Attendance Register
            </a>
        </div>

        <div style="font-size: 13px; color: var(--text-secondary); display: flex; align-items: center; gap: 6px;">
            <span>Attendance Analytics &gt;</span>
            <span class="badge badge-info" style="font-size: 12px;">
                <?php echo htmlspecialchars($current_class['class_name'] ?? 'Class'); ?> (Sec <?php echo htmlspecialchars($current_class['section'] ?? 'A'); ?>) · <?php echo $month_label; ?>
            </span>
        </div>
    </div>

    <!-- ========================================================
         2. Monthly Mode Analysis by Class & Student
         ======================================================== -->
    <div class="card" id="monthlyBreakdown" style="border: 1px solid rgba(99, 102, 241, 0.3); box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4), 0 0 15px rgba(99, 102, 241, 0.15);">
        <div class="card-header" style="background: linear-gradient(135deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.95) 100%);">
            <div class="card-title" style="display: flex; align-items: center; gap: 8px;">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="22" height="22" style="color: #818cf8;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
                <span>Monthly Mode Analysis: <strong><?php echo htmlspecialchars($current_class ? ($current_class['class_name'] . ' — Section ' . $current_class['section']) : 'All Classes'); ?></strong></span>
            </div>

            <div style="font-size: 13px; color: #818cf8; font-weight: 700;">
                📅 Period: <?php echo $month_label; ?>
            </div>
        </div>

        <!-- Custom Month & Class Selection Filter Bar -->
        <div class="month-filter-bar">
            <form method="GET" action="report.php" id="reportFilterForm" style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap; width: 100%; justify-content: space-between;">
                <input type="hidden" name="view" value="monthly">
                <input type="hidden" name="class_id" id="hiddenReportClassId" value="<?php echo $selected_class_id; ?>">
                <input type="hidden" name="month" id="hiddenReportMonth" value="<?php echo htmlspecialchars($selected_month); ?>">

                <div style="display: flex; align-items: center; gap: 14px; flex-wrap: wrap;">
                    <!-- 1. CUSTOM CLASS DROPDOWN -->
                    <div class="custom-att-dropdown" id="reportClassDropdown">
                        <button type="button" class="custom-dropdown-btn" id="reportClassDropdownBtn" onclick="toggleReportClassDropdown(event)">
                            <span class="custom-dropdown-icon">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="14" height="14">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                </svg>
                            </span>
                            <span class="class-title"><?php echo htmlspecialchars($current_class['class_name'] ?? 'Select Class'); ?></span>
                            <span class="section-pill"><?php echo htmlspecialchars($current_class ? ('Section ' . $current_class['section']) : ''); ?></span>
                            <svg class="custom-dropdown-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        
                        <div class="custom-dropdown-menu" id="reportClassDropdownMenu" onclick="event.stopPropagation()">
                            <div class="custom-dropdown-header">
                                <span>Select Class / Section</span>
                                <span class="count-pill"><?php echo count($all_classes); ?> Classes</span>
                            </div>
                            <div class="custom-dropdown-list">
                                <?php foreach ($all_classes as $c): 
                                    $is_active = ($selected_class_id == $c['id']);
                                ?>
                                    <div class="custom-dropdown-item <?php echo $is_active ? 'selected' : ''; ?>" onclick="selectReportClass('<?php echo $c['id']; ?>')">
                                        <div class="item-main">
                                            <span class="item-icon">🏫</span>
                                            <span class="item-name"><?php echo htmlspecialchars($c['class_name']); ?></span>
                                            <span class="item-sec">Sec <?php echo htmlspecialchars($c['section']); ?></span>
                                        </div>
                                        <?php if ($is_active): ?>
                                            <span class="item-check">✓</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- 2. CUSTOM MONTH PICKER -->
                    <div class="custom-att-monthpicker" id="reportMonthPicker">
                        <button type="button" class="custom-monthpicker-btn" id="reportMonthPickerBtn" onclick="toggleReportMonthPicker(event)">
                            <span class="custom-monthpicker-icon">
                                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="14" height="14">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <span class="month-main-str"><?php echo $month_label; ?></span>
                            <?php if ($is_current_month): ?>
                                <span class="current-month-indicator-pill">Current</span>
                            <?php endif; ?>
                            <svg class="custom-monthpicker-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        
                        <div class="custom-monthpicker-popover" id="reportMonthPopover" onclick="event.stopPropagation()">
                            <div class="mp-header">
                                <button type="button" class="mp-nav-btn" onclick="navigateReportYear(-1)" title="Previous Year">◀</button>
                                <span class="mp-year-label" id="mpYearLabel"><!-- Dynamically filled --></span>
                                <button type="button" class="mp-nav-btn" onclick="navigateReportYear(1)" title="Next Year">▶</button>
                            </div>
                            
                            <div class="mp-months-grid" id="mpMonthsGrid">
                                <!-- Dynamically rendered 12 months -->
                            </div>
                            
                            <div class="mp-quick-shortcuts">
                                <button type="button" class="mp-quick-btn" onclick="selectReportMonthQuick('<?php echo date('Y-m'); ?>')">📅 Current Month (<?php echo date('M Y'); ?>)</button>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Month Steppers -->
                    <div class="date-steppers">
                        <a href="report.php?view=monthly&class_id=<?php echo $selected_class_id; ?>&month=<?php echo $prev_month; ?>" class="date-stepper-btn" title="Previous Month">
                            ◀ Prev
                        </a>
                        <a href="report.php?view=monthly&class_id=<?php echo $selected_class_id; ?>&month=<?php echo $current_month_str; ?>" class="date-stepper-btn <?php echo $is_current_month ? 'active' : ''; ?>" title="Jump to Current Month">
                            📅 Current Month
                        </a>
                        <a href="report.php?view=monthly&class_id=<?php echo $selected_class_id; ?>&month=<?php echo $next_month; ?>" class="date-stepper-btn" title="Next Month">
                            Next ▶
                        </a>
                    </div>
                </div>

                <div style="font-size: 13px; color: var(--text-muted);">
                    Showing stats for <strong><?php echo $month_label; ?></strong>
                </div>
            </form>
        </div>

        <!-- Monthly Metric Cards for Selected Class -->
        <div style="padding: 20px 24px 10px 24px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
            <div style="background: rgba(15, 23, 42, 0.6); padding: 16px; border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.08);">
                <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Class Monthly Average</div>
                <div style="font-size: 24px; font-weight: 800; color: #34d399; margin-top: 4px;"><?php echo $class_monthly_avg; ?>%</div>
                <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;"><?php echo $class_total_present; ?> of <?php echo $class_total_sessions; ?> total student days</div>
            </div>

            <div style="background: rgba(15, 23, 42, 0.6); padding: 16px; border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.08);">
                <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Enrolled Students</div>
                <div style="font-size: 24px; font-weight: 800; color: #818cf8; margin-top: 4px;"><?php echo count($monthly_students); ?></div>
                <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;">Active in this roster</div>
            </div>

            <div style="background: rgba(15, 23, 42, 0.6); padding: 16px; border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.08);">
                <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Recorded Class Days</div>
                <div style="font-size: 24px; font-weight: 800; color: #38bdf8; margin-top: 4px;"><?php echo $unique_recorded_days; ?> Days</div>
                <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;">Logged in <?php echo $month_label; ?></div>
            </div>

            <div style="background: rgba(15, 23, 42, 0.6); padding: 16px; border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.08);">
                <div style="font-size: 12px; color: var(--text-muted); font-weight: 700; text-transform: uppercase;">Top Attendance Rate</div>
                <div style="font-size: 16px; font-weight: 800; color: #fbbf24; margin-top: 8px; word-break: break-word;">
                    <?php echo $top_performer ? htmlspecialchars($top_performer) : '—'; ?>
                </div>
                <div style="font-size: 11.5px; color: var(--text-secondary); margin-top: 2px;">Class best performer</div>
            </div>
        </div>

        <!-- Student-by-Student Monthly Attendance & Percentage Breakdown Table -->
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th class="col-roll-no">Roll No</th>
                        <th>Student Name</th>
                        <th>Gender</th>
                        <th style="text-align: center;">Present</th>
                        <th style="text-align: center;">Absent</th>
                        <th style="text-align: center;">Late</th>
                        <th style="text-align: center;">Excused</th>
                        <th style="text-align: center;">Total Sessions</th>
                        <th>Monthly Attendance %</th>
                        <th style="text-align: center;">Performance Tier</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($monthly_students)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; color: var(--text-muted); padding: 40px;">
                                No active students found in this class.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($monthly_students as $stu): 
                            $rate = $stu['attendance_rate'];
                            
                            // Performance Tier & Color
                            if ($rate >= 90) {
                                $tier_class = 'tier-excellent';
                                $tier_label = '🌟 Excellent (≥90%)';
                                $bar_c = '#10b981';
                            } elseif ($rate >= 75) {
                                $tier_class = 'tier-good';
                                $tier_label = '✅ Good (75-89%)';
                                $bar_c = '#0ea5e9';
                            } elseif ($rate >= 60) {
                                $tier_class = 'tier-warning';
                                $tier_label = '⚠️ Warning (60-74%)';
                                $bar_c = '#f59e0b';
                            } else {
                                $tier_class = 'tier-critical';
                                $tier_label = '🚨 Critical (<60%)';
                                $bar_c = '#ef4444';
                            }
                        ?>
                            <tr>
                                <td class="col-roll-no">
                                    <span class="badge badge-info" style="font-family: monospace;">
                                        <?php echo htmlspecialchars($stu['roll_no']); ?>
                                    </span>
                                </td>
                                <td style="font-weight: 600; color: #ffffff;">
                                    <?php echo htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']); ?>
                                </td>
                                <td style="color: var(--text-secondary); font-size: 13px;">
                                    <?php echo htmlspecialchars($stu['gender']); ?>
                                </td>
                                <td style="text-align: center;">
                                    <span style="color: #34d399; font-weight: 700; background: rgba(16, 185, 129, 0.12); padding: 3px 8px; border-radius: 5px;">
                                        <?php echo $stu['present_count']; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span style="color: #f87171; font-weight: 700; background: rgba(239, 68, 68, 0.12); padding: 3px 8px; border-radius: 5px;">
                                        <?php echo $stu['absent_count']; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span style="color: #fbbf24; font-weight: 700; background: rgba(245, 158, 11, 0.12); padding: 3px 8px; border-radius: 5px;">
                                        <?php echo $stu['late_count']; ?>
                                    </span>
                                </td>
                                <td style="text-align: center;">
                                    <span style="color: #38bdf8; font-weight: 700; background: rgba(14, 165, 233, 0.12); padding: 3px 8px; border-radius: 5px;">
                                        <?php echo $stu['excused_count']; ?>
                                    </span>
                                </td>
                                <td style="text-align: center; font-weight: 600; color: var(--text-secondary);">
                                    <?php echo $stu['total_sessions']; ?>
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="att-progress-track">
                                            <div class="att-progress-fill" style="width: <?php echo $rate; ?>%; background: <?php echo $bar_c; ?>;"></div>
                                        </div>
                                        <span style="font-weight: 700; color: <?php echo $bar_c; ?>;"><?php echo $rate; ?>%</span>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="tier-badge <?php echo $tier_class; ?>">
                                        <?php echo $tier_label; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div style="padding: 14px 24px; border-top: 1px solid var(--border-color); background: rgba(15, 23, 42, 0.4); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
            <a href="report.php" class="btn btn-secondary btn-sm">
                &larr; Back to Class-Wise Attendance Performance
            </a>
            <a href="index.php?class_id=<?php echo $selected_class_id; ?>" class="btn btn-primary btn-sm">
                ✏️ Take / Edit Attendance for this Class &rarr;
            </a>
        </div>
    </div>

<?php endif; ?>

<script>
// Custom Dropdown & Month Picker Handlers for Attendance Analytics Page Only
function toggleReportClassDropdown(e) {
    e.stopPropagation();
    var btn = document.getElementById('reportClassDropdownBtn');
    var menu = document.getElementById('reportClassDropdownMenu');
    var isOpen = menu.classList.contains('show');
    
    closeReportPopovers();
    
    if (!isOpen) {
        menu.classList.add('show');
        btn.classList.add('open');
    }
}

function selectReportClass(classId) {
    document.getElementById('hiddenReportClassId').value = classId;
    document.getElementById('reportFilterForm').submit();
}

// Month Picker Logic
var activeSelectedMonth = "<?php echo $selected_month; ?>";
var mpYear = parseInt(activeSelectedMonth.split('-')[0]) || new Date().getFullYear();
var mpMonthIndex = (parseInt(activeSelectedMonth.split('-')[1]) || (new Date().getMonth() + 1)) - 1;

var shortMonthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

function toggleReportMonthPicker(e) {
    e.stopPropagation();
    var btn = document.getElementById('reportMonthPickerBtn');
    var popover = document.getElementById('reportMonthPopover');
    var isOpen = popover.classList.contains('show');
    
    closeReportPopovers();
    
    if (!isOpen) {
        renderMonthPicker(mpYear);
        popover.classList.add('show');
        btn.classList.add('open');
    }
}

function navigateReportYear(delta) {
    mpYear += delta;
    renderMonthPicker(mpYear);
}

function renderMonthPicker(year) {
    var yearLabel = document.getElementById('mpYearLabel');
    if (yearLabel) yearLabel.innerText = year;
    
    var grid = document.getElementById('mpMonthsGrid');
    if (!grid) return;
    grid.innerHTML = '';
    
    var currentYearStr = new Date().getFullYear();
    var currentMonthIdx = new Date().getMonth();
    
    for (var m = 0; m < 12; m++) {
        var mm = (m + 1) < 10 ? '0' + (m + 1) : (m + 1);
        var monthVal = year + '-' + mm;
        
        var cell = document.createElement('div');
        cell.className = 'mp-month-cell';
        if (monthVal === activeSelectedMonth) cell.classList.add('selected-month');
        if (year === currentYearStr && m === currentMonthIdx) cell.classList.add('current-month');
        
        cell.innerText = shortMonthNames[m];
        cell.title = shortMonthNames[m] + ' ' + year;
        (function(targetMonthVal) {
            cell.onclick = function(e) {
                e.stopPropagation();
                selectReportMonthQuick(targetMonthVal);
            };
        })(monthVal);
        
        grid.appendChild(cell);
    }
}

function selectReportMonthQuick(monthVal) {
    document.getElementById('hiddenReportMonth').value = monthVal;
    document.getElementById('reportFilterForm').submit();
}

function closeReportPopovers() {
    var classMenu = document.getElementById('reportClassDropdownMenu');
    var classBtn = document.getElementById('reportClassDropdownBtn');
    if (classMenu) classMenu.classList.remove('show');
    if (classBtn) classBtn.classList.remove('open');
    
    var monthPopover = document.getElementById('reportMonthPopover');
    var monthBtn = document.getElementById('reportMonthPickerBtn');
    if (monthPopover) monthPopover.classList.remove('show');
    if (monthBtn) monthBtn.classList.remove('open');
}

// Global click & escape handler
document.addEventListener('click', function() {
    closeReportPopovers();
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeReportPopovers();
    }
});
</script>

<?php include "../includes/footer.php"; ?>
