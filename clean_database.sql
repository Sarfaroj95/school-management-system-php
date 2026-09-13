-- ==========================================================
-- SQL Script: Clean Database & Keep Only Active 10 Tables
-- Ready for phpMyAdmin / MySQL / InfinityFree / XAMPP / WAMP
-- ==========================================================

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Method 1: Dynamic Automatic Cleanup
-- Drops ANY table that is NOT in the 10 active tables list
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
    'notices'
  );

SET @sql = IF(@tables IS NOT NULL, CONCAT('DROP TABLE IF EXISTS ', @tables), 'SELECT "All extra tables are already cleaned up." AS status');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- --------------------------------------------------------
-- Method 2: Explicit Cleanup of Legacy Tables (Fallback)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admin_login`;
DROP TABLE IF EXISTS `atten`;
DROP TABLE IF EXISTS `attent_five`;
DROP TABLE IF EXISTS `class_five`;
DROP TABLE IF EXISTS `class_six`;
DROP TABLE IF EXISTS `class_seven`;
DROP TABLE IF EXISTS `class_eight`;
DROP TABLE IF EXISTS `class_nine`;
DROP TABLE IF EXISTS `class_ten`;
DROP TABLE IF EXISTS `res_five`;
DROP TABLE IF EXISTS `res_six`;
DROP TABLE IF EXISTS `res_seven`;
DROP TABLE IF EXISTS `res_eight`;
DROP TABLE IF EXISTS `res_nine`;
DROP TABLE IF EXISTS `res_ten`;
DROP TABLE IF EXISTS `student_admission`;
DROP TABLE IF EXISTS `student_admission6`;
DROP TABLE IF EXISTS `tab_five`;
DROP TABLE IF EXISTS `tab_six`;
DROP TABLE IF EXISTS `tab_seven`;
DROP TABLE IF EXISTS `tab_eight`;
DROP TABLE IF EXISTS `tab_nine`;
DROP TABLE IF EXISTS `tab_ten`;
DROP TABLE IF EXISTS `teachers_admin`;
DROP TABLE IF EXISTS `library_admin`;
DROP TABLE IF EXISTS `notices_admin`;
DROP TABLE IF EXISTS `report_issues`;

SET FOREIGN_KEY_CHECKS = 1;

-- Verify remaining active tables
SHOW TABLES;
