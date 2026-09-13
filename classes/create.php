<?php
/**
 * Add New Class Form
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$page_title = "Add Class & Section";
$header_title = "Create Class";
$current_page = "classes";

$error = '';

// Fetch teachers for the in-charge dropdown
$t_stmt = $conn->prepare("SELECT id, name FROM teachers WHERE status = 'Active' ORDER BY name ASC");
$t_stmt->execute();
$teachers = $t_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$t_stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $class_name = trim($_POST['class_name'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $room_no = trim($_POST['room_no'] ?? '');
    $teacher_id = !empty($_POST['teacher_id']) ? intval($_POST['teacher_id']) : null;
    $capacity = intval($_POST['capacity'] ?? 40);

    if (empty($class_name) || empty($section) || empty($room_no)) {
        $error = 'Please fill in class name, section, and room number.';
    } else {
        $stmt = $conn->prepare("INSERT INTO classes (class_name, section, room_no, teacher_id, capacity) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssii", $class_name, $section, $room_no, $teacher_id, $capacity);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: index.php?msg=created");
                exit();
            } else {
                $error = 'Failed to create class: ' . $stmt->error;
                $stmt->close();
            }
        } else {
            $error = 'Query error: ' . $conn->error;
        }
    }
}

include "../includes/header.php";
?>

<div class="card" style="max-width: 650px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            Create New Class & Section
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Classes</a>
    </div>

    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="create.php" method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Class / Grade Name <span class="required">*</span></label>
                    <input type="text" name="class_name" class="form-control" placeholder="e.g. Grade 11" required value="<?php echo htmlspecialchars($_POST['class_name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Section <span class="required">*</span></label>
                    <input type="text" name="section" class="form-control" placeholder="e.g. A, B, Science" required value="<?php echo htmlspecialchars($_POST['section'] ?? 'A'); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Room Number <span class="required">*</span></label>
                    <input type="text" name="room_no" class="form-control" placeholder="e.g. Room 402" required value="<?php echo htmlspecialchars($_POST['room_no'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Class Capacity (Seats)</label>
                    <input type="number" name="capacity" class="form-control" value="<?php echo htmlspecialchars($_POST['capacity'] ?? '40'); ?>">
                </div>

                <div class="form-group col-span-2">
                    <label class="form-label">Class Teacher In-Charge</label>
                    <select name="teacher_id" class="form-control">
                        <option value="">-- Select Faculty In-Charge --</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo (isset($_POST['teacher_id']) && $_POST['teacher_id'] == $t['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Create Class</button>
            </div>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
