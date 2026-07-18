<?php
// ============================================================
// edit_enquiry.php
// Purpose: Update enquiry status and notes
// ============================================================


// ------------------------------------------------------------
// START SESSION
// ------------------------------------------------------------
// Start or resume PHP session.
// Enables access to session variables like:
// $_SESSION['user_id'], $_SESSION['role']
session_start();


// ------------------------------------------------------------
// DATABASE CONNECTION
// ------------------------------------------------------------
// Include database connection file.
// Provides $conn (MySQL connection object)
require '../../config/db.php';


// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
// Only these roles can edit enquiries:
// - admin
// - manager
// - receptionist
//
// If user is not logged in or role is not allowed,
// redirect to login page.
if (
    !isset($_SESSION['user_id']) ||
    !in_array(
        $_SESSION['role'],
        ['admin','manager','receptionist']
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
// edit_enquiry.php?id=5
//
// If no ID is provided, default to 0.
$id = (int)($_GET['id'] ?? 0);


// ------------------------------------------------------------
// FETCH ENQUIRY DATA
// ------------------------------------------------------------
// Retrieve enquiry details from database.
//
// mysqli_fetch_assoc()
// returns row as associative array.
$enq = $id
    ? mysqli_fetch_assoc(
        mysqli_query(
            $conn,
            "SELECT * FROM enquiries WHERE enquiryID=$id"
        )
    )
    : null;


// ------------------------------------------------------------
// VALIDATION: NOT FOUND
// ------------------------------------------------------------
// If enquiry does not exist, stop execution.
if (!$enq) {

    echo "Enquiry not found.";

    exit();
}


// ------------------------------------------------------------
// FORM SUBMISSION
// ------------------------------------------------------------
// Runs when user submits the form (POST request).
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --------------------------------------------------------
    // STATUS FIELD
    // --------------------------------------------------------
    // Selected enquiry status from dropdown.
    $status = mysqli_real_escape_string(
        $conn,
        $_POST['status']
    );

    // --------------------------------------------------------
    // NOTES FIELD
    // --------------------------------------------------------
    // Admin/manager notes about the enquiry.
    $notes = mysqli_real_escape_string(
        $conn,
        trim($_POST['notes'])
    );


    // --------------------------------------------------------
    // UPDATE DATABASE
    // --------------------------------------------------------
    mysqli_query(
        $conn,
        "UPDATE enquiries
         SET
            status='$status',
            notes='$notes'
         WHERE enquiryID=$id"
    );


    // Redirect after successful update.
    header(
        "Location: ../../../frontend/pages/dashboard.php?msg=Enquiry+updated"
    );

    exit();
}


// ------------------------------------------------------------
// PAGE SETTINGS
// ------------------------------------------------------------

// Page title shown in browser tab.
$page_title = "Edit Enquiry";

// CSS file path.
$css_path = "../../../frontend/assets/css/style.css";

// Root directory path.
$root_path = "../../../";

// Active navigation item.
$active_page = "";


// Include header file.
include '../../../frontend/assets/header.php';
?>

<!-- ==========================================================
PAGE CONTENT
========================================================== -->

<div
    class="section"
    style="max-width:480px; margin:0 auto;"
>

    <!-- Page Heading -->
    <h2 class="section-title">

        📬 Update Enquiry:

        <?php
        // Display enquiry name safely
        echo htmlspecialchars($enq['name']);
        ?>

    </h2>


    <!-- Main Card -->
    <div class="card">

        <!-- Contact Info -->
        <p style="margin-bottom:6px;">
            <strong>Phone:</strong>
            <?php echo htmlspecialchars($enq['phone']); ?>
        </p>

        <p style="margin-bottom:14px;">
            <strong>Interest:</strong>
            <?php echo htmlspecialchars($enq['interest']); ?>
        </p>


        <!-- Update Form -->
        <form method="POST">

            <!-- -----------------------------------------
                 STATUS DROPDOWN
            ------------------------------------------ -->
            <div class="form-group">

                <label>
                    Status
                </label>

                <select name="status">

                    <?php
                    // Loop through all status options
                    foreach (
                        [
                            'pending',
                            'contacted',
                            'enrolled',
                            'not_interested'
                        ] as $s
                    ):
                    ?>

                        <option
                            value="<?php echo $s; ?>"

                            <?php
                            // Mark current status as selected
                            if ($enq['status'] == $s)
                                echo 'selected';
                            ?>
                        >

                            <?php
                            // Format label:
                            // replace "_" with space
                            echo ucfirst(
                                str_replace('_',' ',$s)
                            );
                            ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <!-- -----------------------------------------
                 NOTES FIELD
            ------------------------------------------ -->
            <div class="form-group">

                <label>
                    Notes
                </label>

                <textarea name="notes">

                    <?php
                    // Safely display existing notes
                    echo htmlspecialchars($enq['notes']);
                    ?>

                </textarea>

            </div>


            <!-- Save Button -->
            <button
                type="submit"
                class="btn btn-primary"
            >
                💾 Update
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


// Include footer file.
include '../../../frontend/assets/footer.php';

?>


<!-- ==========================================================
VARIABLE EXPLANATIONS
==========================================================

$conn
- MySQL database connection.

$id
- Enquiry ID from URL.

$enq
- Array containing enquiry data.

$status
- Updated enquiry status.

$notes
- Updated notes about enquiry.

$s
- Loop variable for status options.

$page_title
- Browser tab title.

$css_path
- CSS file path.

$root_path
- Root project path.

$active_page
- Active menu indicator.

==========================================================
DATABASE QUERIES
==========================================================

1. Get Enquiry

SELECT *
FROM enquiries
WHERE enquiryID = $id;

Purpose:
Fetch existing enquiry details.

----------------------------------------------------------

2. Update Enquiry

UPDATE enquiries
SET
    status='$status',
    notes='$notes'
WHERE enquiryID=$id;

Purpose:
Update enquiry status and notes.

==========================================================
PROGRAM FLOW
==========================================================

1. Start Session
2. Connect Database
3. Check Login
4. Check Role Permission
5. Get Enquiry ID
6. Fetch Enquiry Data
7. Display Form
8. User Updates Data
9. Submit Form
10. Update Database
11. Redirect Dashboard
12. Close Connection

==========================================================
SECURITY IMPROVEMENTS
==========================================================

1. Use prepared statements instead of raw SQL.

2. Validate status values:
   - pending
   - contacted
   - enrolled
   - not_interested

3. Check if enquiry exists before update.

4. Log changes for tracking.

========================================================== -->