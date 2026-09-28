<?php
/**
 * Delete Subject Handler
 * Restricted to Super Admin and Admin only.
 * Blocks deletion if subject has associated exam marks.
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin']);

$subject_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($subject_id > 0) {
    // Block deletion if marks reference this subject
    $check = $conn->prepare("SELECT COUNT(*) AS total FROM marks WHERE subject_id = ?");
    $check->bind_param("i", $subject_id);
    $check->execute();
    $marks_count = $check->get_result()->fetch_assoc()['total'];
    $check->close();

    if ($marks_count > 0) {
        header("Location: index.php?error=has_marks");
        exit();
    }

    // Safe to delete
    $stmt = $conn->prepare("DELETE FROM subjects WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: index.php?msg=deleted");
exit();
?>
