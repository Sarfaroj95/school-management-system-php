# School Details & Institutional Profile Management Feature

## 1. Overview
The **School Details Management Feature** provides Super Admins and Admins with a dedicated institutional profile administration module in the EduCore School Management System. It allows configuring and updating the institution's official credentials, branding, accreditation, contact data, physical campus location, and academic parameters.

---

## 2. Navigation & Access Control

### Sidebar Menu Location:
- **Section**: **Administration**
- **Menu Item**: **🏫 School Details** (`settings/school_details.php`)
- **Access Level**: Strictly restricted to **Super Admin** and **Admin** (`require_role(['Super Admin', 'Admin'])`).
- **Staff / Teachers / Students**: Blocked with automatic security redirection.

---

## 3. Configurable Institutional Parameters

| Category | Key | Description | Example / Default Value |
| :--- | :--- | :--- | :--- |
| **Identity & Accreditation** | `school_name` | Official School / Institution Name | `KRISHNAPUR PRIMARY SCHOOL` |
| | `school_code` | School Affiliation / Registration Code | `WB-SCH-721242` |
| | `school_established` | Year of Establishment (Estd.) | `1995` |
| | `principal_name` | Headmaster / Principal Full Name | `Headmaster Office` |
| | `school_board` | Affiliation Board / Educational Council | `WBBPE - Primary Education Board` |
| | `school_motto` | Institutional Slogan / Motto | `Empowering Young Minds, Shaping Tomorrow` |
| **Contact & Communication** | `school_email` | Primary Official Contact Email | `contact@educore-sms.edu` |
| | `school_phone` | Primary Telephone / Helpline | `+91 (555) 019-2834` |
| | `school_alt_phone` | Alternate Mobile / Helpline | `+91 98765 43210` |
| | `school_website` | Official Web Portal URL | `https://krishnapur.school.edu` |
| **Campus Location** | `school_address` | Full Physical Campus Address | `RGGM+2V9, Krishnapur, Chandrakona, Krishnapur, West Bengal 721242` |
| | `school_city` | Village / Town / City | `Chandrakona` |
| | `school_district` | District / County | `Paschim Medinipur` |
| | `school_state` | State / Province | `West Bengal` |
| | `school_postal_code` | Postal PIN / ZIP Code | `721242` |
| | `school_country` | Country | `India` |
| **Academic & Financial** | `academic_year` | Active Academic Session / Year | `2026-2027` |
| | `currency_symbol` | Default Currency Symbol | `$` or `₹` |

---

## 4. System-Wide Dynamic Synchronization
All changes saved in **School Details** automatically synchronize across:
- **Official Examination Marksheets & Academic Transcripts** ([`results/print.php`](file:///c:/xampp/htdocs/school-management-system-php/results/print.php))
- **Student Profile & Report Cards** ([`results/index.php`](file:///c:/xampp/htdocs/school-management-system-php/results/index.php))
- **General Settings Portal** ([`settings/index.php`](file:///c:/xampp/htdocs/school-management-system-php/settings/index.php))

---

## 5. Technical Implementation Details
- **Page Controller**: [`settings/school_details.php`](file:///c:/xampp/htdocs/school-management-system-php/settings/school_details.php)
- **Sidebar Integration**: [`includes/sidebar.php`](file:///c:/xampp/htdocs/school-management-system-php/includes/sidebar.php)
- **Database Table**: `system_settings` (Key-Value schema with unique keys and prepared-statement upserts).
