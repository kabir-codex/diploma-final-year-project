<?php
// ============================================================
// delete_enquiry.php
// Purpose: Delete an enquiry from the database
// ============================================================


// ------------------------------------------------------------
// START SESSION
// ------------------------------------------------------------
// Start or resume the current session.
// This allows access to session variables such as:
// $_SESSION['user_id']
// $_SESSION['role']
session_start();


// ------------------------------------------------------------
// DATABASE CONNECTION
// ------------------------------------------------------------
// Include database configuration file.
// Creates the database connection variable $conn.
require '../../config/db.php';


// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
// Only the following roles can delete enquiries:
// - admin
// - manager
// - receptionist
//
// Check:
// 1. User is logged in.
// 2. User role is authorized.
//
// If not authorized, redirect to login page.
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
// GET ENQUIRY ID
// ------------------------------------------------------------
// Retrieve enquiry ID from URL.
//
// Example:
// delete_enquiry.php?id=15
//
// If no ID exists, use 0.
//
// Casting to integer helps prevent SQL injection.
$id = (int)($_GET['id'] ?? 0);


// ------------------------------------------------------------
// DELETE ENQUIRY
// ------------------------------------------------------------
// Only delete if ID is greater than 0.
if ($id > 0) {

    mysqli_query(
        $conn,
        "DELETE FROM enquiries WHERE enquiryID=$id"
    );

}


// ------------------------------------------------------------
// REDIRECT USER
// ------------------------------------------------------------
// After deletion, redirect back to dashboard
// with a success message.
header(
    "Location: ../../../frontend/pages/dashboard.php?msg=Enquiry+deleted"
);


// Stop script execution.
exit();



/* ============================================================
VARIABLE EXPLANATIONS
============================================================

$conn
- Type: MySQL Connection
- Purpose: Database connection object.

$_SESSION['user_id']
- Type: Integer
- Purpose: Stores the logged-in user's ID.

$_SESSION['role']
- Type: String
- Purpose: Stores the logged-in user's role.

$_GET['id']
- Type: String
- Purpose: Receives enquiry ID from URL.

$id
- Type: Integer
- Purpose: Stores the enquiry ID to delete.

============================================================
FUNCTION EXPLANATIONS
============================================================

session_start()
- Starts or resumes a PHP session.

require()
- Includes another PHP file.

isset()
- Checks whether a variable exists.

in_array()
- Checks if a value exists inside an array.

header()
- Sends an HTTP header.
- Used here for page redirection.

exit()
- Terminates script execution.

mysqli_query()
- Executes SQL statements.

(int)
- Converts a value into an integer.

============================================================
EXAMPLE URL
============================================================

delete_enquiry.php?id=10

Result:

$id = 10

SQL Executed:

DELETE FROM enquiries
WHERE enquiryID = 10;

============================================================
DATABASE QUERY
============================================================

DELETE FROM enquiries
WHERE enquiryID = $id;

Purpose:
Deletes the enquiry whose ID matches $id.

Example:

DELETE FROM enquiries
WHERE enquiryID = 25;

This permanently removes enquiry #25
from the enquiries table.

============================================================
PROGRAM FLOW
============================================================

1. Start Session
        │
        ▼
2. Connect Database
        │
        ▼
3. Verify User Login
        │
        ▼
4. Verify User Role
        │
   ┌────┴────┐
   │Allowed? │
   └────┬────┘
        │No
        ▼
   Redirect Login
        │
        ▼
       End

        │Yes
        ▼
5. Get Enquiry ID
        │
        ▼
6. Check ID > 0
        │
   ┌────┴────┐
   │Valid ID?│
   └────┬────┘
        │No
        ▼
   Skip Delete

        │Yes
        ▼
7. Delete Enquiry
        │
        ▼
8. Redirect Dashboard
        │
        ▼
       End

============================================================
SECURITY IMPROVEMENTS
============================================================

1. Verify Enquiry Exists Before Delete

$check = mysqli_query(
    $conn,
    "SELECT enquiryID
     FROM enquiries
     WHERE enquiryID=$id"
);

if(mysqli_num_rows($check) > 0){
    // delete enquiry
}

------------------------------------------------------------

2. Use Prepared Statements

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM enquiries WHERE enquiryID=?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

------------------------------------------------------------

3. Log Deletion Activity

Store:
- User ID
- Enquiry ID
- Date/Time

for audit purposes.

------------------------------------------------------------

4. Show Confirmation Before Delete

Example:

Are you sure you want to delete this enquiry?

This prevents accidental deletions.

============================================================ */
?>