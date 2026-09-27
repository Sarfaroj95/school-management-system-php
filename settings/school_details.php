<?php
/**
 * School Details & Institutional Profile Management
 * Strictly restricted to Super Admin and Admin roles.
 */
$root_path = '../';
include $root_path . "connection.php";
include $root_path . "includes/auth.php";

// Enforce Super Admin and Admin role restriction
require_role(['Super Admin', 'Admin']);

$page_title = "School Details";
$header_title = "School Details & Institutional Profile";
$current_page = "school_details";

$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Handle School Details Add / Update Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_school_details'])) {
    $fields = [
        'school_name'        => trim($_POST['school_name'] ?? ''),
        'school_code'        => trim($_POST['school_code'] ?? ''),
        'school_established' => trim($_POST['school_established'] ?? ''),
        'principal_name'     => trim($_POST['principal_name'] ?? ''),
        'school_board'       => trim($_POST['school_board'] ?? ''),
        'school_motto'       => trim($_POST['school_motto'] ?? ''),
        'school_email'       => trim($_POST['school_email'] ?? ''),
        'school_phone'       => trim($_POST['school_phone'] ?? ''),
        'school_alt_phone'   => trim($_POST['school_alt_phone'] ?? ''),
        'school_website'     => trim($_POST['school_website'] ?? ''),
        'school_address'     => trim($_POST['school_address'] ?? ''),
        'school_city'        => trim($_POST['school_city'] ?? ''),
        'school_district'    => trim($_POST['school_district'] ?? ''),
        'school_state'       => trim($_POST['school_state'] ?? ''),
        'school_postal_code' => trim($_POST['school_postal_code'] ?? ''),
        'school_country'     => trim($_POST['school_country'] ?? ''),
        'academic_year'      => trim($_POST['academic_year'] ?? '2026-2027'),
        'currency_symbol'    => trim($_POST['currency_symbol'] ?? '$')
    ];

    if (empty($fields['school_name']) || empty($fields['school_email'])) {
        $error = "School Name and Official Contact Email are required fields.";
    } else {
        $stmt = $conn->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
        if ($stmt) {
            foreach ($fields as $key => $val) {
                $stmt->bind_param("sss", $key, $val, $val);
                $stmt->execute();
            }
            $stmt->close();
            header("Location: school_details.php?msg=" . urlencode("School details have been successfully saved and updated!"));
            exit();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

// Fetch current system settings from database
$school_settings = [];
$res = mysqli_query($conn, "SELECT setting_key, setting_value FROM system_settings");
if ($res) {
    while ($r = mysqli_fetch_assoc($res)) {
        $school_settings[$r['setting_key']] = $r['setting_value'];
    }
}

include $root_path . "includes/header.php";
?>

<!-- Alert Messages -->
<?php if (!empty($msg)): ?>
    <div class="alert alert-success">
        <div style="display: flex; align-items: center; gap: 8px;">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            <span><?php echo htmlspecialchars($msg); ?></span>
        </div>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <div style="display: flex; align-items: center; gap: 8px;">
            <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span><?php echo htmlspecialchars($error); ?></span>
        </div>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">

    <!-- Left Column: Add / Update School Details Form -->
    <div class="card">
        <div class="card-header">
            <div class="card-title">
                <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20" style="color: var(--primary);">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                Manage Institutional Information & Identity
            </div>
            <span class="badge badge-info">Admin Control</span>
        </div>

        <div class="card-body">
            <form method="POST" action="school_details.php">
                
                <!-- Section 1: Basic Identity -->
                <div style="margin-bottom: 24px;">
                    <h3 style="font-size: 15px; font-weight: 700; color: #38bdf8; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                        <span>🏛️</span> Institution Identity & Accreditation
                    </h3>

                    <div class="form-grid">
                        <div class="form-group col-span-2">
                            <label class="form-label" for="school_name">Institution / School Name <span class="required">*</span></label>
                            <input type="text" id="school_name" name="school_name" class="form-control" placeholder="e.g. KRISHNAPUR PRIMARY SCHOOL" value="<?php echo htmlspecialchars($school_settings['school_name'] ?? 'KRISHNAPUR PRIMARY SCHOOL'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="school_code">School Code / Affiliation No.</label>
                            <input type="text" id="school_code" name="school_code" class="form-control" placeholder="e.g. WB-SCH-721242" value="<?php echo htmlspecialchars($school_settings['school_code'] ?? 'WB-SCH-721242'); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="school_established">Established Year (Estd.)</label>
                            <input type="text" id="school_established" name="school_established" class="form-control" placeholder="e.g. 1995" value="<?php echo htmlspecialchars($school_settings['school_established'] ?? '1995'); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="principal_name">Headmaster / Principal Name</label>
                            <input type="text" id="principal_name" name="principal_name" class="form-control" placeholder="e.g. Dr. Robert Vance" value="<?php echo htmlspecialchars($school_settings['principal_name'] ?? 'Headmaster Office'); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="school_board">Educational Board / Council</label>
                            <input type="text" id="school_board" name="school_board" class="form-control" placeholder="e.g. WBBPE / State Board" value="<?php echo htmlspecialchars($school_settings['school_board'] ?? 'WBBPE - Primary Education Board'); ?>">
                        </div>

                        <div class="form-group col-span-2">
                            <label class="form-label" for="school_motto">Institutional Motto / Slogan</label>
                            <input type="text" id="school_motto" name="school_motto" class="form-control" placeholder="e.g. Empowering Young Minds, Shaping Tomorrow" value="<?php echo htmlspecialchars($school_settings['school_motto'] ?? 'Empowering Young Minds, Shaping Tomorrow'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Section 2: Contact Details -->
                <div style="margin-bottom: 24px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <h3 style="font-size: 15px; font-weight: 700; color: #38bdf8; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                        <span>📞</span> Official Contact & Communication
                    </h3>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="school_email">Official Email Address <span class="required">*</span></label>
                            <input type="email" id="school_email" name="school_email" class="form-control" placeholder="e.g. contact@educore-sms.edu" value="<?php echo htmlspecialchars($school_settings['school_email'] ?? 'contact@educore-sms.edu'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="school_phone">Primary Phone Number</label>
                            <input type="text" id="school_phone" name="school_phone" class="form-control" placeholder="e.g. +91 (555) 019-2834" value="<?php echo htmlspecialchars($school_settings['school_phone'] ?? '+91 (555) 019-2834'); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="school_alt_phone">Alternative / Mobile Phone</label>
                            <input type="text" id="school_alt_phone" name="school_alt_phone" class="form-control" placeholder="e.g. +91 98765 43210" value="<?php echo htmlspecialchars($school_settings['school_alt_phone'] ?? '+91 98765 43210'); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="school_website">Official Website URL</label>
                            <input type="text" id="school_website" name="school_website" class="form-control" placeholder="e.g. https://krishnapur.school.edu" value="<?php echo htmlspecialchars($school_settings['school_website'] ?? 'https://krishnapur.school.edu'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Section 3: Campus Location & Physical Address -->
                <div style="margin-bottom: 24px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <h3 style="font-size: 15px; font-weight: 700; color: #38bdf8; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                        <span>📍</span> Campus Location & Address
                    </h3>

                    <div class="form-grid">
                        <div class="form-group col-span-2">
                            <label class="form-label" for="school_address">Campus Physical Address <span class="required">*</span></label>
                            <textarea id="school_address" name="school_address" class="form-control" rows="3" placeholder="e.g. RGGM+2V9, Krishnapur, Chandrakona, Krishnapur, West Bengal 721242" required><?php echo htmlspecialchars($school_settings['school_address'] ?? 'RGGM+2V9, Krishnapur, Chandrakona, Krishnapur, West Bengal 721242'); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="school_city">City / Village / Post</label>
                            <input type="text" id="school_city" name="school_city" class="form-control" placeholder="e.g. Chandrakona, Krishnapur" value="<?php echo htmlspecialchars($school_settings['school_city'] ?? 'Chandrakona'); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="school_district">District</label>
                            <input type="text" id="school_district" name="school_district" class="form-control" placeholder="e.g. Paschim Medinipur" value="<?php echo htmlspecialchars($school_settings['school_district'] ?? 'Paschim Medinipur'); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="school_state">State / Province</label>
                            <input type="text" id="school_state" name="school_state" class="form-control" placeholder="e.g. West Bengal" value="<?php echo htmlspecialchars($school_settings['school_state'] ?? 'West Bengal'); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="school_postal_code">Postal PIN / ZIP Code</label>
                            <input type="text" id="school_postal_code" name="school_postal_code" class="form-control" placeholder="e.g. 721242" value="<?php echo htmlspecialchars($school_settings['school_postal_code'] ?? '721242'); ?>">
                        </div>

                        <div class="form-group col-span-2">
                            <label class="form-label" for="school_country">Country</label>
                            <input type="text" id="school_country" name="school_country" class="form-control" placeholder="e.g. India" value="<?php echo htmlspecialchars($school_settings['school_country'] ?? 'India'); ?>">
                        </div>
                    </div>
                </div>

                <!-- Section 4: Academic Session & Currency Configuration -->
                <div style="margin-bottom: 24px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                    <h3 style="font-size: 15px; font-weight: 700; color: #38bdf8; margin-bottom: 14px; display: flex; align-items: center; gap: 8px;">
                        <span>⚙️</span> Academic Session & Financial Preferences
                    </h3>

                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label" for="academic_year">Active Academic Session</label>
                            <input type="text" id="academic_year" name="academic_year" class="form-control" placeholder="e.g. 2026-2027" value="<?php echo htmlspecialchars($school_settings['academic_year'] ?? '2026-2027'); ?>">
                        </div>

                        <div class="form-group">
                            <label class="form-label" for="currency_symbol">Currency Symbol</label>
                            <input type="text" id="currency_symbol" name="currency_symbol" class="form-control" placeholder="e.g. ₹ or $" value="<?php echo htmlspecialchars($school_settings['currency_symbol'] ?? '$'); ?>">
                        </div>
                    </div>
                </div>

                <div style="border-top: 1px solid var(--border-color); padding-top: 20px; display: flex; justify-content: flex-end; gap: 12px;">
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" name="save_school_details" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px; font-weight: 700;">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v12a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        Save & Update School Details
                    </button>
                </div>

            </form>
        </div>
    </div>

    <!-- Right Column: Institutional Identity Card & Quick Links -->
    <div style="display: flex; flex-direction: column; gap: 24px;">
        
        <!-- Live Institutional Identity Card Preview -->
        <div class="card" style="background: var(--bg-surface-elevated); border: 1px solid var(--border-color); box-shadow: var(--shadow-md);">
            <div class="card-header" style="border-bottom: 1px solid var(--border-color);">
                <div class="card-title" style="font-size: 14px;">
                    <span>🎓</span> Official Header Preview
                </div>
                <span class="badge badge-success">Live Sync</span>
            </div>
            <div class="card-body" style="text-align: center; padding: 24px 20px;">
                <div style="width: 64px; height: 64px; border-radius: 50%; background: var(--primary-gradient); display: flex; align-items: center; justify-content: center; font-size: 28px; margin: 0 auto 14px auto; box-shadow: var(--shadow-glow);">
                    🏛️
                </div>
                <h3 style="font-size: 17px; font-weight: 800; color: var(--text-primary); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 4px;">
                    <?php echo htmlspecialchars($school_settings['school_name'] ?? 'KRISHNAPUR PRIMARY SCHOOL'); ?>
                </h3>
                <div style="font-size: 11.5px; font-weight: 600; color: #38bdf8; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px;">
                    <?php echo htmlspecialchars($school_settings['school_board'] ?? 'Affiliated Educational Institution'); ?>
                </div>
                <p style="font-size: 12px; color: var(--text-secondary); line-height: 1.5; margin-bottom: 14px;">
                    <?php echo htmlspecialchars($school_settings['school_address'] ?? 'Campus Address'); ?>
                </p>
                <div style="border-top: 1px dashed var(--border-color); padding-top: 12px; font-size: 11.5px; color: var(--text-muted); display: flex; flex-direction: column; gap: 4px; text-align: left;">
                    <div><strong>Email:</strong> <?php echo htmlspecialchars($school_settings['school_email'] ?? 'contact@educore-sms.edu'); ?></div>
                    <div><strong>Phone:</strong> <?php echo htmlspecialchars($school_settings['school_phone'] ?? '+91 (555) 019-2834'); ?></div>
                    <div><strong>Session:</strong> <?php echo htmlspecialchars($school_settings['academic_year'] ?? '2026-2027'); ?></div>
                    <div><strong>Currency:</strong> <?php echo htmlspecialchars($school_settings['currency_symbol'] ?? '$'); ?></div>
                </div>
            </div>
        </div>

        <!-- Quick Administration Shortcuts Card -->
        <div class="card">
            <div class="card-header">
                <div class="card-title" style="font-size: 14px;">
                    <span>⚡</span> Administrative Links
                </div>
            </div>
            <div class="card-body" style="padding: 16px; display: flex; flex-direction: column; gap: 8px;">
                <a href="../results/print.php" target="_blank" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                    <span>🖨️</span> View Official Marksheet Header
                </a>
                <a href="index.php?tab=staff" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                    <span>👥</span> Manage Admins & Staff Users
                </a>
                <a href="db_export.php" class="btn btn-secondary btn-sm" style="justify-content: flex-start;">
                    <span>💾</span> Export Database Backup
                </a>
            </div>
        </div>

    </div>

</div>

<?php include $root_path . "includes/footer.php"; ?>
