<?php
// ============================================================
// delete_material.php
// Purpose: Delete a study material record AND its uploaded file
// ============================================================


// ------------------------------------------------------------
// START SESSION
// ------------------------------------------------------------
// Start or resume user session.
// Enables access to:
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
// Only lecturers are allowed to delete materials.
//
// If user is not logged in OR not a lecturer,
// redirect to login page.
if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] != 'lecturer'
) {
    header("Location: ../../../frontend/pages/login.php");
    exit();
}


// ------------------------------------------------------------
// GET MATERIAL ID
// ------------------------------------------------------------
// Retrieve material ID from URL.
//
// Example:
// delete_material.php?id=10
//
// Default to 0 if not provided.
$id = (int)($_GET['id'] ?? 0);


// ------------------------------------------------------------
// FETCH MATERIAL RECORD
// ------------------------------------------------------------
// Get material details from database.
// Used to find file path for deletion.
$mat = $id
    ? mysqli_fetch_assoc(
        mysqli_query(
            $conn,
            "SELECT * FROM study_materials WHERE studyMaterialID=$id"
        )
    )
    : null;


// ------------------------------------------------------------
// DELETE PROCESS
// ------------------------------------------------------------
// Only run deletion if material exists.
if ($mat) {

    // --------------------------------------------------------
    // FILE PATH CONSTRUCTION
    // --------------------------------------------------------
    // Build full path to uploaded file.
    $file = '../../../uploads/materials/' . $mat['file_path'];

    // --------------------------------------------------------
    // DELETE PHYSICAL FILE
    // --------------------------------------------------------
    // Check if file exists before deleting.
    if (file_exists($file)) {
        unlink($file); // permanently removes file from server
    }

    // --------------------------------------------------------
    // DELETE DATABASE RECORD
    // --------------------------------------------------------
    mysqli_query(
        $conn,
        "DELETE FROM study_materials WHERE studyMaterialID=$id"
    );
}


// ------------------------------------------------------------
// REDIRECT USER
// ------------------------------------------------------------
// After deletion, go back to dashboard
// with success message and anchor link.
header(
    "Location: ../../../frontend/pages/dashboard.php?msg=Material+deleted#materials"
);


// Stop script execution.
exit();



/* ============================================================
VARIABLE EXPLANATIONS
============================================================

$conn
- Type: MySQL connection
- Purpose: Connects PHP to database

$_SESSION['user_id']
- Type: Integer
- Purpose: Logged-in lecturer ID

$_SESSION['role']
- Type: String
- Purpose: User role (must be lecturer)

$_GET['id']
- Type: String (URL parameter)
- Purpose: Material ID from URL

$id
- Type: Integer
- Purpose: Clean numeric material ID

$mat
- Type: Array
- Purpose: Material record from database

$file
- Type: String (file path)
- Purpose: Full path to uploaded file

============================================================
FUNCTION EXPLANATIONS
============================================================

session_start()
- Starts session

require()
- Includes DB connection

isset()
- Checks variable existence

mysqli_query()
- Executes SQL query

mysqli_fetch_assoc()
- Fetches DB row as associative array

file_exists()
- Checks if file exists on server

unlink()
- Deletes file permanently from server

(int)
- Converts value to integer

header()
- Redirects user

exit()
- Stops script execution

============================================================
SQL QUERY
============================================================

SELECT * FROM study_materials WHERE studyMaterialID=$id;

Purpose:
Fetch material details

------------------------------------------------------------

DELETE FROM study_materials WHERE studyMaterialID=$id;

Purpose:
Remove material record from database

============================================================
PROGRAM FLOW
============================================================

1. Start Session
2. Connect Database
3. Check Lecturer Role
4. Get Material ID
5. Fetch Material Record
6. If exists:
      ├── Delete file from server
      └── Delete database record
7. Redirect to dashboard

============================================================
SECURITY IMPROVEMENTS
============================================================

1. Ensure lecturer owns the material before deletion:
   WHERE studyMaterialID=$id AND uploaded_by=$_SESSION['user_id']

2. Use prepared statements

3. Validate file path to prevent directory traversal

4. Log deletion actions

5. Soft delete instead of permanent delete (optional)

============================================================ */