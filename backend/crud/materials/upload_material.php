<?php
// ============================================================
// upload_material.php
// Purpose: Upload study material file + save record in database
// ============================================================


// ------------------------------------------------------------
// START SESSION
// ------------------------------------------------------------
// Start or resume session.
// Used to access:
// $_SESSION['user_id'], $_SESSION['role'], $_SESSION['full_name']
session_start();


// ------------------------------------------------------------
// DATABASE CONNECTION
// ------------------------------------------------------------
// Include DB connection file.
// Provides $conn (MySQL connection)
require '../../config/db.php';


// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
// Only lecturers can upload materials.
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
// FORM DATA COLLECTION
// ------------------------------------------------------------
// Retrieve form inputs from POST request.

// Material title
$title = mysqli_real_escape_string(
    $conn,
    trim($_POST['mat_title'])
);

// Subject name or code
$subject = mysqli_real_escape_string(
    $conn,
    trim($_POST['mat_subject'])
);

// Batch ID (integer)
$batch_id = (int)$_POST['mat_batch_id'];

// Description (optional text)
$description = mysqli_real_escape_string(
    $conn,
    trim($_POST['mat_description'])
);

// Uploader's user id from session (FK to users.userID — study_materials no
// longer stores the lecturer's name as text)
$uploader_id = (int) $_SESSION['user_id'];


// ------------------------------------------------------------
// VALIDATION: REQUIRED FIELDS
// ------------------------------------------------------------
// Ensure essential fields are not empty.
if (
    empty($title) ||
    empty($subject) ||
    !$batch_id
) {

    header(
        "Location: ../../../frontend/pages/dashboard.php?mat_error=Title+subject+and+batch+are+required#materials"
    );

    exit();
}


// ------------------------------------------------------------
// FILE UPLOAD VALIDATION
// ------------------------------------------------------------

// Check if file exists in request
if (
    !isset($_FILES['mat_file']) ||
    $_FILES['mat_file']['error'] != 0
) {

    header(
        "Location: ../../../frontend/pages/dashboard.php?mat_error=Please+select+a+file#materials"
    );

    exit();
}


// ------------------------------------------------------------
// ALLOWED FILE TYPES
// ------------------------------------------------------------
// Only these extensions are allowed for security.
$allowed_types = [
    'pdf',
    'doc',
    'docx',
    'ppt',
    'pptx',
    'txt'
];


// Extract file extension
$ext = strtolower(
    pathinfo(
        $_FILES['mat_file']['name'],
        PATHINFO_EXTENSION
    )
);


// Check if extension is allowed
if (!in_array($ext, $allowed_types)) {

    header(
        "Location: ../../../frontend/pages/dashboard.php?mat_error=Invalid+file+type.+Allowed:+PDF+DOC+PPTX+TXT#materials"
    );

    exit();
}


// ------------------------------------------------------------
// FILE SIZE VALIDATION
// ------------------------------------------------------------
// Maximum allowed file size = 5MB
$max_size = 5 * 1024 * 1024;

if (
    $_FILES['mat_file']['size'] > $max_size
) {

    header(
        "Location: ../../../frontend/pages/dashboard.php?mat_error=File+too+large.+Max+5MB#materials"
    );

    exit();
}


// ------------------------------------------------------------
// GENERATE UNIQUE FILE NAME
// ------------------------------------------------------------
// Prevent file overwriting by using:
// userID + timestamp
$filename =
    'mat_' .
    $_SESSION['user_id'] .
    '_' .
    time() .
    '.' .
    $ext;


// Destination path
$dest =
    '../../../uploads/materials/' .
    $filename;


// ------------------------------------------------------------
// MOVE UPLOADED FILE
// ------------------------------------------------------------
// Moves file from temporary location
// to permanent uploads folder.
if (
    move_uploaded_file(
        $_FILES['mat_file']['tmp_name'],
        $dest
    )
) {

    // --------------------------------------------------------
    // INSERT INTO DATABASE
    // --------------------------------------------------------
    mysqli_query(
        $conn,
        "INSERT INTO study_materials
        (
            batch_id,
            title,
            subject,
            description,
            file_path,
            uploaded_by_lecturer_id
        )
        VALUES
        (
            $batch_id,
            '$title',
            '$subject',
            '$description',
            '$filename',
            $uploader_id
        )"
    );


    // Redirect on success
    header(
        "Location: ../../../frontend/pages/dashboard.php?mat_success=Material+uploaded+successfully#materials"
    );

} else {

    // Upload failed
    header(
        "Location: ../../../frontend/pages/dashboard.php?mat_error=Upload+failed.+Check+folder+permissions#materials"
    );
}


// Stop execution
exit();



/* ============================================================
VARIABLE EXPLANATIONS
============================================================

$conn
- Database connection

$_SESSION['user_id']
- Logged-in lecturer ID

$_SESSION['role']
- User role (must be lecturer)

$_SESSION['full_name']
- Lecturer name

$title
- Material title

$subject
- Subject name/code

$batch_id
- Batch ID (integer)

$description
- Material description

$uploader_id
- Logged-in lecturer's user id (uploader)

$_FILES['mat_file']
- Uploaded file array

$ext
- File extension

$allowed_types
- Allowed file formats

$max_size
- Maximum file size (5MB)

$filename
- Unique generated file name

$dest
- Final file path

============================================================
FUNCTION EXPLANATIONS
============================================================

session_start()
- Starts session

require()
- Includes DB connection

mysqli_real_escape_string()
- Prevents SQL injection

trim()
- Removes whitespace

isset()
- Checks variable existence

pathinfo()
- Gets file extension

strtolower()
- Converts string to lowercase

in_array()
- Checks value in array

move_uploaded_file()
- Moves uploaded file to folder

mysqli_query()
- Runs SQL query

time()
- Current timestamp

header()
- Redirects user

exit()
- Stops script

============================================================
SQL QUERY
============================================================

INSERT INTO study_materials
(
    batch_id,
    title,
    subject,
    description,
    file_path,
    uploaded_by
)
VALUES
(
    $batch_id,
    '$title',
    '$subject',
    '$description',
    '$filename',
    '$uploader'
);

Purpose:
Saves uploaded material record.

============================================================
PROGRAM FLOW
============================================================

1. Start Session
2. Connect Database
3. Check Lecturer role
4. Read Form Data
5. Validate Inputs
6. Check File Exists
7. Validate File Type
8. Validate File Size
9. Generate Unique Filename
10. Move File to Upload Folder
11. Insert DB Record
12. Redirect Result

============================================================
SECURITY IMPROVEMENTS
============================================================

1. Use prepared statements

2. Store file metadata separately

3. Rename files with UUID instead of time()

4. Validate MIME type (not just extension)

5. Restrict upload directory execution

6. Scan uploaded files for malware

============================================================ */
