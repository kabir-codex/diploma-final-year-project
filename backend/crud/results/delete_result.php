<?php
// ============================================================
// delete_result.php
// Purpose: Delete a student result record from database
// ============================================================


// ------------------------------------------------------------
// START SESSION
// ------------------------------------------------------------
// Start or resume session.
// Gives access to:
// $_SESSION['user_id'], $_SESSION['role']
session_start();


// ------------------------------------------------------------
// DATABASE CONNECTION
// ------------------------------------------------------------
// Include DB connection file.
// Provides $conn (MySQL connection object)
require '../../config/db.php';


// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
// Only lecturers are allowed to delete results.
//
// If not logged in OR not lecturer,
// redirect to login page.
if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] != 'lecturer'
) {
    header("Location: ../../../frontend/pages/login.php");
    exit();
}


// ------------------------------------------------------------
// GET RESULT ID
// ------------------------------------------------------------
// Retrieve result ID from URL.
//
// Example:
// delete_result.php?id=3
$id = (int)($_GET['id'] ?? 0);


// ------------------------------------------------------------
// DELETE RESULT
// ------------------------------------------------------------
// Only delete if ID is valid (>0)
if ($id > 0) {

    mysqli_query(
        $conn,
        "DELETE FROM results WHERE id=$id"
    );
}


// ------------------------------------------------------------
// REDIRECT USER
// ------------------------------------------------------------
// Redirect back to dashboard with success message
header(
    "Location: ../../../frontend/pages/dashboard.php?msg=Result+deleted"
);


// Stop script execution
exit();



/* ============================================================
VARIABLE EXPLANATIONS
============================================================

$conn
- Database connection object

$_SESSION['user_id']
- Logged-in lecturer ID

$_SESSION['role']
- User role (must be lecturer)

$_GET['id']
- Result ID from URL

$id
- Clean integer result ID

============================================================
FUNCTION EXPLANATIONS
============================================================

session_start()
- Starts PHP session

require()
- Includes DB connection

isset()
- Checks variable existence

mysqli_query()
- Executes SQL query

header()
- Redirects user

exit()
- Stops script execution

(int)
- Converts value to integer

============================================================
EXAMPLE URL
============================================================

delete_result.php?id=7

SQL:
DELETE FROM results WHERE id=7;

============================================================
PROGRAM FLOW
============================================================

1. Start Session
2. Connect Database
3. Check Lecturer Role
4. Get Result ID
5. If valid:
      └── Delete record
6. Redirect to dashboard

============================================================
SECURITY IMPROVEMENTS
============================================================

1. Verify lecturer owns result before deleting:
   WHERE id=$id AND lecturer_id=$_SESSION['user_id']

2. Use prepared statements

3. Add confirmation prompt before delete

4. Log deletions for audit trail

5. Prevent accidental mass deletions

============================================================ */