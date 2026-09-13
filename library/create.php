<?php
/**
 * Add New Library Book Form
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin', 'Staff']);

$page_title = "Add Library Book";
$header_title = "Add Book";
$current_page = "library";

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book_title = trim($_POST['book_title'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $quantity = intval($_POST['quantity'] ?? 1);
    $rack_no = trim($_POST['rack_no'] ?? '');

    if (empty($book_title) || empty($isbn) || empty($author) || empty($category) || $quantity <= 0) {
        $error = 'Please fill in all mandatory fields with valid quantities.';
    } else {
        $stmt = $conn->prepare("INSERT INTO library_books (book_title, isbn, author, category, quantity, available_copies, rack_no) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $available_copies = $quantity;
            $stmt->bind_param("ssssiis", $book_title, $isbn, $author, $category, $quantity, $available_copies, $rack_no);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: index.php?msg=created");
                exit();
            } else {
                $error = 'Failed to add book: ' . $stmt->error;
                $stmt->close();
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
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0z" />
            </svg>
            Add Book to Library Catalog
        </div>
        <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Catalog</a>
    </div>

    <div class="card-body">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="create.php" method="POST">
            <div class="form-grid">
                <div class="form-group col-span-2">
                    <label class="form-label">Book Title <span class="required">*</span></label>
                    <input type="text" name="book_title" class="form-control" placeholder="e.g. Design Patterns: Elements of Reusable Object-Oriented Software" required value="<?php echo htmlspecialchars($_POST['book_title'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">ISBN Number <span class="required">*</span></label>
                    <input type="text" name="isbn" class="form-control" placeholder="e.g. 978-0201633610" required value="<?php echo htmlspecialchars($_POST['isbn'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Author Name <span class="required">*</span></label>
                    <input type="text" name="author" class="form-control" placeholder="e.g. Erich Gamma, Richard Helm" required value="<?php echo htmlspecialchars($_POST['author'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Category / Subject <span class="required">*</span></label>
                    <input type="text" name="category" class="form-control" placeholder="e.g. Computer Science, Science, Literature" required value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label">Total Quantity / Copies <span class="required">*</span></label>
                    <input type="number" name="quantity" min="1" class="form-control" value="<?php echo htmlspecialchars($_POST['quantity'] ?? '5'); ?>" required>
                </div>

                <div class="form-group col-span-2">
                    <label class="form-label">Rack / Shelf Location</label>
                    <input type="text" name="rack_no" class="form-control" placeholder="e.g. Rack CS-04" value="<?php echo htmlspecialchars($_POST['rack_no'] ?? ''); ?>">
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <a href="index.php" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Add Book to Catalog</button>
            </div>
        </form>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
