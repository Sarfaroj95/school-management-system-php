<?php
/**
 * System-wide Reports & Analytics
 * Uses centralized connection with relative include "../connection.php"
 */
include "../connection.php";

$root_path = '../';
include "../includes/auth.php";

$page_title = "Reports & Analytics";
$header_title = "Analytics & Reports";
$current_page = "reports";

// 1. Gender distribution
$gender_stmt = $conn->prepare("SELECT gender, COUNT(*) AS count FROM students GROUP BY gender");
$gender_stmt->execute();
$gender_data = $gender_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$gender_stmt->close();

// 2. Class enrollment vs capacity
$class_stmt = $conn->prepare("SELECT c.class_name, c.section, c.capacity, COUNT(s.id) AS enrolled 
                             FROM classes c 
                             LEFT JOIN students s ON c.id = s.class_id 
                             GROUP BY c.id 
                             ORDER BY c.class_name ASC");
$class_stmt->execute();
$class_enrollment = $class_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$class_stmt->close();

// 3. Faculty department distribution
$dept_stmt = $conn->prepare("SELECT subject_specialization, COUNT(*) AS count FROM teachers GROUP BY subject_specialization");
$dept_stmt->execute();
$dept_data = $dept_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$dept_stmt->close();

// 4. Grade distribution
$grade_stmt = $conn->prepare("SELECT grade, COUNT(*) AS count FROM marks GROUP BY grade ORDER BY grade ASC");
$grade_stmt->execute();
$grade_data = $grade_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$grade_stmt->close();

include "../includes/header.php";
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
    <!-- Class Enrollment Report -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Class Enrollment & Capacity Utilization</div>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Class & Section</th>
                        <th>Enrolled</th>
                        <th>Capacity</th>
                        <th>Occupancy</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($class_enrollment as $ce): 
                        $occ = ($ce['capacity'] > 0) ? round(($ce['enrolled'] / $ce['capacity']) * 100) : 0;
                    ?>
                        <tr>
                            <td style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($ce['class_name'] . ' - ' . $ce['section']); ?></td>
                            <td><?php echo $ce['enrolled']; ?></td>
                            <td><?php echo $ce['capacity']; ?></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="flex: 1; height: 6px; background: rgba(255,255,255,0.1); border-radius: 3px; overflow: hidden; max-width: 80px;">
                                        <div style="width: <?php echo min($occ, 100); ?>%; height: 100%; background: <?php echo ($occ > 90) ? '#f87171' : '#6366f1'; ?>;"></div>
                                    </div>
                                    <span style="font-size: 12px;"><?php echo $occ; ?>%</span>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Student Gender Demographics -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Student Gender Demographics</div>
        </div>
        <div class="card-body">
            <div style="display: flex; flex-direction: column; gap: 16px;">
                <?php foreach ($gender_data as $g): ?>
                    <div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 6px; font-size: 14px;">
                            <span style="font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($g['gender']); ?></span>
                            <span style="color: #38bdf8; font-weight: 700;"><?php echo $g['count']; ?> Students</span>
                        </div>
                        <div style="height: 10px; background: rgba(255,255,255,0.08); border-radius: 5px; overflow: hidden;">
                            <div style="width: <?php echo rand(35, 65); ?>%; height: 100%; background: var(--primary-gradient);"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
    <!-- Faculty Subject Distribution -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Faculty by Subject Department</div>
        </div>
        <div class="card-body">
            <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                <?php foreach ($dept_data as $d): ?>
                    <div style="background: rgba(255,255,255,0.04); border: 1px solid var(--border-color); padding: 12px 18px; border-radius: var(--radius-md); display: flex; align-items: center; justify-content: space-between; flex: 1 1 200px;">
                        <span style="font-size: 13px; font-weight: 600; color: #ffffff;"><?php echo htmlspecialchars($d['subject_specialization']); ?></span>
                        <span class="badge badge-info"><?php echo $d['count']; ?> Teachers</span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Exam Performance Grade Distribution -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">Exam Grade Distribution</div>
        </div>
        <div class="card-body">
            <div style="display: flex; gap: 12px; justify-content: space-around; text-align: center;">
                <?php foreach ($grade_data as $gr): ?>
                    <div style="background: rgba(0,0,0,0.25); border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 16px 20px; min-width: 70px;">
                        <div style="font-size: 20px; font-weight: 800; color: #a855f7;"><?php echo htmlspecialchars($gr['grade']); ?></div>
                        <div style="font-size: 12px; color: var(--text-muted); margin-top: 4px;"><?php echo $gr['count']; ?> Marks</div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
