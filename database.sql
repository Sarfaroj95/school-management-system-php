-- ==========================================================
-- School Management System (SMS) Database Schema & Seed Data
-- Centralized MySQL Database: school_db
-- Ready for XAMPP / WAMP / MySQL 5.7+ / MariaDB 10.x+
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `school_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `school_db`;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table: admins
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `full_name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `role` ENUM('Super Admin', 'Admin', 'Staff') NOT NULL DEFAULT 'Admin',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed default admin account (Password: admin123)
-- Uses standard password hash for password_verify() as well as md5 compatibility
INSERT INTO `admins` (`id`, `username`, `password`, `full_name`, `email`, `role`, `created_at`) VALUES
(1, 'admin', '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW', 'System Administrator', 'admin@schoolsms.edu', 'Super Admin', NOW()),
(2, 'staff', '$2y$10$EixZaYVK1fsbw1ZfbX3OXePaWxn96p36WQoeG6Lruj3vjPGga31lW', 'Academic Staff', 'staff@schoolsms.edu', 'Staff', NOW());

-- --------------------------------------------------------
-- Table: teachers
-- --------------------------------------------------------
DROP TABLE IF EXISTS `teachers`;
CREATE TABLE `teachers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `emp_id` VARCHAR(20) NOT NULL UNIQUE,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(20) NOT NULL,
  `qualification` VARCHAR(100) NOT NULL,
  `subject_specialization` VARCHAR(100) NOT NULL,
  `joining_date` DATE NOT NULL,
  `salary` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `status` ENUM('Active', 'On Leave', 'Resigned') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `teachers` (`id`, `emp_id`, `name`, `email`, `phone`, `qualification`, `subject_specialization`, `joining_date`, `salary`, `status`, `created_at`) VALUES
(1, 'EMP101', 'Dr. Robert Jenkins', 'r.jenkins@schoolsms.edu', '+1 (555) 234-5678', 'Ph.D in Mathematics', 'Mathematics', '2021-08-15', 4800.00, 'Active', NOW()),
(2, 'EMP102', 'Sarah Mitchell', 's.mitchell@schoolsms.edu', '+1 (555) 345-6789', 'M.Sc in Physics', 'Physical Science', '2022-01-10', 4200.00, 'Active', NOW()),
(3, 'EMP103', 'David Harrison', 'd.harrison@schoolsms.edu', '+1 (555) 456-7890', 'M.A in English Literature', 'English Language', '2020-07-01', 4100.00, 'Active', NOW()),
(4, 'EMP104', 'Emily Rodriguez', 'e.rodriguez@schoolsms.edu', '+1 (555) 567-8901', 'M.Sc in Computer Science', 'Computer Science', '2023-02-14', 4500.00, 'Active', NOW()),
(5, 'EMP105', 'Michael Chang', 'm.chang@schoolsms.edu', '+1 (555) 678-9012', 'M.Sc in Chemistry', 'Chemistry', '2021-11-20', 4300.00, 'Active', NOW());

-- --------------------------------------------------------
-- Table: classes
-- --------------------------------------------------------
DROP TABLE IF EXISTS `classes`;
CREATE TABLE `classes` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `class_name` VARCHAR(50) NOT NULL,
  `section` VARCHAR(10) NOT NULL,
  `room_no` VARCHAR(20) NOT NULL,
  `teacher_id` INT(11) DEFAULT NULL,
  `capacity` INT(11) NOT NULL DEFAULT 40,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_class_teacher` (`teacher_id`),
  CONSTRAINT `fk_class_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `classes` (`id`, `class_name`, `section`, `room_no`, `teacher_id`, `capacity`, `created_at`) VALUES
(1, 'Grade 5', 'A', 'Room 101', 1, 35, NOW()),
(2, 'Grade 6', 'A', 'Room 102', 2, 35, NOW()),
(3, 'Grade 7', 'A', 'Room 201', 3, 40, NOW()),
(4, 'Grade 8', 'A', 'Room 202', 4, 40, NOW()),
(5, 'Grade 9', 'A', 'Room 301', 5, 40, NOW()),
(6, 'Grade 10', 'A', 'Room 302', 1, 45, NOW());

-- --------------------------------------------------------
-- Table: students
-- --------------------------------------------------------
DROP TABLE IF EXISTS `students`;
CREATE TABLE `students` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `roll_no` VARCHAR(20) NOT NULL,
  `first_name` VARCHAR(50) NOT NULL,
  `last_name` VARCHAR(50) NOT NULL,
  `gender` ENUM('Male', 'Female', 'Other') NOT NULL,
  `dob` DATE NOT NULL,
  `email` VARCHAR(100) DEFAULT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `address` TEXT NOT NULL,
  `class_id` INT(11) NOT NULL,
  `admission_date` DATE NOT NULL,
  `parent_name` VARCHAR(100) NOT NULL,
  `parent_phone` VARCHAR(20) NOT NULL,
  `status` ENUM('Active', 'Inactive', 'Graduated', 'Suspended') NOT NULL DEFAULT 'Active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_student_class` (`class_id`),
  CONSTRAINT `fk_student_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `students` (`id`, `roll_no`, `first_name`, `last_name`, `gender`, `dob`, `email`, `phone`, `address`, `class_id`, `admission_date`, `parent_name`, `parent_phone`, `status`, `created_at`) VALUES
(1, 'STD-1001', 'Alex', 'Johnson', 'Male', '2010-04-12', 'alex.j@example.com', '+1 555-0101', '742 Evergreen Terrace, Springfield', 6, '2023-06-01', 'Arthur Johnson', '+1 555-0199', 'Active', NOW()),
(2, 'STD-1002', 'Emma', 'Watson', 'Female', '2010-09-24', 'emma.w@example.com', '+1 555-0102', '124 Conch Street, Bikini Bottom', 6, '2023-06-01', 'Chris Watson', '+1 555-0198', 'Active', NOW()),
(3, 'STD-1003', 'Liam', 'Miller', 'Male', '2011-01-15', 'liam.m@example.com', '+1 555-0103', '221B Baker Street, London', 5, '2023-06-02', 'George Miller', '+1 555-0197', 'Active', NOW()),
(4, 'STD-1004', 'Sophia', 'Davis', 'Female', '2011-06-30', 'sophia.d@example.com', '+1 555-0104', '31 Spooner Street, Quahog', 5, '2023-06-02', 'Mark Davis', '+1 555-0196', 'Active', NOW()),
(5, 'STD-1005', 'Noah', 'Brown', 'Male', '2012-03-18', 'noah.b@example.com', '+1 555-0105', '4 Privet Drive, Little Whinging', 4, '2023-06-05', 'Daniel Brown', '+1 555-0195', 'Active', NOW()),
(6, 'STD-1006', 'Olivia', 'Taylor', 'Female', '2012-07-22', 'olivia.t@example.com', '+1 555-0106', '1313 Mockingbird Lane, Mockingbird Hts', 4, '2023-06-05', 'Samuel Taylor', '+1 555-0194', 'Active', NOW()),
(7, 'STD-1007', 'Lucas', 'Anderson', 'Male', '2013-05-11', 'lucas.a@example.com', '+1 555-0107', '704 Hauser Street, Queens', 3, '2023-06-10', 'Thomas Anderson', '+1 555-0193', 'Active', NOW()),
(8, 'STD-1008', 'Ava', 'Wilson', 'Female', '2013-11-09', 'ava.w@example.com', '+1 555-0108', '1640 Riverside Drive, Hill Valley', 3, '2023-06-10', 'Richard Wilson', '+1 555-0192', 'Active', NOW()),
(9, 'STD-1009', 'Ethan', 'Martinez', 'Male', '2014-02-14', 'ethan.m@example.com', '+1 555-0109', '129 West 81st Street, New York', 2, '2023-06-12', 'Carlos Martinez', '+1 555-0191', 'Active', NOW()),
(10, 'STD-1010', 'Mia', 'Garcia', 'Female', '2015-08-05', 'mia.g@example.com', '+1 555-0110', '420 Paper Street, Wilmington', 1, '2023-06-15', 'Hector Garcia', '+1 555-0190', 'Active', NOW());

-- --------------------------------------------------------
-- Table: subjects
-- --------------------------------------------------------
DROP TABLE IF EXISTS `subjects`;
CREATE TABLE `subjects` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `subject_name` VARCHAR(100) NOT NULL,
  `subject_code` VARCHAR(20) NOT NULL,
  `class_id` INT(11) NOT NULL,
  `teacher_id` INT(11) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_subject_class` (`class_id`),
  KEY `fk_subject_teacher` (`teacher_id`),
  CONSTRAINT `fk_subject_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_subject_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `subjects` (`id`, `subject_name`, `subject_code`, `class_id`, `teacher_id`, `created_at`) VALUES
(1, 'Advanced Mathematics', 'MATH-10', 6, 1, NOW()),
(2, 'Physics & Mechanics', 'PHY-10', 6, 2, NOW()),
(3, 'English Composition', 'ENG-10', 6, 3, NOW()),
(4, 'Computer Science Basics', 'CS-09', 5, 4, NOW()),
(5, 'General Science', 'SCI-08', 4, 2, NOW()),
(6, 'Chemistry Fundamentals', 'CHEM-10', 6, 5, NOW());

-- --------------------------------------------------------
-- Table: attendance
-- --------------------------------------------------------
DROP TABLE IF EXISTS `attendance`;
CREATE TABLE `attendance` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `student_id` INT(11) NOT NULL,
  `class_id` INT(11) NOT NULL,
  `attendance_date` DATE NOT NULL,
  `status` ENUM('Present', 'Absent', 'Late', 'Excused') NOT NULL DEFAULT 'Present',
  `remarks` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_attendance` (`student_id`, `attendance_date`),
  KEY `fk_att_student` (`student_id`),
  KEY `fk_att_class` (`class_id`),
  CONSTRAINT `fk_att_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_att_class` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `attendance` (`student_id`, `class_id`, `attendance_date`, `status`, `remarks`, `created_at`) VALUES
(1, 6, CURDATE(), 'Present', 'On time', NOW()),
(2, 6, CURDATE(), 'Present', 'On time', NOW()),
(3, 5, CURDATE(), 'Present', 'On time', NOW()),
(4, 5, CURDATE(), 'Late', 'Bus delay 15 mins', NOW()),
(5, 4, CURDATE(), 'Absent', 'Sick leave reported', NOW()),
(6, 4, CURDATE(), 'Present', 'On time', NOW()),
(7, 3, CURDATE(), 'Present', 'On time', NOW()),
(8, 3, CURDATE(), 'Excused', 'Doctor appointment', NOW()),
(9, 2, CURDATE(), 'Present', 'On time', NOW()),
(10, 1, CURDATE(), 'Present', 'On time', NOW());

-- --------------------------------------------------------
-- Table: marks
-- --------------------------------------------------------
DROP TABLE IF EXISTS `marks`;
CREATE TABLE `marks` (
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
  PRIMARY KEY (`id`),
  KEY `fk_marks_student` (`student_id`),
  KEY `fk_marks_subject` (`subject_id`),
  CONSTRAINT `fk_marks_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_marks_subject` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `marks` (`id`, `student_id`, `subject_id`, `exam_name`, `marks_obtained`, `max_marks`, `grade`, `remarks`, `exam_date`, `created_at`) VALUES
(1, 1, 1, 'Midterm Exam', 94.50, 100.00, 'A+', 'Outstanding problem solving', '2024-03-15', NOW()),
(2, 1, 2, 'Midterm Exam', 88.00, 100.00, 'A', 'Solid laboratory concepts', '2024-03-17', NOW()),
(3, 1, 3, 'Midterm Exam', 91.00, 100.00, 'A+', 'Excellent essay structure', '2024-03-19', NOW()),
(4, 2, 1, 'Midterm Exam', 82.50, 100.00, 'B+', 'Good effort', '2024-03-15', NOW()),
(5, 2, 2, 'Midterm Exam', 96.00, 100.00, 'A+', 'Top score in class', '2024-03-17', NOW()),
(6, 3, 4, 'Midterm Exam', 89.00, 100.00, 'A', 'Great practical programming', '2024-03-20', NOW());

-- --------------------------------------------------------
-- Table: library_books
-- --------------------------------------------------------
DROP TABLE IF EXISTS `library_books`;
CREATE TABLE `library_books` (
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

INSERT INTO `library_books` (`id`, `book_title`, `isbn`, `author`, `category`, `quantity`, `available_copies`, `rack_no`, `created_at`) VALUES
(1, 'Introduction to Algorithms, 4th Edition', '978-0262046305', 'Thomas H. Cormen', 'Computer Science', 5, 4, 'Rack CS-01', NOW()),
(2, 'Concepts of Modern Physics', '978-0072448481', 'Arthur Beiser', 'Science', 8, 7, 'Rack SC-03', NOW()),
(3, 'Clean Code: A Handbook of Agile Craftsmanship', '978-0132350884', 'Robert C. Martin', 'Programming', 6, 6, 'Rack CS-02', NOW()),
(4, 'Advanced Engineering Mathematics', '978-0470458365', 'Erwin Kreyszig', 'Mathematics', 10, 9, 'Rack MA-01', NOW()),
(5, 'Oxford English Grammar Course: Advanced', '978-0194414906', 'Michael Swan', 'Literature', 12, 12, 'Rack EN-05', NOW());

-- --------------------------------------------------------
-- Table: book_issues
-- --------------------------------------------------------
DROP TABLE IF EXISTS `book_issues`;
CREATE TABLE `book_issues` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `book_id` INT(11) NOT NULL,
  `student_id` INT(11) NOT NULL,
  `issue_date` DATE NOT NULL,
  `due_date` DATE NOT NULL,
  `return_date` DATE DEFAULT NULL,
  `status` ENUM('Issued', 'Returned', 'Overdue') NOT NULL DEFAULT 'Issued',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_issue_book` (`book_id`),
  KEY `fk_issue_student` (`student_id`),
  CONSTRAINT `fk_issue_book` FOREIGN KEY (`book_id`) REFERENCES `library_books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_issue_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `book_issues` (`id`, `book_id`, `student_id`, `issue_date`, `due_date`, `return_date`, `status`, `created_at`) VALUES
(1, 1, 1, CURDATE() - INTERVAL 5 DAY, CURDATE() + INTERVAL 9 DAY, NULL, 'Issued', NOW()),
(2, 2, 2, CURDATE() - INTERVAL 12 DAY, CURDATE() + INTERVAL 2 DAY, NULL, 'Issued', NOW()),
(3, 4, 3, CURDATE() - INTERVAL 20 DAY, CURDATE() - INTERVAL 6 DAY, NULL, 'Overdue', NOW());

-- --------------------------------------------------------
-- Table: notices
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notices`;
CREATE TABLE `notices` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(150) NOT NULL,
  `content` TEXT NOT NULL,
  `target_audience` ENUM('All', 'Students', 'Teachers', 'Parents') NOT NULL DEFAULT 'All',
  `priority` ENUM('Normal', 'Important', 'Urgent') NOT NULL DEFAULT 'Normal',
  `posted_by` VARCHAR(100) NOT NULL DEFAULT 'Administration',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `notices` (`id`, `title`, `content`, `target_audience`, `priority`, `posted_by`, `created_at`) VALUES
(1, 'Annual Science & Tech Fair 2026', 'We are thrilled to announce the Annual Science Fair scheduled for next month. All students from grades 6 through 10 are encouraged to register their project proposals with their science instructors by Friday.', 'All', 'Important', 'Principal Office', NOW() - INTERVAL 2 DAY),
(2, 'Term Examination Schedule Released', 'The upcoming semester examination timetable has been officially finalized and published. Please verify your subject timings and room allocations on the student portal.', 'Students', 'Urgent', 'Examination Cell', NOW() - INTERVAL 1 DAY),
(3, 'Faculty Meeting: Curriculum Review', 'All teachers and department heads are requested to attend a mandatory curriculum review session this Wednesday at 3:30 PM in the Conference Hall.', 'Teachers', 'Normal', 'Vice Principal', NOW());

SET FOREIGN_KEY_CHECKS = 1;
