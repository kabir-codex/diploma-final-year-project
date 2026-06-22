<?php
// ============================================================
// edit_batch.php
// Purpose: Edit an existing batch's information
// ============================================================


// ------------------------------------------------------------
// START SESSION
// ------------------------------------------------------------
// Start or resume user session.
// Gives access to session variables.
session_start();


// ------------------------------------------------------------
// DATABASE CONNECTION
// ------------------------------------------------------------
// Include database connection file.
// Creates $conn variable.
require '../../config/db.php';


// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
// Only the following roles can edit batches:
// - admin
// - manager
// - receptionist
//
// If user is not logged in OR role is not allowed,
// redirect to login page.
if (
    !isset($_SESSION['user_id']) ||
    !in_array(
        $_SESSION['role'],
        ['admin', 'manager', 'receptionist']
    )
) {
    header("Location: ../../../frontend/pages/login.php");
    exit();
}


// ------------------------------------------------------------
// GET BATCH ID
// ------------------------------------------------------------
// Retrieve batch ID from URL.
//
// Example:
// edit_batch.php?id=5
//
// If ID doesn't exist, use 0.
$id = (int)($_GET['id'] ?? 0);


// ------------------------------------------------------------
// LOAD BATCH DETAILS
// ------------------------------------------------------------
// Retrieve selected batch information.
$batch = $id
    ? mysqli_fetch_assoc(
        mysqli_query(
            $conn,
            "SELECT * FROM batches WHERE id=$id"
        )
    )
    : null;


// ------------------------------------------------------------
// CHECK IF BATCH EXISTS
// ------------------------------------------------------------
// If batch is not found, stop execution.
if (!$batch) {

    echo "Batch not found.";

    exit();
}


// ------------------------------------------------------------
// LOAD SUBJECTS
// ------------------------------------------------------------
// Retrieve all subjects.
// Used in Subject dropdown.
$subjects = mysqli_query(
    $conn,
    "SELECT id, name
     FROM subjects
     ORDER BY name"
);


// ------------------------------------------------------------
// LOAD LECTURERS
// ------------------------------------------------------------
// Retrieve all lecturers.
// Used in Lecturer dropdown.
$lecturers = mysqli_query(
    $conn,
    "SELECT id, full_name
     FROM users
     WHERE role='lecturer'
     ORDER BY full_name"
);


// ------------------------------------------------------------
// FORM SUBMISSION
// ------------------------------------------------------------
// Execute when Save Changes button is clicked.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Batch name
    $name = mysqli_real_escape_string(
        $conn,
        trim($_POST['batch_name'])
    );

    // Subject ID
    $sub_id = (int)$_POST['subject_id'];

    // Lecturer ID
    $lec_id = (int)$_POST['lecturer_id'];

    // Schedule
    $schedule = mysqli_real_escape_string(
        $conn,
        trim($_POST['schedule'])
    );

    // Room
    $room = mysqli_real_escape_string(
        $conn,
        trim($_POST['room'])
    );

    // Capacity
    $capacity = (int)$_POST['capacity'];

    // Status
    $status = mysqli_real_escape_string(
        $conn,
        $_POST['status']
    );


    // --------------------------------------------------------
    // UPDATE BATCH
    // --------------------------------------------------------
    mysqli_query(
        $conn,
        "UPDATE batches
         SET
            batch_name='$name',
            subject_id=$sub_id,
            lecturer_id=$lec_id,
            schedule='$schedule',
            room='$room',
            capacity=$capacity,
            status='$status'
         WHERE id=$id"
    );


    // Redirect after successful update.
    header(
        "Location: ../../../frontend/pages/dashboard.php?msg=Batch+updated"
    );

    exit();
}


// ------------------------------------------------------------
// PAGE SETTINGS
// ------------------------------------------------------------

// Page title
$page_title = "Edit Batch";

// CSS file location
$css_path =
    "../../../frontend/assets/css/style.css";

// Root directory path
$root_path = "../../../";

// Active navigation item
$active_page = "";


// Include common page header.
include '../../../frontend/assets/header.php';

?>

<!-- ==========================================================
PAGE CONTENT
========================================================== -->

<div
    class="section"
    style="max-width:580px; margin:0 auto;"
>

    <!-- Page Heading -->
    <h2 class="section-title">

        ✏️ Edit Batch:

        <?php
        // Display batch name safely.
        echo htmlspecialchars(
            $batch['batch_name']
        );
        ?>

    </h2>


    <!-- Main Card -->
    <div class="card">

        <!-- Edit Form -->
        <form method="POST">

            <!-- ---------------------------------------------
                 BATCH NAME
            ---------------------------------------------- -->
            <div class="form-group">

                <label>
                    Batch Name *
                </label>

                <input
                    type="text"
                    name="batch_name"
                    value="<?php echo htmlspecialchars($batch['batch_name']); ?>"
                    required
                >

            </div>


            <!-- ---------------------------------------------
                 SUBJECT + LECTURER
            ---------------------------------------------- -->
            <div class="form-row">

                <!-- Subject Dropdown -->
                <div class="form-group">

                    <label>
                        Subject
                    </label>

                    <select name="subject_id">

                        <?php
                        while (
                            $s = mysqli_fetch_assoc($subjects)
                        ):
                        ?>

                            <option
                                value="<?php echo $s['id']; ?>"

                                <?php
                                if (
                                    $batch['subject_id']
                                    == $s['id']
                                )
                                    echo 'selected';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $s['name']
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- Lecturer Dropdown -->
                <div class="form-group">

                    <label>
                        Lecturer
                    </label>

                    <select name="lecturer_id">

                        <?php
                        while (
                            $l = mysqli_fetch_assoc($lecturers)
                        ):
                        ?>

                            <option
                                value="<?php echo $l['id']; ?>"

                                <?php
                                if (
                                    $batch['lecturer_id']
                                    == $l['id']
                                )
                                    echo 'selected';
                                ?>
                            >

                                <?php
                                echo htmlspecialchars(
                                    $l['full_name']
                                );
                                ?>

                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>

            </div>


            <!-- ---------------------------------------------
                 SCHEDULE + ROOM
            ---------------------------------------------- -->
            <div class="form-row">

                <div class="form-group">

                    <label>
                        Schedule
                    </label>

                    <input
                        type="text"
                        name="schedule"
                        value="<?php echo htmlspecialchars($batch['schedule']); ?>"
                    >

                </div>

                <div class="form-group">

                    <label>
                        Room
                    </label>

                    <input
                        type="text"
                        name="room"
                        value="<?php echo htmlspecialchars($batch['room']); ?>"
                    >

                </div>

            </div>


            <!-- ---------------------------------------------
                 CAPACITY + STATUS
            ---------------------------------------------- -->
            <div class="form-row">

                <div class="form-group">

                    <label>
                        Capacity
                    </label>

                    <input
                        type="number"
                        name="capacity"
                        value="<?php echo $batch['capacity']; ?>"
                    >

                </div>

                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <select name="status">

                        <?php
                        foreach (
                            ['active','upcoming','completed']
                            as $st
                        ):
                        ?>

                            <option
                                value="<?php echo $st; ?>"

                                <?php
                                if (
                                    $batch['status']
                                    == $st
                                )
                                    echo 'selected';
                                ?>
                            >

                                <?php
                                echo ucfirst($st);
                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <!-- Save Button -->
            <button
                type="submit"
                class="btn btn-primary"
            >
                💾 Save Changes
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
- Database connection object.

$id
- Batch ID from URL.

$batch
- Array containing selected batch details.

$subjects
- Result set containing all subjects.

$lecturers
- Result set containing all lecturers.

$name
- Updated batch name.

$sub_id
- Updated subject ID.

$lec_id
- Updated lecturer ID.

$schedule
- Updated schedule.

$room
- Updated room.

$capacity
- Updated student capacity.

$status
- Updated batch status.

$s
- Subject record inside while loop.

$l
- Lecturer record inside while loop.

$st
- Status value inside foreach loop.

$page_title
- Browser page title.

$css_path
- CSS file path.

$root_path
- Root directory path.

$active_page
- Active navigation item.

==========================================================
DATABASE QUERIES
==========================================================

1. Get Batch

SELECT *
FROM batches
WHERE id = $id;

Purpose:
Retrieve existing batch information.

----------------------------------------------------------

2. Get Subjects

SELECT id, name
FROM subjects
ORDER BY name;

Purpose:
Load subjects for dropdown.

----------------------------------------------------------

3. Get Lecturers

SELECT id, full_name
FROM users
WHERE role='lecturer'
ORDER BY full_name;

Purpose:
Load lecturers for dropdown.

----------------------------------------------------------

4. Update Batch

UPDATE batches
SET
    batch_name='$name',
    subject_id=$sub_id,
    lecturer_id=$lec_id,
    schedule='$schedule',
    room='$room',
    capacity=$capacity,
    status='$status'
WHERE id=$id;

Purpose:
Save modified batch information.

==========================================================
PROGRAM FLOW
==========================================================

1. Start Session
2. Connect Database
3. Verify Login
4. Verify User Role
5. Get Batch ID
6. Load Batch Data
7. Load Subjects
8. Load Lecturers
9. Display Edit Form
10. User Updates Data
11. Click Save Changes
12. Update Database
13. Redirect Dashboard
14. Close Database Connection

==========================================================
SECURITY IMPROVEMENTS
==========================================================

1. Use Prepared Statements.

2. Validate Capacity > 0.

3. Verify Subject Exists.

4. Verify Lecturer Exists.

5. Restrict Status Values:
   - active
   - upcoming
   - completed

6. Check Update Success Before Redirecting.

========================================================== -->