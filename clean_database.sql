-- ==========================================================
-- SQL Script: Clean Database & Remove All Deprecated Tables
-- Ready for phpMyAdmin / MySQL / InfinityFree / XAMPP / WAMP
-- ==========================================================

USE `school_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Method 1: Drop All Deprecated / Legacy Tables Explicitly
-- --------------------------------------------------------
DROP TABLE IF EXISTS 
    `admin_login`,
    `teachers_admin`,
    `t_reg`,
    `student_admission`,
    `student_admission6`,
    `class_five`,
    `class_six`,
    `class_seven`,
    `class_eight`,
    `class_nine`,
    `class_ten`,
    `res_five`,
    `res_six`,
    `res_seven`,
    `res_eight`,
    `res_nine`,
    `res_ten`,
    `result_five`,
    `result_six`,
    `result_seven`,
    `result_eight`,
    `result_nine`,
    `result_ten`,
    `tab_five`,
    `tab_six`,
    `tab_seven`,
    `tab_eight`,
    `tab_nine`,
    `tab_ten`,
    `atten`,
    `attent_five`,
    `library_details`,
    `library_log`,
    `library_admin`,
    `notice`,
    `notices_admin`,
    `report`,
    `report_issues`;

-- --------------------------------------------------------
-- Method 2: Dynamic Cleanup (Preserves Only 11 Active Tables)
-- --------------------------------------------------------
SET @tables = NULL;

SELECT GROUP_CONCAT('`', table_name, '`') INTO @tables
FROM information_schema.tables 
WHERE table_schema = DATABASE() 
  AND table_name NOT IN (
    'admins', 
    'teachers', 
    'classes', 
    'students', 
    'subjects', 
    'attendance', 
    'marks', 
    'library_books', 
    'book_issues', 
    'notices',
    'system_settings'
  );

SET @sql = IF(@tables IS NOT NULL, CONCAT('DROP TABLE IF EXISTS ', @tables), 'SELECT "All legacy tables have already been cleaned up." AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Verify Remaining Active Tables (Should show 11 tables)
-- --------------------------------------------------------
SHOW TABLES;
