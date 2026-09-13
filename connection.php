<?php
/**
 * Centralized Database Connection Configuration
 * School Management System
 * 
 * Ready for InfinityFree / XAMPP / WAMP / cPanel environments.
 * Uses a common $conn variable for all database operations.
 */

// Enable output buffering to prevent headers-already-sent errors on shared hosting
if (!ob_get_level()) {
    ob_start();
}

// Start PHP session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database Credentials (Supports both Remote InfinityFree & Local XAMPP/WAMP)
if (!defined('DB_HOST')) {
    // Remote DB (InfinityFree)
    define('DB_HOST', 'sql311.infinityfree.com');
    define('DB_USER', 'if0_42904158');
    define('DB_PASS', 'QdAc5aHcOV');
    define('DB_NAME', 'if0_42904158_school_management');
    define('DB_PORT', 3306);
    
    // For Local XAMPP/WAMP:
    // define('DB_HOST', 'localhost');
    // define('DB_USER', 'root');
    // define('DB_PASS', '');
    // define('DB_NAME', 'school_db');
    // define('DB_PORT', 3306);
}

// Disable internal MySQL exception throwing to handle errors gracefully
mysqli_report(MYSQLI_REPORT_OFF);

// Attempt database connection
$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// Connection Error Handling
if (!$conn) {
    // If connecting to local server and DB is missing (Error 1049), attempt auto-create
    $raw_errno = mysqli_connect_errno();
    if ($raw_errno === 1049) {
        $temp_conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, '', DB_PORT);
        if ($temp_conn) {
            $create_db_query = "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
            if (@mysqli_query($temp_conn, $create_db_query)) {
                mysqli_close($temp_conn);
                $conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
            }
        }
    }
}

// If connection still failed, show friendly diagnostic screen
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
        </style>
    </head>
    <body>
        <div class="error-card">
            <span class="badge">Connection Failed</span>
            <h1>Unable to connect to MySQL Database</h1>
            <p>Could not connect to database <code>' . htmlspecialchars(DB_NAME) . '</code> on <code>' . htmlspecialchars(DB_HOST) . '</code>.</p>
            <div class="code-box">' . htmlspecialchars($error_msg) . '</div>
            <div class="steps">
                <strong>Troubleshooting Steps:</strong>
                <ol>
                    <li>Verify DB hostname, username, and password in <code>connection.php</code>.</li>
                    <li>Ensure the database <code>' . htmlspecialchars(DB_NAME) . '</code> exists in your hosting control panel.</li>
                    <li>For InfinityFree, ensure you are running the script on your InfinityFree website (free hosts block external remote access).</li>
                </ol>
            </div>
            <a href="javascript:location.reload()" class="btn">Retry Connection</a>
        </div>
    </body>
    </html>');
}

// Set character set to utf8mb4
mysqli_set_charset($conn, "utf8mb4");

/**
 * Auto-Installer & Schema Synchronizer
 * Automatically creates all tables and inserts seed data if they do not exist.
 */
function ensure_tables_exist($conn) {
    // Check if admins table exists
    $check_table = @mysqli_query($conn, "SHOW TABLES LIKE 'admins'");
    if (!$check_table || mysqli_num_rows($check_table) === 0) {
        // Table does not exist, create all required tables
        $schema = "
        CREATE TABLE IF NOT EXISTS `admins` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `username` VARCHAR(50) NOT NULL UNIQUE,
          `password` VARCHAR(255) NOT NULL,
          `full_name` VARCHAR(100) NOT NULL,
          `email` VARCHAR(100) NOT NULL,
          `role` ENUM('Super Admin', 'Admin', 'Staff') NOT NULL DEFAULT 'Admin',
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `teachers` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `emp_id` VARCHAR(20) NOT NULL UNIQUE,
          `name` VARCHAR(100) NOT NULL,
          `email` VARCHAR(100) NOT NULL,
          `password` VARCHAR(255) NOT NULL DEFAULT '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW',
          `phone` VARCHAR(20) NOT NULL,
          `qualification` VARCHAR(100) NOT NULL,
          `subject_specialization` VARCHAR(100) NOT NULL,
          `joining_date` DATE NOT NULL,
          `salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
          `status` ENUM('Active', 'On Leave', 'Resigned') NOT NULL DEFAULT 'Active',
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `classes` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `class_name` VARCHAR(50) NOT NULL,
          `section` VARCHAR(10) NOT NULL,
          `room_no` VARCHAR(20) NOT NULL,
          `teacher_id` INT(11) DEFAULT NULL,
          `capacity` INT(11) NOT NULL DEFAULT 40,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `students` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `roll_no` VARCHAR(20) NOT NULL,
          `first_name` VARCHAR(50) NOT NULL,
          `last_name` VARCHAR(50) NOT NULL,
          `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
          `dob` DATE NOT NULL,
          `email` VARCHAR(100) DEFAULT NULL,
          `password` VARCHAR(255) NOT NULL DEFAULT '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW',
          `phone` VARCHAR(20) DEFAULT NULL,
          `address` TEXT NOT NULL,
          `class_id` INT(11) NOT NULL,
          `admission_date` DATE NOT NULL,
          `parent_name` VARCHAR(100) NOT NULL,
          `parent_phone` VARCHAR(20) NOT NULL,
          `status` ENUM('Active', 'Inactive', 'Graduated', 'Suspended') NOT NULL DEFAULT 'Active',
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `subjects` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `subject_name` VARCHAR(100) NOT NULL,
          `subject_code` VARCHAR(20) NOT NULL,
          `class_id` INT(11) NOT NULL,
          `teacher_id` INT(11) DEFAULT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `attendance` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `student_id` INT(11) NOT NULL,
          `class_id` INT(11) NOT NULL,
          `attendance_date` DATE NOT NULL,
          `status` ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL DEFAULT 'Present',
          `remarks` VARCHAR(255) DEFAULT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_attendance` (`student_id`, `attendance_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `marks` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `student_id` INT(11) NOT NULL,
          `subject_id` INT(11) NOT NULL,
          `exam_name` VARCHAR(50) NOT NULL,
          `marks_obtained` DECIMAL(5,2) NOT NULL,
          `max_marks` DECIMAL(5,2) NOT NULL DEFAULT 100.00,
          `grade` VARCHAR(5) NOT NULL,
          `remarks` VARCHAR(255) DEFAULT NULL,
          `exam_date` DATE NOT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `library_books` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `book_title` VARCHAR(150) NOT NULL,
          `isbn` VARCHAR(30) NOT NULL UNIQUE,
          `author` VARCHAR(100) NOT NULL,
          `category` VARCHAR(50) NOT NULL,
          `quantity` INT(11) NOT NULL DEFAULT 1,
          `available_copies` INT(11) NOT NULL DEFAULT 1,
          `rack_no` VARCHAR(20) NOT NULL,
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `book_issues` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `book_id` INT(11) NOT NULL,
          `student_id` INT(11) NOT NULL,
          `issue_date` DATE NOT NULL,
          `due_date` DATE NOT NULL,
          `return_date` DATE DEFAULT NULL,
          `status` ENUM('Issued', 'Returned', 'Overdue') NOT NULL DEFAULT 'Issued',
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `notices` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `title` VARCHAR(150) NOT NULL,
          `content` TEXT NOT NULL,
          `target_audience` ENUM('All', 'Students', 'Teachers', 'Parents') NOT NULL DEFAULT 'All',
          `priority` ENUM('Normal', 'Important', 'Urgent') NOT NULL DEFAULT 'Normal',
          `posted_by` VARCHAR(100) NOT NULL DEFAULT 'Administration',
          `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";

        // Execute schema queries
        mysqli_multi_query($conn, $schema);
        while (mysqli_more_results($conn) && mysqli_next_result($conn)) {
            // clear multi-query buffers
        }

        // Insert Default Accounts
        $hash = '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW'; // hash for admin123
        @mysqli_query($conn, "INSERT IGNORE INTO `admins` (`id`, `username`, `password`, `full_name`, `email`, `role`) VALUES 
            (1, 'admin', '$hash', 'System Administrator', 'admin@schoolsms.edu', 'Super Admin'),
            (2, 'staff', '$hash', 'Academic Staff Member', 'staff@schoolsms.edu', 'Staff')");
        
        // Insert sample classes & teachers
        @mysqli_query($conn, "INSERT IGNORE INTO `teachers` (`id`, `emp_id`, `name`, `email`, `password`, `phone`, `qualification`, `subject_specialization`, `joining_date`, `salary`, `status`) VALUES 
            (1, 'EMP101', 'Dr. Robert Jenkins', 'r.jenkins@schoolsms.edu', '$hash', '+1 555-234-5678', 'Ph.D in Math', 'Mathematics', CURDATE(), 4800.00, 'Active'),
            (2, 'EMP102', 'Sarah Mitchell', 's.mitchell@schoolsms.edu', '$hash', '+1 555-345-6789', 'M.Sc in Physics', 'Physical Science', CURDATE(), 4200.00, 'Active')");

        @mysqli_query($conn, "INSERT IGNORE INTO `classes` (`id`, `class_name`, `section`, `room_no`, `teacher_id`, `capacity`) VALUES 
            (1, 'Grade 5', 'A', 'Room 101', 1, 35),
            (2, 'Grade 6', 'A', 'Room 102', 2, 35),
            (3, 'Grade 7', 'A', 'Room 201', 1, 40),
            (4, 'Grade 8', 'A', 'Room 202', 2, 40),
            (5, 'Grade 9', 'A', 'Room 301', 1, 40),
            (6, 'Grade 10', 'A', 'Room 302', 2, 45)");

        @mysqli_query($conn, "INSERT IGNORE INTO `subjects` (`id`, `subject_name`, `subject_code`, `class_id`, `teacher_id`) VALUES 
            (1, 'Mathematics', 'MATH-10', 6, 1),
            (2, 'Physics', 'PHY-10', 6, 2)");

        @mysqli_query($conn, "INSERT IGNORE INTO `students` (`id`, `roll_no`, `first_name`, `last_name`, `gender`, `dob`, `email`, `password`, `phone`, `address`, `class_id`, `admission_date`, `parent_name`, `parent_phone`, `status`) VALUES 
            (1, 'STD-1001', 'Alex', 'Johnson', 'Male', '2010-04-12', 'alex@example.com', '$hash', '+1 555-0101', '742 Evergreen Terrace', 6, CURDATE(), 'Arthur Johnson', '+1 555-0199', 'Active'),
            (2, 'STD-1002', 'Emma', 'Watson', 'Female', '2010-09-24', 'emma@example.com', '$hash', '+1 555-0102', '124 Conch Street', 6, CURDATE(), 'Chris Watson', '+1 555-0198', 'Active')");

        @mysqli_query($conn, "INSERT IGNORE INTO `notices` (`id`, `title`, `content`, `target_audience`, `priority`, `posted_by`) VALUES 
            (1, 'Welcome to EduCore SMS', 'The school management system is now active and ready for use.', 'All', 'Important', 'Administration')");
    }

    // Auto-migrate schema: Check if password column exists in teachers table
    $check_teacher_pass = @mysqli_query($conn, "SHOW COLUMNS FROM `teachers` LIKE 'password'");
    if ($check_teacher_pass && mysqli_num_rows($check_teacher_pass) === 0) {
        @mysqli_query($conn, "ALTER TABLE `teachers` ADD COLUMN `password` VARCHAR(255) NOT NULL DEFAULT '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW' AFTER `email`");
    }

    // Auto-migrate schema: Check if password column exists in students table
    $check_student_pass = @mysqli_query($conn, "SHOW COLUMNS FROM `students` LIKE 'password'");
    if ($check_student_pass && mysqli_num_rows($check_student_pass) === 0) {
        @mysqli_query($conn, "ALTER TABLE `students` ADD COLUMN `password` VARCHAR(255) NOT NULL DEFAULT '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW' AFTER `email`");
    }
}

// Automatically ensure tables exist
ensure_tables_exist($conn);
?>