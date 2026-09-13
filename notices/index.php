<?php
/**
 * Notice Board Management
 * Uses centralized connection with relative include "../connection.php"
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$page_title = "Notice Board";
$header_title = "School Announcements";
$current_page = "notices";

$audience_filter = isset($_GET['audience']) ? trim($_GET['audience']) : '';

if (!empty($audience_filter)) {
    $stmt = $conn->prepare("SELECT * FROM notices WHERE target_audience = ? OR target_audience = 'All' ORDER BY id DESC");
    $stmt->bind_param("s", $audience_filter);
} else {
    $stmt = $conn->prepare("SELECT * FROM notices ORDER BY id DESC");
}

$stmt->execute();
$notices = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include "../includes/header.php";
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Announcement published successfully!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
    <div class="alert alert-success">Announcement updated successfully!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Announcement deleted!</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
            Active Announcements (<?php echo count($notices); ?>)
        </div>
        <a href="create.php" class="btn btn-primary btn-sm">+ Publish New Notice</a>
    </div>

    <!-- Audience Filter -->
    <div style="padding: 16px 24px; border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.15);">
        <form method="GET" action="index.php" style="display: flex; gap: 12px; align-items: center;">
            <label class="form-label" style="margin:0;">Target Audience:</label>
            <select name="audience" class="form-control" style="width: auto;" onchange="this.form.submit()">
                <option value="">All Audiences</option>
                <option value="Students" <?php echo ($audience_filter === 'Students') ? 'selected' : ''; ?>>Students</option>
                <option value="Teachers" <?php echo ($audience_filter === 'Teachers') ? 'selected' : ''; ?>>Teachers</option>
                <option value="Parents" <?php echo ($audience_filter === 'Parents') ? 'selected' : ''; ?>>Parents</option>
            </select>
            <?php if (!empty($audience_filter)): ?>
                <a href="index.php" class="btn btn-secondary btn-sm" style="color: var(--danger);">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="card-body">
        <?php if (empty($notices)): ?>
            <p style="text-align: center; color: var(--text-muted); padding: 40px;">No announcements found. Click "+ Publish New Notice" to create one.</p>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($notices as $n): ?>
                    <div class="notice-item" style="padding: 20px;">
                        <div class="notice-header">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="notice-title" style="font-size: 16px;"><?php echo htmlspecialchars($n['title']); ?></span>
                                <?php 
                                    $p_class = 'badge-info';
                                    if ($n['priority'] === 'Urgent') $p_class = 'badge-danger';
                                    elseif ($n['priority'] === 'Important') $p_class = 'badge-warning';
                                ?>
                                <span class="badge <?php echo $p_class; ?>"><?php echo htmlspecialchars($n['priority']); ?></span>
                                <span class="badge badge-purple"><?php echo htmlspecialchars($n['target_audience']); ?></span>
                            </div>
                            <div style="display: flex; gap: 6px;">
                                <a href="edit.php?id=<?php echo $n['id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                <a href="delete.php?id=<?php echo $n['id']; ?>" class="btn btn-danger btn-sm btn-delete-confirm" data-name="notice '<?php echo htmlspecialchars($n['title']); ?>'">Delete</a>
                            </div>
                        </div>

                        <div class="notice-content" style="font-size: 14px; margin: 12px 0;">
                            <?php echo nl2br(htmlspecialchars($n['content'])); ?>
                        </div>

                        <div class="notice-meta">
                            <span>Posted by: <strong><?php echo htmlspecialchars($n['posted_by']); ?></strong></span>
                            <span>•</span>
                            <span>Date: <?php echo date('F j, Y - g:i A', strtotime($n['created_at'])); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
