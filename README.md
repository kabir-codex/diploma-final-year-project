# Activate Academy — Student Management System

**Activate Academy** is a web-based Student Management System built for a small to medium tuition/coaching institute. It was developed as a final year academic project using **PHP** and **MySQL**, and covers the day-to-day operations of running a private academy — from enrolling students and running classes, to tracking attendance and exam results, to collecting and approving fee payments.

## About the Project

Managing a tuition institute by hand (paper attendance registers, manual fee tracking, printed exam results) is slow and error-prone. This system brings all of that into one place with a proper login system and a different dashboard for every type of user — so an admin, a lecturer, a student, and a parent all see only what's relevant to them.

The system supports 7 user roles:
- **Admin** — manages users, subjects, batches, announcements, and payments
- **Manager** — views reports and monitors overall academy performance
- **Director** — high-level, read-only overview of the whole institute
- **Lecturer** — takes attendance, uploads exam results, shares study materials and class links
- **Receptionist** — handles walk-in enquiries, registers students, records payments
- **Student** — views their batches, results, attendance, and payment history
- **Parent** — monitors their linked child's academic progress and fee payments

## Key Features

- Secure login system with role-based dashboards
- Student enrollment and batch management
- Daily attendance tracking
- Exam result uploads and academic reports
- Fee payment recording and approval workflow, with printable receipts
- Study material and online class link sharing
- Public enquiry and feedback forms
- Announcements board visible across dashboards

## Technologies Used

- **PHP** — server-side logic and page rendering
- **MySQL** — relational database, accessed via the MySQLi extension
- **HTML5** — page structure
- **CSS3** — styling (custom, no external framework)
- **JavaScript** — client-side form validation and small UI interactions
- **XAMPP** — local development environment (Apache + MySQL + PHP)

## Setup

1. Install [XAMPP](https://www.apachefriends.org/) and start **Apache** and **MySQL**.
2. Copy this project folder into `htdocs/`.
3. Open `http://localhost/phpmyadmin`, go to **Import**, and import `database/database.sql`.
4. Visit `http://localhost/activate_academy/` in your browser.
