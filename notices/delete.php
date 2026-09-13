<?php
/**
 * Delete Notice / Announcement
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$notice_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($notice_id > 0) {
    $stmt = $conn->prepare("DELETE FROM notices WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $notice_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: index.php?msg=deleted");
exit();
?>
