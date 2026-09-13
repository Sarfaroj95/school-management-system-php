<?php
/**
 * Attendance Summary & Analytical Reports
 * Uses centralized connection with include "../connection.php" and prepared statements
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";
require_role(['Super Admin', 'Admin', 'Staff', 'Teacher']);

$page_title = "Attendance Reports";
$header_title = "Attendance Analytics";
$current_page = "attendance";

// Fetch overall attendance stats grouped by status
$stmt = $conn->prepare("SELECT status, COUNT(*) AS count FROM attendance GROUP BY status");
$stmt->execute();
$stats = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$total_logs = 0;
$counts = ['Present' => 0, 'Absent' => 0, 'Late' => 0, 'Excused' => 0];
foreach ($stats as $s) {
    $counts[$s['status']] = $s['count'];
    $total_logs += $s['count'];
}

// Fetch class-wise attendance rates
$class_stmt = $conn->prepare("SELECT c.class_name, c.section, 
                             COUNT(a.id) AS total_records,
                             SUM(CASE WHEN a.status = 'Present' THEN 1 ELSE 0 END) AS present_records
                             FROM classes c
                             LEFT JOIN attendance a ON c.id = a.class_id
                             GROUP BY c.id
                             ORDER BY c.class_name ASC");
$class_stmt->execute();
$class_reports = $class_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$class_stmt->close();

include "../includes/header.php";
?>

<div style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center;">
    <a href="index.php" class="btn btn-secondary btn-sm">&larr; Back to Register</a>
</div>

<div class="grid-stats">
    <div class="stat-card emerald">
        <div class="stat-header">
            <span class="stat-label">Total Present Logs</span>
        </div>
        <div class="stat-value"><?php echo number_format($counts['Present']); ?></div>
        <div class="stat-footer"><?php echo $total_logs > 0 ? round(($counts['Present']/$total_logs)*100, 1) : 0; ?>% of all records</div>
    </div>

    <div class="stat-card rose">
        <div class="stat-header">
            <span class="stat-label">Absences Logged</span>
        </div>
        <div class="stat-value"><?php echo number_format($counts['Absent']); ?></div>
        <div class="stat-footer"><?php echo $total_logs > 0 ? round(($counts['Absent']/$total_logs)*100, 1) : 0; ?>% unexcused</div>
    </div>

    <div class="stat-card amber">
        <div class="stat-header">
            <span class="stat-label">Late Arrivals</span>
        </div>
        <div class="stat-value"><?php echo number_format($counts['Late']); ?></div>
        <div class="stat-footer">Tardiness tracking</div>
    </div>

    <div class="stat-card sky">
        <div class="stat-header">
            <span class="stat-label">Excused Leaves</span>
        </div>
        <div class="stat-value"><?php echo number_format($counts['Excused']); ?></div>
        <div class="stat-footer">Medical & official approvals</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <div class="card-title">Class-Wise Attendance Performance</div>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Class Name</th>
                    <th>Section</th>
                    <th>Total Logged Days</th>
                    <th>Present Count</th>
                    <th>Attendance Percentage</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($class_reports as $cr): 
                    $pct = $cr['total_records'] > 0 ? round(($cr['present_records'] / $cr['total_records']) * 100, 1) : 0;
                ?>
                    <tr>
                        <td style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($cr['class_name']); ?></td>
                        <td><span class="badge badge-info"><?php echo htmlspecialchars($cr['section']); ?></span></td>
                        <td><?php echo number_format($cr['total_records']); ?></td>
                        <td><?php echo number_format($cr['present_records']); ?></td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="flex: 1; height: 8px; background: rgba(255,255,255,0.1); border-radius: 4px; overflow: hidden; max-width: 140px;">
                                    <div style="width: <?php echo $pct; ?>%; height: 100%; background: var(--success);"></div>
                                </div>
                                <span style="font-weight: 700; color: #34d399;"><?php echo $pct; ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
