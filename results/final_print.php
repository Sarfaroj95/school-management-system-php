<?php
/**
 * Official Class Merit Ranking & Final Examination Tabulation Sheet
 * Class-wise ranking based on total marks obtained, subject breakdowns, and institutional grading
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

// Allow Super Admin, Admin, Staff, and Teacher
require_role(['Super Admin', 'Admin', 'Staff', 'Teacher']);

// Fetch System Settings for Institutional Branding
$settings = [];
$set_res = mysqli_query($conn, "SELECT setting_key, setting_value FROM system_settings");
if ($set_res) {
    while ($row = mysqli_fetch_assoc($set_res)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

$school_name    = $settings['school_name'] ?? 'KRISHNAPUR PRIMARY SCHOOL';
$school_email   = $settings['school_email'] ?? 'contact@educore-sms.edu';
$school_phone   = $settings['school_phone'] ?? '+91 (555) 019-2834';
$school_address = $settings['school_address'] ?? 'RGGM+2V9, Krishnapur, Chandrakona, Krishnapur, West Bengal 721242';
$academic_year  = $settings['academic_year'] ?? '2026-2027';
$school_board   = $settings['school_board'] ?? 'Official Academic Board of Education';
$principal_name = $settings['principal_name'] ?? 'Head of Institution';

// Fetch all available classes for selector
$classes_res = mysqli_query($conn, "SELECT id, class_name, section FROM classes ORDER BY class_name, section ASC");
$all_classes = [];
if ($classes_res) {
    while ($cl = mysqli_fetch_assoc($classes_res)) {
        $all_classes[] = $cl;
    }
}

// Resolve selected class_id
$class_id = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
if ($class_id <= 0 && !empty($all_classes)) {
    $class_id = intval($all_classes[0]['id']);
}

// Fetch class details and assigned class teacher
$class_info = null;
if ($class_id > 0) {
    $c_stmt = $conn->prepare("SELECT c.*, t.name AS teacher_name, t.email AS teacher_email 
                             FROM classes c 
                             LEFT JOIN teachers t ON c.teacher_id = t.id 
                             WHERE c.id = ? LIMIT 1");
    $c_stmt->bind_param("i", $class_id);
    $c_stmt->execute();
    $class_info = $c_stmt->get_result()->fetch_assoc();
    $c_stmt->close();
}

// Fetch available examinations for this class
$available_exams = [];
if ($class_id > 0) {
    $ex_stmt = $conn->prepare("SELECT DISTINCT m.exam_name 
                               FROM marks m 
                               JOIN students s ON m.student_id = s.id 
                               WHERE s.class_id = ? AND m.exam_name != '' 
                               ORDER BY m.exam_name ASC");
    $ex_stmt->bind_param("i", $class_id);
    $ex_stmt->execute();
    $ex_res = $ex_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $ex_stmt->close();
    $available_exams = array_column($ex_res, 'exam_name');
}

// If no exams found for this class, check global distinct exams
if (empty($available_exams)) {
    $g_exams_res = mysqli_query($conn, "SELECT DISTINCT exam_name FROM marks WHERE exam_name != '' ORDER BY exam_name ASC");
    if ($g_exams_res) {
        while ($g_ex = mysqli_fetch_assoc($g_exams_res)) {
            $available_exams[] = $g_ex['exam_name'];
        }
    }
}
if (empty($available_exams)) {
    $available_exams = ['Midterm Exam', 'Final Term Exam', 'Unit Test 1', 'Unit Test 2'];
}

// Resolve selected examination
$exam_param = isset($_GET['exam']) ? trim($_GET['exam']) : '';
if (empty($exam_param)) {
    $exam_param = $available_exams[0] ?? 'Final Term Exam';
}

// Fetch subjects for this class
$subjects = [];
if ($class_id > 0) {
    $sub_stmt = $conn->prepare("SELECT id, subject_name, subject_code FROM subjects WHERE class_id = ? ORDER BY subject_name ASC");
    $sub_stmt->bind_param("i", $class_id);
    $sub_stmt->execute();
    $subjects = $sub_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $sub_stmt->close();

    // If no direct class subjects found, fetch distinct subjects that students of this class have marks for
    if (empty($subjects)) {
        $sub_stmt2 = $conn->prepare("SELECT DISTINCT sub.id, sub.subject_name, sub.subject_code 
                                     FROM marks m 
                                     JOIN subjects sub ON m.subject_id = sub.id 
                                     JOIN students s ON m.student_id = s.id 
                                     WHERE s.class_id = ? 
                                     ORDER BY sub.subject_name ASC");
        $sub_stmt2->bind_param("i", $class_id);
        $sub_stmt2->execute();
        $subjects = $sub_stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        $sub_stmt2->close();
    }
}

// Fetch active students belonging to this class
$students = [];
if ($class_id > 0) {
    $st_stmt = $conn->prepare("SELECT id, roll_no, first_name, last_name, gender, dob, parent_name, phone 
                              FROM students 
                              WHERE class_id = ? AND status = 'Active' 
                              ORDER BY roll_no ASC");
    $st_stmt->bind_param("i", $class_id);
    $st_stmt->execute();
    $students = $st_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $st_stmt->close();
}

// Fetch marks for this class and examination
$marks_raw = [];
if ($class_id > 0) {
    if (!empty($exam_param) && $exam_param !== 'All Examinations') {
        $m_stmt = $conn->prepare("SELECT m.student_id, m.subject_id, m.marks_obtained, m.max_marks, m.grade, m.remarks, m.exam_name 
                                  FROM marks m 
                                  JOIN students s ON m.student_id = s.id 
                                  WHERE s.class_id = ? AND m.exam_name = ?");
        $m_stmt->bind_param("is", $class_id, $exam_param);
    } else {
        $m_stmt = $conn->prepare("SELECT m.student_id, m.subject_id, m.marks_obtained, m.max_marks, m.grade, m.remarks, m.exam_name 
                                  FROM marks m 
                                  JOIN students s ON m.student_id = s.id 
                                  WHERE s.class_id = ?");
        $m_stmt->bind_param("i", $class_id);
    }
    $m_stmt->execute();
    $marks_raw = $m_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $m_stmt->close();
}

// Structure marks lookup: $marks_map[student_id][subject_id] = [obtained, max, grade]
$marks_map = [];
foreach ($marks_raw as $mr) {
    $sid = intval($mr['student_id']);
    $subid = intval($mr['subject_id']);
    $marks_map[$sid][$subid] = [
        'obtained'  => floatval($mr['marks_obtained']),
        'max'       => floatval($mr['max_marks']),
        'grade'     => $mr['grade'],
        'remarks'   => $mr['remarks']
    ];
}

// Build evaluation and calculate totals for ranking
$ranked_students = [];
$class_total_obtained = 0;
$class_total_max = 0;
$class_total_passed = 0;
$class_total_failed = 0;
$class_highest_score = 0;
$class_topper_name = 'N/A';

foreach ($students as $st) {
    $sid = intval($st['id']);
    $total_obt = 0;
    $total_mx = 0;
    $has_fail_subject = false;
    $evaluated_subjects = 0;

    foreach ($subjects as $sub) {
        $subid = intval($sub['id']);
        if (isset($marks_map[$sid][$subid])) {
            $obt = $marks_map[$sid][$subid]['obtained'];
            $mx = $marks_map[$sid][$subid]['max'];
            $total_obt += $obt;
            $total_mx += $mx;
            $evaluated_subjects++;

            if (in_array($marks_map[$sid][$subid]['grade'], ['E', 'F']) || ($mx > 0 && ($obt / $mx) < 0.35)) {
                $has_fail_subject = true;
            }
        }
    }

    $pct = ($total_mx > 0) ? round(($total_obt / $total_mx) * 100, 2) : 0;

    // Determine Cumulative Letter Grade based on Marks / Percentage:
    // 80-100: A (Very Good), 65-79: B (Good), 50-64: C (Satisfactory), 35-49: D (Average), Below 35: E (Not Satisfactory)
    if ($pct >= 80) $grade = 'A';
    elseif ($pct >= 65) $grade = 'B';
    elseif ($pct >= 50) $grade = 'C';
    elseif ($pct >= 35) $grade = 'D';
    else $grade = 'E';

    // Determine Result Status
    if ($evaluated_subjects === 0) {
        $status = 'Not Evaluated';
        $status_badge = 'neutral';
    } elseif ($has_fail_subject || in_array($grade, ['E', 'F'])) {
        $status = 'Not Satisfactory / Backlog';
        $status_badge = 'danger';
        $class_total_failed++;
    } elseif ($pct >= 80) {
        $status = 'Passed - Very Good';
        $status_badge = 'success';
        $class_total_passed++;
    } elseif ($pct >= 65) {
        $status = 'Passed - Good';
        $status_badge = 'success';
        $class_total_passed++;
    } elseif ($pct >= 50) {
        $status = 'Passed - Satisfactory';
        $status_badge = 'success';
        $class_total_passed++;
    } else {
        $status = 'Passed - Average';
        $status_badge = 'success';
        $class_total_passed++;
    }

    if ($total_obt > $class_highest_score && $evaluated_subjects > 0) {
        $class_highest_score = $total_obt;
        $class_topper_name = $st['first_name'] . ' ' . $st['last_name'] . ' (' . $st['roll_no'] . ')';
    }

    $class_total_obtained += $total_obt;
    $class_total_max += $total_mx;

    $ranked_students[] = [
        'id'                 => $st['id'],
        'roll_no'            => $st['roll_no'],
        'first_name'         => $st['first_name'],
        'last_name'          => $st['last_name'],
        'gender'             => $st['gender'],
        'total_obtained'     => $total_obt,
        'total_max'          => $total_mx,
        'percentage'         => $pct,
        'grade'              => $grade,
        'status'             => $status,
        'status_badge'       => $status_badge,
        'has_fail'           => $has_fail_subject,
        'evaluated_subjects' => $evaluated_subjects
    ];
}

// Sort Students in DESCENDING order of Total Marks Obtained (Ranking Algorithm)
usort($ranked_students, function ($a, $b) {
    if ($b['total_obtained'] != $a['total_obtained']) {
        return ($b['total_obtained'] <=> $a['total_obtained']);
    }
    if ($b['percentage'] != $a['percentage']) {
        return ($b['percentage'] <=> $a['percentage']);
    }
    return strnatcmp($a['roll_no'], $b['roll_no']);
});

// Calculate Overall Class Averages
$total_enrolled = count($students);
$class_pass_rate = ($total_enrolled > 0) ? round(($class_total_passed / $total_enrolled) * 100, 1) : 0;
$class_avg_percentage = ($class_total_max > 0) ? round(($class_total_obtained / $class_total_max) * 100, 1) : 0;
$print_theme = $_SESSION['theme_mode'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($print_theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Class Merit Tabulation Sheet - <?php echo htmlspecialchars($class_info['class_name'] ?? 'Class') . ' ' . htmlspecialchars($class_info['section'] ?? ''); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --secondary: #0ea5e9;
            --text-main: #0f172a;
            --text-muted: #475569;
            --border-light: #cbd5e1;
            --border-dark: #1e293b;
            --bg-sheet: #ffffff;
            --bg-screen: #0b0f19;
            --bg-toolbar: #111827;
            --border-toolbar: rgba(255, 255, 255, 0.1);
            --text-toolbar: #f8fafc;
        }

        [data-theme="light"] {
            --bg-screen: #f1f5f9;
            --bg-toolbar: #ffffff;
            --border-toolbar: #cbd5e1;
            --text-toolbar: #0f172a;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--bg-screen);
            color: var(--text-toolbar);
            min-height: 100vh;
            padding: 24px 16px 60px 16px;
            line-height: 1.4;
        }

        /* Top Screen Controls Toolbar */
        .no-print-toolbar {
            max-width: 1200px;
            margin: 0 auto 20px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 14px;
            background: var(--bg-toolbar);
            padding: 10px 18px;
            border-radius: 12px;
            border: 1px solid var(--border-toolbar);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            color: var(--text-toolbar);
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
            flex: 1;
        }

        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-shrink: 0;
        }

        @media (max-width: 960px) {
            .no-print-toolbar {
                flex-wrap: wrap;
            }
            .toolbar-left, .toolbar-right {
                flex-wrap: wrap;
                width: 100%;
                justify-content: flex-start;
            }
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 13px;
            font-size: 12.5px;
            font-weight: 600;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s ease;
            border: 1px solid transparent;
            white-space: nowrap;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4f46e5 0%, #06b6d4 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.35);
        }

        .btn-primary:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background: #1e293b;
            color: #e2e8f0;
            border-color: rgba(255, 255, 255, 0.12);
        }

        .btn-secondary:hover {
            background: #334155;
            color: #ffffff;
        }

        [data-theme="light"] .btn-secondary {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        [data-theme="light"] .btn-secondary:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        /* Theme Toggle Button */
        .theme-toggle-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            height: 38px;
            border-radius: 9999px;
            cursor: pointer;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: #f8fafc;
            font-family: inherit;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
            outline: none;
            user-select: none;
        }

        .theme-toggle-btn:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.25);
            transform: translateY(-1px);
        }

        .theme-icon-sun,
        .theme-icon-moon {
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.25s ease;
        }

        .theme-icon-sun {
            color: #f59e0b;
        }

        .theme-icon-moon {
            color: #818cf8;
        }

        [data-theme="dark"] .theme-icon-sun,
        :root:not([data-theme="light"]) .theme-icon-sun {
            display: none;
        }

        [data-theme="dark"] .theme-icon-moon,
        :root:not([data-theme="light"]) .theme-icon-moon {
            display: flex;
        }

        [data-theme="light"] .theme-icon-moon,
        body.light-theme .theme-icon-moon {
            display: none;
        }

        [data-theme="light"] .theme-icon-sun,
        body.light-theme .theme-icon-sun {
            display: flex;
        }

        [data-theme="light"] .theme-toggle-btn,
        body.light-theme .theme-toggle-btn {
            background: #ffffff;
            border-color: #cbd5e1;
            color: #0f172a;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        [data-theme="light"] .theme-toggle-btn:hover,
        body.light-theme .theme-toggle-btn:hover {
            background: #f8fafc;
            border-color: #94a3b8;
        }

        /* Custom Select Component for Class and Examination */
        .custom-select-wrapper {
            position: relative;
            display: inline-block;
            user-select: none;
            width: 100%;
        }

        .custom-select-trigger {
            min-height: 36px;
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            padding: 6px 12px;
            background: linear-gradient(145deg, rgba(30, 41, 59, 0.9) 0%, rgba(15, 23, 42, 0.95) 100%);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 8px;
            color: #f8fafc;
            cursor: pointer;
            font-family: inherit;
            transition: all 0.22s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.08);
            box-sizing: border-box;
            text-align: left;
        }

        .custom-select-trigger:hover {
            border-color: rgba(99, 102, 241, 0.5);
            background: linear-gradient(145deg, rgba(37, 51, 74, 0.95) 0%, rgba(18, 28, 48, 0.98) 100%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.35), 0 0 10px rgba(99, 102, 241, 0.2);
            transform: translateY(-1px);
        }

        .custom-select-trigger.open {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.3), 0 6px 18px rgba(0, 0, 0, 0.4);
        }

        .custom-select-left {
            display: flex;
            align-items: center;
            gap: 8px;
            overflow: hidden;
            flex: 1;
        }

        .custom-select-icon {
            width: 20px;
            height: 20px;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(99, 102, 241, 0.18);
            color: #818cf8;
            border: 1px solid rgba(99, 102, 241, 0.3);
            flex-shrink: 0;
            font-size: 11px;
        }

        .custom-select-label {
            font-size: 13px;
            font-weight: 600;
            color: #f8fafc;
            letter-spacing: -0.2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .custom-select-chevron {
            color: #94a3b8;
            transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
            flex-shrink: 0;
        }

        .custom-select-trigger.open .custom-select-chevron {
            transform: rotate(180deg);
            color: #818cf8;
        }

        .custom-select-wrapper.open {
            z-index: 1050;
            position: relative;
        }

        .custom-select-dropdown {
            position: absolute;
            top: calc(100% + 6px);
            left: 0;
            width: 100%;
            min-width: 240px;
            background: rgba(15, 23, 42, 0.98);
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            padding: 8px;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.75), 0 0 20px rgba(99, 102, 241, 0.2);
            backdrop-filter: blur(20px);
            z-index: 1050;
            opacity: 0;
            transform: translateY(8px) scale(0.98);
            pointer-events: none;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
            box-sizing: border-box;
        }

        .custom-select-dropdown.dropup {
            top: auto;
            bottom: calc(100% + 6px);
            transform: translateY(-8px) scale(0.98);
        }

        .custom-select-dropdown.dropup.show {
            transform: translateY(0) scale(1);
        }

        .custom-select-dropdown.show {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .custom-select-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 10px 8px 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #818cf8;
        }

        .custom-select-header .count-pill {
            background: rgba(99, 102, 241, 0.15);
            color: #a5b4fc;
            padding: 2px 7px;
            border-radius: 6px;
            font-size: 10px;
        }

        .custom-select-search-wrap {
            position: relative;
            padding: 2px 4px 8px 4px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            margin-bottom: 6px;
        }

        .custom-select-search-icon {
            position: absolute;
            left: 14px;
            top: 11px;
            color: #94a3b8;
            width: 14px;
            height: 14px;
            pointer-events: none;
        }

        .custom-select-search-input {
            width: 100%;
            padding: 7px 10px 7px 32px;
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 6px;
            color: #ffffff;
            font-size: 12.5px;
            outline: none;
            font-family: inherit;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }

        .custom-select-search-input:focus {
            border-color: #6366f1;
            background: rgba(0, 0, 0, 0.5);
        }

        .custom-select-options-list {
            max-height: 220px;
            overflow-y: auto;
            overflow-x: hidden;
            scrollbar-width: thin;
            scrollbar-color: transparent transparent;
            transition: scrollbar-color 0.3s ease;
        }

        .custom-select-options-list:hover {
            scrollbar-color: rgba(255, 255, 255, 0.25) transparent;
        }

        .custom-select-options-list::-webkit-scrollbar {
            width: 5px;
        }
        .custom-select-options-list::-webkit-scrollbar-thumb {
            background: transparent;
            border-radius: 9999px;
        }
        .custom-select-options-list:hover::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25);
        }

        .custom-select-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s ease;
            color: #e2e8f0;
            margin-bottom: 2px;
            border: 1px solid transparent;
            font-size: 13px;
            box-sizing: border-box;
            width: 100%;
        }

        .custom-select-option > span:first-child {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            flex: 1;
        }

        .custom-select-option:hover {
            background: rgba(99, 102, 241, 0.18);
            border-color: rgba(99, 102, 241, 0.35);
            color: #ffffff;
            transform: translateX(2px);
        }

        .custom-select-option.selected {
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.3) 0%, rgba(79, 70, 229, 0.22) 100%);
            border-color: rgba(99, 102, 241, 0.5);
            color: #ffffff;
            font-weight: 700;
        }

        .custom-select-option .opt-check {
            color: #34d399;
            font-weight: 800;
            font-size: 13px;
        }

        .custom-select-empty {
            padding: 12px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
        }

        /* Light Mode Custom Select Overrides for Class & Examination */
        [data-theme="light"] .custom-select-trigger,
        body.light-theme .custom-select-trigger {
            background: #ffffff;
            border-color: #cbd5e1;
            color: #0f172a;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        [data-theme="light"] .custom-select-trigger:hover,
        body.light-theme .custom-select-trigger:hover {
            background: #f8fafc;
            border-color: #94a3b8;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.08);
        }

        [data-theme="light"] .custom-select-trigger.open,
        body.light-theme .custom-select-trigger.open {
            background: #ffffff;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.15);
        }

        [data-theme="light"] .custom-select-label,
        body.light-theme .custom-select-label {
            color: #0f172a !important;
        }

        [data-theme="light"] .custom-select-icon,
        body.light-theme .custom-select-icon {
            background: rgba(79, 70, 229, 0.08);
            color: #4f46e5;
            border-color: rgba(79, 70, 229, 0.2);
        }

        [data-theme="light"] .custom-select-chevron,
        body.light-theme .custom-select-chevron {
            color: #64748b;
        }

        [data-theme="light"] .custom-select-trigger.open .custom-select-chevron,
        body.light-theme .custom-select-trigger.open .custom-select-chevron {
            color: #4f46e5;
        }

        [data-theme="light"] .custom-select-dropdown,
        body.light-theme .custom-select-dropdown {
            background: #ffffff;
            border-color: #e2e8f0;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.12), 0 0 20px rgba(79, 70, 229, 0.08);
        }

        [data-theme="light"] .custom-select-header,
        body.light-theme .custom-select-header {
            border-bottom-color: #f1f5f9;
            color: #4f46e5;
        }

        [data-theme="light"] .custom-select-header .count-pill,
        body.light-theme .custom-select-header .count-pill {
            background: rgba(79, 70, 229, 0.1);
            color: #4f46e5;
        }

        [data-theme="light"] .custom-select-search-wrap,
        body.light-theme .custom-select-search-wrap {
            background: #ffffff;
            border-bottom-color: #f1f5f9;
        }

        [data-theme="light"] .custom-select-search-icon,
        body.light-theme .custom-select-search-icon {
            color: #94a3b8;
        }

        [data-theme="light"] .custom-select-search-input,
        body.light-theme .custom-select-search-input {
            background: #f8fafc;
            border-color: #e2e8f0;
            color: #0f172a;
        }

        [data-theme="light"] .custom-select-search-input:focus,
        body.light-theme .custom-select-search-input:focus {
            background: #ffffff;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        [data-theme="light"] .custom-select-options-list:hover::-webkit-scrollbar-thumb,
        body.light-theme .custom-select-options-list:hover::-webkit-scrollbar-thumb {
            background: #cbd5e1;
        }

        [data-theme="light"] .custom-select-option,
        body.light-theme .custom-select-option {
            color: #334155;
        }

        [data-theme="light"] .custom-select-option:hover,
        body.light-theme .custom-select-option:hover {
            background: #f1f5f9;
            color: #0f172a;
            border-color: #e2e8f0;
        }

        [data-theme="light"] .custom-select-option.selected,
        body.light-theme .custom-select-option.selected {
            background: rgba(79, 70, 229, 0.08);
            color: #4f46e5;
            border-color: rgba(79, 70, 229, 0.2);
            font-weight: 700;
        }

        [data-theme="light"] .custom-select-empty,
        body.light-theme .custom-select-empty {
            color: #94a3b8;
        }

        /* Printable Sheet Container */
        .tabulation-page {
            max-width: 1200px;
            margin: 0 auto;
            background: var(--bg-sheet);
            color: var(--text-main);
            padding: 28px 36px;
            border-radius: 12px;
            box-shadow: 0 20px 45px rgba(0,0,0,0.5);
            border: 2px solid #cbd5e1;
        }

        /* Certificate Border */
        .tabulation-inner-border {
            border: 2px double #0f172a;
            padding: 20px 24px;
            background: #ffffff;
        }

        /* Institution Header */
        .inst-header {
            display: grid;
            grid-template-columns: 70px 1fr 70px;
            align-items: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 14px;
            margin-bottom: 14px;
            text-align: center;
        }

        .inst-logo {
            width: 62px;
            height: 62px;
            border-radius: 50%;
            background: #1e1b4b;
            color: #f59e0b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            font-weight: 800;
            border: 2px solid #d97706;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .inst-title h1 {
            font-size: 21px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1e1b4b;
            margin-bottom: 2px;
        }

        .inst-subtitle {
            font-size: 11.5px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #4338ca;
            margin-bottom: 3px;
        }

        .inst-meta {
            font-size: 11px;
            color: #475569;
            line-height: 1.3;
        }

        /* Tabulation Banner */
        .tabulation-banner {
            background: #1e1b4b;
            color: #ffffff;
            text-align: center;
            padding: 6px 14px;
            margin-bottom: 14px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .tabulation-banner h2 {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .tabulation-banner span {
            font-size: 11.5px;
            font-weight: 600;
            color: #cbd5e1;
        }

        /* Class Info Sub-header */
        .class-meta-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px 16px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            border-radius: 6px;
            margin-bottom: 16px;
            font-size: 12px;
        }

        .meta-item {
            display: flex;
            gap: 6px;
        }

        .meta-label {
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            font-size: 10.5px;
        }

        .meta-val {
            font-weight: 700;
            color: #0f172a;
        }

        /* Tabulation Matrix Table */
        .tabulation-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
            font-size: 11.5px;
        }

        .tabulation-table th, .tabulation-table td {
            border: 1px solid #94a3b8;
            padding: 6px 8px;
            text-align: left;
        }

        .tabulation-table th {
            background: #f1f5f9;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.2px;
        }

        .tabulation-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .tabulation-table .text-center {
            text-align: center;
        }

        .tabulation-table .text-right {
            text-align: right;
        }

        .tabulation-table .font-bold {
            font-weight: 700;
        }

        /* Rank Badges */
        .rank-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            border-radius: 4px;
            padding: 2px 6px;
            font-size: 11px;
        }

        .rank-gold {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #f59e0b;
        }

        .rank-silver {
            background: #f1f5f9;
            color: #334155;
            border: 1px solid #94a3b8;
        }

        .rank-bronze {
            background: #ffedd5;
            color: #9a3412;
            border: 1px solid #ea580c;
        }

        .rank-regular {
            color: #1e293b;
            font-weight: 700;
        }

        /* Performance Analytics KPIs */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 18px;
        }

        .kpi-card {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 8px 12px;
            border-radius: 6px;
            text-align: center;
        }

        .kpi-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 2px;
        }

        .kpi-val {
            font-size: 16px;
            font-weight: 800;
            color: #0f172a;
        }

        /* Signatures Area */
        .signatures-area {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-top: 30px;
            padding-top: 16px;
            text-align: center;
        }

        .sig-line {
            border-top: 1.5px solid #334155;
            padding-top: 5px;
            font-size: 11px;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
        }

        .sig-sub {
            font-size: 9.5px;
            font-weight: 500;
            color: #64748b;
        }

        .seal-box {
            width: 68px;
            height: 68px;
            border: 1.5px dashed #94a3b8;
            border-radius: 50%;
            margin: 0 auto 4px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 8.5px;
            color: #94a3b8;
            text-transform: uppercase;
            font-weight: 600;
            text-align: center;
            padding: 3px;
        }

        .doc-footer {
            margin-top: 14px;
            border-top: 1px solid #e2e8f0;
            padding-top: 6px;
            display: flex;
            justify-content: space-between;
            font-size: 9.5px;
            color: #64748b;
        }

        /* Print Media Layout */
        @media print {
            body {
                background-color: #ffffff !important;
                color: #000000 !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .no-print-toolbar {
                display: none !important;
            }

            .tabulation-page {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }

            .tabulation-inner-border {
                border: 2px solid #000000 !important;
                padding: 14px 18px !important;
            }

            .tabulation-banner {
                background: #1e1b4b !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .tabulation-table th {
                background: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @page {
                size: A4 landscape;
                margin: 10mm 8mm;
            }
        }
    </style>
</head>
<body>

    <!-- Top Screen Action Bar (Hidden during print) -->
    <div class="no-print-toolbar">
        <div class="toolbar-left">
            <a href="index.php" onclick="closeTabOrGoBack(event);" class="btn btn-secondary">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Grade Book
            </a>

            <!-- Class Selector -->
            <div style="min-width: 175px;">
                <select id="classFilterSelect" class="custom-select-target" onchange="location.href='final_print.php?class_id=' + this.value + '&exam=' + encodeURIComponent('<?php echo addslashes($exam_param); ?>')">
                    <?php foreach ($all_classes as $cl): ?>
                        <option value="<?php echo $cl['id']; ?>" <?php echo ($class_id == $cl['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cl['class_name'] . ' (' . $cl['section'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Examination Selector -->
            <div style="min-width: 200px;">
                <select id="examFilterSelect" class="custom-select-target" onchange="location.href='final_print.php?class_id=<?php echo $class_id; ?>&exam=' + encodeURIComponent(this.value)">
                    <option value="All Examinations" <?php echo ($exam_param === 'All Examinations') ? 'selected' : ''; ?>>All Examinations (Summary)</option>
                    <?php foreach ($available_exams as $ex_n): ?>
                        <option value="<?php echo htmlspecialchars($ex_n); ?>" <?php echo ($exam_param === $ex_n) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($ex_n); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="toolbar-right">
            <a href="print.php" class="btn btn-secondary" title="Print Individual Student Marksheet">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
                Individual Marksheet
            </a>

            <button onclick="window.print()" class="btn btn-primary">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print Tabulation & Merit Sheet
            </button>
        </div>
    </div>

    <!-- Official Tabulation & Merit Sheet -->
    <div class="tabulation-page">
        <div class="tabulation-inner-border">
            
            <!-- Institution Header -->
            <div class="inst-header">
                <div class="inst-logo">
                    🎓
                </div>
                <div class="inst-title">
                    <h1><?php echo htmlspecialchars($school_name); ?></h1>
                    <div class="inst-subtitle"><?php echo htmlspecialchars($school_board); ?></div>
                    <div class="inst-meta">
                        <?php echo htmlspecialchars($school_address); ?><br>
                        Tel: <?php echo htmlspecialchars($school_phone); ?> | Email: <?php echo htmlspecialchars($school_email); ?>
                    </div>
                </div>
                <div class="inst-logo" style="font-size: 26px;">
                    🏛️
                </div>
            </div>

            <!-- Banner Title -->
            <div class="tabulation-banner">
                <h2>
                    OFFICIAL CLASS MERIT LIST & EXAMINATION TABULATION SHEET - <?php echo htmlspecialchars(strtoupper($exam_param)); ?>
                </h2>
                <span>Academic Session: <?php echo htmlspecialchars($academic_year); ?></span>
            </div>

            <!-- Class & Evaluation Metadata Strip -->
            <div class="class-meta-grid">
                <div class="meta-item">
                    <span class="meta-label">Class & Section:</span>
                    <span class="meta-val"><?php echo htmlspecialchars(($class_info['class_name'] ?? 'Class') . ' - Section ' . ($class_info['section'] ?? 'A')); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Class Teacher:</span>
                    <span class="meta-val"><?php echo htmlspecialchars($class_info['teacher_name'] ?? 'Faculty In-charge'); ?></span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Total Enrolled:</span>
                    <span class="meta-val"><?php echo $total_enrolled; ?> Students</span>
                </div>
                <div class="meta-item">
                    <span class="meta-label">Date of Issue:</span>
                    <span class="meta-val"><?php echo date('F d, Y'); ?></span>
                </div>
            </div>

            <!-- Tabulation & Ranking Matrix Table -->
            <table class="tabulation-table">
                <thead>
                    <tr>
                        <th style="width: 48px;" class="text-center">Rank</th>
                        <th style="width: 75px;">Roll No</th>
                        <th style="min-width: 140px;">Student Full Name</th>
                        
                        <!-- Dynamic Subject Headers -->
                        <?php foreach ($subjects as $sub): ?>
                            <th class="text-center" style="min-width: 70px;">
                                <?php echo htmlspecialchars($sub['subject_name']); ?>
                                <span style="font-size: 9.5px; color: #64748b; display: block; font-weight: 500;">
                                    (<?php echo htmlspecialchars($sub['subject_code']); ?>)
                                </span>
                            </th>
                        <?php endforeach; ?>

                        <th class="text-center" style="width: 85px;">Total Marks</th>
                        <th class="text-center" style="width: 60px;">%</th>
                        <th class="text-center" style="width: 50px;">Grade</th>
                        <th style="min-width: 120px;">Result Standing</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ranked_students)): ?>
                        <tr>
                            <td colspan="<?php echo count($subjects) + 7; ?>" class="text-center" style="padding: 30px; color: #64748b;">
                                No students or examination records found for this class and examination.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        $rank_pos = 1;
                        foreach ($ranked_students as $st): 
                            $sid = intval($st['id']);
                        ?>
                            <tr>
                                <!-- Rank Column with Top 3 Badges -->
                                <td class="text-center">
                                    <?php if ($st['evaluated_subjects'] > 0 && !$st['has_fail']): ?>
                                        <?php if ($rank_pos === 1): ?>
                                            <span class="rank-badge rank-gold">🥇 1st</span>
                                        <?php elseif ($rank_pos === 2): ?>
                                            <span class="rank-badge rank-silver">🥈 2nd</span>
                                        <?php elseif ($rank_pos === 3): ?>
                                            <span class="rank-badge rank-bronze">🥉 3rd</span>
                                        <?php else: ?>
                                            <span class="rank-regular">#<?php echo $rank_pos; ?></span>
                                        <?php endif; ?>
                                        <?php $rank_pos++; ?>
                                    <?php else: ?>
                                        <span style="color: #94a3b8; font-size: 11px;">—</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Roll No -->
                                <td><span style="font-weight: 700; color: #4f46e5;"><?php echo htmlspecialchars($st['roll_no']); ?></span></td>

                                <!-- Student Name -->
                                <td class="font-bold" style="color: #0f172a;">
                                    <?php echo htmlspecialchars($st['first_name'] . ' ' . $st['last_name']); ?>
                                </td>

                                <!-- Marks for each Subject -->
                                <?php foreach ($subjects as $sub): 
                                    $subid = intval($sub['id']);
                                    $has_m = isset($marks_map[$sid][$subid]);
                                     $obt = $has_m ? $marks_map[$sid][$subid]['obtained'] : null;
                                     $mx = $has_m ? $marks_map[$sid][$subid]['max'] : 100;
                                     $is_fail = $has_m && (($obt < ($mx * 0.35)) || in_array($marks_map[$sid][$subid]['grade'], ['E', 'F']));
                                 ?>
                                     <td class="text-center" style="font-weight: 600; color: <?php echo $is_fail ? '#b91c1c' : '#0f172a'; ?>;">
                                         <?php if ($has_m): ?>
                                             <?php echo $obt; ?>
                                             <span style="font-size: 9.5px; color: <?php echo $is_fail ? '#b91c1c' : '#15803d'; ?>; display: block; font-weight: 700;">
                                                 [<?php echo htmlspecialchars($marks_map[$sid][$subid]['grade']); ?>]
                                             </span>
                                         <?php else: ?>
                                             <span style="color: #cbd5e1;">—</span>
                                         <?php endif; ?>
                                     </td>
                                 <?php endforeach; ?>

                                 <!-- Total Marks -->
                                 <td class="text-center font-bold" style="color: #1e1b4b;">
                                     <?php echo $st['total_obtained']; ?> <span style="font-size: 10px; color: #64748b; font-weight: normal;">/ <?php echo $st['total_max']; ?></span>
                                 </td>

                                 <!-- Percentage -->
                                 <td class="text-center font-bold" style="color: #4338ca;">
                                     <?php echo $st['percentage']; ?>%
                                 </td>

                                 <!-- Cumulative Grade -->
                                 <td class="text-center font-bold" style="color: <?php echo in_array($st['grade'], ['E', 'F']) ? '#b91c1c' : '#15803d'; ?>;">
                                     <?php echo $st['grade']; ?>
                                 </td>

                                 <!-- Result Status -->
                                 <td style="font-size: 11px; font-weight: 700; color: <?php echo ($st['status_badge'] === 'danger') ? '#b91c1c' : ($st['status_badge'] === 'success' ? '#15803d' : '#64748b'); ?>;">
                                     <?php echo $st['status']; ?>
                                 </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Summary Analytics KPI Cards -->
            <div class="kpi-grid">
                <div class="kpi-card">
                    <div class="kpi-label">Class Pass Rate</div>
                    <div class="kpi-val" style="color: #15803d;"><?php echo $class_pass_rate; ?>%</div>
                    <div style="font-size: 9.5px; color: #64748b; margin-top: 1px;"><?php echo $class_total_passed; ?> Passed · <?php echo $class_total_failed; ?> Failed</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Class Average Score</div>
                    <div class="kpi-val" style="color: #4338ca;"><?php echo $class_avg_percentage; ?>%</div>
                    <div style="font-size: 9.5px; color: #64748b; margin-top: 1px;">Aggregate performance</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Top Score in Class</div>
                    <div class="kpi-val" style="color: #d97706;"><?php echo $class_highest_score; ?> Marks</div>
                    <div style="font-size: 9.5px; color: #64748b; margin-top: 1px;">Rank #1 Performance</div>
                </div>

                <div class="kpi-card">
                    <div class="kpi-label">Class 1st Ranker (Topper)</div>
                    <div class="kpi-val" style="font-size: 13px; padding-top: 3px; color: #0f172a;">
                        <?php echo htmlspecialchars($class_topper_name); ?>
                    </div>
                </div>
            </div>

            <!-- Official Grading Scale & Significance Reference Box -->
            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 10px 14px; margin-bottom: 16px;">
                <div style="font-size: 10.5px; font-weight: 700; text-transform: uppercase; color: #1e1b4b; margin-bottom: 6px; letter-spacing: 0.5px;">
                    Official Examination Grading Scale & Significance
                </div>
                <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 8px; font-size: 11px; text-align: center;">
                    <div style="background: #ffffff; padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px;">
                        <strong style="color: #15803d; font-size: 12px;">Grade A (80 - 100)</strong><br><span style="color: #475569;">Very Good</span>
                    </div>
                    <div style="background: #ffffff; padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px;">
                        <strong style="color: #0284c7; font-size: 12px;">Grade B (65 - 79)</strong><br><span style="color: #475569;">Good</span>
                    </div>
                    <div style="background: #ffffff; padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px;">
                        <strong style="color: #4f46e5; font-size: 12px;">Grade C (50 - 64)</strong><br><span style="color: #475569;">Satisfactory</span>
                    </div>
                    <div style="background: #ffffff; padding: 6px; border: 1px solid #e2e8f0; border-radius: 4px;">
                        <strong style="color: #d97706; font-size: 12px;">Grade D (35 - 49)</strong><br><span style="color: #475569;">Average</span>
                    </div>
                    <div style="background: #ffffff; padding: 6px; border: 1px solid #fecaca; border-radius: 4px;">
                        <strong style="color: #b91c1c; font-size: 12px;">Grade E (Below 35)</strong><br><span style="color: #b91c1c; font-weight: 600;">Not Satisfactory</span>
                    </div>
                </div>
            </div>

            <!-- Institutional Signatures & Verification Area -->
            <div class="signatures-area">
                <div>
                    <div style="height: 38px;"></div>
                    <div class="sig-line">Tabulator / Evaluator</div>
                    <div class="sig-sub">Marks Verified By</div>
                </div>

                <div>
                    <div style="height: 38px;"></div>
                    <div class="sig-line">Class Teacher</div>
                    <div class="sig-sub"><?php echo htmlspecialchars($class_info['teacher_name'] ?? 'Faculty In-Charge'); ?></div>
                </div>

                <div>
                    <div class="seal-box">
                        Institutional Seal & Stamp
                    </div>
                    <div class="sig-sub">Official Verification</div>
                </div>

                <div>
                    <div style="height: 38px;"></div>
                    <div class="sig-line"><?php echo htmlspecialchars($principal_name); ?></div>
                    <div class="sig-sub">Head of Institution</div>
                </div>
            </div>

            <!-- Computer Generated Authenticity Footer -->
            <div class="doc-footer">
                <div>Tabulation Sheet ID: <code>TAB-CLS-<?php echo $class_id; ?>-<?php echo strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $exam_param), 0, 6)); ?>-<?php echo date('Ymd'); ?></code></div>
                <div>Generated via EduCore SMS on <?php echo date('Y-m-d H:i:s'); ?> | Page 1 of 1</div>
            </div>

        </div>
    </div>

    <!-- Interactive Custom Select & Theme Script (Class & Exam Only) -->
    <script>
    (function() {
        'use strict';

        // 1. Theme Toggle Management
        const themeBtn = document.getElementById('themeToggleBtn');
        const textLabel = themeBtn ? themeBtn.querySelector('.theme-toggle-label') : null;

        function applyTheme(theme, saveRemote) {
            document.documentElement.setAttribute('data-theme', theme);
            if (document.body) {
                if (theme === 'light') {
                    document.body.classList.add('light-theme');
                } else {
                    document.body.classList.remove('light-theme');
                }
            }
            if (textLabel) {
                textLabel.textContent = (theme === 'light') ? 'Light' : 'Dark';
            }
            if (themeBtn) {
                themeBtn.setAttribute('data-current', theme);
            }

            try {
                localStorage.setItem('sms_theme', theme);
            } catch(e) {}

            if (saveRemote) {
                const formData = new FormData();
                formData.append('theme', theme);
                fetch('../api/update_theme.php', {
                    method: 'POST',
                    body: formData
                }).catch(function(err) {
                    console.warn('Theme preference sync:', err);
                });
            }
        }

        const initialTheme = document.documentElement.getAttribute('data-theme') || localStorage.getItem('sms_theme') || 'dark';
        applyTheme(initialTheme, false);

        if (themeBtn) {
            themeBtn.addEventListener('click', function(e) {
                e.preventDefault();
                const current = document.documentElement.getAttribute('data-theme') || 'dark';
                const nextTheme = (current === 'light') ? 'dark' : 'light';
                applyTheme(nextTheme, true);
            });
        }

        // 0. Close tab and return to parent tab handler
        window.closeTabOrGoBack = function(e) {
            if (e) e.preventDefault();
            if (window.opener && !window.opener.closed) {
                try {
                    window.opener.focus();
                } catch(err) {}
            }
            window.close();
            // Fallback if browser blocks closing tabs opened directly
            setTimeout(function() {
                window.location.href = 'index.php';
            }, 200);
        };

        // 2. Custom Select Dropdowns ONLY for Class / Grade and Examination
        function escapeHTML(str) {
            if (!str) return '';
            const div = document.createElement('div');
            div.innerText = str;
            return div.innerHTML;
        }

        let activeOpenDropdown = null;

        function closeAllDropdowns(exceptTrigger) {
            if (activeOpenDropdown && activeOpenDropdown !== exceptTrigger) {
                activeOpenDropdown.classList.remove('open');
                if (activeOpenDropdown.parentElement) {
                    activeOpenDropdown.parentElement.classList.remove('open');
                    const menu = activeOpenDropdown.parentElement.querySelector('.custom-select-dropdown');
                    if (menu) menu.classList.remove('show');
                }
                activeOpenDropdown = null;
            }
        }

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.custom-select-wrapper')) {
                closeAllDropdowns(null);
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAllDropdowns(null);
            }
        });

        // Target ONLY Class and Exam select elements
        const targetSelects = document.querySelectorAll('#classFilterSelect, #examFilterSelect');

        targetSelects.forEach(function(select) {
            const options = Array.from(select.options);
            const optionCount = options.length;
            const hasSearch = optionCount > 10; // Dynamic search only when more than 10 options

            // Hide native select
            select.style.display = 'none';

            // Custom wrapper
            const wrapper = document.createElement('div');
            wrapper.className = 'custom-select-wrapper';

            // Trigger Button
            const selectedOpt = select.options[select.selectedIndex] || options[0];
            const initialText = selectedOpt ? selectedOpt.text : 'Select...';

            const trigger = document.createElement('button');
            trigger.type = 'button';
            trigger.className = 'custom-select-trigger';
            trigger.setAttribute('aria-haspopup', 'listbox');
            const isClass = select.id === 'classFilterSelect';
            const iconSvg = isClass
                ? `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>`
                : `<svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="13" height="13"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>`;

            trigger.innerHTML = `
                <div class="custom-select-left">
                    <div class="custom-select-icon">
                        ${iconSvg}
                    </div>
                    <span class="custom-select-label">${escapeHTML(initialText)}</span>
                </div>
                <svg class="custom-select-chevron" fill="none" viewBox="0 0 24 24" stroke="currentColor" width="15" height="15">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            `;

            // Dropdown Popover
            const dropdown = document.createElement('div');
            dropdown.className = 'custom-select-dropdown';

            let dropdownHTML = `
                <div class="custom-select-header">
                    <span>${select.id === 'classFilterSelect' ? 'Select Class / Grade' : 'Select Examination'}</span>
                    <span class="count-pill">${optionCount} items</span>
                </div>
            `;

            if (hasSearch) {
                dropdownHTML += `
                <div class="custom-select-search-wrap">
                    <svg class="custom-select-search-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <input type="text" class="custom-select-search-input" placeholder="Search ${optionCount} options...">
                </div>
                `;
            }

            dropdownHTML += `<div class="custom-select-options-list"></div>`;
            dropdownHTML += `<div class="custom-select-empty" style="display: none;">No matching options found</div>`;

            dropdown.innerHTML = dropdownHTML;

            const optionsList = dropdown.querySelector('.custom-select-options-list');
            const emptyMsg = dropdown.querySelector('.custom-select-empty');
            const searchInput = dropdown.querySelector('.custom-select-search-input');
            const labelEl = trigger.querySelector('.custom-select-label');

            function renderOptions() {
                optionsList.innerHTML = '';
                options.forEach(function(opt, idx) {
                    const isSelected = opt.selected || select.selectedIndex === idx;
                    const optItem = document.createElement('div');
                    optItem.className = `custom-select-option ${isSelected ? 'selected' : ''}`;
                    optItem.dataset.value = opt.value;
                    optItem.dataset.index = idx;
                    optItem.innerHTML = `
                        <span>${escapeHTML(opt.text)}</span>
                        ${isSelected ? '<span class="opt-check">✓</span>' : ''}
                    `;

                    optItem.addEventListener('click', function(e) {
                        e.stopPropagation();
                        select.selectedIndex = idx;
                        select.value = opt.value;
                        labelEl.textContent = opt.text;

                        optionsList.querySelectorAll('.custom-select-option').forEach(function(el) {
                            el.classList.remove('selected');
                            const chk = el.querySelector('.opt-check');
                            if (chk) chk.remove();
                        });
                        optItem.classList.add('selected');
                        optItem.insertAdjacentHTML('beforeend', '<span class="opt-check">✓</span>');

                        closeDropdown();

                        // Fire onchange for location redirect
                        if (typeof select.onchange === 'function') {
                            select.onchange();
                        } else {
                            select.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    });

                    optionsList.appendChild(optItem);
                });
            }
            renderOptions();

            if (searchInput) {
                searchInput.addEventListener('input', function(e) {
                    const q = e.target.value.toLowerCase().trim();
                    let matchCount = 0;
                    const optItems = optionsList.querySelectorAll('.custom-select-option');

                    optItems.forEach(function(item) {
                        const txt = item.textContent.toLowerCase();
                        if (txt.includes(q)) {
                            item.style.display = 'flex';
                            matchCount++;
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    emptyMsg.style.display = matchCount === 0 ? 'block' : 'none';
                });

                searchInput.addEventListener('click', function(e) {
                    e.stopPropagation();
                });
            }

            function openDropdown() {
                closeAllDropdowns(trigger);

                const rect = trigger.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                const spaceAbove = rect.top;

                if (spaceBelow < 280 && spaceAbove > spaceBelow) {
                    dropdown.classList.add('dropup');
                } else {
                    dropdown.classList.remove('dropup');
                }

                wrapper.classList.add('open');
                trigger.classList.add('open');
                dropdown.classList.add('show');
                activeOpenDropdown = trigger;

                if (searchInput) {
                    searchInput.value = '';
                    optionsList.querySelectorAll('.custom-select-option').forEach(function(it) {
                        it.style.display = 'flex';
                    });
                    emptyMsg.style.display = 'none';
                    setTimeout(function() {
                        searchInput.focus();
                    }, 80);
                }
            }

            function closeDropdown() {
                wrapper.classList.remove('open');
                trigger.classList.remove('open');
                dropdown.classList.remove('show');
                if (activeOpenDropdown === trigger) activeOpenDropdown = null;
            }

            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                if (dropdown.classList.contains('show')) {
                    closeDropdown();
                } else {
                    openDropdown();
                }
            });

            // Insert into DOM right after native select
            select.parentNode.insertBefore(wrapper, select.nextSibling);
            wrapper.appendChild(trigger);
            wrapper.appendChild(dropdown);
        });
    })();
    </script>
</body>
</html>
