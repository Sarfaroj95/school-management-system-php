# Project Documentation & Implementation Plan

This file stores the complete **Implementation Plan, System Architecture, Access Control Matrix, and User Management Documentation** for the School Management System.

---

> [!NOTE]
> The full detailed documentation has also been archived at [`documents/IMPLEMENTATION_PLAN.md`](file:///c:/xampp/htdocs/school-management-system-php/documents/IMPLEMENTATION_PLAN.md).

---

## 1. System Architecture & Features

```
┌────────────────────────────────────────────────────────────────────────────┐
│                        Admin "Settings" Portal                             │
├───────────────────┬───────────────────┬──────────────────┬─────────────────┤
│ 1. Admins & Staff │ 2. Teachers       │ 3. Students      │ 4. Permissions  │
│ - Super Admin     │ - Employee ID     │ - Roll Number    │ & Section Access│
│ - Admin           │ - Temp Password   │ - Temp Password  │ Matrix for each │
│ - Staff Members   │ - Subject/Dept    │ - Class/Section  │ Role & User     │
└───────────────────┴───────────────────┴──────────────────┴─────────────────┘
```

---

## 2. Core Functional Highlights

### 🛡️ 1. Admin-Only Access Control
- Enforced via `require_role(['Super Admin', 'Admin'])` in [`includes/auth.php`](file:///c:/xampp/htdocs/school-management-system-php/includes/auth.php).
- **"Settings & Users"** menu in [`includes/sidebar.php`](file:///c:/xampp/htdocs/school-management-system-php/includes/sidebar.php) is visible only to Super Admin and Admin roles.

### 👥 2. Multi-Role User Management ([`settings/index.php`](file:///c:/xampp/htdocs/school-management-system-php/settings/index.php))
- **Admins & Staff Tab**: Add, view, edit, reset temp passwords, delete staff accounts.
- **Super Admin & Admin Protection**:
  - **Super Admin Accounts**: The **Actions** column (`Temp Pass`, `Edit`, `Delete`) is strictly hidden for non-Super Admins. They see a `🔒 Super Admin Only` badge.
  - **Other Admin Accounts**: A standard Admin can only operate (`Temp Pass`, `Edit`) on their **own** account. Other Admin rows display a `🔒 Protected` badge, preventing cross-admin credential resets, edits, or deletion.
- **Teachers Tab**: Manage instructors, employee IDs, subject specializations, and statuses.
- **Students Tab**: Search and view enrolled students, roll numbers, assigned classes, and parent info.
- **Real-Time Search**: Search users by unique ID, name, or email on all tabs.
- **Institutional Settings Tab**: School name, academic session, email, phone, and currency.

### ⚡ 3. Unique ID & Temporary Password Generator ([`settings/user_create.php`](file:///c:/xampp/htdocs/school-management-system-php/settings/user_create.php))
- Auto-generates unique usernames/IDs:
  - Staff / Admins: `STF-101`, `ADM-101`
  - Teachers: `EMP-101`, `EMP-102`
  - Students: `STD-1001`, `STD-1002`
- Auto-generates secure temporary passwords (e.g. `8d!K3#p9Z`).
- **1-Click Copy Credential Card** to easily share credentials with the new user.

### 🔑 4. 1-Click Temporary Password Reset ([`settings/reset_temp_pass.php`](file:///c:/xampp/htdocs/school-management-system-php/settings/reset_temp_pass.php))
- Generates a new temporary password and updates the database with a bcrypt hash.
- Strict authorization check: Only a logged-in **Super Admin** can reset passwords for Super Admin accounts.

### 🔒 5. Role Section Permissions Matrix
Defined in [`includes/auth.php`](file:///c:/xampp/htdocs/school-management-system-php/includes/auth.php) and rendered at [`settings/index.php?tab=matrix`](file:///c:/xampp/htdocs/school-management-system-php/settings/index.php?tab=matrix):

| Section | Super Admin | Admin | Staff | Teacher | Student |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Dashboard** | Full Access | Full Access | View | Faculty View | Personal View |
| **Students** | Full CRUD | Full CRUD | Create/Edit | Roster View | Restricted |
| **Teachers** | Full CRUD | Full CRUD | View Only | Restricted | Restricted |
| **Classes** | Full CRUD | Full CRUD | View Only | Schedule View| Restricted |
| **Attendance**| Full Control| Full Control| Mark/View | Mark Roster | View History |
| **Results** | Full Control| Full Control| View Only | Gradebook | Report Card |
| **Library** | Full Control| Full Control| Issue/Return| View Catalog | View Catalog |
| **Notices** | Full CRUD | Full CRUD | Post Notices| View Notices | View Notices |
| **Reports** | Full Access | Full Access | Generate | Restricted | Restricted |
| **Settings**| Full Access | Full Access | Restricted | Restricted | Restricted |

### 📊 7. Attendance Feature: Monthly Mode Analysis ([`attendance/report.php`](file:///c:/xampp/htdocs/school-management-system-php/attendance/report.php))
- **Class-Wise Clickable Analytics**: Click any grade/class row in the performance table to view its detailed monthly breakdown.
- **Monthly Student Tallies & Percentage**: Displays student-by-student Present, Absent, Late, Excused counts, attendance percentage, and health status tiers (`🌟 Excellent`, `✅ Good`, `⚠️ Warning`, `🚨 Critical`).
- **Complete Feature Specification**: Documented in [`documents/ATTENDANCE_FEATURE.md`](file:///c:/xampp/htdocs/school-management-system-php/documents/ATTENDANCE_FEATURE.md).

---

## 3. Directory Layout

- [`db_table.md`](file:///c:/xampp/htdocs/school-management-system-php/db_table.md): Database Architecture & Active vs. Legacy Tables Catalog
- [`database.sql`](file:///c:/xampp/htdocs/school-management-system-php/database.sql): Complete SQL database backup & schema export
- [`clean_database.sql`](file:///c:/xampp/htdocs/school-management-system-php/clean_database.sql): Cleanup script to remove obsolete legacy tables
- [`db/school_db.sql`](file:///c:/xampp/htdocs/school-management-system-php/db/school_db.sql): Secondary database export archive
- [`settings/db_export.php`](file:///c:/xampp/htdocs/school-management-system-php/settings/db_export.php): 1-Click live SQL database download utility
- [`attendance/report.php`](file:///c:/xampp/htdocs/school-management-system-php/attendance/report.php): Monthly Mode Analysis & Attendance Analytics
- [`attendance/index.php`](file:///c:/xampp/htdocs/school-management-system-php/attendance/index.php): Daily Attendance Register
- [`documents/ATTENDANCE_FEATURE.md`](file:///c:/xampp/htdocs/school-management-system-php/documents/ATTENDANCE_FEATURE.md): Attendance Feature Architecture & Documentation
- [`settings/index.php`](file:///c:/xampp/htdocs/school-management-system-php/settings/index.php): Settings dashboard & User Management
- [`settings/user_create.php`](file:///c:/xampp/htdocs/school-management-system-php/settings/user_create.php): User provisioning wizard
- [`settings/user_edit.php`](file:///c:/xampp/htdocs/school-management-system-php/settings/user_edit.php): User edit page
- [`settings/user_delete.php`](file:///c:/xampp/htdocs/school-management-system-php/settings/user_delete.php): User deletion handler
- [`settings/reset_temp_pass.php`](file:///c:/xampp/htdocs/school-management-system-php/settings/reset_temp_pass.php): Temporary password reset handler
- [`documents/ENTITY_RELATIONSHIP_DIAGRAM.md`](file:///c:/xampp/htdocs/school-management-system-php/documents/ENTITY_RELATIONSHIP_DIAGRAM.md): Visual ER Model, Schema Cardinalities & Data Flow Pipelines
- [`documents/BUSINESS_FLOW_AND_FUNCTIONALITY.md`](file:///c:/xampp/htdocs/school-management-system-php/documents/BUSINESS_FLOW_AND_FUNCTIONALITY.md): End-to-End Business Flow, Role Journeys & System Functionality Guide
- [`documents/IMPLEMENTATION_PLAN.md`](file:///c:/xampp/htdocs/school-management-system-php/documents/IMPLEMENTATION_PLAN.md): Archived documentation
