<?php
/**
 * Book Issue & Return Management
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin', 'Staff']);

$page_title = "Issue & Return Books";
$header_title = "Library Circulations";
$current_page = "library";

$error = '';
$pre_book_id = isset($_GET['book_id']) ? intval($_GET['book_id']) : 0;

// Handle Return Book action
if (isset($_GET['action']) && $_GET['action'] === 'return' && isset($_GET['issue_id'])) {
    $issue_id = intval($_GET['issue_id']);
    
    // Fetch issue record to get book_id
    $chk = $conn->prepare("SELECT book_id, status FROM book_issues WHERE id = ? LIMIT 1");
    $chk->bind_param("i", $issue_id);
    $chk->execute();
    $iss_data = $chk->get_result()->fetch_assoc();
    $chk->close();

    if ($iss_data && $iss_data['status'] !== 'Returned') {
        // Update issue status to Returned
        $ret_stmt = $conn->prepare("UPDATE book_issues SET status = 'Returned', return_date = CURDATE() WHERE id = ?");
        $ret_stmt->bind_param("i", $issue_id);
        $ret_stmt->execute();
        $ret_stmt->close();

        // Increment available copies on the book
        $inc_stmt = $conn->prepare("UPDATE library_books SET available_copies = available_copies + 1 WHERE id = ?");
        $inc_stmt->bind_param("i", $iss_data['book_id']);
        $inc_stmt->execute();
        $inc_stmt->close();

        header("Location: issue.php?msg=returned");
        exit();
    }
}

// Handle Issue Book form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['issue_book'])) {
    $book_id = intval($_POST['book_id'] ?? 0);
    $student_id = intval($_POST['student_id'] ?? 0);
    $due_date = $_POST['due_date'] ?? date('Y-m-d', strtotime('+14 days'));

    if ($book_id <= 0 || $student_id <= 0) {
        $error = 'Please select both a valid book and student.';
    } else {
        // Check available copies
        $book_chk = $conn->prepare("SELECT available_copies FROM library_books WHERE id = ? LIMIT 1");
        $book_chk->bind_param("i", $book_id);
        $book_chk->execute();
        $b_res = $book_chk->get_result()->fetch_assoc();
        $book_chk->close();

        if ($b_res && $b_res['available_copies'] > 0) {
            $issue_stmt = $conn->prepare("INSERT INTO book_issues (book_id, student_id, issue_date, due_date, status) VALUES (?, ?, CURDATE(), ?, 'Issued')");
            $issue_stmt->bind_param("iis", $book_id, $student_id, $due_date);
            if ($issue_stmt->execute()) {
                $issue_stmt->close();

                // Decrement available copies
                $dec_stmt = $conn->prepare("UPDATE library_books SET available_copies = available_copies - 1 WHERE id = ?");
                $dec_stmt->bind_param("i", $book_id);
                $dec_stmt->execute();
                $dec_stmt->close();

                header("Location: issue.php?msg=issued");
                exit();
            } else {
                $error = 'Failed to issue book: ' . $issue_stmt->error;
                $issue_stmt->close();
            }
        } else {
            $error = 'Sorry, this book is currently out of stock / no copies available.';
        }
    }
}

// Fetch available books
$b_list_stmt = $conn->prepare("SELECT id, book_title, isbn, available_copies FROM library_books WHERE available_copies > 0 ORDER BY book_title ASC");
$b_list_stmt->execute();
$available_books = $b_list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$b_list_stmt->close();

// Fetch active students
$s_list_stmt = $conn->prepare("SELECT s.id, s.roll_no, s.first_name, s.last_name, c.class_name, c.section FROM students s JOIN classes c ON s.class_id = c.id WHERE s.status = 'Active' ORDER BY s.roll_no ASC");
$s_list_stmt->execute();
$active_students = $s_list_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$s_list_stmt->close();

// Fetch currently issued and overdue books
$issues_stmt = $conn->prepare("SELECT bi.*, b.book_title, b.isbn, s.first_name, s.last_name, s.roll_no, c.class_name, c.section
                              FROM book_issues bi
                              JOIN library_books b ON bi.book_id = b.id
                              JOIN students s ON bi.student_id = s.id
                              JOIN classes c ON s.class_id = c.id
                              ORDER BY bi.id DESC");
$issues_stmt->execute();
$active_issues = $issues_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$issues_stmt->close();

include "../includes/header.php";
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'issued'): ?>
    <div class="alert alert-success">Book issued successfully to student!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'returned'): ?>
    <div class="alert alert-success">Book returned and inventory copy restored!</div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 24px;">
    <!-- Issue Book Form -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Issue Book to Student</div>
        </div>
        <div class="card-body">
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form action="issue.php" method="POST">
                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Select Book <span class="required">*</span></label>
                    <select name="book_id" class="form-control" required>
                        <option value="">-- Choose Book --</option>
                        <?php foreach ($available_books as $bk): ?>
                            <option value="<?php echo $bk['id']; ?>" <?php echo ($pre_book_id == $bk['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($bk['book_title'] . ' (' . $bk['available_copies'] . ' avail)'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label">Select Student <span class="required">*</span></label>
                    <select name="student_id" class="form-control" required>
                        <option value="">-- Choose Student --</option>
                        <?php foreach ($active_students as $st): ?>
                            <option value="<?php echo $st['id']; ?>">
                                <?php echo htmlspecialchars($st['roll_no'] . ' - ' . $st['first_name'] . ' ' . $st['last_name'] . ' (' . $st['class_name'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 24px;">
                    <label class="form-label">Due Date <span class="required">*</span></label>
                    <input type="date" name="due_date" class="form-control" required value="<?php echo date('Y-m-d', strtotime('+14 days')); ?>">
                </div>

                <button type="submit" name="issue_book" class="btn btn-primary" style="width: 100%;">
                    Confirm Book Issue
                </button>
            </form>
        </div>
    </div>

    <!-- Active Circulations Table -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Circulation & Borrowing Logs (<?php echo count($active_issues); ?>)</div>
            <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Catalog</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Book Title</th>
                        <th>Student</th>
                        <th>Issue Date</th>
                        <th>Due Date</th>
                        <th>Status</th>
                        <th style="text-align: right;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($active_issues)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">No circulation history found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($active_issues as $iss): ?>
                            <tr>
                                <td style="font-weight: 600; color: #ffffff;">
                                    <?php echo htmlspecialchars($iss['book_title']); ?>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($iss['first_name'] . ' ' . $iss['last_name']); ?></div>
                                    <div style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($iss['roll_no']); ?></div>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($iss['issue_date'])); ?></td>
                                <td>
                                    <?php 
                                        $is_overdue = ($iss['status'] === 'Issued' && strtotime($iss['due_date']) < strtotime(date('Y-m-d')));
                                    ?>
                                    <span style="<?php echo $is_overdue ? 'color:#f87171; font-weight:700;' : ''; ?>">
                                        <?php echo date('M d, Y', strtotime($iss['due_date'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($iss['status'] === 'Returned'): ?>
                                        <span class="badge badge-success">Returned</span>
                                    <?php elseif ($is_overdue): ?>
                                        <span class="badge badge-danger">Overdue</span>
                                    <?php else: ?>
                                        <span class="badge badge-info">Active Loan</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <?php if ($iss['status'] !== 'Returned'): ?>
                                        <a href="issue.php?action=return&issue_id=<?php echo $iss['id']; ?>" class="btn btn-secondary btn-sm" style="color: #34d399;" onclick="return confirm('Confirm return of this book?');">
                                            Return Book
                                        </a>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: var(--text-muted);">Completed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
