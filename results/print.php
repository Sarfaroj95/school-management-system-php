<?php
/**
 * Official Student Examination Marksheet & Academic Transcript Print View
 * Supports single exam report cards, multi-exam transcripts, and student selector
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

// Allow Super Admin, Admin, Staff, Teacher, and Student
require_role(['Super Admin', 'Admin', 'Staff', 'Teacher', 'Student']);

// Resolve Student ID
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$mark_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$exam_param = isset($_GET['exam']) ? trim($_GET['exam']) : '';

// If accessed via Mark ID, lookup student and exam name
if ($mark_id > 0 && $student_id <= 0) {
    $m_stmt = $conn->prepare("SELECT student_id, exam_name FROM marks WHERE id = ? LIMIT 1");
    $m_stmt->bind_param("i", $mark_id);
    $m_stmt->execute();
    $m_row = $m_stmt->get_result()->fetch_assoc();
    $m_stmt->close();
    if ($m_row) {
        $student_id = intval($m_row['student_id']);
        if (empty($exam_param)) {
            $exam_param = $m_row['exam_name'];
        }
    }
}

// Security Check: If logged in as Student, restrict strictly to own profile
if (has_role('Student')) {
    $my_student_id = intval($_SESSION['student_id'] ?? 0);
    $student_id = $my_student_id;
}

// Fetch System Settings for Institution Branding
$settings = [];
$set_res = mysqli_query($conn, "SELECT setting_key, setting_value FROM system_settings");
if ($set_res) {
    while ($row = mysqli_fetch_assoc($set_res)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}

$school_name = $settings['school_name'] ?? 'KRISHNAPUR PRIMARY SCHOOL';
$school_email = $settings['school_email'] ?? 'contact@educore-sms.edu';
$school_phone = $settings['school_phone'] ?? '+91 (555) 019-2834';
$school_address = $settings['school_address'] ?? 'RGGM+2V9, Krishnapur, Chandrakona, Krishnapur, West Bengal 721242';
$academic_year = $settings['academic_year'] ?? '2026-2027';
$school_board = $settings['school_board'] ?? 'Official Student Academic Transcript & Evaluation Report';
$principal_name = $settings['principal_name'] ?? 'Principal / Controller';

// If no student_id is provided and user is Admin/Teacher/Staff, render Student & Exam Selection View
if ($student_id <= 0) {
    $page_title = "Print Student Examination Result";
    $header_title = "Print Marksheet";
    $current_page = "results";
    
    // Fetch classes and students for picker
    $students_stmt = $conn->prepare("SELECT s.id, s.roll_no, s.first_name, s.last_name, c.class_name, c.section 
                                    FROM students s 
                                    JOIN classes c ON s.class_id = c.id 
                                    WHERE s.status = 'Active' 
                                    ORDER BY c.class_name, s.roll_no ASC");
    $students_stmt->execute();
    $all_students = $students_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $students_stmt->close();

    // Fetch distinct exams available in database
    $exams_res = mysqli_query($conn, "SELECT DISTINCT exam_name FROM marks ORDER BY exam_name ASC");
    $available_exams = [];
    if ($exams_res) {
        while ($ex = mysqli_fetch_assoc($exams_res)) {
            $available_exams[] = $ex['exam_name'];
        }
    }

    include "../includes/header.php";
    ?>
    <div style="margin-bottom: 20px;">
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Results Grade Book</a>
    </div>

    <div class="card" style="max-width: 680px; margin: 0 auto;">
        <div class="card-header">
            <div class="card-title">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Generate & Print Student Examination Result
            </div>
        </div>
        <div class="card-body">
            <p style="color: var(--text-secondary); margin-bottom: 20px; font-size: 14px;">
                Select a student and examination term to generate an official, formatted printable marksheet transcript with full student bio-data and institutional grading.
            </p>

            <form method="GET" action="print.php" target="_blank">
                <div class="form-group" style="margin-bottom: 18px;">
                    <label class="form-label" for="student_select">Select Student *</label>
                    <select name="student_id" id="student_select" class="form-control" required>
                        <option value="">-- Choose Student by Roll No & Name --</option>
                        <?php foreach ($all_students as $st): ?>
                            <option value="<?php echo $st['id']; ?>">
                                <?php echo htmlspecialchars($st['roll_no'] . ' - ' . $st['first_name'] . ' ' . $st['last_name'] . ' (' . $st['class_name'] . ' ' . $st['section'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label" for="exam_select">Examination Term (Optional / All)</label>
                    <select name="exam" id="exam_select" class="form-control">
                        <option value="">-- All / Latest Examination Record --</option>
                        <?php foreach ($available_exams as $ex_name): ?>
                            <option value="<?php echo htmlspecialchars($ex_name); ?>">
                                <?php echo htmlspecialchars($ex_name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; gap: 12px; justify-content: flex-end;">
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Generate & Open Marksheet
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php
    include "../includes/footer.php";
    exit();
}

// Fetch complete Student Details along with Class and Class Teacher info
$st_stmt = $conn->prepare("SELECT s.*, c.class_name, c.section, c.room_no, t.name AS teacher_name, t.email AS teacher_email 
                          FROM students s 
                          LEFT JOIN classes c ON s.class_id = c.id 
                          LEFT JOIN teachers t ON c.teacher_id = t.id 
                          WHERE s.id = ? LIMIT 1");
$st_stmt->bind_param("i", $student_id);
$st_stmt->execute();
$student = $st_stmt->get_result()->fetch_assoc();
$st_stmt->close();

if (!$student) {
    die("Error: Student record not found.");
}

// Fetch list of distinct examinations taken by this student
$exam_stmt = $conn->prepare("SELECT DISTINCT exam_name FROM marks WHERE student_id = ? ORDER BY exam_name ASC");
$exam_stmt->bind_param("i", $student_id);
$exam_stmt->execute();
$student_exams = $exam_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$exam_stmt->close();

$student_exam_names = array_column($student_exams, 'exam_name');

// If no specific exam requested and student has exams, default to first/most relevant exam or 'all'
if (empty($exam_param)) {
    if (!empty($student_exam_names)) {
        $exam_param = $student_exam_names[0];
    } else {
        $exam_param = 'All Examinations';
    }
}

// Query Marks for this Student based on Exam filter
if (!empty($exam_param) && $exam_param !== 'all' && $exam_param !== 'All Examinations') {
    $marks_query = $conn->prepare("SELECT m.*, sub.subject_name, sub.subject_code 
                                  FROM marks m 
                                  JOIN subjects sub ON m.subject_id = sub.id 
                                  WHERE m.student_id = ? AND m.exam_name = ? 
                                  ORDER BY sub.subject_name ASC");
    $marks_query->bind_param("is", $student_id, $exam_param);
} else {
    $marks_query = $conn->prepare("SELECT m.*, sub.subject_name, sub.subject_code 
                                  FROM marks m 
                                  JOIN subjects sub ON m.subject_id = sub.id 
                                  WHERE m.student_id = ? 
                                  ORDER BY m.exam_name ASC, sub.subject_name ASC");
    $marks_query->bind_param("i", $student_id);
}

$marks_query->execute();
$marks_records = $marks_query->get_result()->fetch_all(MYSQLI_ASSOC);
$marks_query->close();

// Compute Aggregate Calculations
$total_subjects = count($marks_records);
$total_obtained = 0;
$total_max = 0;
$has_failure = false;
$highest_score = 0;
$latest_exam_date = '';

foreach ($marks_records as $mk) {
    $obtained = floatval($mk['marks_obtained']);
    $max = floatval($mk['max_marks']);
    $total_obtained += $obtained;
    $total_max += $max;

    if ($obtained > $highest_score) {
        $highest_score = $obtained;
    }

    if (in_array(strtoupper($mk['grade']), ['E', 'F']) || ($max > 0 && ($obtained / $max) < 0.35)) {
        $has_failure = true;
    }

    if (!empty($mk['exam_date']) && (empty($latest_exam_date) || strtotime($mk['exam_date']) > strtotime($latest_exam_date))) {
        $latest_exam_date = $mk['exam_date'];
    }
}

$overall_percentage = ($total_max > 0) ? round(($total_obtained / $total_max) * 100, 2) : 0;

// Determine Cumulative Overall Grade based on Marks / Percentage:
// 80-100: A (Very Good), 65-79: B (Good), 50-64: C (Satisfactory), 35-49: D (Average), Below 35: E (Not Satisfactory)
if ($overall_percentage >= 80) {
    $overall_grade = 'A';
    $grade_remark = 'Very Good';
} elseif ($overall_percentage >= 65) {
    $overall_grade = 'B';
    $grade_remark = 'Good';
} elseif ($overall_percentage >= 50) {
    $overall_grade = 'C';
    $grade_remark = 'Satisfactory';
} elseif ($overall_percentage >= 35) {
    $overall_grade = 'D';
    $grade_remark = 'Average';
} else {
    $overall_grade = 'E';
    $grade_remark = 'Not Satisfactory';
}

// Determine Final Result Standing
if ($total_subjects === 0) {
    $result_status = "NO RECORDS";
    $result_badge = "neutral";
} elseif ($has_failure || in_array($overall_grade, ['E', 'F'])) {
    $result_status = "NOT SATISFACTORY / FAILED";
    $result_badge = "danger";
} elseif ($overall_percentage >= 80) {
    $result_status = "PASSED WITH DISTINCTION (VERY GOOD)";
    $result_badge = "success";
} elseif ($overall_percentage >= 65) {
    $result_status = "PASSED - 1ST DIVISION (GOOD)";
    $result_badge = "success";
} elseif ($overall_percentage >= 50) {
    $result_status = "PASSED (SATISFACTORY)";
    $result_badge = "success";
} else {
    $result_status = "PASSED - AVERAGE";
    $result_badge = "success";
}

$issue_date = !empty($latest_exam_date) ? date('F d, Y', strtotime($latest_exam_date)) : date('F d, Y');
$print_theme = $_SESSION['theme_mode'] ?? 'dark';
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($print_theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Marksheet - <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name'] . ' (' . $student['roll_no'] . ')'); ?></title>
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
            --border-dark: #334155;
            --bg-sheet: #ffffff;
            --bg-light: #f8fafc;
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
            line-height: 1.5;
        }

        /* Top Screen Controls Toolbar */
        .no-print-toolbar {
            max-width: 960px;
            margin: 0 auto 24px auto;
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

        @media (max-width: 900px) {
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

        /* Custom Select Component for Examination */
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

        /* Light Mode Custom Select Overrides for Examination */
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
        .marksheet-page {
            max-width: 900px;
            margin: 0 auto;
            background: var(--bg-sheet);
            color: var(--text-main);
            padding: 36px 44px;
            border-radius: 12px;
            box-shadow: 0 20px 45px rgba(0,0,0,0.5);
            position: relative;
            border: 2px solid #e2e8f0;
        }

        /* Ornate Certificate Border */
        .marksheet-inner-border {
            border: 2px double #0f172a;
            padding: 24px 28px;
            position: relative;
            background: #ffffff;
        }

        /* Institution Header */
        .inst-header {
            display: grid;
            grid-template-columns: 80px 1fr 80px;
            align-items: center;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 18px;
            margin-bottom: 18px;
            text-align: center;
        }

        .inst-logo {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: #1e1b4b;
            color: #f59e0b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: 800;
            border: 2px solid #d97706;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .inst-title h1 {
            font-size: 23px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #1e1b4b;
            margin-bottom: 3px;
        }

        .inst-subtitle {
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #4338ca;
            margin-bottom: 4px;
        }

        .inst-meta {
            font-size: 11.5px;
            color: #475569;
            line-height: 1.4;
        }

        /* Document Title Banner */
        .report-banner {
            background: #1e1b4b;
            color: #ffffff;
            text-align: center;
            padding: 7px 14px;
            margin-bottom: 18px;
            border-radius: 4px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .report-banner h2 {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin: 0;
        }

        .report-banner span {
            font-size: 12px;
            font-weight: 600;
            color: #cbd5e1;
        }

        /* Student Bio Grid */
        .student-bio-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px 24px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            padding: 14px 18px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 12.5px;
        }

        .bio-item {
            display: flex;
            justify-content: space-between;
            border-bottom: 1px dotted #cbd5e1;
            padding-bottom: 4px;
        }

        .bio-label {
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            font-size: 11px;
            letter-spacing: 0.5px;
        }

        .bio-value {
            font-weight: 700;
            color: #0f172a;
            text-align: right;
        }

        /* Marks Table */
        .marks-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 12.5px;
        }

        .marks-table th, .marks-table td {
            border: 1px solid #94a3b8;
            padding: 8px 10px;
            text-align: left;
        }

        .marks-table th {
            background: #f1f5f9;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            font-size: 11.5px;
            letter-spacing: 0.3px;
        }

        .marks-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .marks-table .text-center {
            text-align: center;
        }

        .marks-table .text-right {
            text-align: right;
        }

        .marks-table .font-bold {
            font-weight: 700;
        }

        /* Summary Analytics Bar */
        .summary-box {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 20px;
        }

        .summary-card {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 10px 14px;
            border-radius: 6px;
            text-align: center;
        }

        .summary-label {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 3px;
        }

        .summary-val {
            font-size: 18px;
            font-weight: 800;
            color: #0f172a;
        }

        /* Grading Legend & Performance Evaluation */
        .evaluation-section {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 16px;
            margin-bottom: 24px;
            font-size: 11.5px;
        }

        .grading-legend-box {
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            border-radius: 6px;
            background: #ffffff;
        }

        .legend-title {
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            color: #334155;
            margin-bottom: 6px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 3px;
        }

        .legend-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 4px;
            font-size: 10.5px;
        }

        .legend-item {
            background: #f1f5f9;
            padding: 3px 5px;
            border-radius: 3px;
            text-align: center;
        }

        .remarks-box {
            border: 1px solid #cbd5e1;
            padding: 10px 14px;
            border-radius: 6px;
            background: #ffffff;
        }

        /* Signatures Section */
        .signatures-area {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-top: 36px;
            padding-top: 20px;
            text-align: center;
        }

        .sig-line {
            border-top: 1.5px solid #334155;
            padding-top: 6px;
            font-size: 11.5px;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
        }

        .sig-sub {
            font-size: 10px;
            font-weight: 500;
            color: #64748b;
        }

        .seal-box {
            width: 76px;
            height: 76px;
            border: 1.5px dashed #94a3b8;
            border-radius: 50%;
            margin: 0 auto 6px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 9.5px;
            color: #94a3b8;
            text-transform: uppercase;
            font-weight: 600;
            text-align: center;
            padding: 4px;
        }

        /* Document Footer Note */
        .doc-footer {
            margin-top: 18px;
            border-top: 1px solid #e2e8f0;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            font-size: 10px;
            color: #64748b;
        }

        /* Print Media Styles */
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

            .marksheet-page {
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                max-width: 100% !important;
                width: 100% !important;
            }

            .marksheet-inner-border {
                border: 2px solid #000000 !important;
                padding: 18px 22px !important;
            }

            .report-banner {
                background: #1e1b4b !important;
                color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .marks-table th {
                background: #e2e8f0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @page {
                size: A4 portrait;
                margin: 12mm 10mm;
            }
        }
    </style>
</head>
<body>

    <!-- Top Screen Action Bar (Hidden during printing) -->
    <div class="no-print-toolbar">
        <div class="toolbar-left">
            <a href="index.php" onclick="closeTabOrGoBack(event);" class="btn btn-secondary">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Grade Book
            </a>

            <!-- Switch Exam Filter -->
            <?php if (!empty($student_exam_names)): ?>
                <div style="min-width: 200px;">
                    <select id="examFilterSelect" class="custom-select-target" onchange="location.href='print.php?student_id=<?php echo $student_id; ?>&exam=' + encodeURIComponent(this.value)">
                        <option value="All Examinations" <?php echo ($exam_param === 'All Examinations' || $exam_param === 'all') ? 'selected' : ''; ?>>All Examinations</option>
                        <?php foreach ($student_exam_names as $ex_n): ?>
                            <option value="<?php echo htmlspecialchars($ex_n); ?>" <?php echo ($exam_param === $ex_n) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($ex_n); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>

        <div class="toolbar-right">
            <?php if (!has_role('Student')): ?>
                <a href="print.php" class="btn btn-secondary">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Select Another Student
                </a>
            <?php endif; ?>

            <button onclick="window.print()" class="btn btn-primary">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                </svg>
                Print Official Marksheet
            </button>
        </div>
    </div>

    <!-- Official Printable Marksheet Page -->
    <div class="marksheet-page">
        <div class="marksheet-inner-border">
            
            <!-- School Institution Header -->
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
                <div class="inst-logo" style="font-size: 28px;">
                    🏛️
                </div>
            </div>

            <!-- Report Banner -->
            <div class="report-banner">
                <h2>
                    <?php echo htmlspecialchars(!empty($exam_param) ? $exam_param : 'Examination Report Card'); ?>
                </h2>
                <span>Academic Session: <?php echo htmlspecialchars($academic_year); ?></span>
            </div>

            <!-- Student Bio-Data Details Grid -->
            <div class="student-bio-grid">
                <div class="bio-item">
                    <span class="bio-label">Student Full Name:</span>
                    <span class="bio-value"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></span>
                </div>
                <div class="bio-item">
                    <span class="bio-label">Student Roll No:</span>
                    <span class="bio-value" style="color: #4f46e5;"><?php echo htmlspecialchars($student['roll_no']); ?></span>
                </div>
                <div class="bio-item">
                    <span class="bio-label">Class & Section:</span>
                    <span class="bio-value"><?php echo htmlspecialchars(($student['class_name'] ?? 'N/A') . ' - Section ' . ($student['section'] ?? 'A')); ?></span>
                </div>
                <div class="bio-item">
                    <span class="bio-label">Class Teacher:</span>
                    <span class="bio-value"><?php echo htmlspecialchars($student['teacher_name'] ?? 'Faculty In-charge'); ?></span>
                </div>
                <div class="bio-item">
                    <span class="bio-label">Date of Birth:</span>
                    <span class="bio-value"><?php echo date('F d, Y', strtotime($student['dob'])); ?></span>
                </div>
                <div class="bio-item">
                    <span class="bio-label">Gender / Status:</span>
                    <span class="bio-value"><?php echo htmlspecialchars($student['gender'] . ' (' . $student['status'] . ')'); ?></span>
                </div>
                <div class="bio-item">
                    <span class="bio-label">Parent / Guardian:</span>
                    <span class="bio-value"><?php echo htmlspecialchars($student['parent_name']); ?></span>
                </div>
                <div class="bio-item">
                    <span class="bio-label">Parent Contact:</span>
                    <span class="bio-value"><?php echo htmlspecialchars($student['parent_phone'] ?: ($student['phone'] ?: 'N/A')); ?></span>
                </div>
                <div class="bio-item">
                    <span class="bio-label">Admission Date:</span>
                    <span class="bio-value"><?php echo date('M d, Y', strtotime($student['admission_date'])); ?></span>
                </div>
                <div class="bio-item">
                    <span class="bio-label">Date of Issue:</span>
                    <span class="bio-value"><?php echo $issue_date; ?></span>
                </div>
            </div>

            <!-- Subject-wise Marks Table -->
            <table class="marks-table">
                <thead>
                    <tr>
                        <th style="width: 38px;" class="text-center">#</th>
                        <th style="width: 90px;">Code</th>
                        <th>Subject Name</th>
                        <th class="text-center" style="width: 80px;">Max Marks</th>
                        <th class="text-center" style="width: 80px;">Pass Marks</th>
                        <th class="text-center" style="width: 90px;">Obtained</th>
                        <th class="text-center" style="width: 70px;">%</th>
                        <th class="text-center" style="width: 65px;">Grade</th>
                        <th>Remarks / Evaluation</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($marks_records)): ?>
                        <tr>
                            <td colspan="9" class="text-center" style="padding: 24px; color: #64748b;">
                                No examination marks recorded for <strong><?php echo htmlspecialchars($exam_param); ?></strong>.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $sno = 1; foreach ($marks_records as $mk): 
                            $m_obt = floatval($mk['marks_obtained']);
                            $m_max = floatval($mk['max_marks']);
                            $pass_threshold = round($m_max * 0.35, 1);
                            $pct = ($m_max > 0) ? round(($m_obt / $m_max) * 100, 1) : 0;
                            $is_pass = ($m_obt >= $pass_threshold && !in_array($mk['grade'], ['E', 'F']));
                        ?>
                            <tr>
                                <td class="text-center"><?php echo $sno++; ?></td>
                                <td style="font-weight: 600; color: #475569;"><?php echo htmlspecialchars($mk['subject_code']); ?></td>
                                <td class="font-bold"><?php echo htmlspecialchars($mk['subject_name']); ?></td>
                                <td class="text-center"><?php echo $m_max; ?></td>
                                <td class="text-center" style="color: #64748b;"><?php echo $pass_threshold; ?></td>
                                <td class="text-center font-bold" style="color: <?php echo $is_pass ? '#0f172a' : '#b91c1c'; ?>;">
                                    <?php echo $m_obt; ?>
                                </td>
                                <td class="text-center"><?php echo $pct; ?>%</td>
                                <td class="text-center font-bold" style="color: <?php echo in_array($mk['grade'], ['E', 'F']) ? '#b91c1c' : '#15803d'; ?>;">
                                    <?php echo htmlspecialchars($mk['grade']); ?>
                                </td>
                                <td style="font-size: 11.5px; color: #475569;">
                                    <?php echo !empty($mk['remarks']) ? htmlspecialchars($mk['remarks']) : ($is_pass ? 'Satisfactory' : 'Needs Remedial Support'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                        <!-- Grand Total Summary Row -->
                        <tr style="background: #f1f5f9; font-weight: 700; border-top: 2px solid #0f172a;">
                            <td colspan="3" class="text-right" style="text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px;">
                                Grand Total & Aggregate:
                            </td>
                            <td class="text-center font-bold"><?php echo $total_max; ?></td>
                            <td class="text-center">-</td>
                            <td class="text-center font-bold" style="color: #1e1b4b; font-size: 13px;">
                                <?php echo $total_obtained; ?>
                            </td>
                            <td class="text-center font-bold" style="color: #4338ca; font-size: 13px;">
                                <?php echo $overall_percentage; ?>%
                            </td>
                            <td class="text-center font-bold" style="color: <?php echo in_array($overall_grade, ['E', 'F']) ? '#b91c1c' : '#15803d'; ?>; font-size: 13px;">
                                <?php echo $overall_grade; ?>
                            </td>
                            <td class="font-bold" style="color: #1e293b;">
                                <?php echo $grade_remark; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Summary Highlights Cards -->
            <div class="summary-box">
                <div class="summary-card">
                    <div class="summary-label">Total Marks</div>
                    <div class="summary-val"><?php echo $total_obtained; ?> <span style="font-size: 13px; color: #64748b; font-weight: 500;">/ <?php echo $total_max; ?></span></div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Aggregate Percentage</div>
                    <div class="summary-val" style="color: #4338ca;"><?php echo $overall_percentage; ?>%</div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Cumulative Grade</div>
                    <div class="summary-val" style="color: <?php echo in_array($overall_grade, ['E', 'F']) ? '#b91c1c' : '#15803d'; ?>;"><?php echo $overall_grade; ?></div>
                </div>
                <div class="summary-card">
                    <div class="summary-label">Result Standing</div>
                    <div class="summary-val" style="font-size: 13px; padding-top: 4px; color: <?php echo ($result_badge === 'danger') ? '#b91c1c' : '#15803d'; ?>;">
                        <?php echo $result_status; ?>
                    </div>
                </div>
            </div>

            <!-- Evaluation and Grading Legend Strip -->
            <div class="evaluation-section">
                <div class="grading-legend-box">
                    <div class="legend-title">Official Grading Scale & Significance Key</div>
                    <div class="legend-grid" style="grid-template-columns: repeat(2, 1fr);">
                        <div class="legend-item"><strong style="color: #15803d;">A (80 - 100%)</strong>: Very Good</div>
                        <div class="legend-item"><strong style="color: #0284c7;">B (65 - 79%)</strong>: Good</div>
                        <div class="legend-item"><strong style="color: #4f46e5;">C (50 - 64%)</strong>: Satisfactory</div>
                        <div class="legend-item"><strong style="color: #d97706;">D (35 - 49%)</strong>: Average</div>
                        <div class="legend-item" style="grid-column: span 2; color: #b91c1c;"><strong>E (Below 35%)</strong>: Not Satisfactory</div>
                    </div>
                </div>

                <div class="remarks-box">
                    <div class="legend-title">Teacher & Evaluator Assessment</div>
                    <p style="font-size: 11px; color: #334155; line-height: 1.5; margin-top: 3px;">
                        <?php 
                            if ($overall_percentage >= 85) {
                                echo "Exemplary academic achievement demonstrating strong analytical capability and consistent engagement.";
                            } elseif ($overall_percentage >= 70) {
                                echo "Commendable performance with clear mastery of core concepts. Continued effort will lead to excellence.";
                            } elseif ($overall_percentage >= 50) {
                                echo "Satisfactory completion of assessment requirements. Regular practice is advised in weaker subject areas.";
                            } else {
                                echo "Academic intervention and regular review sessions required to improve performance in upcoming examinations.";
                            }
                        ?>
                    </p>
                </div>
            </div>

            <!-- Institutional Signatures & Official Stamp Area -->
            <div class="signatures-area">
                <div>
                    <div style="height: 48px;"></div>
                    <div class="sig-line">Class Teacher</div>
                    <div class="sig-sub">Academic In-Charge</div>
                </div>

                <div>
                    <div class="seal-box">
                        Institutional Seal & Stamp
                    </div>
                    <div class="sig-sub">Official Verification</div>
                </div>

                <div>
                    <div style="height: 48px;"></div>
                    <div class="sig-line"><?php echo htmlspecialchars($principal_name); ?></div>
                    <div class="sig-sub">Head of Institution</div>
                </div>
            </div>

            <!-- Verification & Computer Generated Disclaimer -->
            <div class="doc-footer">
                <div>Document ID: <code>TRX-<?php echo str_pad($student['id'], 4, '0', STR_PAD_LEFT); ?>-<?php echo strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $exam_param), 0, 6)); ?>-<?php echo date('Ymd'); ?></code></div>
                <div>Generated via EduCore SMS on <?php echo date('Y-m-d H:i:s'); ?> | Page 1 of 1</div>
            </div>

        </div>
    </div>

    <!-- Auto-print script if parameter requested -->
    <?php if (isset($_GET['autoprint']) && $_GET['autoprint'] == '1'): ?>
        <script>
            window.addEventListener('load', function() {
                setTimeout(function() {
                    window.print();
                }, 500);
            });
        </script>
    <?php endif; ?>

    <!-- Interactive Custom Select Script (Examination Dropdown Only) -->
    <script>
    (function() {
        'use strict';

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

        const targetSelects = document.querySelectorAll('#examFilterSelect');

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
            trigger.innerHTML = `
                <div class="custom-select-left">
                    <div class="custom-select-icon">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="13" height="13">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
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
                    <span>Select Examination</span>
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
