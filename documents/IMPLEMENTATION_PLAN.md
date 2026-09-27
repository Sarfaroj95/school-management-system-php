# Institutional Management System - Architecture & Implementation Documentation

## Project: Admin Settings & Multi-Role User Management Portal

---

## 1. Executive Summary & Overview
This document contains the complete technical implementation specification, database schemas, access control policies, credential provisioning mechanisms, and user profile management systems built for the **School Management System (SMS)**.

---

## 2. System Architecture & Component Diagram

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

## 3. Core Modules & Capabilities

### A. Admin-Only Access Control
- **Middleware Guard**: `require_role(['Super Admin', 'Admin'])` enforced in `includes/auth.php`.
- **Navigation Visibility**: The **"Settings & Users"** menu item in `includes/sidebar.php` dynamically appears only for authorized administrative accounts. Unauthorized direct URL access returns HTTP `403 Forbidden`.

### B. Unified Multi-Role User Creation & Provisioning
- **Dedicated Provisioning Wizard**: Located at `settings/user_create.php`.
- **Auto-Generated Unique Identifiers**:
  - Staff / Admins: `STF-101`, `ADM-101`
  - Faculty / Instructors: `EMP-101`, `EMP-102`
  - Students: `STD-1001`, `STD-1002`
- **Temporary Password Generator**: Generates randomized secure passwords (e.g. `8d!K3#p9Z`).
- **1-Click Copy Credential Card**: Post-creation card with instant clipboard copy to facilitate handover to new users.

### C. 1-Click Temporary Password Reset
- Located at `settings/reset_temp_pass.php`.
- Admins can trigger instant password regeneration for any user account across all 3 tables (`admins`, `teachers`, `students`).
- Automatically hashes the password with `password_hash($pass, PASSWORD_BCRYPT)` in MySQL.

### D. Multi-Role Section Access & Permissions Matrix
Defined in `includes/auth.php` via `get_role_permissions_matrix()`:

| Module / Section | Super Admin | Admin | Staff | Teacher | Student |
| :--- | :---: | :---: | :---: | :---: | :---: |
| **Dashboard & Analytics** | Full Access | Full Access | View Only | Faculty View | Personal View |
| **Student Management** | Full CRUD | Full CRUD | Create / Edit | Roster View | Restricted |
| **Faculty & Teachers** | Full CRUD | Full CRUD | View Only | Restricted | Restricted |
| **Classes & Sections** | Full CRUD | Full CRUD | View Only | Schedule View | Restricted |
| **Attendance Portal** | Full Control | Full Control | Mark / View | Mark Roster | View History |
| **Grades & Exams** | Full Control | Full Control | View Only | Gradebook Entry| Report Card |
| **Library Catalog** | Full Control | Full Control | Issue / Return | View Catalog | View Catalog |
| **Notice Board** | Full CRUD | Full CRUD | Post Notices | View Notices | View Notices |
| **Reports & Export** | Full Access | Full Access | Generate | Restricted | Restricted |
| **Settings & Users** | Full Access | Full Access | Restricted | Restricted | Restricted |

### E. Universal Self-Service Profile & Password Change
- Located at `profile.php`.
- Available to all authenticated accounts from the sidebar footer.
- Allows users to replace their temporary password with a permanent password and review their account capabilities and section permissions.

### F. Attendance Feature: Monthly Mode Analysis & Student Breakdown
- Located at [`attendance/report.php`](file:///c:/xampp/htdocs/school-management-system-php/attendance/report.php) and documented in [`documents/ATTENDANCE_FEATURE.md`](file:///c:/xampp/htdocs/school-management-system-php/documents/ATTENDANCE_FEATURE.md).
- Allows interactive clicking on any class/grade row in the Performance table to analyze student-by-student monthly attendance counts (Present, Absent, Late, Excused), total sessions, attendance percentages, and tier health badges.

---

## 4. File Structure & Location Reference

```
school-management-system-php/
├── connection.php               # Centralized DB connection, schema migrations, seed data
├── profile.php                  # Universal user self-service profile & password update
├── login.php                    # Multi-role authentication portal
├── attendance/
│   ├── index.php                # Daily Attendance Register & quick fill
│   └── report.php               # Class-Wise & Monthly Mode Analysis Reports
├── documents/
│   ├── IMPLEMENTATION_PLAN.md   # This documentation file
│   └── ATTENDANCE_FEATURE.md    # Attendance Feature Specification
├── includes/
│   ├── auth.php                 # Role RBAC, permissions matrix, ID generators
│   ├── header.php               # HTML layout header & topbar
│   ├── sidebar.php              # Dynamic role-aware navigation
│   └── footer.php               # Layout footer & global scripts
└── settings/
    ├── index.php                # Admin hub (Staff, Teachers, Students, Matrix, General Settings)
    ├── user_create.php          # Provision new user with unique ID & temp password
    ├── user_edit.php            # Edit user attributes, status, and credentials
    ├── user_delete.php          # Safe deletion handler
    └── reset_temp_pass.php      # 1-click temporary password generator
```

---

## 5. Security & Verification Standards
1. **Password Hashing**: Bcrypt (`PASSWORD_BCRYPT`) used for all permanent and temporary passwords.
2. **SQL Injection Defense**: Prepared statements (`$conn->prepare()`) used across all create, update, delete, and query endpoints.
3. **Account Safety**: Self-deletion protection prevents the active administrator from accidentally locking out their own account.
