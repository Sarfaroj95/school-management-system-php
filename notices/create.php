<?php
/**
 * Post New Notice / Announcement
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$page_title = "Publish Announcement";
$header_title = "Publish Notice";
$current_page = "notices";

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $target_audience = $_POST['target_audience'] ?? 'All';
    $priority = $_POST['priority'] ?? 'Normal';
    $posted_by = trim($_POST['posted_by'] ?? $_SESSION['full_name'] ?? 'Administration');

    if (empty($title) || empty($content)) {
        $error = 'Please provide both title and announcement body content.';
    } else {
        $stmt = $conn->prepare("INSERT INTO notices (title, content, target_audience, priority, posted_by) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssss", $title, $content, $target_audience, $priority, $posted_by);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: index.php?msg=created");
                exit();
            } else {
                $error = 'Failed to publish notice: ' . $stmt->error;
                $stmt->close();
            }
        } else {
            $error = 'Query preparation error: ' . $conn->error;
        }
    }
}

include "../includes/header.php";
?>

<div class="card" style="max-width: 750px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            Publish New Notice / Announcement
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Notices</a>
    </div>

    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="create.php" method="POST">
            <div class="form-group" style="margin-bottom: 20px;">
                <label class="form-label">Notice Title <span class="required">*</span></label>
                <input type="text" name="title" class="form-control" placeholder="e.g. Science Exhibition Registration Open" required value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
            </div>

            <div class="form-grid" style="margin-bottom: 20px;">
                <div class="form-group">
                    <label class="form-label">Target Audience</label>
                    <select name="target_audience" class="form-control">
                        <option value="All">All School Community</option>
                        <option value="Students">Students Only</option>
                        <option value="Teachers">Faculty & Staff</option>
                        <option value="Parents">Parents & Guardians</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Priority Level</label>
                    <select name="priority" class="form-control">
                        <option value="Normal">Normal</option>
                        <option value="Important">Important</option>
                        <option value="Urgent">Urgent / Action Required</option>
                    </select>
                </div>

                <div class="form-group col-span-2">
                    <label class="form-label">Posted By / Department</label>
                    <input type="text" name="posted_by" class="form-control" value="<?php echo htmlspecialchars($_POST['posted_by'] ?? $_SESSION['full_name'] ?? 'Principal Office'); ?>">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 24px;">
                <label class="form-label">Announcement Content <span class="required">*</span></label>
                <textarea name="content" class="form-control" style="min-height: 140px;" placeholder="Write detailed notice details here..." required><?php echo htmlspecialchars($_POST['content'] ?? ''); ?></textarea>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Publish Announcement</button>
            </div>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
