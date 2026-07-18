<?php
// ============================================================
// delete_user.php
// Purpose: Delete a user account (admin only)
// Safety: Prevents admin from deleting their own account
// ============================================================


// ------------------------------------------------------------
// START SESSION
// ------------------------------------------------------------
// Access session variables:
// $_SESSION['user_id'], $_SESSION['role']
session_start();


// ------------------------------------------------------------
// DATABASE CONNECTION
// ------------------------------------------------------------
require '../../config/db.php';


// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
// Only admin can delete users
if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] != 'admin'
) {
    header("Location: ../../../frontend/pages/login.php");
    exit();
}


// ------------------------------------------------------------
// GET USER ID FROM URL
// ------------------------------------------------------------
// Example:
// delete_user.php?id=5
$id = (int)($_GET['id'] ?? 0);


// ------------------------------------------------------------
// SAFETY CHECK: PREVENT SELF-DELETION
// ------------------------------------------------------------
// Admin cannot delete their own account
if ($id == $_SESSION['user_id']) {

    header(
        "Location: ../../../frontend/pages/dashboard.php?msg=Cannot+delete+your+own+account"
    );

    exit();
}


// ------------------------------------------------------------
// DELETE USER
// ------------------------------------------------------------
// Only runs if valid ID is provided.
// NOTE: batches.lecturer_id is ON DELETE RESTRICT, so this query
// will FAIL (return false) if this user is a lecturer still
// assigned to one or more batches. We check the result instead of
// assuming success, so the message shown always matches reality.
$delete_ok = false;
if ($id > 0) {

    $delete_ok = mysqli_query(
        $conn,
        "DELETE FROM users WHERE userID=$id"
    );
}


// ------------------------------------------------------------
// REDIRECT AFTER DELETE
// ------------------------------------------------------------
if ($delete_ok) {
    header(
        "Location: ../../../frontend/pages/dashboard.php?msg=User+deleted"
    );
} else {
    // Blocked, most likely because this lecturer still has batches
    // assigned to them (ON DELETE RESTRICT). Deactivate the account
    // instead, or reassign/remove their batches first.
    header(
        "Location: ../../../frontend/pages/dashboard.php?msg=" .
        urlencode("Cannot delete: this user still has batches or records linked to them. Set their status to Inactive instead, or reassign their batches first.")
    );
}


// Stop script execution
exit();



/* ============================================================
VARIABLE EXPLANATIONS
============================================================

$conn
- Database connection object

$id
- User ID from URL

$_SESSION['user_id']
- Logged-in admin ID

$_SESSION['role']
- User role (must be admin)

============================================================
FUNCTION EXPLANATIONS
============================================================

session_start()
- Starts session handling

require()
- Loads database connection

(int)
- Converts value to integer

header()
- Redirects browser

exit()
- Stops execution

mysqli_query()
- Runs SQL query

isset()
- Checks variable existence

============================================================
SQL QUERY
============================================================

DELETE FROM users WHERE userID=5;

Purpose:
Removes user from system permanently

============================================================
PROGRAM FLOW
============================================================

1. Start session
2. Check admin role
3. Get user ID
4. Prevent self-delete
5. Delete user
6. Redirect dashboard

============================================================
SECURITY IMPROVEMENTS
============================================================

1. Use soft delete instead of permanent delete

2. Prevent deletion if user has active records

3. Add confirmation popup before delete

4. Log all deletions

5. Use prepared statements

============================================================ */