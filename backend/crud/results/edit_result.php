<?php
// ============================================================
// edit_result.php
// Purpose: Edit an existing exam result record
// Includes grade recalculation logic
// ============================================================


// ------------------------------------------------------------
// START SESSION
// ------------------------------------------------------------
// Start or resume session.
// Gives access to:
// $_SESSION['user_id'], $_SESSION['role']
session_start();


// ------------------------------------------------------------
// DATABASE CONNECTION
// ------------------------------------------------------------
// Provides $conn (MySQL connection object)
require '../../config/db.php';


// ------------------------------------------------------------
// HELPER FUNCTIONS
// ------------------------------------------------------------
// Includes helper functions such as:
// calc_grade($marks)
require '../../config/helpers.php';


// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
// Only lecturers can edit result.
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
// GET RESULT ID
// ------------------------------------------------------------
// Retrieve result ID from URL.
//
// Example:
// edit_result.php?id=5
$id = (int)($_GET['id'] ?? 0);


// ------------------------------------------------------------
// FETCH RESULT DATA
// ------------------------------------------------------------
// Get result record from database.
$res = $id
    ? mysqli_fetch_assoc(
        mysqli_query(
            $conn,
            "SELECT * FROM result WHERE resultID=$id"
        )
    )
    : null;


// ------------------------------------------------------------
// VALIDATION: NOT FOUND
// ------------------------------------------------------------
// Stop script if result doesn't exist.
if (!$res) {

    echo "Result not found.";

    exit();
}


// ------------------------------------------------------------
// FORM SUBMISSION
// ------------------------------------------------------------
// Runs when Save button is clicked.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --------------------------------------------------------
    // EXAM NAME
    // --------------------------------------------------------
    $exam_name = mysqli_real_escape_string(
        $conn,
        trim($_POST['exam_name'])
    );

    // --------------------------------------------------------
    // EXAM DATE
    // --------------------------------------------------------
    $exam_date = mysqli_real_escape_string(
        $conn,
        $_POST['exam_date']
    );

    // --------------------------------------------------------
    // MARKS
    // --------------------------------------------------------
    $marks = (int)$_POST['marks'];

    // Total marks fixed
    $total_marks = 100;

    // --------------------------------------------------------
    // GRADE CALCULATION
    // --------------------------------------------------------
    // Uses helper function calc_grade()
    $grade = mysqli_real_escape_string(
        $conn,
        calc_grade($marks)
    );

    // --------------------------------------------------------
    // COMMENTS
    // --------------------------------------------------------
    $comments = mysqli_real_escape_string(
        $conn,
        trim($_POST['comments'])
    );

    // --------------------------------------------------------
    // UPDATE DATABASE
    // --------------------------------------------------------
    mysqli_query(
        $conn,
        "UPDATE result
         SET
            exam_name='$exam_name',
            exam_date='$exam_date',
            marks=$marks,
            total_marks=$total_marks,
            grade='$grade',
            comments='$comments'
         WHERE resultID=$id"
    );

    // Redirect after update
    header(
        "Location: ../../../frontend/pages/dashboard.php?msg=Result+updated"
    );

    exit();
}


// ------------------------------------------------------------
// PAGE SETTINGS
// ------------------------------------------------------------

// Page title
$page_title = "Edit Result";

// CSS file path
$css_path = "../../../frontend/assets/css/style.css";

// Root directory path
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
        ✏️ Edit Exam Result
    </h2>


    <!-- Main Card -->
    <div class="card">

        <form method="POST">

            <!-- -----------------------------------------
                 EXAM NAME
            ------------------------------------------ -->
            <div class="form-group">

                <label>
                    Exam Name
                </label>

                <input
                    type="text"
                    name="exam_name"
                    value="<?php echo htmlspecialchars($res['exam_name']); ?>"
                    required
                >

            </div>


            <!-- -----------------------------------------
                 DATE + GRADE
            ------------------------------------------ -->
            <div class="form-row">

                <div class="form-group">

                    <label>
                        Date
                    </label>

                    <input
                        type="date"
                        name="exam_date"
                        value="<?php echo $res['exam_date']; ?>"
                    >

                </div>

                <div class="form-group">

                    <label>
                        Grade
                        <small style="color:#64748b;">
                            (calculated automatically)
                        </small>
                    </label>

                    <input
                        type="text"
                        id="res_grade_display"
                        value="<?php echo htmlspecialchars($res['grade']); ?>"
                        readonly
                        disabled
                        style="
                            background:#f1f5f9;
                            cursor:not-allowed;
                            font-weight:700;
                        "
                    >

                </div>

            </div>


            <!-- -----------------------------------------
                 MARKS + TOTAL MARKS
            ------------------------------------------ -->
            <div class="form-row">

                <div class="form-group">

                    <label>
                        Marks (out of 100)
                    </label>

                    <input
                        type="number"
                        id="res_marks"
                        name="marks"
                        value="<?php echo $res['marks']; ?>"
                        min="0"
                        max="100"
                        oninput="aaUpdateGrade()"
                    >

                </div>

                <div class="form-group">

                    <label>
                        Total Marks
                    </label>

                    <input
                        type="number"
                        value="100"
                        readonly
                        disabled
                        style="
                            background:#f1f5f9;
                            cursor:not-allowed;
                        "
                    >

                </div>

            </div>


            <!-- -----------------------------------------
                 COMMENTS
            ------------------------------------------ -->
            <div class="form-group">

                <label>
                    Comments
                </label>

                <input
                    type="text"
                    name="comments"
                    value="<?php echo htmlspecialchars($res['comments']); ?>"
                >

            </div>


            <!-- Save Button -->
            <button
                type="submit"
                class="btn btn-primary"
            >
                💾 Save
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


<!-- ==========================================================
JAVASCRIPT: LIVE GRADE CALCULATION
========================================================== -->

<script>

// ------------------------------------------------------------
// FUNCTION: CALCULATE GRADE
// ------------------------------------------------------------
// Converts marks into grade scale
function aaCalcGrade(marks) {

    if (marks === '' || isNaN(marks))
        return '';

    marks = Number(marks);

    if (marks >= 85) return 'A+';
    if (marks >= 70) return 'A';
    if (marks >= 65) return 'A-';
    if (marks >= 60) return 'B+';
    if (marks >= 55) return 'B';
    if (marks >= 50) return 'B-';
    if (marks >= 45) return 'C+';
    if (marks >= 40) return 'C';
    if (marks >= 35) return 'C-';
    if (marks >= 30) return 'D+';
    if (marks >= 25) return 'D';

    return 'E';
}


// ------------------------------------------------------------
// FUNCTION: UPDATE GRADE LIVE
// ------------------------------------------------------------
// Updates grade field while typing marks
function aaUpdateGrade() {

    document.getElementById(
        'res_grade_display'
    ).value = aaCalcGrade(
        document.getElementById('res_marks').value
    );
}

</script>


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

$res
- Result record from database

$id
- Result ID from URL

$exam_name
- Exam name input

$exam_date
- Exam date input

$marks
- Student marks (integer)

$total_marks
- Fixed value = 100

$grade
- Calculated grade (A+, A, B, etc.)

$comments
- Lecturer comments

$res['...']
- Database fields (exam_name, marks, grade, etc.)

============================================================
FUNCTION EXPLANATIONS
============================================================

session_start()
- Start session

require()
- Load DB + helper functions

mysqli_real_escape_string()
- Prevent SQL injection

trim()
- Remove whitespace

mysqli_query()
- Execute SQL query

mysqli_fetch_assoc()
- Fetch DB row

htmlspecialchars()
- Prevent XSS

calc_grade()
- Helper function that converts marks → grade

header()
- Redirect user

exit()
- Stop script

============================================================
JAVASCRIPT FUNCTIONS
============================================================

aaCalcGrade(marks)
- Converts numeric marks into grade string

aaUpdateGrade()
- Updates grade display in real-time

============================================================
PROGRAM FLOW
============================================================

1. Start Session
2. Connect Database
3. Load Helper Functions
4. Check Lecturer Role
5. Get Result ID
6. Fetch Result
7. Show Form
8. User edits marks
9. JS updates grade live
10. Submit form
11. Recalculate grade in PHP
12. Update database
13. Redirect dashboard

============================================================
SECURITY IMPROVEMENTS
============================================================

1. Ensure lecturer owns result before editing

2. Validate marks (0–100)

3. Prevent grade tampering (server-side only)

4. Use prepared statements

5. Log grade changes

============================================================ */