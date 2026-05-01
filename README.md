# Hospital Appointment Management System

## Production-Ready, Role-Based Healthcare Management Platform

**Version:** 1.0.0  
**Stack:** PHP 8.x, MySQL 8.x, HTML5, CSS3, JavaScript, Bootstrap 5  
**Database Name:** `hospital_db`  
**Project Folder:** `hospital_appointment_system`  
**Install Path:** `/xampp/htdocs/hospital_appointment_system/` (or `/opt/lampp/htdocs/`)

---

## SYSTEM ARCHITECTURE

### 3-Tier Architecture Pattern

```
┌─────────────────────────────────────────────────────────────┐
│                     PRESENTATION LAYER                        │
│  ┌─────────┬──────────┬─────────────┬──────────┬─────────┐ │
│  │  Home   │  Admin   │   Doctor    │Reception │ Patient │ │
│  │  Pages  │Dashboard │  Dashboard  │ Dashboard│Dashboard│ │
│  └─────────┴──────────┴─────────────┴──────────┴─────────┘ │
├─────────────────────────────────────────────────────────────┤
│                   APPLICATION LAYER                         │
│  ┌──────────────┬─────────────┬─────────────┬──────────┐  │
│  │   Auth       │Appointment  │   User      │  Audit   │  │
│  │ Controller   │ Controller  │ Management  │   Log    │  │
│  ├──────────────┼─────────────┼─────────────┼──────────┤  │
│  │Role Middleware│Notification │   Report    │  System  │  │
│  │              │  Engine     │   Engine    │ Settings │  │
│  └──────────────┴─────────────┴─────────────┴──────────┘  │
├─────────────────────────────────────────────────────────────┤
│                      DATA LAYER                             │
│  ┌────────┬──────────┬─────────────┬──────────┬─────────┐ │
│  │ Users  │Roles     │Appointments │ Medical  │Doctor   │ │
│  │        │          │             │ Records  │Schedules│ │
│  ├────────┼──────────┼─────────────┼──────────┼─────────┤ │
│  │Departs │Notificat.│  Audit Logs │ Settings │Uploads  │ │
│  └────────┴──────────┴─────────────┴──────────┴─────────┘ │
└─────────────────────────────────────────────────────────────┘
```

### Design Patterns Used
- **Singleton Pattern:** Database connection (`Database::getInstance()`)
- **Front Controller:** All requests flow through centralized authentication
- **Template Method:** Shared header/sidebar/footer templates
- **RBAC (Role-Based Access Control):** Middleware enforces permissions
- **Repository Pattern:** Views encapsulate complex queries
- **Observer Pattern:** Triggers auto-generate notifications

---

## DATABASE DESIGN (Normalized Schema)

### Entity Relationship Structure

```
┌─────────────┐       ┌─────────────┐       ┌─────────────┐
│   roles     │       │   users     │       │departments  │
├─────────────┤       ├─────────────┤       ├─────────────┤
│ role_id(PK) │──┐    │ user_id(PK) │       │ dept_id(PK) │
│ role_name   │  │    │ role_id(FK) │───────┤ dept_name   │
│ role_slug   │  │    │ dept_id(FK) │───────┤ location    │
│ permissions │  │    │ email       │       └─────────────┘
└─────────────┘  │    │ password    │              │
                 │    │ first_name  │              │
                 │    │ last_name   │              ▼
                 │    │ is_active   │       ┌─────────────┐
                 │    │ created_at  │       │doctor_sched.│
                 │    └─────────────┘       ├─────────────┤
                 │           │              │ schedule_id │
                 │           │              │ doctor_id   │
                 │           ▼              │ day_of_week │
                 │    ┌─────────────┐       │ start_time  │
                 │    │appointments │       └─────────────┘
                 │    ├─────────────┤
                 │    │appointment_ │
                 │    │    id(PK)   │
                 │    │ patient_id  │───────┐
                 │    │ doctor_id   │───────┤
                 │    │ dept_id     │───────┘
                 │    │ status      │       ┌─────────────┐
                 │    │ created_at  │       │   medical   │
                 │    └─────────────┘       │   records   │
                 │           │              ├─────────────┤
                 │           └──────────────│ record_id   │
                 │                          │ patient_id  │
                 │    ┌─────────────┐       │ doctor_id   │
                 │    │ notifications│       │ diagnosis   │
                 │    ├─────────────┤       │ prescription│
                 │    │notification_│      └─────────────┘
                 │    │    id(PK)   │
                 │    │ user_id(FK) │
                 │    │ type        │
                 │    │ is_read     │
                 │    └─────────────┘
                 │
                 └────┐
                      ▼
                ┌─────────────┐
                │  audit_logs │
                ├─────────────┤
                │ log_id(PK)  │
                │ user_id(FK) │
                │ action      │
                │ entity_type │
                │ old_values  │
                │ new_values  │
                │ ip_address  │
                └─────────────┘
```

### Key Normalization Decisions
1. **Single Users Table** (not separate tables per role) — simplifies joins, indexes, and queries
2. **Role-specific fields nullable** — `dept_id`, `specialization`, etc. only populated for doctors
3. **Appointment status workflow** — proper state machine: pending → approved → completed
4. **Soft deletes** — `is_active` flag instead of hard deletion for audit trail
5. **Audit logs** — JSON columns store old/new values for compliance
6. **Notifications table** — decoupled from business logic, scalable

---

## FOLDER / PROJECT STRUCTURE

```
hospital_appointment_system/          <-- ROOT FOLDER (place in htdocs)
│
├── index.php                          <-- Public landing page
│
├── database/
│   └── hospital_db.sql                <-- Full database schema + seed data
│
├── assets/
│   ├── css/
│   │   └── style.css                  <-- Custom Bootstrap 5 theme
│   ├── js/
│   │   └── main.js                    <-- Global JS (validation, AJAX, UI)
│   └── images/                        <-- Uploads / static images
│
├── includes/
│   ├── config.php                     <-- DB config, constants, DB class
│   ├── functions.php                  <-- Helper functions, utilities
│   ├── auth.php                       <-- Login, register, logout, session
│   ├── middleware.php                 <-- RBAC, role guards, permissions
│   ├── header.php                     <-- Dashboard layout: sidebar + top nav
│   └── footer.php                     <-- Dashboard layout: closing tags
│
├── pages/
│   ├── auth/
│   │   ├── login.php                  <-- Login form
│   │   ├── register.php               <-- Patient registration
│   │   └── logout.php                 <-- Session destroy
│   │
│   ├── admin/
│   │   ├── dashboard.php              <-- Admin home with stats
│   │   ├── users.php                  <-- CRUD all users (add/edit/delete)
│   │   ├── departments.php            <-- CRUD departments
│   │   ├── appointments.php           <-- View all appointments
│   │   ├── reports.php                <-- Analytics & charts
│   │   └── settings.php             <-- System configuration
│   │
│   ├── doctor/
│   │   ├── dashboard.php              <-- Doctor home with schedule
│   │   ├── appointments.php           <-- View/complete/add notes
│   │   ├── schedule.php               <-- Manage weekly availability
│   │   ├── patients.php               <-- Assigned patients list
│   │   └── records.php                <-- Medical records management
│   │
│   ├── receptionist/
│   │   ├── dashboard.php              <-- Front desk overview
│   │   ├── appointments.php           <-- Approve/reject/assign/reschedule
│   │   ├── walkins.php                <-- Walk-in patient registration
│   │   ├── patients.php               <-- Patient records management
│   │   └── doctors.php                <-- View doctor schedules
│   │
│   └── patient/
│       ├── dashboard.php              <-- Patient home
│       ├── book.php                   <-- Book new appointment
│       ├── appointments.php           <-- View/cancel own appointments
│       ├── history.php                <-- Medical history view
│       └── profile.php                <-- Update personal info
│
└── uploads/                           <-- File uploads directory
```

---

## WHAT WAS WRONG IN TYPICAL PROTOTYPES — AND HOW WE FIXED IT

| Problem in Typical Prototype | Our Production Fix |
|------------------------------|----------------------|
| Single user table, no roles | **RBAC with roles table** + role_id foreign key in users |
| No password hashing | **Bcrypt with cost 12** + automatic rehash detection |
| No input sanitization | **Prepared statements everywhere** + htmlspecialchars output |
| No SQL injection protection | **PDO prepared statements** with parameter binding on ALL queries |
| No session security | **httponly cookies** + strict mode + timeout + regenerate_id |
| No access control | **Middleware layer** — `requireAdmin()`, `requireDoctor()`, etc. |
| No audit trail | **Audit logs table** with IP, user agent, old/new JSON values |
| No notifications | **Notifications table** + MySQL triggers auto-create on status change |
| Flat appointment status | **Proper workflow:** pending → approved → completed (with rejected, cancelled states) |
| No pagination | **Reusable `paginate()` helper** on ALL list queries |
| No file upload validation | **Size, type, extension checks** + random filename generation |
| No account lockout | **5 failed attempts = 15 minute lockout** with timestamp tracking |
| No doctor schedules | **doctor_schedules table** with day_of_week, time slots, availability |
| No medical records | **medical_records table** linked to appointments and patients |
| No department management | **departments table** with location, code, active/inactive |
| No search/filter | **Search + status filter + date range** on all list pages |
| No responsive design | **Bootstrap 5** with custom CSS + mobile sidebar overlay |
| Inline CSS/JS everywhere | **Centralized style.css + main.js** + template includes |
| No error logging | **error_log()** on all exceptions + graceful degradation |

---

## SETUP INSTRUCTIONS

### Step 1: Create the Database

1. Open **phpMyAdmin** (http://localhost/phpmyadmin)
2. Create database: `hospital_db`
3. Select `hospital_db`
4. Go to **Import** tab
5. Choose file: `hospital_appointment_system/database/hospital_db.sql`
6. Click **Go**

### Step 2: Install the Project

#### Option A: XAMPP (Windows/Mac/Linux)
1. Copy the `hospital_appointment_system` folder
2. Paste into `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (Mac) or `/opt/lampp/htdocs/` (Linux)
3. Access: `http://localhost/hospital_appointment_system/`

#### Option B: Laragon (Windows)
1. Copy folder to `C:\laragon\www\`
2. Access: `http://hospital_appointment_system.test/`

### Step 3: Database Credentials (if needed)

Edit `includes/config.php` if your MySQL credentials differ:
```php
define('DB_USERNAME', 'root');     // Change if different
define('DB_PASSWORD', '');         // Set your MySQL password
```

### Step 4: Default Login Credentials

| Role | Email | Password |
|------|-------|----------|
| Admin | `admin@hospital.com` | `password123` |
| Doctor | `dr.smith@hospital.com` | `password123` |
| Doctor | `dr.jones@hospital.com` | `password123` |
| Doctor | `dr.patel@hospital.com` | `password123` |
| Receptionist | `reception@hospital.com` | `password123` |
| Patient | `patient@demo.com` | `password123` |
| Patient | `linda.parker@demo.com` | `password123` |

---

## FILE NAMING CONVENTIONS

| File | Purpose | Naming Rule |
|------|---------|-------------|
| Database file | Schema + data | `hospital_db.sql` |
| Config | DB + constants | `config.php` |
| Auth | Login/register | `auth.php` |
| Middleware | Role guards | `middleware.php` |
| Functions | Helpers | `functions.php` |
| Header | Dashboard shell | `header.php` |
| Footer | Dashboard shell | `footer.php` |
| Dashboard | Role home page | `dashboard.php` |
| CRUD pages | Entity management | Plural noun: `users.php`, `appointments.php` |
| Actions | Single operations | Verb + entity: `book.php`, `walkins.php` |

---

## ROLE PERMISSIONS MATRIX

| Feature | Admin | Doctor | Receptionist | Patient |
|---------|:-----:|:------:|:------------:|:-------:|
| Manage all users | ✅ | ❌ | ❌ | ❌ |
| Manage departments | ✅ | ❌ | ❌ | ❌ |
| View system reports | ✅ | ❌ | ❌ | ❌ |
| Configure settings | ✅ | ❌ | ❌ | ❌ |
| View own schedule | ❌ | ✅ | ❌ | ❌ |
| Complete appointments | ❌ | ✅ | ❌ | ❌ |
| Add medical records | ❌ | ✅ | ❌ | ❌ |
| Add consultation notes | ❌ | ✅ | ❌ | ❌ |
| Approve appointments | ❌ | ❌ | ✅ | ❌ |
| Assign doctors | ❌ | ❌ | ✅ | ❌ |
| Register walk-ins | ❌ | ❌ | ✅ | ❌ |
| Book appointment | ❌ | ❌ | ❌ | ✅ |
| View own appointments | ❌ | ❌ | ❌ | ✅ |
| Cancel own appointment | ❌ | ❌ | ❌ | ✅ |
| View medical history | ❌ | ❌ | ❌ | ✅ |
| Update own profile | ✅ | ✅ | ✅ | ✅ |

---

## SECURITY FEATURES IMPLEMENTED

1. **Password Security:** Bcrypt hashing (cost 12) with automatic rehashing
2. **Session Protection:** httponly, strict_mode, timeout, IP tracking
3. **Account Lockout:** 5 failed attempts = 15-minute lockout
4. **SQL Injection Prevention:** 100% prepared statements with parameter binding
5. **XSS Prevention:** `htmlspecialchars()` on ALL output
6. **CSRF Protection:** Session binding with regenerate_id on login
7. **Audit Logging:** Every action logged with IP and user agent
8. **File Upload Security:** Type validation, size limits, random filenames
9. **Role Escalation Prevention:** Middleware checks on every protected page
10. **Input Validation:** Client-side + server-side on all forms

---

## APPOINTMENT WORKFLOW

```
Patient                     Receptionist               Doctor
   |                            |                        |
   |── Request Appointment ────>|                        |
   |                            |── Review & Approve ──>|
   |<── Notification ───────────|                        |
   |                            |                        |── View in Schedule
   |                            |                        |
   |── Arrives ────────────────>|── Check In ───────────>|
   |                            |                        |── Consultation
   |                            |                        |── Add Notes
   |<── Record Available ──────|                        |── Mark Complete
   |                            |                        |
```

---

## SCALABILITY CONSIDERATIONS

1. **Database Views** (`vw_appointment_details`, `vw_doctor_departments`) — pre-optimized complex joins
2. **Stored Procedures** (`sp_get_available_slots`, `sp_get_dashboard_stats`) — server-side logic
3. **Database Indexes** — indexes on `email`, `role_id`, `status`, `appointment_date` for fast queries
4. **Pagination** — ALL list queries use LIMIT/OFFSET to prevent memory overload
5. **JSON Columns** — flexible settings and permissions storage
6. **Trigger Automation** — notifications auto-generated on status changes, no PHP overhead

---

## DEVELOPMENT NOTES

### To add a new role:
1. Insert into `roles` table with JSON permissions
2. Add role constant in `config.php`
3. Add menu items in `includes/header.php`
4. Create folder under `pages/`
5. Add middleware function in `middleware.php`

### To add a new page:
1. Create file in appropriate `pages/{role}/` folder
2. Include `middleware.php` and call appropriate `require*()` function
3. Set `$pageTitle` and `$activeMenu` variables
4. Include `header.php` at top and `footer.php` at bottom
5. Add menu link in `includes/header.php` nav array

---

**End of Documentation**
