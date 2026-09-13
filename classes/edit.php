<?php
/**
 * Edit Class & Section Form
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$page_title = "Edit Class";
$header_title = "Update Class";
$current_page = "classes";

$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($class_id <= 0) {
    header("Location: index.php");
    exit();
}

$error = '';

// Fetch class record
$stmt = $conn->prepare("SELECT * FROM classes WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $class_id);
$stmt->execute();
$class = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$class) {
    header("Location: index.php");
    exit();
}

// Fetch active teachers
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
        $error = 'Please fill in all mandatory fields.';
    } else {
        $update_stmt = $conn->prepare("UPDATE classes SET class_name = ?, section = ?, room_no = ?, teacher_id = ?, capacity = ? WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("sssiii", $class_name, $section, $room_no, $teacher_id, $capacity, $class_id);
            if ($update_stmt->execute()) {
                $update_stmt->close();
                header("Location: index.php?msg=updated");
                exit();
            } else {
                $error = 'Update failed: ' . $update_stmt->error;
                $update_stmt->close();
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit Class: <?php echo htmlspecialchars($class['class_name'] . ' - ' . $class['section']); ?>
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Classes</a>
    </div>

    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="edit.php?id=<?php echo $class_id; ?>" method="POST">
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">Class / Grade Name <span class="required">*</span></label>
                    <input type="text" name="class_name" class="form-control" required value="<?php echo htmlspecialchars($_POST['class_name'] ?? $class['class_name']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Section <span class="required">*</span></label>
                    <input type="text" name="section" class="form-control" required value="<?php echo htmlspecialchars($_POST['section'] ?? $class['section']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Room Number <span class="required">*</span></label>
                    <input type="text" name="room_no" class="form-control" required value="<?php echo htmlspecialchars($_POST['room_no'] ?? $class['room_no']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Class Capacity</label>
                    <input type="number" name="capacity" class="form-control" value="<?php echo htmlspecialchars($_POST['capacity'] ?? $class['capacity']); ?>">
                </div>

                <div class="form-group col-span-2">
                    <label class="form-label">Class Teacher In-Charge</label>
                    <select name="teacher_id" class="form-control">
                        <option value="">-- Select Faculty In-Charge --</option>
                        <?php foreach ($teachers as $t): ?>
                            <option value="<?php echo $t['id']; ?>" <?php echo ($class['teacher_id'] == $t['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($t['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
