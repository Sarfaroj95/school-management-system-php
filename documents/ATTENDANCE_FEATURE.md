# Attendance Feature: Monthly Mode Analysis & Student Performance Breakdown

## Overview & Architecture
The **Attendance Feature** provides granular attendance tracking and analytical breakdown for teachers, administrators, and staff. Users can view institutional-level metrics, analyze class-level rates, and drill down into monthly student-by-student attendance records and percentage evaluations.

---

## 🚀 Key Feature Capabilities

### 1. 📊 Class-Wise Attendance Performance
- **Aggregated Overview**: Displays all active classes/grades with total sessions logged, present count, and visual percentage progress bars.
- **Interactive Click-To-Analyze**: Clicking any class row instantly switches the **Monthly Mode Analysis** view to that specific class.

### 2. 📅 Monthly Mode Analysis
- **Month-Year Date Navigation**: Select any month (`YYYY-MM`) with month picker and rapid stepper buttons (`◀ Prev`, `📅 Current Month`, `Next ▶`).
- **Class Switcher**: Switch classes on the fly from the analytics toolbar without returning to the main dashboard.
- **Class Monthly Summary KPI Cards**:
  - **Class Monthly Average**: Overall percentage attendance for the selected month.
  - **Enrolled Students**: Active count of students in the class roster.
  - **Recorded Class Days**: Distinct dates attendance was marked.
  - **Top Attendance Performer**: Student with the highest attendance rate in the month.

### 3. 👥 Student-by-Student Monthly Breakdown Table
Each student in the selected class displays:
- **Roll No**: Monospace identification badge.
- **Student Name & Gender**: Full student name and demographics.
- **Detailed Tallies**:
  - 🟢 **Present Count**: Number of attended days.
  - 🔴 **Absent Count**: Number of unexcused absences.
  - 🟡 **Late Count**: Number of tardy arrivals.
  - 🔵 **Excused Count**: Authorized leaves (medical/official).
- **Total Sessions**: Total recorded days for that student in the month.
- **Monthly Attendance Rate (%)**: Calculated as `(Present Count / Total Sessions) * 100` with visual progress bar.
- **Performance Tier Health Badges**:
  - `🌟 Excellent (≥90%)` (Emerald)
  - `✅ Good (75-89%)` (Sky Blue)
  - `⚠️ Warning (60-74%)` (Amber)
  - `🚨 Critical (<60%)` (Rose Red)

---

## 🗄️ Database Queries & Logic

### Monthly Student Tallies
```sql
SELECT student_id,
       COUNT(id) AS total_sessions,
       SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) AS present_count,
       SUM(CASE WHEN status = 'Absent' THEN 1 ELSE 0 END) AS absent_count,
       SUM(CASE WHEN status = 'Late' THEN 1 ELSE 0 END) AS late_count,
       SUM(CASE WHEN status = 'Excused' THEN 1 ELSE 0 END) AS excused_count
FROM attendance
WHERE class_id = ? AND attendance_date BETWEEN ? AND ?
GROUP BY student_id;
```

### Attendance Percentage Formula
$$\text{Attendance Rate (\%)} = \left(\frac{\text{Present Days}}{\text{Total Recorded Sessions}}\right) \times 100$$

---

## 🔒 Access Control & Role Permissions

| Role | Access Level |
| :--- | :--- |
| **Super Admin & Admin** | Full access to all classes, date ranges, and student analytics |
| **Staff** | Full access to institutional reports and student records |
| **Teacher** | Access to assign rosters and analyze class performance |
| **Student** | Access to personal monthly attendance logs via `attendance/index.php` |

---

## 📁 File Reference
- **Analytics & Monthly Mode Analysis**: [`attendance/report.php`](file:///c:/xampp/htdocs/school-management-system-php/attendance/report.php)
- **Daily Register & Quick Fill**: [`attendance/index.php`](file:///c:/xampp/htdocs/school-management-system-php/attendance/index.php)
- **Access Control & Permissions**: [`includes/auth.php`](file:///c:/xampp/htdocs/school-management-system-php/includes/auth.php)
