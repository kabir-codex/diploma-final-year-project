<?php
// ============================================================
// edit_announcement.php
// Purpose: Edit an existing announcement
// ============================================================


// Start or resume the current session.
// Allows access to session variables such as user_id.
session_start();


// Include database connection file.
// Creates a MySQL connection stored in $conn.
require '../../config/db.php';


// ------------------------------------------------------------
// AUTHENTICATION CHECK
// ------------------------------------------------------------
// Verify that the user is logged in.
//
// If no user_id exists in the session,
// redirect to login page.
if (!isset($_SESSION['user_id'])) {

    header("Location: ../../../frontend/pages/login.php");

    exit();
}


// ------------------------------------------------------------
// GET ANNOUNCEMENT ID
// ------------------------------------------------------------
// Read announcement ID from URL.
//
// Example:
// edit_announcement.php?id=5
//
// If ID does not exist, default to 0.
$id = (int)($_GET['id'] ?? 0);


// ------------------------------------------------------------
// FETCH ANNOUNCEMENT DATA
// ------------------------------------------------------------
// If ID exists, retrieve announcement details
// from the database.
//
// mysqli_fetch_assoc()
// returns the row as an associative array.
$ann = $id
    ? mysqli_fetch_assoc(
        mysqli_query(
            $conn,
            "SELECT * FROM announcements WHERE id=$id"
        )
    )
    : null;


// ------------------------------------------------------------
// RECORD NOT FOUND
// ------------------------------------------------------------
// If announcement does not exist,
// display message and stop execution.
if (!$ann) {

    echo "Not found.";

    exit();
}


// ------------------------------------------------------------
// FORM SUBMISSION
// ------------------------------------------------------------
// Run when the user clicks Save.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Get title from form.
    // trim() removes extra spaces.
    // mysqli_real_escape_string() escapes special characters.
    $title = mysqli_real_escape_string(
        $conn,
        trim($_POST['title'])
    );

    // Get updated message.
    $message = mysqli_real_escape_string(
        $conn,
        trim($_POST['message'])
    );

    // Get selected audience.
    $audience = mysqli_real_escape_string(
        $conn,
        $_POST['audience']
    );


    // --------------------------------------------------------
    // UPDATE DATABASE RECORD
    // --------------------------------------------------------
    mysqli_query(
        $conn,
        "UPDATE announcements
         SET
             title='$title',
             message='$message',
             audience='$audience'
         WHERE id=$id"
    );


    // Redirect user after successful update.
    header(
        "Location: ../../../frontend/pages/dashboard.php?msg=Announcement+updated"
    );

    exit();
}


// ------------------------------------------------------------
// PAGE SETTINGS
// ------------------------------------------------------------

// Browser/page title.
$page_title = "Edit Announcement";

// CSS file location.
$css_path = "../../../frontend/assets/css/style.css";

// Root project path.
$root_path = "../../../";

// Active menu item.
$active_page = "";


// Include page header.
include '../../../frontend/assets/header.php';
?>

<!-- ==========================================================
     PAGE CONTENT
========================================================== -->

<div
    class="section"
    style="max-width:520px; margin:0 auto;"
>

    <!-- Page heading -->
    <h2 class="section-title">
        ✏️ Edit Announcement
    </h2>

    <!-- Card container -->
    <div class="card">

        <!-- Form submits back to same page -->
        <form method="POST">

            <!-- -----------------------------------------
                 TITLE FIELD
            ------------------------------------------ -->
            <div class="form-group">

                <label>
                    Title
                </label>

                <input
                    type="text"
                    name="title"
                    value="<?php echo htmlspecialchars($ann['title']); ?>"
                    required
                >

            </div>


            <!-- -----------------------------------------
                 AUDIENCE DROPDOWN
            ------------------------------------------ -->
            <div class="form-group">

                <label>
                    Audience
                </label>

                <select name="audience">

                    <?php
                    // Loop through all audience types.
                    foreach (
                        ['all','students','parents','staff']
                        as $a
                    ):
                    ?>

                        <option
                            value="<?php echo $a; ?>"

                            <?php
                            // Automatically select
                            // current audience value.
                            if ($ann['audience'] == $a)
                                echo 'selected';
                            ?>
                        >
                            <?php
                            // Convert first letter
                            // to uppercase.
                            echo ucfirst($a);
                            ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- -----------------------------------------
                 MESSAGE FIELD
            ------------------------------------------ -->
            <div class="form-group">

                <label>
                    Message
                </label>

                <textarea
                    name="message"
                    required
                ><?php echo htmlspecialchars($ann['message']); ?></textarea>

            </div>


            <!-- -----------------------------------------
                 SAVE BUTTON
            ------------------------------------------ -->
            <button
                type="submit"
                class="btn btn-primary"
            >
                💾 Save
            </button>


            <!-- -----------------------------------------
                 CANCEL BUTTON
            ------------------------------------------ -->
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


// Include common footer.
include '../../../frontend/assets/footer.php';

?>


<!-- ==========================================================
VARIABLE EXPLANATIONS
==========================================================

$conn
- Type: MySQL Connection
- Purpose: Connects PHP to the MySQL database.

$_SESSION['user_id']
- Type: Integer
- Purpose: Stores the logged-in user's ID.

$_GET['id']
- Type: String
- Purpose: Contains the announcement ID from URL.

$id
- Type: Integer
- Purpose: Stores announcement ID being edited.

$ann
- Type: Array
- Purpose: Stores announcement details fetched
  from database.

$title
- Type: String
- Purpose: Updated announcement title.

$message
- Type: String
- Purpose: Updated announcement message.

$audience
- Type: String
- Purpose: Updated target audience.

$page_title
- Type: String
- Purpose: Browser page title.

$css_path
- Type: String
- Purpose: Location of stylesheet.

$root_path
- Type: String
- Purpose: Root folder path.

$active_page
- Type: String
- Purpose: Indicates active navigation item.

$a
- Type: String
- Purpose: Used inside foreach loop to
  represent each audience type.

==========================================================
FUNCTION EXPLANATIONS
==========================================================

session_start()
- Starts or resumes a session.

require()
- Includes another PHP file.

isset()
- Checks whether a variable exists.

mysqli_query()
- Executes an SQL query.

mysqli_fetch_assoc()
- Retrieves a database row as an array.

mysqli_real_escape_string()
- Escapes special characters before SQL.

trim()
- Removes spaces from beginning/end.

header()
- Sends HTTP redirect headers.

exit()
- Stops script execution.

htmlspecialchars()
- Converts special characters to safe HTML.

foreach()
- Loops through an array.

ucfirst()
- Converts first letter to uppercase.

mysqli_close()
- Closes database connection.

==========================================================
SQL QUERIES
==========================================================

1. Retrieve Announcement

SELECT *
FROM announcements
WHERE id = $id;

Purpose:
Fetch announcement details for editing.


2. Update Announcement

UPDATE announcements
SET
    title = '$title',
    message = '$message',
    audience = '$audience'
WHERE id = $id;

Purpose:
Save the updated announcement information.

==========================================================
PROGRAM FLOW
==========================================================

1. Start Session
        │
        ▼
2. Connect Database
        │
        ▼
3. Check Login
        │
   ┌────┴────┐
   │Logged In│
   └────┬────┘
        │No
        ▼
   Redirect Login
        │
        ▼
       End

        │Yes
        ▼
4. Get Announcement ID
        │
        ▼
5. Retrieve Announcement
        │
   ┌────┴────┐
   │ Found ? │
   └────┬────┘
        │No
        ▼
   Show "Not found"
        │
        ▼
       End

        │Yes
        ▼
6. Display Form
        │
        ▼
7. User Updates Data
        │
        ▼
8. Click Save
        │
        ▼
9. Update Database
        │
        ▼
10. Redirect Dashboard
        │
        ▼
       End

==========================================================
SECURITY IMPROVEMENTS
==========================================================

1. Restrict editing to authorized roles:

if (
    !isset($_SESSION['user_id']) ||
    !in_array(
        $_SESSION['role'],
        ['admin','manager','director']
    )
) {
    header("Location: login.php");
    exit();
}

2. Use Prepared Statements instead of
   embedding variables directly into SQL.

3. Validate title and message before update.

4. Check if UPDATE query succeeds before
   redirecting.

========================================================== -->