<?php
/**
 * Common Header Layout Component
 */
$root_path = isset($root_path) ? $root_path : '';
$page_title = isset($page_title) ? $page_title . ' - EduCore SMS' : 'EduCore School Management System';
?>
<?php
$user_theme = $_SESSION['theme_mode'] ?? 'dark';
if ($user_theme !== 'light' && $user_theme !== 'dark') {
    $user_theme = 'dark';
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($user_theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Fast Theme Initialization (Zero Flash) -->
    <script>
        (function() {
            try {
                const stored = localStorage.getItem('sms_theme');
                const theme = stored || '<?php echo htmlspecialchars($user_theme); ?>' || 'dark';
                document.documentElement.setAttribute('data-theme', theme);
                if (theme === 'light' && document.body) {
                    document.body.classList.add('light-theme');
                }
            } catch(e) {}
        })();
        window.SMS_ROOT_PATH = "<?php echo $root_path; ?>";
    </script>

    <!-- Design System Stylesheet -->
    <link rel="stylesheet" href="<?php echo $root_path; ?>assets/css/style.css?v=<?php echo @filemtime(__DIR__ . '/../assets/css/style.css') ?: time(); ?>">
</head>
<body class="<?php echo ($user_theme === 'light') ? 'light-theme' : ''; ?>">
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
                <!-- Theme Mode Toggle Button -->
                <button type="button" id="themeToggleBtn" class="theme-toggle-btn" title="Toggle Light / Dark Mode" aria-label="Toggle Light / Dark Mode" data-current="<?php echo htmlspecialchars($user_theme); ?>">
                    <span class="theme-icon-sun" title="Light Mode">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </span>
                    <span class="theme-icon-moon" title="Dark Mode">
                        <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </span>
                    <span class="theme-toggle-label"><?php echo ($user_theme === 'light') ? 'Light' : 'Dark'; ?></span>
                </button>

                <div class="header-badge">
                    Database Connected
                </div>
                <a href="<?php echo $root_path; ?>logout" class="btn btn-secondary btn-sm">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" width="16" height="16">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </a>
            </div>
        </header>

        <main class="content-body">
