<?php
/**
 * Edit Library Book
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$page_title = "Edit Book";
$header_title = "Update Catalog";
$current_page = "library";

$book_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($book_id <= 0) {
    header("Location: index.php");
    exit();
}

$error = '';

$stmt = $conn->prepare("SELECT * FROM library_books WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $book_id);
$stmt->execute();
$book = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$book) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_title = trim($_POST['book_title'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 1);
    $available_copies = intval($_POST['available_copies'] ?? 1);
    $rack_no = trim($_POST['rack_no'] ?? '');

    if (empty($book_title) || empty($isbn) || empty($author) || empty($category) || $quantity <= 0) {
        $error = 'Please fill in all mandatory fields with valid numbers.';
    } else {
        $update_stmt = $conn->prepare("UPDATE library_books SET book_title = ?, isbn = ?, author = ?, category = ?, quantity = ?, available_copies = ?, rack_no = ? WHERE id = ?");
        if ($update_stmt) {
            $update_stmt->bind_param("ssssiisi", $book_title, $isbn, $author, $category, $quantity, $available_copies, $rack_no, $book_id);
            if ($update_stmt->execute()) {
                $update_stmt->close();
                header("Location: index.php?msg=updated");
                exit();
            } else {
                $error = 'Failed to update book: ' . $update_stmt->error;
                $update_stmt->close();
            }
        } else {
            $error = 'Query error: ' . $conn->error;
        }
    }
}

include "../includes/header.php";
?>

<div class="card" style="max-width: 750px; margin: 0 auto;">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit Book: <?php echo htmlspecialchars($book['book_title']); ?>
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Catalog</a>
    </div>

    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="edit.php?id=<?php echo $book_id; ?>" method="POST">
            <div class="form-grid">
                <div class="form-group col-span-2">
                    <label class="form-label">Book Title <span class="required">*</span></label>
                    <input type="text" name="book_title" class="form-control" required value="<?php echo htmlspecialchars($_POST['book_title'] ?? $book['book_title']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">ISBN Number <span class="required">*</span></label>
                    <input type="text" name="isbn" class="form-control" required value="<?php echo htmlspecialchars($_POST['isbn'] ?? $book['isbn']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Author Name <span class="required">*</span></label>
                    <input type="text" name="author" class="form-control" required value="<?php echo htmlspecialchars($_POST['author'] ?? $book['author']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Category / Subject <span class="required">*</span></label>
                    <input type="text" name="category" class="form-control" required value="<?php echo htmlspecialchars($_POST['category'] ?? $book['category']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Rack / Shelf Location</label>
                    <input type="text" name="rack_no" class="form-control" value="<?php echo htmlspecialchars($_POST['rack_no'] ?? $book['rack_no']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Total Quantity</label>
                    <input type="number" name="quantity" min="1" class="form-control" required value="<?php echo htmlspecialchars($_POST['quantity'] ?? $book['quantity']); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Available Copies</label>
                    <input type="number" name="available_copies" min="0" class="form-control" required value="<?php echo htmlspecialchars($_POST['available_copies'] ?? $book['available_copies']); ?>">
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
