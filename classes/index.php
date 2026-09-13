<?php
/**
 * Classes & Sections Management
 * Uses centralized connection with relative include "../connection.php"
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin', 'Staff', 'Teacher']);

$page_title = "Classes & Sections";
$header_title = "Academic Classes";
$current_page = "classes";

// Fetch classes with teacher name and enrolled student counts
$query = "SELECT c.*, t.name AS teacher_name, 
          (SELECT COUNT(*) FROM students s WHERE s.class_id = c.id) AS student_count 
          FROM classes c 
          LEFT JOIN teachers t ON c.teacher_id = t.id 
          ORDER BY c.class_name ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$classes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include "../includes/header.php";
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Class created successfully!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
    <div class="alert alert-success">Class updated successfully!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Class deleted successfully!</div>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'has_students'): ?>
    <div class="alert alert-danger">Cannot delete class with currently enrolled students. Please reassign students first.</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
            Classes & Grade Sections (<?php echo count($classes); ?>)
        </div>
        <?php if (can_manage_classes()): ?>
            <a href="create.php" class="btn btn-primary btn-sm">+ Add New Class</a>
        <?php endif; ?>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Class Name</th>
                    <th>Section</th>
                    <th>Room No</th>
                    <th>Class Teacher</th>
                    <th>Capacity</th>
                    <th>Enrolled Students</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($classes)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            No classes defined yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($classes as $c): ?>
                        <tr>
                            <td style="font-weight: 700; color: #ffffff;">
                                <?php echo htmlspecialchars($c['class_name']); ?>
                            </td>
                            <td><span class="badge badge-info">Section <?php echo htmlspecialchars($c['section']); ?></span></td>
                            <td><?php echo htmlspecialchars($c['room_no']); ?></td>
                            <td><?php echo htmlspecialchars($c['teacher_name'] ?? 'Unassigned'); ?></td>
                            <td><?php echo $c['capacity']; ?> Seats</td>
                            <td>
                                <a href="../students/index.php?class_id=<?php echo $c['id']; ?>" class="badge badge-purple" style="cursor: pointer;">
                                    <?php echo $c['student_count']; ?> Enrolled
                                </a>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <?php if (can_manage_classes()): ?>
                                        <a href="edit.php?id=<?php echo $c['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                    <?php endif; ?>
                                    <?php if (can_delete()): ?>
                                        <a href="delete.php?id=<?php echo $c['id']; ?>" class="btn btn-danger btn-sm btn-delete-confirm" data-name="<?php echo htmlspecialchars($c['class_name'] . ' Section ' . $c['section']); ?>">Delete</a>
                                    <?php endif; ?>
                                    <?php if (!can_manage_classes() && !can_delete()): ?>
                                        <span style="color: var(--text-muted); font-size: 12px;">View Only</span>
                                    <?php endif; ?>
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
