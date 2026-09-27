<?php
/**
 * User Account Deletion Handler
 * Enforces admin authorization and prevents self-deletion
 */
$root_path = '../';
include $root_path . "connection.php";
include $root_path . "includes/auth.php";

require_role(['Super Admin', 'Admin']);

$type = $_GET['type'] ?? 'admin';
$id = intval($_GET['id'] ?? 0);

if ($id <= 0) {
    header("Location: index.php?error=" . urlencode("Invalid user ID."));
    exit();
}

if ($type === 'admin') {
    // Prevent self-deletion
    if ($id == ($_SESSION['user_id'] ?? 0)) {
        header("Location: index.php?tab=staff&error=" . urlencode("Security restriction: You cannot delete your currently active account."));
        exit();
    }

    $stmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: index.php?tab=staff&msg=" . urlencode("Administrative user successfully removed."));
    } else {
        header("Location: index.php?tab=staff&error=" . urlencode("Failed to delete user: " . $conn->error));
    }
    $stmt->close();
} elseif ($type === 'teacher') {
    $stmt = $conn->prepare("DELETE FROM teachers WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: index.php?tab=teachers&msg=" . urlencode("Faculty member successfully removed."));
    } else {
        header("Location: index.php?tab=teachers&error=" . urlencode("Failed to delete teacher: " . $conn->error));
    }
    $stmt->close();
} elseif ($type === 'student') {
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        header("Location: index.php?tab=students&msg=" . urlencode("Student record successfully removed."));
    } else {
        header("Location: index.php?tab=students&error=" . urlencode("Failed to delete student: " . $conn->error));
    }
    $stmt->close();
} else {
    header("Location: index.php?error=" . urlencode("Invalid account category."));
}
exit();
?>
