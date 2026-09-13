<?php
/**
 * Common Header Layout Component
 */
$root_path = isset($root_path) ? $root_path : '';
$page_title = isset($page_title) ? $page_title . ' - EduCore SMS' : 'EduCore School Management System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Design System Stylesheet -->
    <link rel="stylesheet" href="<?php echo $root_path; ?>assets/css/style.css">
</head>
<body>
<div class="app-container">
    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main-wrapper">
        <header class="top-header">
            <div class="header-left">
                <button id="sidebarToggle" class="btn btn-secondary btn-icon" style="display: none;" aria-label="Toggle Navigation">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <h1 class="page-title"><?php echo isset($header_title) ? htmlspecialchars($header_title) : 'Dashboard Overview'; ?></h1>
            </div>

            <div class="header-actions">
                <div class="header-badge">
                    Database Connected
                </div>
                <a href="<?php echo $root_path; ?>logout.php" class="btn btn-secondary btn-sm">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </a>
            </div>
        </header>

        <main class="content-body">
