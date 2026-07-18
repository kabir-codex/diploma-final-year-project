<?php
// ============================================================
// approve_payment.php
// Purpose: Approve or reject a payment request
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
// Include database connection file.
// Provides $conn for MySQL queries
require '../../config/db.php';


// ------------------------------------------------------------
// FEATURE DISABLED (ADMIN ONLY): Payment Approval & Management
// ------------------------------------------------------------
// The admin panel's payment management feature is temporarily
// turned off, so admin is blocked here even via a direct URL.
// Receptionist and manager are NOT affected. To re-enable for
// admin: delete this block.
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    header("Location: ../../../frontend/pages/dashboard.php?msg=" . urlencode("This feature is currently disabled."));
    exit();
}


// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
// Only these roles can approve/reject payments:
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
        ['admin','manager','receptionist']
    )
) {
    header("Location: ../../../frontend/pages/login.php");
    exit();
}


// ------------------------------------------------------------
// GET PARAMETERS
// ------------------------------------------------------------

// Payment ID from URL
$id = (int)($_GET['id'] ?? 0);

// Action from URL (approve or reject)
$action = $_GET['action'] ?? '';


// ------------------------------------------------------------
// VALIDATION
// ------------------------------------------------------------
// Only allow valid actions: approve or reject
if (
    $id > 0 &&
    in_array($action, ['approve', 'reject'])
) {

    // --------------------------------------------------------
    // DETERMINE STATUS
    // --------------------------------------------------------
    // Convert action into database status value
    $status = ($action == 'approve')
        ? 'approved'
        : 'rejected';


    // --------------------------------------------------------
    // UPDATE PAYMENT STATUS
    // --------------------------------------------------------
    // Also records who approved/rejected it (audit trail).
    $approver_id = (int)$_SESSION['user_id'];
    mysqli_query(
        $conn,
        "UPDATE payment
         SET status='$status', approved_by_receptionist_id=$approver_id
         WHERE paymentID=$id"
    );
}


// ------------------------------------------------------------
// USER-FRIENDLY MESSAGE
// ------------------------------------------------------------
// Convert action into readable word for UI message
$status_word = ($action == 'approve')
    ? 'Approved'
    : 'Rejected';


// ------------------------------------------------------------
// REDIRECT USER
// ------------------------------------------------------------
// Send user back to dashboard with status message
header(
    "Location: ../../../frontend/pages/dashboard.php?msg=Payment+" .
    $status_word
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
- User role (admin/manager/receptionist)

$_GET['id']
- Payment ID from URL

$_GET['action']
- Action type (approve or reject)

$id
- Clean integer payment ID

$action
- Action string from request

$status
- Database status value (approved/rejected)

$status_word
- Human-readable message text

============================================================
FUNCTION EXPLANATIONS
============================================================

session_start()
- Starts session

require()
- Loads DB connection

isset()
- Checks variable existence

in_array()
- Checks value in array

mysqli_query()
- Executes SQL query

header()
- Redirects browser

exit()
- Stops execution

(int)
- Converts value to integer

============================================================
EXAMPLE URLS
============================================================

Approve Payment:
approve_payment.php?id=5&action=approve

Reject Payment:
approve_payment.php?id=5&action=reject

============================================================
DATABASE QUERY
============================================================

UPDATE payment
SET status='approved'
WHERE paymentID=5;

OR

UPDATE payment
SET status='rejected'
WHERE paymentID=5;

Purpose:
Updates payment status in database.

============================================================
PROGRAM FLOW
============================================================

1. Start Session
2. Connect Database
3. Check User Role
4. Read Payment ID + Action
5. Validate Action
6. Update Payment Status
7. Build Message
8. Redirect Dashboard

============================================================
SECURITY IMPROVEMENTS
============================================================

1. Use prepared statements

2. Validate payment ownership or existence

3. Log approval/rejection actions

4. Prevent repeated updates (idempotency check)

5. Ensure status only allowed values:
   - pending
   - approved
   - rejected

============================================================ */