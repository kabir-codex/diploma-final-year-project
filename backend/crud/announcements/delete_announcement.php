<?php
// ============================================================
// delete_announcement.php
// Purpose: Delete an announcement from the database
// ============================================================


// Start or resume the current session.
// This allows access to session variables such as user_id.
session_start();


// Include the database connection file.
// This file should create a MySQL connection object in $conn.
require '../../config/db.php';


// ------------------------------------------------------------
// AUTHENTICATION CHECK
// ------------------------------------------------------------
// Verify that the user is logged in.
//
// If user_id is not found in the session,
// redirect the user to the login page.
if (!isset($_SESSION['user_id'])) {

    header("Location: ../../../frontend/pages/login.php");

    exit();
}


// ------------------------------------------------------------
// GET ANNOUNCEMENT ID
// ------------------------------------------------------------
// Read the 'id' parameter from the URL.
//
// Example:
// delete_announcement.php?id=5
//
// If id does not exist, use 0.
//
// (int) converts the value to an integer
// to help prevent SQL injection.
$id = (int)($_GET['id'] ?? 0);


// ------------------------------------------------------------
// DELETE RECORD
// ------------------------------------------------------------
// Only run the DELETE query if the ID is greater than 0.
//
// Example:
// DELETE FROM announcements WHERE id=5
if ($id > 0) {

    mysqli_query(
        $conn,
        "DELETE FROM announcements WHERE id=$id"
    );
}


// ------------------------------------------------------------
// REDIRECT AFTER DELETE
// ------------------------------------------------------------
// Send user back to dashboard page
// with a success message in the URL.
header(
    "Location: ../../../frontend/pages/dashboard.php?msg=Announcement+deleted"
);


// Stop script execution.
exit();



/* ============================================================
VARIABLE EXPLANATIONS
============================================================

$conn
- Type: MySQL Connection
- Purpose: Connects PHP to the database.

$_SESSION['user_id']
- Type: Integer
- Purpose: Stores the logged-in user's ID.

$_GET['id']
- Type: String (URL Parameter)
- Purpose: Contains the announcement ID passed in the URL.

$id
- Type: Integer
- Purpose: Stores the announcement ID that should be deleted.

============================================================
EXAMPLE URL
============================================================

delete_announcement.php?id=7

Result:

$id = 7

Query executed:

DELETE FROM announcements WHERE id=7;

============================================================
PROGRAM FLOW
============================================================

1. Start Session
        │
        ▼
2. Connect to Database
        │
        ▼
3. Check User Login
        │
   ┌────┴────┐
   │Logged In│
   └────┬────┘
        │No
        ▼
   Redirect to Login
        │
        ▼
       End

        │Yes
        ▼
4. Get Announcement ID
        │
        ▼
5. Check ID > 0
        │
   ┌────┴────┐
   │ Valid ? │
   └────┬────┘
        │No
        ▼
   Skip Delete

        │Yes
        ▼
6. Delete Announcement
        │
        ▼
7. Redirect Dashboard
        │
        ▼
       End

============================================================
SQL QUERY
============================================================

DELETE FROM announcements
WHERE id = $id;

Purpose:
Removes the announcement whose ID matches $id.

Example:

DELETE FROM announcements
WHERE id = 3;

This permanently removes announcement #3
from the announcements table.

============================================================
POSSIBLE IMPROVEMENT
============================================================

Current code allows ANY logged-in user to delete announcements.

Better:

if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'],
        ['admin','manager','director']
    )
) {
    header("Location: login.php");
    exit();
}

This ensures only authorized staff can delete announcements.

============================================================ */
?>
