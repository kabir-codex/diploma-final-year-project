<?php
// ============================================================
// update_payment.php
// Purpose: Update payment status (pending / approved / rejected)
// Used by admin dashboard dropdown (inline update)
// ============================================================


// ------------------------------------------------------------
// START SESSION
// ------------------------------------------------------------
// Starts or resumes session.
// Provides access to:
// $_SESSION['user_id'], $_SESSION['role']
session_start();


// ------------------------------------------------------------
// DATABASE CONNECTION
// ------------------------------------------------------------
// Include database configuration file.
// Provides $conn (MySQL connection object)
require '../../config/db.php';


// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
// Only these roles can update payment status:
// - admin
// - manager
//
// If not authorized, redirect to login page.
if (
    !isset($_SESSION['user_id']) ||
    !in_array(
        $_SESSION['role'],
        ['admin', 'manager']
    )
) {
    header("Location: ../../../frontend/pages/login.php");
    exit();
}


// ------------------------------------------------------------
// GET FORM DATA (POST)
// ------------------------------------------------------------

// Payment ID from form input
$pay_id = (int)$_POST['pay_id'];

// New status selected from dropdown
$new_status = mysqli_real_escape_string(
    $conn,
    $_POST['new_status']
);


// ------------------------------------------------------------
// VALID STATUS VALUES
// ------------------------------------------------------------
// Only these statuses are allowed in database.
$allowed = [
    'pending',
    'approved',
    'rejected'
];


// ------------------------------------------------------------
// VALIDATION CHECK
// ------------------------------------------------------------
// Ensure ID is valid AND status is allowed
if (
    $pay_id > 0 &&
    in_array($new_status, $allowed)
) {

    // --------------------------------------------------------
    // UPDATE DATABASE
    // --------------------------------------------------------
    // Also records who changed the status (audit trail).
    $approver_id = (int)$_SESSION['user_id'];
    mysqli_query(
        $conn,
        "UPDATE payment
         SET status='$new_status', approved_by_receptionist_id=$approver_id
         WHERE paymentID=$pay_id"
    );


    // Success message
    $msg =
        "Payment status updated to " .
        ucfirst($new_status);

} else {

    // Error message
    $msg = "Invalid request";
}


// ------------------------------------------------------------
// REDIRECT USER
// ------------------------------------------------------------
// Return to dashboard payments section
header(
    "Location: ../../../frontend/pages/dashboard.php?pay_msg=" .
    urlencode($msg) .
    "#payments"
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

$_POST['pay_id']
- Payment ID from form submission

$_POST['new_status']
- New selected status from dropdown

$pay_id
- Clean integer payment ID

$new_status
- Sanitized status string

$allowed
- Array of valid status values

$msg
- Feedback message for UI

============================================================
FUNCTION EXPLANATIONS
============================================================

session_start()
- Starts session

require()
- Includes DB connection

isset()
- Checks variable existence

in_array()
- Validates allowed values

mysqli_real_escape_string()
- Prevents SQL injection

mysqli_query()
- Executes SQL query

ucfirst()
- Capitalizes first letter

urlencode()
- Encodes URL-safe message

header()
- Redirects browser

exit()
- Stops execution

(int)
- Converts value to integer

============================================================
EXAMPLE REQUEST
============================================================

POST:
pay_id = 5
new_status = approved

SQL:
UPDATE payment
SET status='approved'
WHERE paymentID=5;

============================================================
PROGRAM FLOW
============================================================

1. Start Session
2. Connect Database
3. Check Admin/Manager Role
4. Read POST Data
5. Validate Input
6. Update Payment Status
7. Create Message
8. Redirect to Dashboard

============================================================
SECURITY IMPROVEMENTS
============================================================

1. Use prepared statements

2. Prevent updating already finalized payments

3. Add audit log:
   - who changed status
   - when it was changed

4. Validate payment exists before update

5. Restrict transitions (e.g. approved → pending blocked)

============================================================ */