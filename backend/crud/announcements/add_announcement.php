<?php
// ============================================================
// add_announcement.php — Post a New Announcement
// ============================================================


// Start or resume the current session.
// This allows access to session variables such as user_id and role.
session_start();


// Include the database connection file.
// The file should create a database connection stored in $conn.
require '../../config/db.php';


// -----------------------------------------------------------------
// AUTHORIZATION CHECK
// -----------------------------------------------------------------
// Verify that:
// 1. The user is logged in (user_id exists in session)
// 2. The user role is allowed to post announcements
//
// Allowed roles:
// - admin
// - manager
// - director
// - lecturer
//
// If not authorized, redirect to login page.
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'], ['admin', 'manager', 'director', 'lecturer'])
) {
    header("Location: ../../../frontend/pages/login.php");
    exit();
}


// -----------------------------------------------------------------
// FORM PROCESSING
// -----------------------------------------------------------------
// Run only when the form is submitted using POST method.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Retrieve title from form.
    // trim() removes extra spaces.
    // mysqli_real_escape_string() escapes special characters.
    $title = mysqli_real_escape_string(
        $conn,
        trim($_POST['title'])
    );

    // Retrieve announcement message.
    $message = mysqli_real_escape_string(
        $conn,
        trim($_POST['message'])
    );

    // Retrieve selected audience.
    $audience = mysqli_real_escape_string(
        $conn,
        $_POST['audience']
    );

    // Store today's date.
    $date = date('Y-m-d');

    // Get ID of currently logged-in user.
    $by = $_SESSION['user_id'];



    // -------------------------------------------------------------
    // VALIDATION
    // -------------------------------------------------------------
    // Ensure title and message are provided.
    if (empty($title) || empty($message)) {

        // Store error message for display.
        $error = "Title and message are required.";

    } else {

        // ---------------------------------------------------------
        // DATABASE INSERT
        // ---------------------------------------------------------
        // Insert announcement into announcements table.
        mysqli_query(
            $conn,
            "INSERT INTO announcements
            (
                title,
                message,
                audience,
                post_date,
                posted_by
            )
            VALUES
            (
                '$title',
                '$message',
                '$audience',
                '$date',
                $by
            )"
        );

        // Redirect user back to dashboard with success message.
        header(
            "Location: ../../../frontend/pages/dashboard.php?msg=Announcement+posted"
        );

        exit();
    }
}


// -----------------------------------------------------------------
// PAGE SETTINGS
// -----------------------------------------------------------------

// Browser/page title.
$page_title = "Post Announcement";

// CSS file path.
$css_path = "../../../frontend/assets/css/style.css";

// Root project path.
$root_path = "../../../";

// Active menu item (empty in this page).
$active_page = "";


// Include common page header.
include '../../../frontend/assets/header.php';
?>

<!-- ============================================================
     PAGE CONTENT
============================================================ -->

<!-- Main content container -->
<div class="section" style="max-width:520px; margin:0 auto;">

    <!-- Page heading -->
    <h2 class="section-title">
        📢 Post New Announcement
    </h2>

    <!-- ---------------------------------------------------------
         ERROR MESSAGE
    ---------------------------------------------------------- -->
    <!-- Display only if validation failed -->
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



    <!-- Announcement form card -->
    <div class="card">

        <!-- Form submits data back to this page -->
        <form method="POST">

            <!-- -------------------------------------------------
                 TITLE FIELD
            -------------------------------------------------- -->
            <div class="form-group">

                <!-- Input label -->
                <label>
                    Title *
                </label>

                <!-- Announcement title -->
                <input
                    type="text"
                    name="title"
                    required
                    placeholder="e.g. Exam on Saturday"
                >

            </div>



            <!-- -------------------------------------------------
                 AUDIENCE DROPDOWN
            -------------------------------------------------- -->
            <div class="form-group">

                <label>
                    Audience
                </label>

                <select name="audience">

                    <!-- Visible to everyone -->
                    <option value="all">
                        All Users
                    </option>

                    <!-- Visible only to students -->
                    <option value="students">
                        Students Only
                    </option>

                    <!-- Visible only to parents -->
                    <option value="parents">
                        Parents Only
                    </option>

                    <!-- Visible only to staff -->
                    <option value="staff">
                        Staff Only
                    </option>

                </select>

            </div>



            <!-- -------------------------------------------------
                 MESSAGE FIELD
            -------------------------------------------------- -->
            <div class="form-group">

                <label>
                    Message *
                </label>

                <!-- Multi-line text input -->
                <textarea
                    name="message"
                    required
                    placeholder="Type your announcement here..."
                ></textarea>

            </div>



            <!-- -------------------------------------------------
                 SUBMIT BUTTON
            -------------------------------------------------- -->
            <button
                type="submit"
                class="btn btn-primary"
            >
                📤 Post Announcement
            </button>



            <!-- -------------------------------------------------
                 CANCEL BUTTON
            -------------------------------------------------- -->
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

// -----------------------------------------------------------------
// CLEANUP
// -----------------------------------------------------------------

// Close database connection.
mysqli_close($conn);


// Include common footer.
include '../../../frontend/assets/footer.php';

?>


<!-- ============================================================
VARIABLE EXPLANATIONS
============================================================

$conn
- Type: MySQL Connection
- Purpose: Connects PHP to the database.

$_SESSION['user_id']
- Type: Integer
- Purpose: Stores the logged-in user's ID.

$_SESSION['role']
- Type: String
- Purpose: Stores the user's role.

$title
- Type: String
- Purpose: Stores announcement title entered by user.

$message
- Type: String
- Purpose: Stores announcement content.

$audience
- Type: String
- Purpose: Stores target audience.

$date
- Type: String
- Purpose: Stores current date.

$by
- Type: Integer
- Purpose: Stores ID of announcement creator.

$error
- Type: String
- Purpose: Stores validation error message.

$page_title
- Type: String
- Purpose: Browser/page title.

$css_path
- Type: String
- Purpose: Path to stylesheet.

$root_path
- Type: String
- Purpose: Root project directory path.

$active_page
- Type: String
- Purpose: Used for menu highlighting.

============================================================
PROGRAM FLOW
============================================================

1. Start Session
2. Connect to Database
3. Check Login Status
4. Check User Role
5. Display Form
6. User Submits Form
7. Validate Title and Message
8. Insert Record into Database
9. Redirect to Dashboard
10. Close Database Connection

============================================================
DATABASE QUERY
============================================================

INSERT INTO announcements
(
    title,
    message,
    audience,
    post_date,
    posted_by
)
VALUES
(
    '$title',
    '$message',
    '$audience',
    '$date',
    $by
);

Purpose:
Creates a new announcement record in the announcements table.

============================================================
-->
