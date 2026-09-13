<?php
/**
 * View Student Profile & Academic Details
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// If logged in as student, restrict to own profile
if (has_role('Student')) {
    $my_id = intval($_SESSION['student_id'] ?? 0);
    if ($student_id !== $my_id) {
        $student_id = $my_id;
    }
}

if ($student_id <= 0) {
    header("Location: index.php");
    exit();
}

$page_title = "Student Profile";
$header_title = "Student Details";
$current_page = "students";

// Fetch student details with class and teacher info
$stmt = $conn->prepare("SELECT s.*, c.class_name, c.section, c.room_no, t.name AS teacher_name 
                        FROM students s 
                        LEFT JOIN classes c ON s.class_id = c.id 
                        LEFT JOIN teachers t ON c.teacher_id = t.id 
                        WHERE s.id = ? LIMIT 1");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$student) {
    header("Location: index.php");
    exit();
}

// Fetch student exam marks
$marks_stmt = $conn->prepare("SELECT m.*, sub.subject_name, sub.subject_code 
                             FROM marks m 
                             JOIN subjects sub ON m.subject_id = sub.id 
                             WHERE m.student_id = ? 
                             ORDER BY m.id DESC");
$marks_stmt->bind_param("i", $student_id);
$marks_stmt->execute();
$exam_marks = $marks_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$marks_stmt->close();

// Fetch student attendance logs
$att_stmt = $conn->prepare("SELECT * FROM attendance WHERE student_id = ? ORDER BY attendance_date DESC LIMIT 10");
$att_stmt->bind_param("i", $student_id);
$att_stmt->execute();
$attendance_logs = $att_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$att_stmt->close();

include "../includes/header.php";
?>

<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
    <?php if (!has_role('Student')): ?>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Students List</a>
    <?php else: ?>
        <a href="../index.php" class="btn btn-secondary btn-sm">&larr; Back to Dashboard</a>
    <?php endif; ?>
    <div style="display: flex; gap: 8px;">
        <?php if (can_manage_students()): ?>
            <a href="edit.php?id=<?php echo $student['id']; ?>" class="btn btn-primary btn-sm">Edit Profile</a>
        <?php endif; ?>
        <?php if (can_delete()): ?>
            <a href="delete.php?id=<?php echo $student['id']; ?>" class="btn btn-danger btn-sm btn-delete-confirm" data-name="<?php echo htmlspecialchars($student['first_name']); ?>">Delete</a>
        <?php endif; ?>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
    <!-- Profile Info Card -->
    <div class="card">
        <div class="card-body" style="text-align: center; padding-bottom: 16px;">
            <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--primary-gradient); display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 800; color: #fff; margin: 0 auto 16px auto; box-shadow: var(--shadow-glow);">
                <?php echo strtoupper(substr($student['first_name'], 0, 1)); ?>
            </div>
            <h2 style="font-size: 20px; font-weight: 700; color: #ffffff;"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h2>
            <p style="color: #38bdf8; font-weight: 600; font-size: 14px; margin-top: 4px;"><?php echo htmlspecialchars($student['roll_no']); ?></p>
            <div style="margin-top: 10px;">
                <span class="badge badge-success"><?php echo htmlspecialchars($student['status']); ?></span>
            </div>
        </div>

        <div style="padding: 0 24px 24px 24px; font-size: 13px;">
            <div style="border-top: 1px solid var(--border-color); padding: 12px 0; display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Class / Section:</span>
                <span style="font-weight: 600; color: #f1f5f9;"><?php echo htmlspecialchars(($student['class_name'] ?? 'N/A') . ' - ' . ($student['section'] ?? '')); ?></span>
            </div>
            <div style="border-top: 1px solid var(--border-color); padding: 12px 0; display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Class Teacher:</span>
                <span style="font-weight: 600; color: #f1f5f9;"><?php echo htmlspecialchars($student['teacher_name'] ?? 'Unassigned'); ?></span>
            </div>
            <div style="border-top: 1px solid var(--border-color); padding: 12px 0; display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Date of Birth:</span>
                <span style="font-weight: 600; color: #f1f5f9;"><?php echo date('M d, Y', strtotime($student['dob'])); ?></span>
            </div>
            <div style="border-top: 1px solid var(--border-color); padding: 12px 0; display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Gender:</span>
                <span style="font-weight: 600; color: #f1f5f9;"><?php echo htmlspecialchars($student['gender']); ?></span>
            </div>
            <div style="border-top: 1px solid var(--border-color); padding: 12px 0; display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Admission Date:</span>
                <span style="font-weight: 600; color: #f1f5f9;"><?php echo date('M d, Y', strtotime($student['admission_date'])); ?></span>
            </div>
            <div style="border-top: 1px solid var(--border-color); padding: 12px 0; display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Parent / Guardian:</span>
                <span style="font-weight: 600; color: #f1f5f9;"><?php echo htmlspecialchars($student['parent_name']); ?></span>
            </div>
            <div style="border-top: 1px solid var(--border-color); padding: 12px 0; display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Parent Phone:</span>
                <span style="font-weight: 600; color: #f1f5f9;"><?php echo htmlspecialchars($student['parent_phone']); ?></span>
            </div>
            <div style="border-top: 1px solid var(--border-color); padding: 12px 0; display: flex; justify-content: space-between;">
                <span style="color: var(--text-muted);">Address:</span>
                <span style="font-weight: 600; color: #f1f5f9; max-width: 180px; text-align: right;"><?php echo htmlspecialchars($student['address']); ?></span>
            </div>
        </div>
    </div>

    <!-- Academic Records & Attendance -->
    <div>
        <!-- Exam Results -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: #6366f1;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Academic Performance & Exam Marks
                </div>
                <a href="../results/create.php" class="btn btn-secondary btn-sm">+ Record Marks</a>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Exam Name</th>
                            <th>Score</th>
                            <th>Grade</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($student_marks)): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted); padding: 24px;">No exam marks recorded yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($student_marks as $mark): ?>
                                <tr>
                                    <td style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                                    <td><?php echo htmlspecialchars($mark['exam_name']); ?></td>
                                    <td><strong><?php echo $mark['marks_obtained']; ?></strong> / <?php echo $mark['max_marks']; ?></td>
                                    <td><span class="badge badge-purple"><?php echo htmlspecialchars($mark['grade']); ?></span></td>
                                    <td style="font-size: 12px; color: var(--text-secondary);"><?php echo htmlspecialchars($mark['remarks'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recent Attendance Logs -->
        <div class="card">
            <div class="card-header">
                <div class="card-title">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: #10b981;">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Recent Attendance Logs
                </div>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($attendance_logs)): ?>
                            <tr>
                                <td colspan="3" style="text-align: center; color: var(--text-muted); padding: 24px;">No attendance logged for this student.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($attendance_logs as $att): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($att['attendance_date'])); ?></td>
                                    <td>
                                        <?php
                                            $att_badge = 'badge-success';
                                            if ($att['status'] === 'Absent') $att_badge = 'badge-danger';
                                            elseif ($att['status'] === 'Late') $att_badge = 'badge-warning';
                                            elseif ($att['status'] === 'Excused') $att_badge = 'badge-info';
                                        ?>
                                        <span class="badge <?php echo $att_badge; ?>"><?php echo htmlspecialchars($att['status']); ?></span>
                                    </td>
                                    <td style="color: var(--text-secondary);"><?php echo htmlspecialchars($att['remarks'] ?? '-'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
