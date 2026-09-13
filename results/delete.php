<?php
/**
 * Delete Exam Marks Record
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$mark_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($mark_id > 0) {
    $stmt = $conn->prepare("DELETE FROM marks WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $mark_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: index.php?msg=deleted");
exit();
?>
