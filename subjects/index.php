<?php
/**
 * Subjects Management - Index / List with Class-Wise Filter
 * Accessible only by Super Admin and Admin.
 * Filter design mirrors results/index.php style.
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin']);

$page_title   = "Subjects Management";
$header_title = "Subjects & Curriculum";
$current_page = "subjects";

// --- Filter inputs (same pattern as results/index.php) ---
$filter_class_id = isset($_GET['class_id']) ? intval($_GET['class_id'])  : 0;
$search_query    = isset($_GET['search'])   ? trim($_GET['search'])       : '';

// Fetch all classes for filter dropdown
$classes_res = mysqli_query($conn, "SELECT id, class_name, section FROM classes ORDER BY class_name ASC, section ASC");
$all_classes = [];
if ($classes_res) {
    while ($cl = mysqli_fetch_assoc($classes_res)) {
        $all_classes[] = $cl;
    }
}

// Build dynamic WHERE with prepared statement params
$where_clauses = [];
$params        = [];
$types         = '';

if ($filter_class_id > 0) {
    $where_clauses[] = "sub.class_id = ?";
    $params[]        = $filter_class_id;
    $types          .= 'i';
}

if (!empty($search_query)) {
    $where_clauses[] = "(sub.subject_name LIKE ? OR sub.subject_code LIKE ? OR t.name LIKE ?)";
    $like     = '%' . $search_query . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types   .= 'sss';
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$sql = "SELECT sub.*, c.class_name, c.section, t.name AS teacher_name
        FROM subjects sub
        LEFT JOIN classes  c ON sub.class_id  = c.id
        LEFT JOIN teachers t ON sub.teacher_id = t.id
        {$where_sql}
        ORDER BY c.class_name ASC, sub.subject_name ASC";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$subjects = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include "../includes/header.php";
?>

<?php if (isset($_GET['msg']) && $_GET['msg'] === 'created'): ?>
    <div class="alert alert-success">Subject created successfully!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
    <div class="alert alert-success">Subject updated successfully!</div>
<?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'deleted'): ?>
    <div class="alert alert-success">Subject deleted successfully.</div>
<?php elseif (isset($_GET['error']) && $_GET['error'] === 'has_marks'): ?>
    <div class="alert alert-danger">Cannot delete subject — it has associated exam marks. Remove marks first.</div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <div class="card-title">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
            </svg>
            Subjects &amp; Curriculum (<?php echo count($subjects); ?>)
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <?php if (can_manage_subjects()): ?>
                <a href="create.php" class="btn btn-primary btn-sm" id="btn-add-subject">+ Add New Subject</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filter & Search Toolbar (mirrors results/index.php) -->
    <div class="table-filter-bar">
        <form method="GET" action="index.php" id="form-filter-subjects" style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">

            <!-- Search input -->
            <input type="text"
                   name="search"
                   class="search-input"
                   placeholder="Search subject name, code or teacher..."
                   value="<?php echo htmlspecialchars($search_query); ?>"
                   style="min-width: 220px;">

            <!-- Class / Grade filter -->
            <select name="class_id" class="form-control" style="width: auto;" id="filter_class_id">
                <option value="">All Classes / Grades</option>
                <?php foreach ($all_classes as $cl): ?>
                    <option value="<?php echo $cl['id']; ?>"
                        <?php echo ($filter_class_id == $cl['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($cl['class_name'] . ' (' . $cl['section'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>

            <?php if (!empty($search_query) || $filter_class_id > 0): ?>
                <a href="index.php" class="btn btn-secondary btn-sm" style="color: var(--danger);">Reset</a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Subjects Table -->
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Subject Name</th>
                    <th>Subject Code</th>
                    <th>Class / Grade</th>
                    <th>Assigned Teacher</th>
                    <th>Created</th>
                    <?php if (can_manage_subjects() || can_delete()): ?>
                        <th style="text-align: right;">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($subjects)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">
                            <?php if (!empty($search_query) || $filter_class_id > 0): ?>
                                No subjects found matching your filter criteria.
                            <?php else: ?>
                                No subjects defined yet. Click <strong>+ Add New Subject</strong> to get started.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $i = 1; foreach ($subjects as $sub): ?>
                        <tr>
                            <td style="color: var(--text-muted); font-size: 13px;"><?php echo $i++; ?></td>

                            <td style="font-weight: 600; color: var(--text-primary);">
                                <?php echo htmlspecialchars($sub['subject_name']); ?>
                                <span style="font-size: 11px; color: var(--text-muted); display: block;">
                                    <?php echo htmlspecialchars($sub['subject_code']); ?>
                                </span>
                            </td>

                            <td>
                                <span class="badge badge-info"><?php echo htmlspecialchars($sub['subject_code']); ?></span>
                            </td>

                            <td>
                                <?php if ($sub['class_name']): ?>
                                    <span class="badge badge-purple">
                                        <?php echo htmlspecialchars($sub['class_name'] . ' (' . $sub['section'] . ')'); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 13px;">Not Assigned</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <?php if ($sub['teacher_name']): ?>
                                    <span style="color: var(--text-primary); font-weight: 500;">
                                        <?php echo htmlspecialchars($sub['teacher_name']); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-size: 13px;">Unassigned</span>
                                <?php endif; ?>
                            </td>

                            <td style="font-size: 13px; color: var(--text-secondary);">
                                <?php echo date('M d, Y', strtotime($sub['created_at'])); ?>
                            </td>

                            <td style="text-align: right;">
                                <div style="display: inline-flex; gap: 6px;">
                                    <?php if (can_manage_subjects()): ?>
                                        <a href="edit.php?id=<?php echo $sub['id']; ?>"
                                           class="btn btn-secondary btn-sm"
                                           id="btn-edit-subject-<?php echo $sub['id']; ?>"
                                           title="Edit Subject">Edit</a>
                                    <?php endif; ?>
                                    <?php if (can_delete()): ?>
                                        <a href="delete.php?id=<?php echo $sub['id']; ?>"
                                           class="btn btn-danger btn-sm btn-delete-confirm"
                                           id="btn-delete-subject-<?php echo $sub['id']; ?>"
                                           data-name="<?php echo htmlspecialchars($sub['subject_name']); ?>"
                                           title="Delete Subject">Delete</a>
                                    <?php endif; ?>
                                    <?php if (!can_manage_subjects() && !can_delete()): ?>
                                        <span style="color: var(--text-muted); font-size: 12px;">View Only</span>
                                    <?php endif; ?>
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
