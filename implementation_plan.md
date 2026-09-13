# Implementation Plan - Multi-Role Login & RBAC System (Super Admin, Admin, Staff, Teacher, Student)

Implement a comprehensive, enterprise-grade **Role-Based Access Control (RBAC)** and authentication system supporting five distinct roles:
1. **Super Admin**: Complete master administrative access (all CRUD operations across all modules).
2. **Admin**: Academic administration & management (CRUD on students, teachers, classes, attendance, marks, library, notices, reports).
3. **Staff**: Operational support (View students/classes, Add/Edit attendance and student details, manage library book issues and notices; restricted from deleting records and editing teacher payroll/admin credentials).
4. **Teacher**: Academic faculty access (View assigned classes and students, record and update daily attendance, enter and modify exam marks/results, view library catalog, view notices).
5. **Student**: Student self-service portal (View personalized academic dashboard, view own attendance percentage, view own exam marks & report cards, browse library catalog, view school notices; read-only access).

---

## User Review Required

> [!IMPORTANT]
> - **Default Passwords for Testing**:
>   - Super Admin: `admin` / `admin123`
>   - Staff: `staff` / `admin123`
>   - Teacher: `EMP101` or `r.jenkins@schoolsms.edu` / `teacher123`
>   - Student: `STD-1001` or `alex.j@example.com` / `student123`
> - Automatic schema migration will run safely via [connection.php](file:///d:/School/school-management-system-php/connection.php) to ensure `password` columns are added to existing `teachers` and `students` tables without losing existing data.

---

## Role & Permission Matrix

| Module | Super Admin | Admin | Staff | Teacher | Student |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Dashboard** | Full Metrics | Full Metrics | Operational Metrics | Faculty Dashboard | Personal Student Overview |
| **Students** | CRUD (Add, Edit, Delete) | CRUD | View + Add + Edit | View (Class lists) | View Own Profile Only |
| **Teachers** | CRUD + Salary | CRUD | View Only | View Directory | ❌ No Access |
| **Classes & Sections** | CRUD | CRUD | View Only | View | ❌ No Access |
| **Attendance** | Full CRUD | Full CRUD | View + Record/Edit | View + Record/Edit | View Own Attendance |
| **Exams & Results** | Full CRUD | Full CRUD | View Only | View + Add/Edit Marks | View Own Grades / Results |
| **Library** | Full CRUD | Full CRUD | Full CRUD + Issues | View Catalog | View Catalog + My Issued |
| **Notices** | Full CRUD | Full CRUD | View + Add + Edit | View Notices | View Notices |
| **Reports** | Full Access | Full Access | View Reports | Class Reports | ❌ No Access |

---

## Proposed Changes

### 1. Database Schema & Synchronization

#### [MODIFY] [connection.php](file:///d:/School/school-management-system-php/connection.php)
- Add schema auto-migration check to automatically verify and add `password` columns to `teachers` and `students` tables if not already present.
- Seed default passwords (`$2y$10$...`) for sample teachers (`teacher123`) and sample students (`student123`).

#### [MODIFY] [database.sql](file:///d:/School/school-management-system-php/database.sql)
- Update standard schema definition for `teachers` and `students` to include `password VARCHAR(255) NOT NULL`.
- Add seeded test records with encrypted bcrypt passwords.

---

### 2. Authentication & Permission Middleware

#### [MODIFY] [includes/auth.php](file:///d:/School/school-management-system-php/includes/auth.php)
- Implement RBAC helper functions:
  - `is_logged_in()`: verifies active session.
  - `get_user_role()`: retrieves normalized user role.
  - `has_role(array|string $allowed_roles)`: checks whether the logged-in user possesses one of the allowed roles.
  - `require_role(array|string $allowed_roles)`: enforces page-level route guard. If user lacks permission, returns a 403 Forbidden alert or redirects with a friendly notice.
  - `can_delete()`: returns true only for `Super Admin` and `Admin`.
  - `can_manage_teachers()`: returns true for `Super Admin` and `Admin`.
  - `can_manage_results()`: returns true for `Super Admin`, `Admin`, and `Teacher`.
  - `can_record_attendance()`: returns true for `Super Admin`, `Admin`, `Staff`, and `Teacher`.

---

### 3. Unified Multi-Role Login Portal

#### [MODIFY] [login.php](file:///d:/School/school-management-system-php/login.php)
- Enhance the login system to support all 5 roles:
  - Add clean Role Switcher / Tabs or Smart Identifier Detection (Username / Email / Roll No / Employee ID).
  - Authenticate across `admins` (Super Admin, Admin, Staff), `teachers` (Teacher), and `students` (Student).
  - Store full context in session: `user_id`, `username`, `full_name`, `email`, `role`, `teacher_id`, `student_id`, `class_id`, etc.
  - Display helpful demo credential quick-fill cards for easy testing.

---

### 4. Dynamic Role-Based Sidebar Navigation

#### [MODIFY] [includes/sidebar.php](file:///d:/School/school-management-system-php/includes/sidebar.php)
- Dynamically render menu items based on `$user_role`:
  - **Super Admin & Admin**: All modules visible.
  - **Staff**: Dashboard, Students, Classes, Attendance, Library, Notices, Reports.
  - **Teacher**: Dashboard, Students, Classes, Attendance, Results & Marks, Library, Notices.
  - **Student**: Dashboard (My Overview), My Attendance, My Results, Library Catalog, Notice Board.
- Show badge/tag with specific role and avatar styling.

---

### 5. Role Guarding & CRUD Protection across Modules

#### [MODIFY] [index.php](file:///d:/School/school-management-system-php/index.php)
- Adapt dashboard widgets to show relevant metrics based on role:
  - Admins/Staff: Total students, faculty, classes, attendance rate, recent notices.
  - Teacher: Assigned classes count, student count, pending marks, notices.
  - Student: Personal attendance rate, recent test grades, issued library books, latest notices.

#### [MODIFY] `students/` ([index.php](file:///d:/School/school-management-system-php/students/index.php), [create.php](file:///d:/School/school-management-system-php/students/create.php), [edit.php](file:///d:/School/school-management-system-php/students/edit.php), [delete.php](file:///d:/School/school-management-system-php/students/delete.php), [view.php](file:///d:/School/school-management-system-php/students/view.php))
- Hide "Add Student" button for Teacher and Student roles.
- Hide "Edit" button for Teacher and Student roles.
- Hide "Delete" button for Staff, Teacher, and Student roles (restricted to Super Admin & Admin).
- In `create.php`, `edit.php`, `delete.php`, call `require_role(...)` to protect the backend endpoints.

#### [MODIFY] `teachers/` ([index.php](file:///d:/School/school-management-system-php/teachers/index.php), [create.php](file:///d:/School/school-management-system-php/teachers/create.php), [edit.php](file:///d:/School/school-management-system-php/teachers/edit.php), [delete.php](file:///d:/School/school-management-system-php/teachers/delete.php))
- Restrict access to Admins and Super Admins for creation, editing, and deletion.
- For Staff, allow view-only (hide salary column for non-Super Admins).
- Protect `create.php`, `edit.php`, `delete.php` with `require_role(['Super Admin', 'Admin'])`.

#### [MODIFY] `classes/` ([index.php](file:///d:/School/school-management-system-php/classes/index.php), [create.php](file:///d:/School/school-management-system-php/classes/create.php), [edit.php](file:///d:/School/school-management-system-php/classes/edit.php), [delete.php](file:///d:/School/school-management-system-php/classes/delete.php))
- Restrict create/edit/delete to Super Admin and Admin. Protect actions.

#### [MODIFY] `attendance/` ([index.php](file:///d:/School/school-management-system-php/attendance/index.php), [report.php](file:///d:/School/school-management-system-php/attendance/report.php))
- For Students: Show personalized attendance view (own presence, dates, and percentage).
- For Teachers / Staff / Admins: Show class selection and daily attendance recording form.

#### [MODIFY] `results/` ([index.php](file:///d:/School/school-management-system-php/results/index.php), [create.php](file:///d:/School/school-management-system-php/results/create.php), [edit.php](file:///d:/School/school-management-system-php/results/edit.php), [delete.php](file:///d:/School/school-management-system-php/results/delete.php))
- For Students: Show personalized results and grades report card.
- For Teachers / Admins: Show full mark sheets with "Add Marks", "Edit Marks" capabilities.
- Restrict delete action to Super Admin and Admin.

#### [MODIFY] `library/` ([index.php](file:///d:/School/school-management-system-php/library/index.php), [create.php](file:///d:/School/school-management-system-php/library/create.php), [edit.php](file:///d:/School/school-management-system-php/library/edit.php), [delete.php](file:///d:/School/school-management-system-php/library/delete.php), [issue.php](file:///d:/School/school-management-system-php/library/issue.php))
- Hide book management and issue actions for Students and Teachers (Catalog view only).
- Allow Staff, Admin, Super Admin to manage catalog and issue/return books.

#### [MODIFY] `notices/` ([index.php](file:///d:/School/school-management-system-php/notices/index.php), [create.php](file:///d:/School/school-management-system-php/notices/create.php), [edit.php](file:///d:/School/school-management-system-php/notices/edit.php), [delete.php](file:///d:/School/school-management-system-php/notices/delete.php))
- Filter notices by target audience if relevant (e.g. notices targeted for Students, Teachers, All).
- Allow Super Admin, Admin, and Staff to post/edit notices; delete restricted to Admins.

---

## Verification Plan

### Automated Verification
- Run PHP syntax linting (`php -l`) across all modified and updated files to ensure 0 syntax errors:
  ```powershell
  Get-ChildItem -Recurse -Filter *.php | ForEach-Object { php -l $_.FullName }
  ```

### Functional & Role Permission Verification
1. **Super Admin Login**:
   - Login with `admin` / `admin123`.
   - Verify sidebar shows all 9 navigation items.
   - Verify Add, Edit, Delete buttons appear and work on all modules (Students, Teachers, Classes, Results, Library, Notices).
2. **Staff Login**:
   - Login with `staff` / `admin123`.
   - Verify Teachers module is view-only or hidden, and Delete buttons are disabled/hidden across modules.
   - Verify attempting direct URL access to `students/delete.php?id=1` is blocked.
3. **Teacher Login**:
   - Login with `EMP101` / `teacher123`.
   - Verify sidebar displays Teacher modules (Dashboard, Students, Classes, Attendance, Results & Marks, Library, Notices).
   - Verify teacher can add/edit marks in Results, take attendance, and view student lists, but cannot delete students or access teacher management.
4. **Student Login**:
   - Login with `STD-1001` / `student123`.
   - Verify student portal shows student's personal overview, own attendance records, own exam results/grades, library catalog, and notices.
   - Verify student cannot access admin or teacher editing forms.
