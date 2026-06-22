<?php
// ============================================================
// add_subject.php
// Purpose: Add a new subject into the system
// ============================================================


// ------------------------------------------------------------
// START SESSION
// ------------------------------------------------------------
// Enables session access:
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
// Only admin and manager can add subjects.
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
// FORM SUBMISSION HANDLER
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
    // LEVEL (O/L, A/L, etc.)
    // --------------------------------------------------------
    $level = mysqli_real_escape_string(
        $conn,
        $_POST['level']
    );

    // --------------------------------------------------------
    // MONTHLY FEE
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
    // VALIDATION CHECK
    // --------------------------------------------------------
    if (empty($name) || empty($code)) {

        $error = "Name and code are required.";

    } else {

        // ----------------------------------------------------
        // INSERT INTO DATABASE
        // ----------------------------------------------------
        mysqli_query(
            $conn,
            "INSERT INTO subjects
            (
                name,
                code,
                level,
                fee,
                description
            )
            VALUES
            (
                '$name',
                '$code',
                '$level',
                $fee,
                '$desc'
            )"
        );

        // Redirect after success
        header(
            "Location: ../../../frontend/pages/dashboard.php?msg=Subject+added"
        );

        exit();
    }
}


// ------------------------------------------------------------
// PAGE CONFIGURATION
// ------------------------------------------------------------

// Page title
$page_title = "Add Subject";

// CSS file path
$css_path = "../../../frontend/assets/css/style.css";

// Root path reference
$root_path = "../../../";

// Active navigation item
$active_page = "";


// Include header layout
include '../../../frontend/assets/header.php';
?>


<!-- ==========================================================
PAGE UI
========================================================== -->

<div
    class="section"
    style="max-width:520px; margin:0 auto;"
>

    <!-- Page Title -->
    <h2 class="section-title">
        📚 Add New Subject
    </h2>


    <!-- Error Message -->
    <?php if (isset($error)): ?>
        <div
            style="
                background:#fee2e2;
                color:#991b1b;
                padding:12px;
                border-radius:8px;
                margin-bottom:16px;
            "
        >
            ❌ <?php echo $error; ?>
        </div>
    <?php endif; ?>


    <!-- Form Card -->
    <div class="card">

        <form method="POST">

            <!-- Subject Name + Code -->
            <div class="form-row">

                <div class="form-group">

                    <label>
                        Subject Name *
                    </label>

                    <input
                        type="text"
                        name="name"
                        placeholder="e.g. Mathematics"
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
                        placeholder="e.g. MATH-OL"
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

                        <option value="O/L">O/L</option>
                        <option value="A/L">A/L</option>
                        <option value="Primary">Primary</option>
                        <option value="Other">Other</option>

                    </select>

                </div>

                <div class="form-group">

                    <label>
                        Monthly Fee (LKR)
                    </label>

                    <input
                        type="number"
                        name="fee"
                        value="0"
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
                    placeholder="Brief description..."
                ></textarea>

            </div>


            <!-- Submit Button -->
            <button
                type="submit"
                class="btn btn-primary"
            >
                ✅ Add Subject
            </button>


            <!-- Cancel Button -->
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
// CLOSE DATABASE CONNECTION
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

$name
- Subject name

$code
- Subject code

$level
- Subject level (O/L, A/L, Primary, Other)

$fee
- Monthly fee (float)

$desc
- Subject description

$_POST['...']
- Form inputs

$error
- Error message for UI

============================================================
FUNCTION EXPLANATIONS
============================================================

session_start()
- Starts session

require()
- Loads DB connection

mysqli_real_escape_string()
- Prevents SQL injection

trim()
- Removes whitespace

(float)
- Converts to decimal number

mysqli_query()
- Executes SQL query

isset()
- Checks variable existence

header()
- Redirects browser

exit()
- Stops script

htmlspecialchars()
- Prevents XSS

============================================================
SQL QUERY
============================================================

INSERT INTO subjects
(name, code, level, fee, description)
VALUES (...)

Purpose:
Adds new subject to system

============================================================
PROGRAM FLOW
============================================================

1. Start Session
2. Connect DB
3. Check Role
4. Wait for POST
5. Validate inputs
6. Insert subject
7. Redirect dashboard

============================================================
SECURITY IMPROVEMENTS
============================================================

1. Use prepared statements

2. Prevent duplicate subject codes

3. Validate fee >= 0

4. Limit role access strictly

5. Add audit log for subject creation

============================================================ */