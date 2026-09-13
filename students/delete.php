<?php
/**
 * Delete Student Record
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin']);

$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($student_id > 0) {
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: index.php?msg=deleted");
exit();
?>
