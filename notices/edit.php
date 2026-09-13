<?php
/**
 * Edit Notice Form
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$page_title = "Edit Announcement";
$header_title = "Update Notice";
$current_page = "notices";

$notice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($notice_id <= 0) {
    header("Location: index.php");
    exit();
}

$error = '';

$stmt = $conn->prepare("SELECT * FROM notices WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $notice_id);
$stmt->execute();
$notice = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$notice) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $target_audience = $_POST['target_audience'] ?? 'All';
    $priority = $_POST['priority'] ?? 'Normal';
    $posted_by = trim($_POST['posted_by'] ?? 'Administration');

    if (empty($title) || empty($content)) {
        $error = 'Please fill in all mandatory fields.';
    } else {
        $update_stmt = $conn->prepare("UPDATE notices SET title = ?, content = ?, target_audience = ?, priority = ?, posted_by = ? WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("sssssi", $title, $content, $target_audience, $priority, $posted_by, $notice_id);
            if ($update_stmt->execute()) {
                $update_stmt->close();
                header("Location: index.php?msg=updated");
                exit();
            } else {
                $error = 'Failed to update notice: ' . $update_stmt->error;
                $update_stmt->close();
            }
        } else {
            $error = 'Query error: ' . $conn->error;
        }
    }
}

include "../includes/header.php";
?>

<div class="card" style="max-width: 750px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit Announcement: <?php echo htmlspecialchars($notice['title']); ?>
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Notices</a>
    </div>

    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="edit.php?id=<?php echo $notice_id; ?>" method="POST">
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Notice Title <span class="required">*</span></label>
                <input type="text" name="title" class="form-control" required value="<?php echo htmlspecialchars($_POST['title'] ?? $notice['title']); ?>">
            </div>

            <div class="form-grid" style="margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label">Target Audience</label>
                    <select name="target_audience" class="form-control">
                        <option value="All" <?php echo ($notice['target_audience'] === 'All') ? 'selected' : ''; ?>>All School Community</option>
                        <option value="Students" <?php echo ($notice['target_audience'] === 'Students') ? 'selected' : ''; ?>>Students Only</option>
                        <option value="Teachers" <?php echo ($notice['target_audience'] === 'Teachers') ? 'selected' : ''; ?>>Faculty & Staff</option>
                        <option value="Parents" <?php echo ($notice['target_audience'] === 'Parents') ? 'selected' : ''; ?>>Parents & Guardians</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Priority Level</label>
                    <select name="priority" class="form-control">
                        <option value="Normal" <?php echo ($notice['priority'] === 'Normal') ? 'selected' : ''; ?>>Normal</option>
                        <option value="Important" <?php echo ($notice['priority'] === 'Important') ? 'selected' : ''; ?>>Important</option>
                        <option value="Urgent" <?php echo ($notice['priority'] === 'Urgent') ? 'selected' : ''; ?>>Urgent / Action Required</option>
                    </select>
                </div>

                <div class="form-group col-span-2">
                    <label class="form-label">Posted By / Department</label>
                    <input type="text" name="posted_by" class="form-control" value="<?php echo htmlspecialchars($_POST['posted_by'] ?? $notice['posted_by']); ?>">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label">Announcement Content <span class="required">*</span></label>
                <textarea name="content" class="form-control" style="min-height: 140px;" required><?php echo htmlspecialchars($_POST['content'] ?? $notice['content']); ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
