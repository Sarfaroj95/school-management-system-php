<?php
/**
 * Main Dashboard Overview
 * School Management System
 */
include "connection.php";

$root_path = '';
include "includes/auth.php";

$page_title = "Dashboard Overview";
$header_title = "Academic Dashboard";
$current_page = "dashboard";

// Fetch Summary Metric Counts via Prepared Statements / Queries on $conn
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

// Fetch Today's Attendance Status
$today_present = 0;
$today_total = 0;
$att_stmt = $conn->prepare("SELECT status, COUNT(*) as cnt FROM attendance WHERE attendance_date = CURDATE() GROUP BY status");
if ($att_stmt) {
    $att_stmt->execute();
    $att_res = $att_stmt->get_result();
    while ($row = $att_res->fetch_assoc()) {
        $today_total += $row['cnt'];
        if ($row['status'] === 'Present') {
            $today_present += $row['cnt'];
        }
    }
    $att_stmt->close();
}

$attendance_rate = $today_total > 0 ? round(($today_present / $today_total) * 100) : 100;

// Fetch Recent Admissions (Limit 5)
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

// Fetch Latest Notices (Limit 3)
$recent_notices = [];
$not_stmt = $conn->prepare("SELECT id, title, content, target_audience, priority, posted_by, created_at 
                            FROM notices ORDER BY id DESC LIMIT 3");
if ($not_stmt) {
    $not_stmt->execute();
    $recent_notices = $not_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $not_stmt->close();
}

include "includes/header.php";
?>

<!-- Statistics Overview Grid -->
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
        <div class="stat-footer">Active instructional staff</div>
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
            <a href="students/create.php" class="btn btn-primary btn-sm">
                + New Student Admission
            </a>
            <a href="teachers/create.php" class="btn btn-secondary btn-sm">
                + Add Teacher
            </a>
            <a href="attendance/index.php" class="btn btn-secondary btn-sm">
                Mark Attendance
            </a>
            <a href="results/create.php" class="btn btn-secondary btn-sm">
                + Enter Marks
            </a>
            <a href="notices/create.php" class="btn btn-secondary btn-sm">
                + Post Notice
            </a>
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

<?php include "includes/footer.php"; ?>