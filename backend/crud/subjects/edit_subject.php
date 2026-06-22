<?php
// ============================================================
// edit_subject.php
// Purpose: Edit an existing subject record
// ============================================================


// ------------------------------------------------------------
// START SESSION
// ------------------------------------------------------------
// Enables session usage:
// $_SESSION['user_id'], $_SESSION['role']
session_start();


// ------------------------------------------------------------
// DATABASE CONNECTION
// ------------------------------------------------------------
// Provides $conn (MySQL connection object)
require '../../config/db.php';


// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
// Only admin and manager can edit subjects.
//
// If unauthorized → redirect to login page
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'], ['admin','manager'])
) {
    header("Location: ../../../frontend/pages/login.php");
    exit();
}


// ------------------------------------------------------------
// GET SUBJECT ID
// ------------------------------------------------------------
// Retrieve subject ID from URL
$id = (int)($_GET['id'] ?? 0);


// ------------------------------------------------------------
// FETCH SUBJECT DATA
// ------------------------------------------------------------
// Get subject record from database
$subject = $id
    ? mysqli_fetch_assoc(
        mysqli_query(
            $conn,
            "SELECT * FROM subjects WHERE id=$id"
        )
    )
    : null;


// ------------------------------------------------------------
// VALIDATION: NOT FOUND
// ------------------------------------------------------------
// Stop execution if subject does not exist
if (!$subject) {

    echo "Subject not found.";

    exit();
}


// ------------------------------------------------------------
// UPDATE FORM HANDLER
// ------------------------------------------------------------
// Runs when form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --------------------------------------------------------
    // SUBJECT NAME
    // --------------------------------------------------------
    $name = mysqli_real_escape_string(
        $conn,
        trim($_POST['name'])
    );

    // --------------------------------------------------------
    // SUBJECT CODE
    // --------------------------------------------------------
    $code = mysqli_real_escape_string(
        $conn,
        trim($_POST['code'])
    );

    // --------------------------------------------------------
    // LEVEL
    // --------------------------------------------------------
    $level = mysqli_real_escape_string(
        $conn,
        $_POST['level']
    );

    // --------------------------------------------------------
    // FEE
    // --------------------------------------------------------
    $fee = (float)$_POST['fee'];

    // --------------------------------------------------------
    // DESCRIPTION
    // --------------------------------------------------------
    $desc = mysqli_real_escape_string(
        $conn,
        trim($_POST['description'])
    );

    // --------------------------------------------------------
    // UPDATE DATABASE
    // --------------------------------------------------------
    mysqli_query(
        $conn,
        "UPDATE subjects
         SET
            name='$name',
            code='$code',
            level='$level',
            fee=$fee,
            description='$desc'
         WHERE id=$id"
    );

    // Redirect after success
    header(
        "Location: ../../../frontend/pages/dashboard.php?msg=Subject+updated"
    );

    exit();
}


// ------------------------------------------------------------
// PAGE SETTINGS
// ------------------------------------------------------------

// Page title
$page_title = "Edit Subject";

// CSS file path
$css_path = "../../../frontend/assets/css/style.css";

// Root path
$root_path = "../../../";

// Active menu item
$active_page = "";


// Include header
include '../../../frontend/assets/header.php';
?>


<!-- ==========================================================
PAGE UI
========================================================== -->

<div
    class="section"
    style="max-width:520px; margin:0 auto;"
>

    <!-- Title -->
    <h2 class="section-title">
        ✏️ Edit Subject
    </h2>


    <!-- Form Card -->
    <div class="card">

        <form method="POST">

            <!-- Name + Code -->
            <div class="form-row">

                <div class="form-group">

                    <label>
                        Name *
                    </label>

                    <input
                        type="text"
                        name="name"
                        value="<?php echo htmlspecialchars($subject['name']); ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        Code *
                    </label>

                    <input
                        type="text"
                        name="code"
                        value="<?php echo htmlspecialchars($subject['code']); ?>"
                        required
                    >

                </div>

            </div>


            <!-- Level + Fee -->
            <div class="form-row">

                <div class="form-group">

                    <label>
                        Level
                    </label>

                    <select name="level">

                        <?php foreach (['O/L','A/L','Primary','Other'] as $l): ?>

                            <option
                                value="<?php echo $l; ?>"
                                <?php if ($subject['level'] == $l) echo 'selected'; ?>
                            >
                                <?php echo $l; ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label>
                        Fee (LKR)
                    </label>

                    <input
                        type="number"
                        name="fee"
                        value="<?php echo $subject['fee']; ?>"
                        min="0"
                        step="0.01"
                    >

                </div>

            </div>


            <!-- Description -->
            <div class="form-group">

                <label>
                    Description
                </label>

                <textarea
                    name="description"
                ><?php echo htmlspecialchars($subject['description']); ?></textarea>

            </div>


            <!-- Buttons -->
            <button
                type="submit"
                class="btn btn-primary"
            >
                💾 Save Changes
            </button>


            <a
                href="../../../frontend/pages/dashboard.php"
                class="btn btn-outline"
                style="margin-left:8px;"
            >
                Cancel
            </a>

        </form>

    </div>

</div>


<?php

// ------------------------------------------------------------
// CLOSE DB CONNECTION
// ------------------------------------------------------------
mysqli_close($conn);


// Include footer
include '../../../frontend/assets/footer.php';

?>


/* ============================================================
VARIABLE EXPLANATIONS
============================================================

$conn
- Database connection object

$id
- Subject ID from URL

$subject
- Subject record from database

$name
- Subject name input

$code
- Subject code input

$level
- Subject level (O/L, A/L, Primary, Other)

$fee
- Subject fee (float)

$desc
- Subject description

$_POST['...']
- Form inputs

============================================================
FUNCTION EXPLANATIONS
============================================================

session_start()
- Starts session

require()
- Loads DB connection

mysqli_fetch_assoc()
- Fetch DB row

mysqli_real_escape_string()
- Prevent SQL injection

trim()
- Remove whitespace

(float)
- Convert to decimal

mysqli_query()
- Execute SQL query

htmlspecialchars()
- Prevent XSS

header()
- Redirect user

exit()
- Stop script

foreach()
- Loop through levels

============================================================
SQL QUERY
============================================================

UPDATE subjects
SET name=?, code=?, level=?, fee=?, description=?
WHERE id=?;

Purpose:
Updates subject details

============================================================
PROGRAM FLOW
============================================================

1. Start Session
2. Connect DB
3. Check Role
4. Get ID
5. Fetch Subject
6. Show Form
7. User edits data
8. Submit form
9. Update database
10. Redirect dashboard

============================================================
SECURITY IMPROVEMENTS
============================================================

1. Prevent duplicate subject codes

2. Use prepared statements

3. Validate fee >= 0

4. Check subject dependencies (batches)

5. Log subject updates

============================================================ */