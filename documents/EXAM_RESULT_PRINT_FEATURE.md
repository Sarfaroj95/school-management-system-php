# Student Examination Result & Marksheet Print Feature

## 1. Overview
The **Examination Results & Marksheet Print Feature** provides a complete, official academic transcript and report card printing solution within the EduCore School Management System. It allows administrators, teachers, and students to generate and print exam-specific or comprehensive academic marksheets.

---

## 2. Key Capabilities & Specifications

### A. Comprehensive Student Bio-Data
The generated marksheet includes full institutional and student identification details:
- **Student Full Name**: First Name & Last Name (e.g. `Alex Johnson`)
- **Roll Number**: Official Student ID / Roll Code (e.g. `STD-1001`)
- **Class & Section**: Grade / Class and Section (e.g. `Grade 10 - Section A`)
- **Class Teacher**: Assigned Faculty in-charge
- **Date of Birth**: Formatted Date of Birth
- **Gender & Status**: Student Gender and Enrollment Status
- **Parent / Guardian**: Full Parent Name
- **Parent Contact / Phone**: Emergency & Parent contact number
- **Admission Date**: Date of school enrollment
- **Date of Issue**: Official date of transcript generation

### B. Institutional Branding & System Settings
- Pulls live institution settings from the `system_settings` table:
  - Institution Name (e.g. `KRISHNAPUR PRIMARY SCHOOL`)
  - Official Address, Telephone Number, and Email Address
  - Current Academic Session / Year (e.g. `2026-2027`)
  - Official Emblem and Watermark

### C. Examination Breakdown & Performance Analytics
- **Subject-wise Marks Table**:
  - Serial Number (#)
  - Subject Code & Name
  - Maximum Marks
  - Passing Threshold Marks (35% benchmark)
  - Marks Obtained
  - Subject Percentage (%)
  - Letter Grade (A, B, C, D, E)
  - Performance Remarks / Qualitative Assessment
- **Summary Aggregate KPI Blocks**:
  - Total Maximum Marks vs Total Marks Obtained
  - Overall Cumulative Percentage
  - Overall Letter Grade
  - Final Result Standing (`PASSED - VERY GOOD`, `PASSED - GOOD`, `PASSED - SATISFACTORY`, `PASSED - AVERAGE`, `NOT SATISFACTORY / FAILED`)
- **Official Results Grading Scale**:

| Marks Range (%) | Letter Grade | Official Significance | Result Status |
| :---: | :---: | :--- | :--- |
| **80 – 100** | **A** | **Very Good** | Passed with Distinction |
| **65 – 79** | **B** | **Good** | Passed - 1st Division |
| **50 – 64** | **C** | **Satisfactory** | Passed |
| **35 – 49** | **D** | **Average** | Passed - Average |
| **Below 35** | **E** | **Not Satisfactory** | Failed / Backlog |

- **Evaluator Remarks**: Performance narrative based on cumulative standing.
- **Official Signatures & Verification Area**:
  - Class Teacher Signature & Date line
  - Official Institutional Stamp & Seal box
  - Principal / Controller of Examinations Signature line
  - System Document ID & Timestamp

---

## 3. Access Control & Role-Specific UI

### Student View (`results/index.php`):
- Displays the personalized summary table **without** an extraneous Actions column.
- Features a top-level **"🖨️ Print Official Marksheet"** button in the card header that opens the printable marksheet transcript in a new tab.
- Role-based security restricts students to only viewing and printing their own academic records.

### Admin, Super Admin, Teacher, and Staff View (`results/index.php`):
- The Grade Book table includes an **Actions** column with a direct **"🖨️ Print"** button on each row linking to `print.php?student_id=X&exam=Y`.
- The top header toolbar features a **"🖨️ Print Student Result"** button that opens a Student & Exam Selector dialog.
- **Advanced Multi-Criteria Filtering Toolbar**:
  - **Keyword Search**: Quick search by student name, roll number, or subject.
  - **Class / Grade Filter**: Dynamic dropdown of all classes (e.g. `Grade 10 (A)`, `Class 5 (A)`).
  - **Examination Term Filter**: Dynamic dropdown of all exams recorded in the database.
  - **Letter Grade Filter**: Filter by letter grade (`A`, `B`, `C`, `D`, `E`).
  - **Reset Button**: Resets active filters with a single click.

### Direct Profile Integration (`students/view.php`):
- In the student profile under *Academic Performance & Exam Marks*, an instant **"🖨️ Print Marksheet"** button is integrated.

---

## 4. Class-wise Merit Ranking & Tabulation Sheet (Final Print)

### Overview (`results/final_print.php`):
The **Final Print (Class Merit List)** feature generates an official, comprehensive class-level tabulation sheet and merit list sorted in descending order of total marks obtained.

### Features & Capabilities:
1. **Merit Ranking**:
   - Students are ranked based on total marks obtained (with percentage and roll number as tie-breakers).
   - Top 3 rankers feature distinct medal badges (🥇 1st Rank, 🥈 2nd Rank, 🥉 3rd Rank).
2. **Tabulation Matrix**:
   - Displays all subjects with individual marks and pass/fail indicators.
   - Total marks obtained and total maximum marks.
   - Cumulative percentage (%) and overall letter grade.
   - Final standing (`Passed with Distinction`, `Passed - 1st Div`, `Passed`, `Failed / Backlog`).
3. **Class Statistics & Analytics Summary**:
   - Total Students Enrolled
   - Overall Pass Rate (%)
   - Class Average Percentage (%)
   - Class Topper Name, Roll Number, and Top Score
4. **Institutional Verification & Dual Signatures**:
   - Class Teacher Signature
   - Institutional Seal Block
   - Head of Institution / Principal Signature

---

## 5. Technical File Structure
- [`results/final_print.php`](file:///c:/xampp/htdocs/school-management-system-php/results/final_print.php): Class-wise merit ranking and final examination tabulation sheet with `@media print` layout.
- [`results/print.php`](file:///c:/xampp/htdocs/school-management-system-php/results/print.php): Dedicated printable individual student marksheet transcript template.
- [`results/index.php`](file:///c:/xampp/htdocs/school-management-system-php/results/index.php): Updated Grade Book table with **Final Print (Class Merit List)** button positioned before **Print Student Result**.
- [`students/view.php`](file:///c:/xampp/htdocs/school-management-system-php/students/view.php): Updated with direct marksheet print trigger and corrected marks table bindings.
- [`settings/school_details.php`](file:///c:/xampp/htdocs/school-management-system-php/settings/school_details.php): Administration page for updating school information and branding.
