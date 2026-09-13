<?php
/**
 * Centralized Database Connection Configuration
 * School Management System
 * 
 * Ready for XAMPP / WAMP / LAMP environments.
 * Uses a common $conn variable for all database operations.
 */

// Centralized Database Credentials
// local DB
// define('DB_HOST', 'localhost');
// define('DB_USER', 'root');
// define('DB_PASS', '');
// define('DB_NAME', 'school_db');
// define('DB_PORT', 3306);

// Remote DB
define('DB_HOST', 'sql311.infinityfree.com');
define('DB_USER', 'if0_42904158');
define('DB_PASS', 'QdAc5aHcOV');
define('DB_NAME', 'if0_42904158_school_management');
define('DB_PORT', 3306);

// Disable raw internal MySQL error reporting to output custom user-friendly handling
mysqli_report(MYSQLI_REPORT_OFF);

// Attempt database connection
$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Connection Error Handling
if (!$conn) {
    // If the database 'school_db' doesn't exist yet, try connecting without DB to auto-create it or guide user
    $raw_error = mysqli_connect_error();
    $raw_errno = mysqli_connect_errno();

    // Check if error is specifically unknown database (Error 1049)
    if ($raw_errno === 1049) {
        $temp_conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
        if ($temp_conn) {
            // Attempt to automatically create the database if missing
            $create_db_query = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            if (@mysqli_query($temp_conn, $create_db_query)) {
                mysqli_close($temp_conn);
                $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            }
        }
    }
}

// Final check if connection failed
if (!$conn) {
    $error_msg = mysqli_connect_error();
    die('<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Error - SMS</title>
        <style>
            body { font-family: system-ui, -apple-system, sans-serif; background: #0f172a; color: #f8fafc; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
            .error-card { background: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 32px; max-width: 580px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5); }
            .badge { display: inline-block; background: #ef4444; color: white; padding: 4px 10px; border-radius: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; margin-bottom: 16px; }
            h1 { margin: 0 0 12px 0; font-size: 22px; color: #f1f5f9; }
            p { margin: 0 0 16px 0; line-height: 1.6; color: #94a3b8; font-size: 14px; }
            .code-box { background: #0f172a; border-left: 4px solid #ef4444; padding: 12px 16px; border-radius: 6px; font-family: monospace; font-size: 13px; color: #fca5a5; margin-bottom: 20px; word-break: break-all; }
            .steps { background: #0f172a; padding: 16px 20px; border-radius: 8px; border: 1px solid #334155; margin-bottom: 20px; }
            .steps ol { margin: 0; padding-left: 20px; color: #cbd5e1; font-size: 13px; line-height: 1.7; }
            .btn { display: inline-block; background: #3b82f6; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; text-align: center; }
            .btn:hover { background: #2563eb; }
        </style>
    </head>
    <body>
        <div class="error-card">
            <span class="badge">Connection Failed</span>
            <h1>Unable to connect to MySQL Database</h1>
            <p>The application could not establish a connection using the centralized configuration in <code>connection.php</code>.</p>
            
            <div class="code-box">' . htmlspecialchars($error_msg) . '</div>

            <div class="steps">
                <strong>How to fix this in XAMPP / WAMP:</strong>
                <ol>
                    <li>Start the <strong>Apache</strong> and <strong>MySQL</strong> services in your XAMPP Control Panel.</li>
                    <li>Open <strong>phpMyAdmin</strong> (<a href="http://localhost/phpmyadmin" style="color:#60a5fa;" target="_blank">http://localhost/phpmyadmin</a>).</li>
                    <li>Create a database named <code>' . DB_NAME . '</code> (or import <code>database.sql</code> from this project directory).</li>
                    <li>Verify your database user is <code>' . DB_USER . '</code> with your MySQL password.</li>
                </ol>
            </div>
            <a href="javascript:location.reload()" class="btn">Retry Connection</a>
        </div>
    </body>
    </html>');
}

// Set character set to utf8mb4 for full Unicode support
mysqli_set_charset($conn, "utf8mb4");

// Start PHP session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>