<?php
// ============================================================
// delete_subject.php
// Purpose: Permanently delete a subject from database
// ============================================================


// ------------------------------------------------------------
// START SESSION
// ------------------------------------------------------------
// Enables session variables access:
// $_SESSION['user_id'], $_SESSION['role']
session_start();


// ------------------------------------------------------------
// DATABASE CONNECTION
// ------------------------------------------------------------
// Provides $conn (MySQL connection object)
require '../../config/db.php';


// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
// Only admin and manager can delete subjects.
//
// If unauthorized → redirect to login page
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'], ['admin','manager'])
) {
    header("Location: ../../../frontend/pages/login.php");
    exit();
}


// ------------------------------------------------------------
// GET SUBJECT ID
// ------------------------------------------------------------
// Retrieve subject ID from URL.
//
// Example:
// delete_subject.php?id=3
$id = (int)($_GET['id'] ?? 0);


// ------------------------------------------------------------
// DELETE SUBJECT
// ------------------------------------------------------------
// Only execute delete if ID is valid
if ($id > 0) {

    mysqli_query(
        $conn,
        "DELETE FROM subjects WHERE id=$id"
    );
}


// ------------------------------------------------------------
// REDIRECT USER
// ------------------------------------------------------------
// After deletion, return to dashboard
header(
    "Location: ../../../frontend/pages/dashboard.php?msg=Subject+deleted"
);


// Stop script execution
exit();



/* ============================================================
VARIABLE EXPLANATIONS
============================================================

$conn
- Database connection object

$_SESSION['user_id']
- Logged-in user ID

$_SESSION['role']
- User role (admin/manager)

$_GET['id']
- Subject ID from URL

$id
- Clean integer subject ID

============================================================
FUNCTION EXPLANATIONS
============================================================

session_start()
- Starts session

require()
- Loads DB connection

mysqli_query()
- Executes SQL query

header()
- Redirects user

exit()
- Stops script

(int)
- Converts string → integer

isset()
- Checks variable existence

in_array()
- Checks allowed roles

============================================================
SQL QUERY
============================================================

DELETE FROM subjects WHERE id=5;

Purpose:
Removes subject permanently from system

============================================================
PROGRAM FLOW
============================================================

1. Start Session
2. Connect Database
3. Check Role (admin/manager)
4. Get Subject ID
5. Validate ID
6. Delete record
7. Redirect dashboard

============================================================
SECURITY IMPROVEMENTS
============================================================

1. Prevent deletion if subject has batches linked

2. Use soft delete instead of permanent delete

3. Add confirmation step before deletion

4. Use prepared statements

5. Log deletion action for audit trail

============================================================ */