<?php
/**
 * Delete Teacher Record
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin']);

$teacher_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($teacher_id > 0) {
    $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: index.php?msg=deleted");
exit();
?>
