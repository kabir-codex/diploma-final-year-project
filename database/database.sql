-- ============================================================
--  activate_academy_db.sql  –  Full Database for Activate Academy
--
--  WHAT THIS FILE DOES:
--  Creates all the database tables and inserts sample data.
--
--  HOW TO USE (SIMPLE – only 3 steps):
--  1. Open phpMyAdmin: http://localhost/phpmyadmin
--  2. Click the "Import" tab at the TOP (do NOT click into any database first)
--  3. Click "Choose File", select this file, scroll down and click "Go"
--
--  The database and all tables are created automatically.
--  You do NOT need to create the database manually first.
--
--  TABLES IN THIS DATABASE:
--  1. users              – All login accounts (every role)
--  2. subjects           – Subjects/courses offered
--  3. batches            – Classes (linked to subjects + lecturers)
--  4. enrollments        – Which students are in which batch
--  5. attendance         – Daily attendance records
--  6. results            – Exam marks and grades
--  7. payments           – Fee payment records
--  8. announcements      – Notice board posts
--  9. enquiries          – Walk-in/phone enquiry records
-- 10. feedback           – Public feedback submissions
-- 11. performance_points – Points awarded to students
-- 12. parent_student     – Links each parent to their child
-- ============================================================

-- Step 1: Create the database if it doesn't exist yet
-- This means you do NOT need to create it manually in phpMyAdmin first.
-- Just go to phpMyAdmin, click Import from the HOME screen (not inside any database),
-- select this file and click Go – everything will be created automatically.
CREATE DATABASE IF NOT EXISTS activate_academy_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_general_ci;

-- Step 2: Tell MySQL to use this database for all the tables below
USE activate_academy_db;

-- ============================================================
--  TABLE 1: users
--  Stores every person who can log in to the system.
--  The 'role' column decides which dashboard they see.
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,  -- Unique number for each user
    username   VARCHAR(60)  NOT NULL UNIQUE,    -- Login username (must be unique)
    password   VARCHAR(255) NOT NULL,           -- Hashed with password_hash() (see seed note below)
    full_name  VARCHAR(120) NOT NULL,           -- Display name
    email      VARCHAR(120),
    phone      VARCHAR(30),
    -- role can only be one of these 7 values:
    role       ENUM('admin','manager','director','lecturer','receptionist','student','parent') NOT NULL,
    status     ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
--  TABLE 2: subjects
--  Subjects/courses offered at the institute.
-- ============================================================
CREATE TABLE IF NOT EXISTS subjects (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    code        VARCHAR(30)  NOT NULL UNIQUE,   -- e.g. MATH-101
    name        VARCHAR(120) NOT NULL,          -- e.g. Mathematics
    level       VARCHAR(50),                    -- e.g. O/L, A/L
    fee         DECIMAL(10,2) DEFAULT 0,        -- Monthly fee in LKR
    description TEXT,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
--  TABLE 3: batches
--  A batch is a specific class run by a lecturer for a subject.
--  e.g. "Math Batch A – Saturdays 9AM" taught by Mr. Silva
-- ============================================================
CREATE TABLE IF NOT EXISTS batches (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    batch_name  VARCHAR(120) NOT NULL,
    subject_id  INT NOT NULL,              -- Links to subjects.id
    lecturer_id INT NOT NULL,              -- Links to users.id (lecturer)
    schedule    VARCHAR(120),              -- e.g. "Sat 9:00 AM – 12:00 PM"
    room        VARCHAR(60),               -- e.g. "Room 101"
    capacity    INT DEFAULT 30,
    status      ENUM('upcoming','active','completed') DEFAULT 'active',
    start_date  DATE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    -- Foreign keys ensure referenced rows exist
    FOREIGN KEY (subject_id)  REFERENCES subjects(id) ON DELETE CASCADE,
    FOREIGN KEY (lecturer_id) REFERENCES users(id)    ON DELETE CASCADE
);

-- ============================================================
--  TABLE 4: enrollments
--  Records which students are enrolled in which batches.
-- ============================================================
CREATE TABLE IF NOT EXISTS enrollments (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    student_id   INT NOT NULL,              -- Links to users.id (student)
    batch_id     INT NOT NULL,              -- Links to batches.id
    enroll_date  DATE,
    status       ENUM('active','dropped','completed') DEFAULT 'active',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (batch_id)   REFERENCES batches(id)  ON DELETE CASCADE
);

-- ============================================================
--  TABLE 5: attendance
--  Daily attendance record: one row per student per class day.
-- ============================================================
CREATE TABLE IF NOT EXISTS attendance (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    student_id   INT  NOT NULL,
    batch_id     INT  NOT NULL,
    attend_date  DATE NOT NULL,
    status       ENUM('present','absent','late') DEFAULT 'present',
    marked_by    INT,               -- Which lecturer marked it (users.id)
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (batch_id)   REFERENCES batches(id)  ON DELETE CASCADE
);

-- ============================================================
--  TABLE 6: results
--  Exam marks uploaded by lecturers for their students.
-- ============================================================
CREATE TABLE IF NOT EXISTS results (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    student_id   INT         NOT NULL,
    batch_id     INT         NOT NULL,
    exam_name    VARCHAR(120) NOT NULL,    -- e.g. "Mid-Term Test 1"
    exam_date    DATE,
    marks        INT DEFAULT 0,
    total_marks  INT DEFAULT 100,
    grade        VARCHAR(5),              -- e.g. A+, A, A-, B+, B, B-, C+, C, C-, D+, D, E
    comments     TEXT,
    uploaded_by  INT,                     -- Lecturer who uploaded (users.id)
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (batch_id)   REFERENCES batches(id)  ON DELETE CASCADE
);

-- ============================================================
--  TABLE 7: payments
--  Monthly fee payment records submitted by students.
--  Admin must approve or reject each payment.
-- ============================================================
CREATE TABLE IF NOT EXISTS payments (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    student_id   INT NOT NULL,
    batch_id     INT NOT NULL,
    amount       DECIMAL(10,2) NOT NULL,
    pay_month    VARCHAR(7) NOT NULL,       -- Format: "2025-05" (YYYY-MM)
    receipt_no   VARCHAR(60),
    receipt_file VARCHAR(255),             -- Uploaded file name stored here
    pay_date     DATE,
    status       ENUM('pending','approved','rejected') DEFAULT 'pending',
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id)    ON DELETE CASCADE,
    FOREIGN KEY (batch_id)   REFERENCES batches(id)  ON DELETE CASCADE
);

-- ============================================================
--  TABLE 8: announcements
--  Posts made by admin visible on dashboards and home page.
-- ============================================================
CREATE TABLE IF NOT EXISTS announcements (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    title      VARCHAR(200) NOT NULL,
    message    TEXT         NOT NULL,
    audience   ENUM('all','students','parents','staff') DEFAULT 'all',
    post_date  DATE,
    posted_by  INT,                      -- User who wrote it (users.id)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(id) ON DELETE SET NULL
);

-- ============================================================
--  TABLE 9: enquiries
--  Walk-in or phone enquiry records logged by receptionists.
-- ============================================================
CREATE TABLE IF NOT EXISTS enquiries (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    phone      VARCHAR(30)  NOT NULL,
    email      VARCHAR(120),
    interest   VARCHAR(120),            -- Which subject they asked about
    notes      TEXT,
    status     ENUM('pending','contacted','enrolled','dropped') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
--  TABLE 10: feedback
--  Feedback submitted by students or parents on the website.
-- ============================================================
CREATE TABLE IF NOT EXISTS feedback (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(120) NOT NULL,
    role       VARCHAR(50),             -- e.g. Student, Parent
    subject    VARCHAR(120),
    email      VARCHAR(120),
    rating     INT DEFAULT 5,           -- 1 to 5 stars
    comments   TEXT,
    recommend  ENUM('yes','maybe','no') DEFAULT 'yes',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
--  TABLE 11: performance_points
--  Points awarded to students by lecturers for good work.
-- ============================================================
CREATE TABLE IF NOT EXISTS performance_points (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    student_id  INT NOT NULL,
    awarded_by  INT NOT NULL,           -- Lecturer who gave the points
    batch_id    INT,
    points      INT NOT NULL DEFAULT 0,
    reason      VARCHAR(255),
    award_date  DATE,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (awarded_by) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
--  TABLE 12: parent_student
--  Links each parent account to their child's student account.
--  One parent can have multiple children (one row per child).
-- ============================================================
CREATE TABLE IF NOT EXISTS parent_student (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    parent_id  INT NOT NULL,            -- Links to users.id (role=parent)
    student_id INT NOT NULL,            -- Links to users.id (role=student)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
);


-- ============================================================
--  SAMPLE DATA
--  This inserts demo records so you can test every feature.
-- ============================================================


-- ---- USERS ----
-- NOTE: Passwords below are seeded as plain text on purpose for easy setup/demo.
-- login.php now hashes passwords with PHP's password_hash() / password_verify().
-- The first time each demo account logs in, login.php detects the plain-text
-- password, verifies it, and automatically rewrites it as a bcrypt hash in the
-- database. No manual migration step is required.
INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES
-- Admin
('admin',        'admin123',    'Admin User',           'admin@activateacademy.lk',        '0771000001', 'admin',        'active'),
-- Manager
('manager',      'manager123',  'Roshini Perera',       'manager@activateacademy.lk',      '0771000002', 'manager',      'active'),
-- Director
('director',     'director123', 'Dr. Nalaka Fernando',  'director@activateacademy.lk',     '0771000003', 'director',     'active'),
-- Lecturers
('lec_math',     'math123',     'Mr. Saman Jayawardena','math@activateacademy.lk',          '0771000004', 'lecturer',     'active'),
('lec_eng',      'eng123',      'Ms. Dilnoza Hashimova','english@activateacademy.lk',       '0771000005', 'lecturer',     'active'),
('lec_sci',      'sci123',      'Mr. Ruwan Bandara',    'science@activateacademy.lk',       '0771000006', 'lecturer',     'active'),
-- Receptionist
('receptionist', 'recep123',    'Nimesha Wickrama',     'reception@activateacademy.lk',    '0771000007', 'receptionist', 'active'),
-- Students
('student1',     'kabir123',    'Kabir Mohamed',        'kabir@student.lk',           '0771000008', 'student',      'active'),
('student2',     'ishfaq123',   'Ishfaq Rahaman',       'ishfaq@student.lk',          '0771000009', 'student',      'active'),
('student3',     'amaya123',    'Amaya Dissanayake',    'amaya@student.lk',           '0771000010', 'student',      'active'),
('student4',     'nuwan123',    'Nuwan Rathnayake',     'nuwan@student.lk',           '0771000011', 'student',      'active'),
('student5',     'hasini123',   'Hasini Gunasekara',    'hasini@student.lk',          '0771000012', 'student',      'active'),
-- Parents
('parent1',      'parent123',   'Mr. Mohamed Farhan',   'parent1@email.lk',           '0771000013', 'parent',       'active'),
('parent2',      'parent456',   'Mrs. Rathnayake',      'parent2@email.lk',           '0771000014', 'parent',       'active');


-- ---- SUBJECTS ----
INSERT INTO subjects (code, name, level, fee, description) VALUES
('MATH-OL', 'Mathematics',        'O/L',         2500.00, 'Comprehensive O/L Mathematics covering all modules.'),
('ENG-OL',  'English Language',   'O/L',         2200.00, 'Grammar, comprehension and essay writing for O/L students.'),
('SCI-OL',  'Science',            'O/L',         2500.00, 'Physics, Chemistry and Biology combined for O/L.'),
('MATH-AL', 'Combined Maths',     'A/L',         3500.00, 'Pure and Applied Mathematics for A/L students.'),
('PHY-AL',  'Physics',            'A/L',         3200.00, 'Mechanics, Electricity, Waves and Modern Physics.'),
('ICT-FD',  'ICT Fundamentals',   'Foundation',  1800.00, 'Introduction to computers, MS Office and basic programming.');


-- ---- BATCHES ----
-- (subject_id and lecturer_id must match the IDs inserted above)
-- subjects inserted as IDs 1–6, lecturers as IDs 4 (math), 5 (eng), 6 (sci)
INSERT INTO batches (batch_name, subject_id, lecturer_id, schedule, room, capacity, status, start_date) VALUES
('Math O/L Batch A',   1, 4, 'Sat & Sun  9:00 AM – 12:00 PM', 'Room 101', 25, 'active',    '2025-01-06'),
('Math O/L Batch B',   1, 4, 'Sat & Sun  1:00 PM –  4:00 PM', 'Room 101', 25, 'active',    '2025-01-06'),
('English O/L Batch',  2, 5, 'Sat        9:00 AM – 12:00 PM', 'Room 102', 20, 'active',    '2025-01-11'),
('Science O/L Batch',  3, 6, 'Sun        9:00 AM – 12:00 PM', 'Room 103', 22, 'active',    '2025-01-12'),
('Combined Maths A/L', 4, 4, 'Fri        4:00 PM –  7:00 PM', 'Room 201', 18, 'active',    '2025-02-07'),
('Physics A/L Batch',  5, 6, 'Fri        4:00 PM –  7:00 PM', 'Room 202', 18, 'upcoming',  '2025-06-06'),
('ICT Foundation',     6, 5, 'Wed        4:00 PM –  6:00 PM', 'Lab 01',   20, 'active',    '2025-01-15');


-- ---- ENROLLMENTS ----
-- student IDs: Kabir=8, Ishfaq=9, Amaya=10, Nuwan=11, Hasini=12
-- batch IDs: 1=Math-A, 2=Math-B, 3=English, 4=Science, 5=CombMaths, 7=ICT
INSERT INTO enrollments (student_id, batch_id, enroll_date, status) VALUES
(8,  1, '2025-01-06', 'active'),   -- Kabir   → Math A
(8,  3, '2025-01-11', 'active'),   -- Kabir   → English
(9,  1, '2025-01-06', 'active'),   -- Ishfaq  → Math A
(9,  4, '2025-01-12', 'active'),   -- Ishfaq  → Science
(10, 2, '2025-01-06', 'active'),   -- Amaya   → Math B
(10, 3, '2025-01-11', 'active'),   -- Amaya   → English
(10, 7, '2025-01-15', 'active'),   -- Amaya   → ICT
(11, 2, '2025-01-06', 'active'),   -- Nuwan   → Math B
(11, 4, '2025-01-12', 'active'),   -- Nuwan   → Science
(12, 5, '2025-02-07', 'active'),   -- Hasini  → Combined Maths
(12, 7, '2025-01-15', 'active');   -- Hasini  → ICT


-- ---- ATTENDANCE ----
-- lecturer IDs: 4 = Math lecturer, 5 = Eng lecturer, 6 = Sci lecturer
INSERT INTO attendance (student_id, batch_id, attend_date, status, marked_by) VALUES
-- Kabir – Math A (batch 1)
(8, 1, '2025-01-11', 'present', 4),
(8, 1, '2025-01-18', 'present', 4),
(8, 1, '2025-01-25', 'late',    4),
(8, 1, '2025-02-01', 'present', 4),
(8, 1, '2025-02-08', 'absent',  4),
(8, 1, '2025-02-15', 'present', 4),
-- Kabir – English (batch 3)
(8, 3, '2025-01-11', 'present', 5),
(8, 3, '2025-01-18', 'present', 5),
(8, 3, '2025-01-25', 'present', 5),
(8, 3, '2025-02-01', 'absent',  5),
-- Ishfaq – Math A (batch 1)
(9, 1, '2025-01-11', 'present', 4),
(9, 1, '2025-01-18', 'absent',  4),
(9, 1, '2025-01-25', 'present', 4),
(9, 1, '2025-02-01', 'present', 4),
(9, 1, '2025-02-08', 'late',    4),
-- Ishfaq – Science (batch 4)
(9, 4, '2025-01-12', 'present', 6),
(9, 4, '2025-01-19', 'present', 6),
(9, 4, '2025-01-26', 'present', 6),
-- Amaya – Math B (batch 2)
(10, 2, '2025-01-11', 'present', 4),
(10, 2, '2025-01-18', 'present', 4),
(10, 2, '2025-01-25', 'present', 4),
(10, 2, '2025-02-01', 'present', 4),
(10, 2, '2025-02-08', 'present', 4),
-- Amaya – English (batch 3)
(10, 3, '2025-01-11', 'present', 5),
(10, 3, '2025-01-18', 'late',    5),
(10, 3, '2025-01-25', 'present', 5),
-- Nuwan – Math B (batch 2)
(11, 2, '2025-01-11', 'absent',  4),
(11, 2, '2025-01-18', 'present', 4),
(11, 2, '2025-01-25', 'present', 4),
(11, 2, '2025-02-01', 'absent',  4),
(11, 2, '2025-02-08', 'present', 4),
-- Hasini – Combined Maths (batch 5)
(12, 5, '2025-02-08', 'present', 4),
(12, 5, '2025-02-15', 'present', 4),
(12, 5, '2025-02-22', 'late',    4),
-- Hasini – ICT (batch 7)
(12, 7, '2025-01-15', 'present', 5),
(12, 7, '2025-01-22', 'present', 5),
(12, 7, '2025-01-29', 'present', 5);


-- ---- RESULTS ----
-- uploaded_by: 4=Math, 5=Eng/ICT, 6=Science
INSERT INTO results (student_id, batch_id, exam_name, exam_date, marks, total_marks, grade, comments, uploaded_by) VALUES
-- Kabir – Math A exams
(8, 1, 'Monthly Test 1', '2025-02-01', 78, 100, 'A', 'Good effort!', 4),
(8, 1, 'Monthly Test 2', '2025-03-01', 85, 100, 'A+', 'Excellent work!', 4),
(8, 1, 'Mid-Term Exam', '2025-04-15', 80, 100, 'A', 'Very good performance.', 4),
-- Kabir – English
(8, 3, 'Grammar Test', '2025-02-15', 72, 100, 'A', 'Needs more practice.', 5),
(8, 3, 'Essay Test', '2025-03-20', 68, 100, 'A-', 'Work on writing style.', 5),
-- Ishfaq – Math A
(9, 1, 'Monthly Test 1', '2025-02-01', 65, 100, 'A-', 'Revise algebra.', 4),
(9, 1, 'Monthly Test 2', '2025-03-01', 70, 100, 'A', 'Improving!', 4),
(9, 1, 'Mid-Term Exam', '2025-04-15', 74, 100, 'A', 'Good progress.', 4),
-- Ishfaq – Science
(9, 4, 'Theory Test 1', '2025-02-20', 82, 100, 'A', 'Strong in Physics.', 6),
(9, 4, 'Theory Test 2', '2025-03-25', 78, 100, 'A', 'Good overall.', 6),
-- Amaya – Math B
(10, 2, 'Monthly Test 1', '2025-02-01', 91, 100, 'A+', 'Outstanding!', 4),
(10, 2, 'Monthly Test 2', '2025-03-01', 88, 100, 'A+', 'Keep it up!', 4),
(10, 2, 'Mid-Term Exam', '2025-04-15', 93, 100, 'A+', 'Top of the class!', 4),
-- Amaya – English
(10, 3, 'Grammar Test', '2025-02-15', 80, 100, 'A', 'Very good!', 5),
-- Nuwan – Math B
(11, 2, 'Monthly Test 1', '2025-02-01', 55, 100, 'B', 'Please revise Chapter 3.', 4),
(11, 2, 'Monthly Test 2', '2025-03-01', 62, 100, 'B+', 'Slight improvement.', 4),
(11, 2, 'Mid-Term Exam', '2025-04-15', 70, 100, 'A', 'Good progress!', 4),
-- Hasini – Combined Maths
(12, 5, 'Integration Test', '2025-03-10', 88, 100, 'A+', 'Excellent!', 4),
(12, 5, 'Vectors Test', '2025-04-10', 84, 100, 'A', 'Very good.', 4),
-- Hasini – ICT
(12, 7, 'MS Office Test',     '2025-02-20', 95, 100, 'A',  'Perfect score!',          5);


-- ---- PAYMENTS ----
-- student IDs: 8=Kabir, 9=Ishfaq, 10=Amaya, 11=Nuwan, 12=Hasini
INSERT INTO payments (student_id, batch_id, amount, pay_month, receipt_no, pay_date, status) VALUES
-- Kabir – Math A (batch 1)
(8, 1,  2500.00, '2025-01', 'RCP-001', '2025-01-08', 'approved'),
(8, 1,  2500.00, '2025-02', 'RCP-005', '2025-02-07', 'approved'),
(8, 1,  2500.00, '2025-03', 'RCP-010', '2025-03-06', 'approved'),
(8, 1,  2500.00, '2025-04', 'RCP-015', '2025-04-07', 'pending'),
-- Kabir – English (batch 3)
(8, 3,  2200.00, '2025-01', 'RCP-002', '2025-01-12', 'approved'),
(8, 3,  2200.00, '2025-02', 'RCP-006', '2025-02-11', 'approved'),
(8, 3,  2200.00, '2025-03', 'RCP-011', '2025-03-10', 'pending'),
-- Ishfaq – Math A (batch 1)
(9, 1,  2500.00, '2025-01', 'RCP-003', '2025-01-09', 'approved'),
(9, 1,  2500.00, '2025-02', 'RCP-007', '2025-02-08', 'approved'),
(9, 1,  2500.00, '2025-03', 'RCP-012', '2025-03-07', 'rejected'),
(9, 1,  2500.00, '2025-04', 'RCP-016', '2025-04-08', 'pending'),
-- Amaya – Math B (batch 2)
(10, 2, 2500.00, '2025-01', 'RCP-004', '2025-01-10', 'approved'),
(10, 2, 2500.00, '2025-02', 'RCP-008', '2025-02-10', 'approved'),
(10, 2, 2500.00, '2025-03', 'RCP-013', '2025-03-08', 'approved'),
(10, 2, 2500.00, '2025-04', 'RCP-017', '2025-04-09', 'approved'),
-- Nuwan – Math B (batch 2)
(11, 2, 2500.00, '2025-01', 'RCP-018', '2025-01-11', 'approved'),
(11, 2, 2500.00, '2025-02', 'RCP-019', '2025-02-12', 'approved'),
(11, 2, 2500.00, '2025-03', 'RCP-020', '2025-03-11', 'pending'),
-- Hasini – Combined Maths (batch 5)
(12, 5, 3500.00, '2025-02', 'RCP-021', '2025-02-10', 'approved'),
(12, 5, 3500.00, '2025-03', 'RCP-022', '2025-03-09', 'approved'),
(12, 5, 3500.00, '2025-04', 'RCP-023', '2025-04-10', 'approved');


-- ---- ANNOUNCEMENTS ----
-- posted_by ID 1 = admin
INSERT INTO announcements (title, message, audience, post_date, posted_by) VALUES
('Welcome to 2025 Academic Year!',
 'Dear students and parents, welcome to the 2025 academic year at Activate Academy. We look forward to another year of academic excellence and personal growth.',
 'all', '2025-01-03', 1),

('Mid-Term Exam Schedule Released',
 'The mid-term exam schedule for all batches is now available. Please check with your batch lecturer for your specific date and time.',
 'students', '2025-04-01', 1),

('Payment Reminder – March 2025',
 'Kindly ensure all March 2025 fees are paid before the 15th of the month. Students with overdue fees may not be permitted to sit exams.',
 'parents', '2025-03-05', 1),

('New ICT Batch Starting Soon',
 'An advanced ICT batch will begin in June 2025. Seats are limited. Please contact reception to register your interest early.',
 'all', '2025-05-01', 1),

('Institute Closed on Vesak Poya',
 'Please note that the institute will be closed on the Vesak Full Moon Poya Day. No classes will be held on that day.',
 'all', '2025-05-12', 1);


-- ---- ENQUIRIES ----
INSERT INTO enquiries (name, phone, email, interest, notes, status) VALUES
('Priya Nandana',    '0712345678', 'priya@email.lk',    'Mathematics',      'Mother enquired about O/L batch. Very interested.', 'pending'),
('Ruwan Kumara',     '0776543210', 'ruwan@email.lk',    'Science',          'Student came in person. Wants to join ASAP.',        'contacted'),
('Ayesha Fathima',   '0754321098', 'ayesha@email.lk',   'English Language', 'Phone enquiry. Will call back on Friday.',           'pending'),
('Sandaru Perera',   '0767890123', '',                  'Combined Maths',   'Parent enquired. Son currently at another institute.','contacted'),
('Thisuri Weerasiri','0723456789', 'this@email.lk',     'ICT Fundamentals', 'Walk-in. Enrolled in ICT batch successfully.',       'enrolled');


-- ---- FEEDBACK ----
INSERT INTO feedback (name, role, subject, email, rating, comments, recommend) VALUES
('Kabir Mohamed',     'Student', 'Mathematics',      'kabir@email.lk',  5, 'The mathematics classes are excellent. Mr. Saman explains everything very clearly!',             'yes'),
('Mr. Mohamed Farhan','Parent',  'General / Overall','',                5, 'My son has improved so much this year. Highly recommend Activate Academy to all parents.',            'yes'),
('Amaya Dissanayake', 'Student', 'English Language', 'amaya@email.lk',  4, 'The English classes are very helpful. I have improved my essay writing significantly.',         'yes'),
('Mrs. Rathnayake',   'Parent',  'Mathematics',      '',                4, 'The teachers are dedicated and the management is very professional. A great institute.',         'yes'),
('Ishfaq Rahaman',    'Student', 'Science',          'ishfaq@email.lk', 5, 'Science classes are amazing! Mr. Ruwan makes even the hardest topics easy to understand.',      'yes');


-- ---- PERFORMANCE POINTS ----
-- awarded_by: 4=Math, 5=Eng, 6=Science
INSERT INTO performance_points (student_id, awarded_by, batch_id, points, reason, award_date) VALUES
(8,  4, 1, 15, 'Top marks in Monthly Test 2',       '2025-03-05'),
(8,  5, 3, 10, 'Best essay in the class',           '2025-03-22'),
(9,  6, 4, 12, 'Outstanding Theory Test 1 result',  '2025-02-22'),
(9,  4, 1, 8,  'Good improvement in Test 2',        '2025-03-05'),
(10, 4, 2, 20, 'Top of class – Mid-Term Exam',      '2025-04-18'),
(10, 5, 3, 10, 'Excellent grammar test performance','2025-02-18'),
(10, 4, 2, 15, 'Consistent top performer',          '2025-03-05'),
(11, 4, 2, 5,  'Good improvement shown',            '2025-04-18'),
(12, 4, 5, 18, 'Excellent integration test result', '2025-03-12'),
(12, 5, 7, 20, 'Perfect score in ICT test',         '2025-02-22');


-- ---- PARENT–STUDENT LINKS ----
-- parent1 (ID=13) is linked to student1 Kabir (ID=8)
-- parent2 (ID=14) is linked to student4 Nuwan (ID=11)
INSERT INTO parent_student (parent_id, student_id) VALUES
(13, 8),
(14, 11);


-- ============================================================
--  DONE! All tables and sample data are ready.
--  You can now run Activate Academy on XAMPP.
-- ============================================================

-- ============================================================
-- Study Materials Table (added for Activate Academy)
-- ============================================================
CREATE TABLE IF NOT EXISTS `study_materials` (
  `id`          INT AUTO_INCREMENT PRIMARY KEY,
  `batch_id`    INT           NOT NULL,
  `title`       VARCHAR(255)  NOT NULL,
  `subject`     VARCHAR(255)  NOT NULL,
  `description` TEXT,
  `file_path`   VARCHAR(500)  NOT NULL,
  `uploaded_by` VARCHAR(255)  NOT NULL,
  `created_at`  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`batch_id`) REFERENCES `batches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- If you already created study_materials without batch_id, run this instead:
-- ALTER TABLE `study_materials` ADD COLUMN `batch_id` INT NOT NULL DEFAULT 0 AFTER `id`;


-- ============================================================
--  class_links table (merged from class_links_table.sql)
-- ============================================================
CREATE TABLE IF NOT EXISTS `class_links` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `lecturer_id`  INT          NOT NULL,
  `batch_id`     INT          NOT NULL,
  `title`        VARCHAR(255) NOT NULL,
  `link_url`     TEXT         NOT NULL,
  `class_date`   DATE         NOT NULL,
  `created_at`   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`lecturer_id`) REFERENCES `users`(`id`)   ON DELETE CASCADE,
  FOREIGN KEY (`batch_id`)    REFERENCES `batches`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
