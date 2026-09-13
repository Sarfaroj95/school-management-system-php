<?php
/**
 * Role-Aware Dynamic Dashboard Overview
 * Adapts to: Super Admin, Admin, Staff, Teacher, Student
 */
include "connection.php";

$root_path = '';
include "includes/auth.php";

$user_role = get_user_role();
$page_title = "Dashboard Overview";
$header_title = has_role('Student') ? "Student Portal Overview" : (has_role('Teacher') ? "Faculty Academic Dashboard" : "Institutional Dashboard");
$current_page = "dashboard";

// ========================================================
// 1. DATA GATHERING BY ROLE
// ========================================================

if (has_role('Student')) {
    // Current Student's Context
    $student_id = $_SESSION['student_id'] ?? 0;
    
    // Fetch Student Profile & Class Details
    $stu_info = null;
    $s_stmt = $conn->prepare("SELECT s.*, c.class_name, c.section, c.room_no, t.name as class_teacher 
                             FROM students s 
                             LEFT JOIN classes c ON s.class_id = c.id 
                             LEFT JOIN teachers t ON c.teacher_id = t.id 
                             WHERE s.id = ? LIMIT 1");
    if ($s_stmt) {
        $s_stmt->bind_param("i", $student_id);
        $s_stmt->execute();
        $stu_info = $s_stmt->get_result()->fetch_assoc();
        $s_stmt->close();
    }

    // Attendance stats for student
    $my_present = 0;
    $my_total_days = 0;
    $att_stmt = $conn->prepare("SELECT status, COUNT(*) as cnt FROM attendance WHERE student_id = ? GROUP BY status");
    if ($att_stmt) {
        $att_stmt->bind_param("i", $student_id);
        $att_stmt->execute();
        $att_res = $att_stmt->get_result();
        while ($row = $att_res->fetch_assoc()) {
            $my_total_days += $row['cnt'];
            if ($row['status'] === 'Present') {
                $my_present += $row['cnt'];
            }
        }
        $att_stmt->close();
    }
    $my_att_rate = $my_total_days > 0 ? round(($my_present / $my_total_days) * 100) : 100;

    // Exam Results for student
    $my_results = [];
    $res_stmt = $conn->prepare("SELECT m.*, sub.subject_name, sub.subject_code 
                                FROM marks m 
                                JOIN subjects sub ON m.subject_id = sub.id 
                                WHERE m.student_id = ? 
                                ORDER BY m.id DESC LIMIT 5");
    if ($res_stmt) {
        $res_stmt->bind_param("i", $student_id);
        $res_stmt->execute();
        $my_results = $res_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $res_stmt->close();
    }

    // Issued Books for student
    $my_books_count = 0;
    $bk_stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM book_issues WHERE student_id = ? AND status = 'Issued'");
    if ($bk_stmt) {
        $bk_stmt->bind_param("i", $student_id);
        $bk_stmt->execute();
        $my_books_count = $bk_stmt->get_result()->fetch_assoc()['cnt'] ?? 0;
        $bk_stmt->close();
    }

} elseif (has_role('Teacher')) {
    // Current Teacher's Context
    $teacher_id = $_SESSION['teacher_id'] ?? 0;

    // Assigned Classes
    $assigned_classes = [];
    $c_stmt = $conn->prepare("SELECT c.*, (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id AND s.status = 'Active') as student_count 
                             FROM classes c WHERE c.teacher_id = ?");
    if ($c_stmt) {
        $c_stmt->bind_param("i", $teacher_id);
        $c_stmt->execute();
        $assigned_classes = $c_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $c_stmt->close();
    }

    // Total students under teacher's classes
    $teacher_students_count = 0;
    foreach ($assigned_classes as $cls) {
        $teacher_students_count += $cls['student_count'];
    }

    // Recent Marks entered
    $recent_marks = [];
    $m_stmt = $conn->prepare("SELECT m.*, s.first_name, s.last_name, s.roll_no, sub.subject_name 
                             FROM marks m 
                             JOIN students s ON m.student_id = s.id 
                             JOIN subjects sub ON m.subject_id = sub.id 
                             WHERE sub.teacher_id = ? 
                             ORDER BY m.id DESC LIMIT 5");
    if ($m_stmt) {
        $m_stmt->bind_param("i", $teacher_id);
        $m_stmt->execute();
        $recent_marks = $m_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $m_stmt->close();
    }

} else {
    // Super Admin / Admin / Staff Overview Metrics
    $total_students = 0;
    if ($res = $conn->query("SELECT COUNT(*) AS total FROM students WHERE status = 'Active'")) {
        $total_students = $res->fetch_assoc()['total'];
    }

    $total_teachers = 0;
    if ($res = $conn->query("SELECT COUNT(*) AS total FROM teachers WHERE status = 'Active'")) {
        $total_teachers = $res->fetch_assoc()['total'];
    }

    $total_classes = 0;
    if ($res = $conn->query("SELECT COUNT(*) AS total FROM classes")) {
        $total_classes = $res->fetch_assoc()['total'];
    }

    $total_books = 0;
    if ($res = $conn->query("SELECT SUM(quantity) AS total FROM library_books")) {
        $row = $res->fetch_assoc();
        $total_books = $row['total'] ?? 0;
    }

    // Recent Admissions (Limit 5)
    $recent_students = [];
    $stu_stmt = $conn->prepare("SELECT s.id, s.roll_no, s.first_name, s.last_name, s.gender, s.admission_date, c.class_name, c.section 
                                FROM students s 
                                LEFT JOIN classes c ON s.class_id = c.id 
                                ORDER BY s.id DESC LIMIT 5");
    if ($stu_stmt) {
        $stu_stmt->execute();
        $recent_students = $stu_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stu_stmt->close();
    }
}

// Fetch Latest Notices (Audience Filtered)
$recent_notices = [];
if (has_role('Student')) {
    $not_stmt = $conn->prepare("SELECT * FROM notices WHERE target_audience IN ('All', 'Students') ORDER BY id DESC LIMIT 4");
} elseif (has_role('Teacher')) {
    $not_stmt = $conn->prepare("SELECT * FROM notices WHERE target_audience IN ('All', 'Teachers') ORDER BY id DESC LIMIT 4");
} else {
    $not_stmt = $conn->prepare("SELECT * FROM notices ORDER BY id DESC LIMIT 4");
}

if ($not_stmt) {
    $not_stmt->execute();
    $recent_notices = $not_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $not_stmt->close();
}

include "includes/header.php";
?>

<?php if (has_role('Student')): ?>
    <!-- ========================================== -->
    <!-- STUDENT PORTAL DASHBOARD VIEW              -->
    <!-- ========================================== -->
    <div class="grid-stats">
        <div class="stat-card indigo">
            <div class="stat-header">
                <span class="stat-label">Assigned Class</span>
                <div class="stat-icon">🎓</div>
            </div>
            <div class="stat-value"><?php echo htmlspecialchars($stu_info['class_name'] ?? 'Grade 10') . ' - ' . htmlspecialchars($stu_info['section'] ?? 'A'); ?></div>
            <div class="stat-footer">Room: <?php echo htmlspecialchars($stu_info['room_no'] ?? '101'); ?> | Teacher: <?php echo htmlspecialchars($stu_info['class_teacher'] ?? 'Assigned'); ?></div>
        </div>

        <div class="stat-card emerald">
            <div class="stat-header">
                <span class="stat-label">My Attendance Rate</span>
                <div class="stat-icon">📅</div>
            </div>
            <div class="stat-value"><?php echo $my_att_rate; ?>%</div>
            <div class="stat-footer"><?php echo $my_present; ?> of <?php echo $my_total_days; ?> sessions present</div>
        </div>

        <div class="stat-card sky">
            <div class="stat-header">
                <span class="stat-label">Books Borrowed</span>
                <div class="stat-icon">📚</div>
            </div>
            <div class="stat-value"><?php echo $my_books_count; ?></div>
            <div class="stat-footer">Active issued library copies</div>
        </div>

        <div class="stat-card amber">
            <div class="stat-header">
                <span class="stat-label">Student Roll No</span>
                <div class="stat-icon">🆔</div>
            </div>
            <div class="stat-value" style="font-size: 22px;"><?php echo htmlspecialchars($_SESSION['roll_no'] ?? 'STD-1001'); ?></div>
            <div class="stat-footer">Status: Active Enrolled Student</div>
        </div>
    </div>

    <!-- Student Quick Navigation -->
    <div class="card" style="background: linear-gradient(135deg, rgba(59, 130, 246, 0.12) 0%, rgba(16, 185, 129, 0.08) 100%); border-color: rgba(59, 130, 246, 0.25);">
        <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 4px;">Student Academic Shortcuts</h3>
                <p style="font-size: 13px; color: var(--text-secondary);">Direct links to your academic reports, attendance history, and library catalog</p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="results/index.php" class="btn btn-primary btn-sm">📊 View My Grades & Results</a>
                <a href="attendance/index.php" class="btn btn-secondary btn-sm">📅 View My Attendance</a>
                <a href="library/index.php" class="btn btn-secondary btn-sm">📖 Browse Library Books</a>
                <a href="notices/index.php" class="btn btn-secondary btn-sm">📢 School Announcements</a>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Student Recent Exam Results -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: #10b981;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    My Recent Exam Results
                </div>
                <a href="results/index.php" class="btn btn-secondary btn-sm">Full Report Card &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Exam Name</th>
                            <th>Marks</th>
                            <th>Max</th>
                            <th>Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($my_results)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    No exam records posted yet.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($my_results as $res): ?>
                                <tr>
                                    <td style="font-weight: 600; color: #fff;"><?php echo htmlspecialchars($res['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($res['exam_name']); ?></td>
                                    <td><strong style="color: #38bdf8;"><?php echo htmlspecialchars($res['marks_obtained']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($res['max_marks']); ?></td>
                                    <td><span class="badge badge-success"><?php echo htmlspecialchars($res['grade']); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Student Notices -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: #f59e0b;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Announcements
                </div>
                <a href="notices/index.php" class="btn btn-secondary btn-sm">All &rarr;</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_notices)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 20px 0;">No active notices.</p>
                <?php else: ?>
                    <?php foreach ($recent_notices as $notice): ?>
                        <div class="notice-item">
                            <div class="notice-header">
                                <span class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></span>
                                <span class="badge badge-info"><?php echo htmlspecialchars($notice['priority']); ?></span>
                            </div>
                            <div class="notice-content">
                                <?php echo htmlspecialchars(substr($notice['content'], 0, 110)) . (strlen($notice['content']) > 110 ? '...' : ''); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php elseif (has_role('Teacher')): ?>
    <!-- ========================================== -->
    <!-- TEACHER / FACULTY DASHBOARD VIEW           -->
    <!-- ========================================== -->
    <div class="grid-stats">
        <div class="stat-card emerald">
            <div class="stat-header">
                <span class="stat-label">Classes Under Mentorship</span>
                <div class="stat-icon">🏫</div>
            </div>
            <div class="stat-value"><?php echo count($assigned_classes); ?></div>
            <div class="stat-footer">Assigned standard sections</div>
        </div>

        <div class="stat-card indigo">
            <div class="stat-header">
                <span class="stat-label">Total Assigned Students</span>
                <div class="stat-icon">👥</div>
            </div>
            <div class="stat-value"><?php echo number_format($teacher_students_count); ?></div>
            <div class="stat-footer">Active enrolled pupils</div>
        </div>

        <div class="stat-card sky">
            <div class="stat-header">
                <span class="stat-label">Specialization</span>
                <div class="stat-icon">🔬</div>
            </div>
            <div class="stat-value" style="font-size: 20px;"><?php echo htmlspecialchars($_SESSION['subject_specialization'] ?? 'Mathematics'); ?></div>
            <div class="stat-footer">Department Faculty</div>
        </div>

        <div class="stat-card amber">
            <div class="stat-header">
                <span class="stat-label">Faculty ID</span>
                <div class="stat-icon">🪪</div>
            </div>
            <div class="stat-value" style="font-size: 22px;"><?php echo htmlspecialchars($_SESSION['emp_id'] ?? 'EMP101'); ?></div>
            <div class="stat-footer">Status: Active Instructor</div>
        </div>
    </div>

    <!-- Teacher Action Shortcuts -->
    <div class="card" style="background: linear-gradient(135deg, rgba(16, 185, 129, 0.12) 0%, rgba(59, 130, 246, 0.08) 100%); border-color: rgba(16, 185, 129, 0.25);">
        <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 4px;">Faculty Actions</h3>
                <p style="font-size: 13px; color: var(--text-secondary);">Direct access to daily attendance recording, grade entry, and student lists</p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <a href="attendance/index.php" class="btn btn-primary btn-sm">📝 Take Daily Attendance</a>
                <a href="results/create.php" class="btn btn-secondary btn-sm">+ Enter Exam Marks</a>
                <a href="students/index.php" class="btn btn-secondary btn-sm">👥 View Students</a>
                <a href="classes/index.php" class="btn btn-secondary btn-sm">🏫 Class Roster</a>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Teacher Assigned Classes -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">My Assigned Classes</div>
                <a href="classes/index.php" class="btn btn-secondary btn-sm">View All &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Class Name</th>
                            <th>Section</th>
                            <th>Room No</th>
                            <th>Student Count</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($assigned_classes)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    No direct class assignments found.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($assigned_classes as $cls): ?>
                                <tr>
                                    <td style="font-weight: 600; color: #fff;"><?php echo htmlspecialchars($cls['class_name']); ?></td>
                                    <td><?php echo htmlspecialchars($cls['section']); ?></td>
                                    <td><?php echo htmlspecialchars($cls['room_no']); ?></td>
                                    <td><span class="badge badge-info"><?php echo $cls['student_count']; ?> Students</span></td>
                                    <td>
                                        <a href="attendance/index.php?class_id=<?php echo $cls['id']; ?>" class="btn btn-primary btn-sm">Attendance</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Faculty Notices -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">Staff & School Notices</div>
                <a href="notices/index.php" class="btn btn-secondary btn-sm">All &rarr;</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_notices)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 20px 0;">No notices published.</p>
                <?php else: ?>
                    <?php foreach ($recent_notices as $notice): ?>
                        <div class="notice-item">
                            <div class="notice-header">
                                <span class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></span>
                                <span class="badge badge-info"><?php echo htmlspecialchars($notice['priority']); ?></span>
                            </div>
                            <div class="notice-content">
                                <?php echo htmlspecialchars(substr($notice['content'], 0, 110)) . (strlen($notice['content']) > 110 ? '...' : ''); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ========================================== -->
    <!-- SUPER ADMIN / ADMIN / STAFF DASHBOARD VIEW -->
    <!-- ========================================== -->
    <div class="grid-stats">
        <div class="stat-card indigo">
            <div class="stat-header">
                <span class="stat-label">Total Active Students</span>
                <div class="stat-icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
            </div>
            <div class="stat-value"><?php echo number_format($total_students); ?></div>
            <div class="stat-footer">Enrolled in 2026 Academic Year</div>
        </div>

        <div class="stat-card emerald">
            <div class="stat-header">
                <span class="stat-label">Faculty & Teachers</span>
                <div class="stat-icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
                    </svg>
                </div>
            </div>
            <div class="stat-value"><?php echo number_format($total_teachers); ?></div>
            <div class="stat-footer">Active instructional faculty</div>
        </div>

        <div class="stat-card amber">
            <div class="stat-header">
                <span class="stat-label">Active Classes</span>
                <div class="stat-icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
            </div>
            <div class="stat-value"><?php echo number_format($total_classes); ?></div>
            <div class="stat-footer">Standard & grade sections</div>
        </div>

        <div class="stat-card sky">
            <div class="stat-header">
                <span class="stat-label">Library Catalog</span>
                <div class="stat-icon">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
            </div>
            <div class="stat-value"><?php echo number_format($total_books); ?></div>
            <div class="stat-footer">Total copies & reference books</div>
        </div>
    </div>

    <!-- Quick Actions Banner -->
    <div class="card" style="background: linear-gradient(135deg, rgba(79, 70, 229, 0.15) 0%, rgba(6, 182, 212, 0.08) 100%); border-color: rgba(79, 70, 229, 0.25);">
        <div class="card-body" style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
            <div>
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 4px;">Quick Management Shortcuts</h3>
                <p style="font-size: 13px; color: var(--text-secondary);">Direct access to key administrative actions across the school portal</p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <?php if (can_manage_students()): ?>
                    <a href="students/create.php" class="btn btn-primary btn-sm">+ New Student Admission</a>
                <?php endif; ?>

                <?php if (can_manage_teachers()): ?>
                    <a href="teachers/create.php" class="btn btn-secondary btn-sm">+ Add Teacher</a>
                <?php endif; ?>

                <a href="attendance/index.php" class="btn btn-secondary btn-sm">Mark Attendance</a>

                <?php if (can_manage_results()): ?>
                    <a href="results/create.php" class="btn btn-secondary btn-sm">+ Enter Marks</a>
                <?php endif; ?>

                <?php if (can_manage_notices()): ?>
                    <a href="notices/create.php" class="btn btn-secondary btn-sm">+ Post Notice</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px;">
        <!-- Recent Student Admissions Table -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Recent Admissions
                </div>
                <a href="students/index.php" class="btn btn-secondary btn-sm">View All Students &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Roll No</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Gender</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recent_students)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 30px;">
                                    No student records found. Click "+ New Student Admission" to add one.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recent_students as $stu): ?>
                                <tr>
                                    <td><span class="badge badge-info"><?php echo htmlspecialchars($stu['roll_no']); ?></span></td>
                                    <td style="font-weight: 600; color: #ffffff;">
                                        <?php echo htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(($stu['class_name'] ?? 'N/A') . ' - ' . ($stu['section'] ?? '')); ?></td>
                                    <td><?php echo htmlspecialchars($stu['gender']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($stu['admission_date'])); ?></td>
                                    <td>
                                        <a href="students/view.php?id=<?php echo $stu['id']; ?>" class="btn btn-secondary btn-sm">View</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Notice Board -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: #f59e0b;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    Latest Announcements
                </div>
                <a href="notices/index.php" class="btn btn-secondary btn-sm">All &rarr;</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_notices)): ?>
                    <p style="color: var(--text-muted); text-align: center; padding: 20px 0;">No notices published yet.</p>
                <?php else: ?>
                    <?php foreach ($recent_notices as $notice): ?>
                        <div class="notice-item">
                            <div class="notice-header">
                                <span class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></span>
                                <?php 
                                    $priority_class = 'badge-info';
                                    if ($notice['priority'] === 'Urgent') $priority_class = 'badge-danger';
                                    elseif ($notice['priority'] === 'Important') $priority_class = 'badge-warning';
                                ?>
                                <span class="badge <?php echo $priority_class; ?>"><?php echo htmlspecialchars($notice['priority']); ?></span>
                            </div>
                            <div class="notice-content">
                                <?php echo htmlspecialchars(substr($notice['content'], 0, 110)) . (strlen($notice['content']) > 110 ? '...' : ''); ?>
                            </div>
                            <div class="notice-meta">
                                <span>By: <?php echo htmlspecialchars($notice['posted_by']); ?></span>
                                <span>•</span>
                                <span>Audience: <?php echo htmlspecialchars($notice['target_audience']); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php include "includes/footer.php"; ?>