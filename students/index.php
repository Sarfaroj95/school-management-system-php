<?php
/**
 * Students Directory & Management
 * Uses centralized connection with relative include "../connection.php"
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$page_title = "Students Directory";
$header_title = "Students Management";
$current_page = "students";

// Filter params
$class_filter = isset($_GET['class_id']) ? intval($_GET['class_id']) : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Fetch all classes for filter dropdown
$classes_stmt = $conn->prepare("SELECT id, class_name, section FROM classes ORDER BY class_name ASC");
$classes_stmt->execute();
$classes_list = $classes_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$classes_stmt->close();

// Build query using prepared statement
if ($class_filter > 0 && !empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    $stmt = $conn->prepare("SELECT s.*, c.class_name, c.section 
                            FROM students s 
                            LEFT JOIN classes c ON s.class_id = c.id 
                            WHERE s.class_id = ? AND (s.first_name LIKE ? OR s.last_name LIKE ? OR s.roll_no LIKE ? OR s.email LIKE ?)
                            ORDER BY s.id DESC");
    $stmt->bind_param("issss", $class_filter, $search_param, $search_param, $search_param, $search_param);
} elseif ($class_filter > 0) {
    $stmt = $conn->prepare("SELECT s.*, c.class_name, c.section 
                            FROM students s 
                            LEFT JOIN classes c ON s.class_id = c.id 
                            WHERE s.class_id = ?
                            ORDER BY s.id DESC");
    $stmt->bind_param("i", $class_filter);
} elseif (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    $stmt = $conn->prepare("SELECT s.*, c.class_name, c.section 
                            FROM students s 
                            LEFT JOIN classes c ON s.class_id = c.id 
                            WHERE (s.first_name LIKE ? OR s.last_name LIKE ? OR s.roll_no LIKE ? OR s.email LIKE ?)
                            ORDER BY s.id DESC");
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
} else {
    $stmt = $conn->prepare("SELECT s.*, c.class_name, c.section 
                            FROM students s 
                            LEFT JOIN classes c ON s.class_id = c.id 
                            ORDER BY s.id DESC");
}

$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include "../includes/header.php";
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Student admission recorded successfully!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
    <div class="alert alert-success">Student details updated successfully!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Student record deleted successfully!</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            All Registered Students (<?php echo count($students); ?>)
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="create.php" class="btn btn-primary btn-sm">+ Admit New Student</a>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div style="padding: 16px 24px; border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.15);">
        <form method="GET" action="index.php" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <input type="text" name="search" class="search-input" placeholder="Search by name, roll, email..." value="<?php echo htmlspecialchars($search_query); ?>">
            
            <select name="class_id" class="form-control" style="width: auto; padding: 8px 14px; font-size: 13px;">
                <option value="0">All Classes</option>
                <?php foreach ($classes_list as $cls): ?>
                    <option value="<?php echo $cls['id']; ?>" <?php echo ($class_filter === $cls['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cls['class_name'] . ' - ' . $cls['section']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <?php if (!empty($search_query) || $class_filter > 0): ?>
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
                    <th>Gender</th>
                    <th>Parent / Guardian</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            No student records match your criteria.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $stu): ?>
                        <tr>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($stu['roll_no']); ?></span></td>
                            <td style="font-weight: 600; color: #ffffff;">
                                <a href="view.php?id=<?php echo $stu['id']; ?>" style="color: #ffffff; hover: underline;">
                                    <?php echo htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars(($stu['class_name'] ?? 'Unassigned') . ' ' . ($stu['section'] ?? '')); ?></td>
                            <td><?php echo htmlspecialchars($stu['gender']); ?></td>
                            <td><?php echo htmlspecialchars($stu['parent_name']); ?></td>
                            <td style="font-size: 13px;"><?php echo htmlspecialchars($stu['parent_phone']); ?></td>
                            <td>
                                <?php 
                                    $st_class = 'badge-success';
                                    if ($stu['status'] === 'Inactive') $st_class = 'badge-warning';
                                    elseif ($stu['status'] === 'Suspended') $st_class = 'badge-danger';
                                ?>
                                <span class="badge <?php echo $st_class; ?>"><?php echo htmlspecialchars($stu['status']); ?></span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="view.php?id=<?php echo $stu['id']; ?>" class="btn btn-secondary btn-sm" title="View Profile">View</a>
                                    <a href="edit.php?id=<?php echo $stu['id']; ?>" class="btn btn-secondary btn-sm" title="Edit Student">Edit</a>
                                    <a href="delete.php?id=<?php echo $stu['id']; ?>" class="btn btn-danger btn-sm btn-delete-confirm" data-name="<?php echo htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']); ?>" title="Delete Student">Delete</a>
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
