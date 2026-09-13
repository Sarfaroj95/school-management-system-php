<?php
/**
 * Teachers Directory & Management
 * Uses centralized connection with relative include "../connection.php"
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$page_title = "Teachers Directory";
$header_title = "Teachers & Faculty";
$current_page = "teachers";

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

if (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE (name LIKE ? OR emp_id LIKE ? OR subject_specialization LIKE ? OR email LIKE ?) ORDER BY id DESC");
    $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
} else {
    $stmt = $conn->prepare("SELECT * FROM teachers ORDER BY id DESC");
}

$stmt->execute();
$teachers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include "../includes/header.php";
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Teacher record added successfully!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
    <div class="alert alert-success">Teacher details updated successfully!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Teacher record deleted successfully!</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z" />
            </svg>
            Faculty Members (<?php echo count($teachers); ?>)
        </div>
        <a href="create.php" class="btn btn-primary btn-sm">+ Add New Teacher</a>
    </div>

    <!-- Filter & Search Toolbar -->
    <div style="padding: 16px 24px; border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.15);">
        <form method="GET" action="index.php" style="display: flex; gap: 12px; align-items: center;">
            <input type="text" name="search" class="search-input" placeholder="Search teacher by name, ID, subject..." value="<?php echo htmlspecialchars($search_query); ?>">
            <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            <?php if (!empty($search_query)): ?>
                <a href="index.php" class="btn btn-secondary btn-sm" style="color: var(--danger);">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Emp ID</th>
                    <th>Teacher Name</th>
                    <th>Specialization</th>
                    <th>Qualification</th>
                    <th>Email / Phone</th>
                    <th>Joining Date</th>
                    <th>Status</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($teachers)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            No teacher records found. Click "+ Add New Teacher" to register faculty.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($teachers as $t): ?>
                        <tr>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($t['emp_id']); ?></span></td>
                            <td style="font-weight: 600; color: #ffffff;">
                                <?php echo htmlspecialchars($t['name']); ?>
                            </td>
                            <td><span class="badge badge-purple"><?php echo htmlspecialchars($t['subject_specialization']); ?></span></td>
                            <td><?php echo htmlspecialchars($t['qualification']); ?></td>
                            <td>
                                <div><?php echo htmlspecialchars($t['email']); ?></div>
                                <div style="font-size: 12px; color: var(--text-muted);"><?php echo htmlspecialchars($t['phone']); ?></div>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($t['joining_date'])); ?></td>
                            <td>
                                <span class="badge <?php echo ($t['status'] === 'Active') ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo htmlspecialchars($t['status']); ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="edit.php?id=<?php echo $t['id']; ?>" class="btn btn-secondary btn-sm" title="Edit Teacher">Edit</a>
                                    <a href="delete.php?id=<?php echo $t['id']; ?>" class="btn btn-danger btn-sm btn-delete-confirm" data-name="<?php echo htmlspecialchars($t['name']); ?>" title="Delete Teacher">Delete</a>
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
