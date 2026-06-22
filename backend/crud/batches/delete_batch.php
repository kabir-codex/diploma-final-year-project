<?php
// ============================================================
// delete_batch.php
// Purpose: Delete a batch from the database
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
// Creates database connection variable: $conn
require '../../config/db.php';


// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
// Only users with the following roles can delete batches:
// - admin
// - manager
//
// Conditions checked:
// 1. User must be logged in.
// 2. User role must be admin or manager.
//
// If either condition fails,
// redirect to login page.
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
// GET BATCH ID
// ------------------------------------------------------------
// Retrieve the batch ID from the URL.
//
// Example:
// delete_batch.php?id=5
//
// If no ID is provided,
// use 0 as the default value.
//
// Casting to (int) ensures the value is an integer.
$id = (int)($_GET['id'] ?? 0);


// ------------------------------------------------------------
// DELETE BATCH
// ------------------------------------------------------------
// Only run the DELETE query if ID is greater than 0.
//
// Example:
// DELETE FROM batches WHERE id=5
if ($id > 0) {

    mysqli_query(
        $conn,
        "DELETE FROM batches WHERE id=$id"
    );

}


// ------------------------------------------------------------
// REDIRECT USER
// ------------------------------------------------------------
// After deleting the batch,
// redirect back to dashboard with a success message.
header(
    "Location: ../../../frontend/pages/dashboard.php?msg=Batch+deleted"
);


// Stop script execution.
exit();



/* ============================================================
VARIABLE EXPLANATIONS
============================================================

$conn
- Type: MySQL Connection
- Purpose: Connects PHP to the MySQL database.

$_SESSION['user_id']
- Type: Integer
- Purpose: Stores the logged-in user's ID.

$_SESSION['role']
- Type: String
- Purpose: Stores the logged-in user's role.

$_GET['id']
- Type: String
- Purpose: Contains the batch ID passed through URL.

$id
- Type: Integer
- Purpose: Stores the batch ID to be deleted.

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
- Checks whether a value exists in an array.

header()
- Sends an HTTP header (used for redirects).

exit()
- Stops script execution immediately.

mysqli_query()
- Executes an SQL query.

(int)
- Converts a value into an integer.

============================================================
EXAMPLE URL
============================================================

delete_batch.php?id=12

Result:

$id = 12

SQL Query Executed:

DELETE FROM batches
WHERE id = 12;

============================================================
DATABASE QUERY
============================================================

DELETE FROM batches
WHERE id = $id;

Purpose:
Deletes the batch record whose ID matches $id.

Example:

DELETE FROM batches
WHERE id = 8;

This permanently removes Batch #8
from the batches table.

============================================================
PROGRAM FLOW
============================================================

1. Start Session
        │
        ▼
2. Connect Database
        │
        ▼
3. Check User Login
        │
        ▼
4. Check User Role
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
5. Get Batch ID
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
7. Delete Batch
        │
        ▼
8. Redirect Dashboard
        │
        ▼
       End

============================================================
SECURITY IMPROVEMENTS
============================================================

1. Verify Batch Exists Before Delete

$check = mysqli_query(
    $conn,
    "SELECT id FROM batches WHERE id=$id"
);

if(mysqli_num_rows($check) > 0){
    // delete batch
}

------------------------------------------------------------

2. Use Prepared Statements

$stmt = mysqli_prepare(
    $conn,
    "DELETE FROM batches WHERE id=?"
);

mysqli_stmt_bind_param(
    $stmt,
    "i",
    $id
);

mysqli_stmt_execute($stmt);

------------------------------------------------------------

3. Prevent Deleting Batches With Students

Check whether students are enrolled
before deleting the batch.

------------------------------------------------------------

4. Log Deletion Activity

Store:
- User ID
- Batch ID
- Date/Time

for audit tracking.

============================================================ */
?>