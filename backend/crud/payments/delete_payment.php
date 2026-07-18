<?php
// ============================================================
// delete_payment.php
// Purpose: Permanently delete a payment record
//          and remove uploaded receipt file (if exists)
// ============================================================


// ------------------------------------------------------------
// START SESSION
// ------------------------------------------------------------
// Starts or resumes session.
// Gives access to:
// $_SESSION['user_id'], $_SESSION['role']
session_start();


// ------------------------------------------------------------
// DATABASE CONNECTION
// ------------------------------------------------------------
// Includes DB configuration file.
// Provides $conn (MySQL connection object)
require '../../config/db.php';


// ------------------------------------------------------------
// FEATURE DISABLED (ADMIN ONLY): Payment Approval & Management
// ------------------------------------------------------------
// The admin panel's payment management feature is temporarily
// turned off, so admin is blocked here even via a direct URL.
// Receptionist and manager are NOT affected — this same file is
// still used by the receptionist's (separate, still-active)
// Payment History feature. To re-enable for admin: delete this block.
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: ../../../frontend/pages/dashboard.php?msg=" . urlencode("This feature is currently disabled."));
    exit();
}


// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
// Only these roles can delete payments:
// - admin
// - manager
// - receptionist
//
// If user is not logged in OR role not allowed,
// redirect to login page.
if (
    !isset($_SESSION['user_id']) ||
    !in_array(
        $_SESSION['role'],
        ['admin', 'manager', 'receptionist']
    )
) {
    header("Location: ../../../frontend/pages/login.php");
    exit();
}


// ------------------------------------------------------------
// GET PAYMENT ID
// ------------------------------------------------------------
// Retrieve payment ID from URL.
// Example: delete_payment.php?id=5
$id = (int)($_GET['id'] ?? 0);


// ------------------------------------------------------------
// DELETE PROCESS
// ------------------------------------------------------------
// Only proceed if ID is valid (>0)
if ($id > 0) {

    // --------------------------------------------------------
    // FETCH PAYMENT RECORD
    // --------------------------------------------------------
    // Needed to check if receipt file exists
    $pay = mysqli_fetch_assoc(
        mysqli_query(
            $conn,
            "SELECT * FROM payment WHERE paymentID=$id"
        )
    );


    // --------------------------------------------------------
    // CHECK PAYMENT EXISTS
    // --------------------------------------------------------
    if ($pay) {

        // ----------------------------------------------------
        // DELETE RECEIPT FILE (IF EXISTS)
        // ----------------------------------------------------
        if (!empty($pay['receipt_file'])) {

            // Build full file path
            $file_path =
                '../../../uploads/receipts/' .
                $pay['receipt_file'];

            // Check file exists before deleting
            if (file_exists($file_path)) {
                unlink($file_path); // permanently remove file
            }
        }


        // ----------------------------------------------------
        // DELETE DATABASE RECORD
        // ----------------------------------------------------
        mysqli_query(
            $conn,
            "DELETE FROM payment WHERE paymentID=$id"
        );
    }
}


// ------------------------------------------------------------
// ROLE-BASED REDIRECTION
// ------------------------------------------------------------
// Redirect user based on role after deletion
$role = $_SESSION['role'];

if ($role == 'receptionist') {

    // Receptionist dashboard section
    header(
        "Location: ../../../frontend/pages/dashboard.php?msg=Payment+deleted#payment_history"
    );

} else {

    // Admin / Manager dashboard section
    header(
        "Location: ../../../frontend/pages/dashboard.php?pay_msg=Payment+deleted#payments"
    );
}


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
- User role (admin/manager/receptionist)

$_GET['id']
- Payment ID from URL

$id
- Clean integer payment ID

$pay
- Payment record fetched from database

$pay['receipt_file']
- Uploaded receipt filename (if exists)

$file_path
- Full path to receipt file on server

$role
- Current logged-in user role

============================================================
FUNCTION EXPLANATIONS
============================================================

session_start()
- Starts session

require()
- Loads database connection

isset()
- Checks variable existence

in_array()
- Validates role permissions

mysqli_query()
- Executes SQL query

mysqli_fetch_assoc()
- Fetches DB row as array

file_exists()
- Checks file existence

unlink()
- Deletes file permanently

header()
- Redirects user

exit()
- Stops execution

(int)
- Converts value to integer

============================================================
EXAMPLE URL
============================================================

delete_payment.php?id=12

============================================================
DATABASE QUERY
============================================================

SELECT * FROM payment WHERE paymentID=12;

DELETE FROM payment WHERE paymentID=12;

Purpose:
Removes payment record and associated file.

============================================================
PROGRAM FLOW
============================================================

1. Start Session
2. Connect Database
3. Check User Role
4. Get Payment ID
5. Fetch Payment Record
6. If exists:
      ├── Delete receipt file (if any)
      └── Delete database record
7. Check user role again
8. Redirect to correct dashboard section

============================================================
SECURITY IMPROVEMENTS
============================================================

1. Use prepared statements

2. Verify payment ownership or permissions

3. Prevent deleting already approved payments

4. Log deletion actions (audit trail)

5. Use soft delete instead of permanent delete

============================================================ */