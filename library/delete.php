<?php
/**
 * Delete Library Book
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($book_id > 0) {
    $stmt = $conn->prepare("DELETE FROM library_books WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $book_id);
        $stmt->execute();
        $stmt->close();
    }
}

header("Location: index.php?msg=deleted");
exit();
?>
