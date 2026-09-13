<?php
/**
 * Delete Class Handler
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin']);

$class_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($class_id > 0) {
    // Check for enrolled students
    $check_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM students WHERE class_id = ?");
    $check_stmt->bind_param("i", $class_id);
    $check_stmt->execute();
    $student_count = $check_stmt->get_result()->fetch_assoc()['total'];
    $check_stmt->close();

    if ($student_count > 0) {
        header("Location: index.php?error=has_students");
        exit();
    }

    // Safe to delete
    $stmt = $conn->prepare("DELETE FROM classes WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $class_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: index.php?msg=deleted");
exit();
?>
