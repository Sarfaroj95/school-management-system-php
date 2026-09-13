<?php
/**
 * Library Catalog & Inventory
 * Uses centralized connection with relative include "../connection.php"
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$page_title = "Library Catalog";
$header_title = "Library Management";
$current_page = "library";

$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$category_filter = isset($_GET['category']) ? trim($_GET['category']) : '';

// Build prepared query
if (!empty($category_filter) && !empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    $stmt = $conn->prepare("SELECT * FROM library_books WHERE category = ? AND (book_title LIKE ? OR isbn LIKE ? OR author LIKE ?) ORDER BY id DESC");
    $stmt->bind_param("ssss", $category_filter, $search_param, $search_param, $search_param);
} elseif (!empty($category_filter)) {
    $stmt = $conn->prepare("SELECT * FROM library_books WHERE category = ? ORDER BY id DESC");
    $stmt->bind_param("s", $category_filter);
} elseif (!empty($search_query)) {
    $search_param = '%' . $search_query . '%';
    $stmt = $conn->prepare("SELECT * FROM library_books WHERE (book_title LIKE ? OR isbn LIKE ? OR author LIKE ?) ORDER BY id DESC");
    $stmt->bind_param("sss", $search_param, $search_param, $search_param);
} else {
    $stmt = $conn->prepare("SELECT * FROM library_books ORDER BY id DESC");
}

$stmt->execute();
$books = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Fetch categories for filter
$cat_res = $conn->query("SELECT DISTINCT category FROM library_books WHERE category != '' ORDER BY category ASC");
$categories = $cat_res ? $cat_res->fetch_all(MYSQLI_ASSOC) : [];

include "../includes/header.php";
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Book added to library catalog!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
    <div class="alert alert-success">Book information updated!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Book record removed from catalog!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'issued'): ?>
    <div class="alert alert-success">Book issued to student successfully!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'returned'): ?>
    <div class="alert alert-success">Book return confirmed and inventory updated!</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            Library Catalog Inventory (<?php echo count($books); ?> Titles)
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="issue.php" class="btn btn-secondary btn-sm">Issue / Return Book</a>
            <a href="create.php" class="btn btn-primary btn-sm">+ Add New Book</a>
        </div>
    </div>

    <!-- Filter & Search Toolbar -->
    <div style="padding: 16px 24px; border-bottom: 1px solid var(--border-color); background: rgba(0,0,0,0.15);">
        <form method="GET" action="index.php" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
            <input type="text" name="search" class="search-input" placeholder="Search by title, author, ISBN..." value="<?php echo htmlspecialchars($search_query); ?>">
            
            <select name="category" class="form-control" style="width: auto;">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" <?php echo ($category_filter === $cat['category']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cat['category']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-secondary btn-sm">Search</button>
            <?php if (!empty($search_query) || !empty($category_filter)): ?>
                <a href="index.php" class="btn btn-secondary btn-sm" style="color: var(--danger);">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ISBN</th>
                    <th>Book Title</th>
                    <th>Author</th>
                    <th>Category</th>
                    <th>Rack / Shelf</th>
                    <th>Total Qty</th>
                    <th>Available</th>
                    <th style="text-align: right;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($books)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            No books found in the library. Click "+ Add New Book" to add a title.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($books as $b): ?>
                        <tr>
                            <td><span class="badge badge-info"><?php echo htmlspecialchars($b['isbn']); ?></span></td>
                            <td style="font-weight: 600; color: #ffffff;">
                                <?php echo htmlspecialchars($b['book_title']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($b['author']); ?></td>
                            <td><span class="badge badge-purple"><?php echo htmlspecialchars($b['category']); ?></span></td>
                            <td><?php echo htmlspecialchars($b['rack_no']); ?></td>
                            <td><?php echo $b['quantity']; ?></td>
                            <td>
                                <span class="badge <?php echo ($b['available_copies'] > 0) ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $b['available_copies']; ?> Available
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <a href="issue.php?book_id=<?php echo $b['id']; ?>" class="btn btn-secondary btn-sm" title="Issue Book">Issue</a>
                                    <a href="edit.php?id=<?php echo $b['id']; ?>" class="btn btn-secondary btn-sm" title="Edit Book">Edit</a>
                                    <a href="delete.php?id=<?php echo $b['id']; ?>" class="btn btn-danger btn-sm btn-delete-confirm" data-name="<?php echo htmlspecialchars($b['book_title']); ?>" title="Delete Book">Delete</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
