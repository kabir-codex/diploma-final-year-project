# Activate Academy — Student Management System

A web-based institute management system built as a PHP & MySQL project. It handles everything a small to medium tuition centre needs — from student registration and batch management to exam results, payments, and parent monitoring.

---

## Technologies Used

### Backend
**PHP 8+** is the server-side language that powers the entire system. Every page is a `.php` file. PHP reads data from the database, processes form submissions, checks who is logged in, and builds the HTML that gets sent to the browser.

**MySQL** is the database. All data — users, results, payments, attendance — is stored in MySQL tables. PHP talks to MySQL using the **MySQLi** extension (`mysqli_query`, `mysqli_fetch_assoc`, etc.).

### Frontend
**HTML5** structures every page — forms, tables, buttons, and layout.

**CSS3** handles all the styling. One single file (`style.css`) styles the entire system — the navigation bar, dashboards, cards, tables, badges, progress bars, and forms. No external CSS framework is used.

**Vanilla JavaScript** is used in small amounts — mainly to highlight the active sidebar section as the user scrolls down the dashboard.

### Server Environment
**XAMPP** is the local development environment. It bundles Apache (web server), MySQL, and PHP together so the project runs on your own computer without needing the internet.

---

## How the System Works

### Sessions & Login
When a user logs in, PHP creates a **session** — a small piece of memory on the server that remembers who is logged in. The session stores the user's ID, name, and role. Every protected page checks the session first. If no session exists, the user is sent back to the login page.

```
User types username + password
→ PHP checks the users table in MySQL
→ If match found: session is created, user goes to dashboard
→ If no match: error message shown
```

### Role-Based Access
Every user has a **role** stored in the database (admin, manager, director, lecturer, receptionist, student, parent). When a logged-in user visits `dashboard.php`, PHP reads their role from the session and loads the matching dashboard file. Each role sees only what they are allowed to see.

### The Dashboard Router
`dashboard.php` is the central hub. It does not contain any dashboard content itself — it simply checks the role and loads the right file:

```
admin        → admin_dash.php
manager      → manager_dash.php
director     → director_dash.php
lecturer     → lecturer_dash.php
receptionist → receptionist_dash.php
student      → student_dash.php
parent       → parent_dash.php
```

### CRUD Operations
CRUD stands for **Create, Read, Update, Delete** — the four basic database operations. Every feature in the system is built from these four actions.

For example, for Users:
- **Create** → `add_user.php` inserts a new row into the `users` table
- **Read** → `admin_dash.php` selects and displays all users
- **Update** → `edit_user.php` updates an existing row
- **Delete** → `delete_user.php` removes a row

The same pattern applies to subjects, batches, payments, results, announcements, and enquiries.

### How a Page Works (Step by Step)
```
1. Browser requests a URL (e.g. /frontend/pages/dashboard.php)
2. Apache receives the request and passes it to PHP
3. PHP starts the session to check who is logged in
4. PHP connects to MySQL using db.php
5. PHP runs SQL queries to fetch the data needed
6. PHP builds the HTML page, inserting data values into it
7. The finished HTML is sent back to the browser
8. The browser displays the page to the user
```

---

## Project Structure

```
activate_academy/
│
├── index.php                        Home page (public)
├── README.md                        This file
│
├── frontend/
│   ├── assets/
│   │   ├── css/style.css            All styling for the entire site
│   │   ├── header.php               Navigation bar included on every page
│   │   └── footer.php               Footer included on every page
│   │
│   └── pages/
│       ├── login.php                Login form
│       ├── logout.php               Clears session, redirects to home
│       ├── dashboard.php            Loads the correct dashboard by role
│       ├── about.php                About us (public)
│       ├── courses.php              Courses and batches (public)
│       ├── enquiry.php              Public enquiry form
│       ├── feedback.php             Public feedback form
│       ├── print_receipt.php        Printable payment receipt
│       ├── report_financial.php     Financial report
│       ├── report_academic.php      Academic report
│       │
│       └── dashboards/
│           ├── admin_dash.php
│           ├── manager_dash.php
│           ├── director_dash.php
│           ├── lecturer_dash.php
│           ├── receptionist_dash.php
│           ├── student_dash.php
│           └── parent_dash.php
│
├── backend/
│   ├── config/
│   │   ├── db.php                   Database connection (4 lines)
│   │   └── helpers.php              Shared functions used everywhere
│   │
│   └── crud/
│       ├── users/                   add, edit, delete
│       ├── subjects/                add, edit, delete
│       ├── batches/                 add, edit, delete
│       ├── payments/                approve, update, delete
│       ├── announcements/           add, edit, delete
│       ├── enquiries/               edit, delete
│       ├── results/                 edit, delete
│       └── materials/               upload, delete
│
├── uploads/
│   ├── receipts/                    Payment receipt images
│   └── materials/                   Study material files
│
└── database/
    └── database.sql                 All tables + sample data
```

---

## Database Tables

The database is named `activate_academy_db` and contains 14 tables.

| Table | Purpose |
|-------|---------|
| `users` | Stores every login account — admin, lecturer, student, parent, etc. One row per person |
| `subjects` | The subjects offered — name, code, level (O/L, A/L), and monthly fee |
| `batches` | A class — one subject, one lecturer, a schedule, a room, and a capacity limit |
| `enrollments` | Records which student is enrolled in which batch |
| `attendance` | One row per student per class day — present, absent, or late |
| `results` | Exam marks and grades uploaded by lecturers |
| `payments` | Monthly fee payment records — pending, approved, or rejected |
| `announcements` | Notice board posts visible on dashboards and the home page |
| `enquiries` | Walk-in or online enquiries from prospective students |
| `feedback` | Public feedback form submissions with ratings |
| `performance_points` | Points awarded to students by lecturers for good work |
| `parent_student` | Links a parent account to their child's student account |
| `study_materials` | Files uploaded by lecturers for students to download |
| `class_sessions` | Online class links (Zoom, Google Meet, etc.) shared by lecturers |

---

## User Roles

### Admin
Full control of everything. Can manage all users, subjects, batches, payments, announcements, enquiries, and parent–student links. Can generate financial and academic reports.

### Manager
Monitoring role. Can view all students, lecturers, batches, attendance records, performance data, and revenue. Cannot change or delete data. Can generate reports.

### Director
Read-only overview. Sees key stats, the top student leaderboard, a batch summary, and can generate reports. Designed for senior oversight.

### Lecturer
Teaching role. Can mark attendance for their own batches, upload exam results, award performance points to students, share online class links, upload study materials, and post announcements.

### Receptionist
Front desk role. Can register new students, create batches, record incoming enquiries, enroll students into batches, record fee payments and generate receipts, link parents to students, and manage payment status.

### Student
Can view their own enrolled batches, exam results, attendance summary, performance points, and payment history. Can submit payments and download study materials uploaded by their lecturers.

### Parent
Can view their linked child's batches, exam results, attendance, performance points, and payment history. Can also read announcements.

---

## Key Features

**Authentication and Sessions** — Secure login with role-based access. Each role sees a different dashboard.

**Student Registration and Enrolment** — Receptionist registers students, creates batches, and assigns students to batches.

**Attendance Marking** — Lecturers mark attendance batch by batch. Students and parents can see the summary.

**Exam Results** — Lecturers upload marks and grades. Students see their own results. Reports show pass rates and top performers.

**Payment Management** — Students or receptionists record payments. Admin approves or rejects them. Printable receipts are generated automatically.

**Performance Points** — Lecturers award points to students. A leaderboard in the director dashboard shows top students.

**Study Materials** — Lecturers upload PDF, Word, or PowerPoint files. Students in that batch can download them.

**Online Class Links** — Lecturers paste Zoom or Meet links. Students in that batch see a Join button. Today's classes are highlighted in green.

**Announcements** — Admins and lecturers post notices targeted by audience — all users, students only, parents only, or staff only.

**Enquiry Management** — Public enquiry form on the website. Receptionist tracks status from pending to contacted to enrolled.

**Reports** — Financial report shows all payments with filters by month and status, revenue by subject, and revenue by month. Academic report shows grade distribution, pass rate, top students, and attendance summaries.

**Parent–Student Linking** — Admin or receptionist can link any parent account to any student account. Parents then see their child's full profile.

---

## Setup Instructions

### Requirements
- XAMPP (or any Apache + PHP + MySQL stack)
- PHP 8 or higher
- A modern web browser

### Step 1 — Place the project
Copy the `activate_academy` folder into your XAMPP `htdocs` folder:
```
Windows:   C:\xampp\htdocs\activate_academy\
Mac/Linux: /Applications/XAMPP/htdocs/activate_academy/
```

### Step 2 — Start XAMPP
Open XAMPP Control Panel and start both **Apache** and **MySQL**.

### Step 3 — Import the database
1. Open your browser and go to `http://localhost/phpmyadmin`
2. Click the **Import** tab at the very top (not inside any existing database)
3. Click **Choose File** and select `database/database.sql`
4. Click **Go**

This creates the database `activate_academy_db` with all 14 tables and sample data automatically.

### Step 4 — Check upload folders
Make sure these two folders exist inside the project and are writable:
```
activate_academy/uploads/receipts/
activate_academy/uploads/materials/
```
They are included in the project already. If your server blocks writes, right-click and set permissions to allow writing.

### Step 5 — Open in browser
```
http://localhost/activate_academy/
```

---

## Login Accounts (Demo)

| Username | Password | Role |
|----------|----------|------|
| admin | admin123 | Admin |
| manager | manager123 | Manager |
| director | director123 | Director |
| lec_math | math123 | Math Lecturer |
| lec_eng | eng123 | English Lecturer |
| receptionist | recep123 | Receptionist |
| student1 | kabir123 | Student |
| student2 | ishfaq123 | Student |
| parent1 | parent123 | Parent |

---

## How the Code is Organised

### db.php
Four lines. Opens a connection to MySQL. Every other PHP file includes this first using `require`.

### helpers.php
Shared functions used across many files:
- `count_rows()` — counts rows in any table with an optional condition
- `get_one_row()` — runs a query and returns the first result row
- `clean()` — escapes user input before using it in a SQL query
- `require_login()` — redirects to login if no session exists

### header.php and footer.php
Included at the top and bottom of every page. The header outputs the HTML head tag and navigation bar. The footer closes the page. Each page sets `$page_title`, `$css_path`, `$root_path`, and `$active_page` before including them.

### CRUD Files
Each file in `backend/crud/` does one thing only. It validates the input, runs one SQL query, then redirects back to the dashboard with a success message. They never output HTML directly — they just process and redirect.

### Dashboard Files
Each dashboard file is included inside `dashboard.php`. It can use `$conn` (database connection), `$user_id`, `$full_name`, and `$role` because those are already set by `dashboard.php` before the include happens.

---

*Activate Academy — PHP and MySQL Institute Management System*

