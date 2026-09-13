# EduCore - Modern School Management System (PHP & MySQL)

A complete, production-ready School Management System built in PHP and MySQL with a **centralized database connection architecture**, **100% prepared statements** for SQL injection protection, a **modern responsive UI design**, and complete **CRUD functionality**.

---

## Key Features & Architecture

* **Centralized Database Connection (`connection.php`)**:
  - All database credentials (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`, `DB_PORT`) are defined exclusively in `connection.php`.
  - Uses the standardized `$conn` variable across the entire application.
  - Root files use `include "connection.php";` and all subfolder files use `include "../connection.php";`.
  - Built-in graceful connection error handling with troubleshooting steps for XAMPP/WAMP.
* **SQL Injection Prevention**:
  - 100% of queries use MySQLi prepared statements (`$stmt = $conn->prepare(...)`, `$stmt->bind_param(...)`, `$stmt->execute()`).
* **Complete CRUD Modules**:
  1. **Students**: Admission registration, update student profiles, student directory with search & class filters, profile view with academic grades and attendance history, deletion.
  2. **Teachers & Faculty**: Faculty roster, teacher registration, specialization & salary management, editing, deletion.
  3. **Classes & Sections**: Class creation, section & room assignment, faculty in-charge linkage, enrolled student counts, deletion safeguards.
  4. **Attendance Management**: Class-wise daily attendance register (Present, Absent, Late, Excused), upsert operations, and summary reports.
  5. **Examinations & Marks**: Record marks for exams, automated letter grade calculation (A+, A, B, C, D, F), update marks, and exam filter.
  6. **Library Catalog**: Book inventory management, ISBN tracking, category filter, real-time copy availability, student loan/issue and return workflow.
  7. **Notice Board**: School announcements with target audience filters (Students, Teachers, Parents) and priority tags (Normal, Important, Urgent).
  8. **Reports & Analytics**: Visual summary cards, capacity utilization, demographics, grade distributions.

---

## Project Folder Structure

```
school-management-system-php/
├── connection.php               # Centralized MySQL database connection ($conn)
├── database.sql                 # Full DB schema, foreign keys & rich seed data
├── index.php                    # Dashboard Overview (include "connection.php";)
├── login.php                    # Authentication portal (include "connection.php";)
├── logout.php                   # Session termination
├── assets/
│   ├── css/
│   │   └── style.css            # Modern UI design system (glassmorphism, dark palette)
│   └── js/
│       └── main.js              # Table live search, modals, dismissible alerts
├── includes/
│   ├── header.php               # Header navbar & meta
│   ├── sidebar.php              # Modern sidebar navigation
│   ├── footer.php               # System footer & scripts
│   └── auth.php                 # Protected session middleware
├── students/
│   ├── index.php                # List students (include "../connection.php";)
│   ├── create.php               # New admission (include "../connection.php";)
│   ├── edit.php                 # Edit student (include "../connection.php";)
│   ├── delete.php               # Delete student (include "../connection.php";)
│   └── view.php                 # Student profile & report card
├── teachers/
│   ├── index.php                # List faculty (include "../connection.php";)
│   ├── create.php               # Add faculty (include "../connection.php";)
│   ├── edit.php                 # Edit faculty (include "../connection.php";)
│   └── delete.php               # Delete faculty (include "../connection.php";)
├── classes/
│   ├── index.php                # List classes (include "../connection.php";)
│   ├── create.php               # Add class (include "../connection.php";)
│   ├── edit.php                 # Edit class (include "../connection.php";)
│   └── delete.php               # Delete class (include "../connection.php";)
├── attendance/
│   ├── index.php                # Attendance register (include "../connection.php";)
│   └── report.php               # Attendance statistics (include "../connection.php";)
├── results/
│   ├── index.php                # Grade book (include "../connection.php";)
│   ├── create.php               # Enter marks (include "../connection.php";)
│   ├── edit.php                 # Edit marks (include "../connection.php";)
│   └── delete.php               # Delete marks (include "../connection.php";)
├── library/
│   ├── index.php                # Book catalog (include "../connection.php";)
│   ├── create.php               # Add book (include "../connection.php";)
│   ├── edit.php                 # Edit book (include "../connection.php";)
│   ├── delete.php               # Delete book (include "../connection.php";)
│   └── issue.php                # Issue & return books (include "../connection.php";)
├── notices/
│   ├── index.php                # Notice board (include "../connection.php";)
│   ├── create.php               # Post notice (include "../connection.php";)
│   ├── edit.php                 # Edit notice (include "../connection.php";)
│   └── delete.php               # Delete notice (include "../connection.php";)
└── reports/
    └── index.php                # Analytics & reports (include "../connection.php";)
```

---

## Installation & Running on XAMPP / WAMP

### Step 1: Copy Project Folder
Place the project inside your XAMPP or WAMP web root directory:
- **XAMPP**: `C:\xampp\htdocs\school-management-system-php\`
- **WAMP**: `C:\wamp64\www\school-management-system-php\`

### Step 2: Start Apache and MySQL
1. Launch the **XAMPP Control Panel** (or WAMP).
2. Click **Start** for both **Apache** and **MySQL**.

### Step 3: Import Database
1. Open your browser and navigate to **phpMyAdmin**: [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
2. Click the **Import** tab at the top.
3. Choose the file `database.sql` located inside the project directory.
4. Click **Go** / **Import**. This will create the database `school_db` with all tables and pre-loaded seed data.

### Step 4: Open Application in Browser
Visit the project URL in your browser:
```
http://localhost/school-management-system-php/
```

### Step 5: Log In
Use the default administrator credentials:
* **Username**: `admin`
* **Password**: `admin123`

---

## Database Connection Details (`connection.php`)

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'school_db');
define('DB_PORT', 3306);
```

All PHP files utilize the central `$conn` variable. No separate connections are created anywhere in the project.
