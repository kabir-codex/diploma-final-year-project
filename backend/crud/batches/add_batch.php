<?php
// ============================================================
// add_batch.php — Create a New Batch
// Purpose: Create a new class batch and assign a subject,
// lecturer, schedule, room, capacity, and start date.
// ============================================================


// ------------------------------------------------------------
// START SESSION
// ------------------------------------------------------------
// Resume or create a session.
// Allows access to session variables such as:
// $_SESSION['user_id']
// $_SESSION['role']
session_start();


// ------------------------------------------------------------
// DATABASE CONNECTION
// ------------------------------------------------------------
// Include database configuration file.
// Creates database connection variable: $conn
require '../../config/db.php';


// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
// Only these roles can create batch:
// - admin
// - manager
// - receptionist
//
// If user is not logged in or does not have permission,
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
// LOAD SUBJECTS
// ------------------------------------------------------------
// Retrieve all subject from database.
//
// Used to populate Subject dropdown list.
$subject = mysqli_query(
    $conn,
    "SELECT subjectID, name, code
     FROM subject
     ORDER BY name"
);


// ------------------------------------------------------------
// LOAD LECTURERS
// ------------------------------------------------------------
// Retrieve all active lecturers.
//
// Used to populate Lecturer dropdown list.
$lecturers = mysqli_query(
    $conn,
    "SELECT userID, full_name
     FROM users
     WHERE role='lecturer'
     AND status='active'
     ORDER BY full_name"
);


// ------------------------------------------------------------
// FORM SUBMISSION
// ------------------------------------------------------------
// Execute only when form is submitted.
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --------------------------------------------------------
    // GET FORM VALUES
    // --------------------------------------------------------

    // Batch name
    $name = mysqli_real_escape_string(
        $conn,
        trim($_POST['batch_name'])
    );

    // Selected subject ID
    $sub_id = (int)$_POST['subject_id'];

    // Selected lecturer ID
    $lec_id = (int)$_POST['lecturer_id'];

    // Class schedule
    $schedule = mysqli_real_escape_string(
        $conn,
        trim($_POST['schedule'])
    );

    // Classroom or hall
    $room = mysqli_real_escape_string(
        $conn,
        trim($_POST['room'])
    );

    // Maximum student capacity
    $capacity = (int)$_POST['capacity'];

    // Batch starting date
    $start = mysqli_real_escape_string(
        $conn,
        $_POST['start_date']
    );

    // Current batch status
    $status = mysqli_real_escape_string(
        $conn,
        $_POST['status']
    );


    // --------------------------------------------------------
    // VALIDATION
    // --------------------------------------------------------
    // Ensure required fields are provided.
    if (
        empty($name) ||
        !$sub_id ||
        !$lec_id
    ) {

        $error =
            "Batch name, subject and lecturer are required.";

    } else {

        // ----------------------------------------------------
        // INSERT NEW BATCH
        // ----------------------------------------------------
        mysqli_query(
            $conn,
            "INSERT INTO batch
            (
                batch_name,
                subject_id,
                lecturer_id,
                schedule,
                room,
                capacity,
                status,
                start_date
            )
            VALUES
            (
                '$name',
                $sub_id,
                $lec_id,
                '$schedule',
                '$room',
                $capacity,
                '$status',
                '$start'
            )"
        );


        // Redirect to dashboard after successful creation.
        header(
            "Location: ../../../frontend/pages/dashboard.php?msg=Batch+created"
        );

        exit();
    }
}


// ------------------------------------------------------------
// PAGE CONFIGURATION
// ------------------------------------------------------------

// Browser title
$page_title = "Add Batch";

// CSS file path
$css_path =
    "../../../frontend/assets/css/style.css";

// Root directory path
$root_path = "../../../";

// Active menu item
$active_page = "";


// Include page header
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
        🗓️ Create New Batch
    </h2>


    <!-- ------------------------------------------------------
         ERROR MESSAGE
    ------------------------------------------------------- -->
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


    <!-- Main Form Container -->
    <div class="card">

        <form method="POST">

            <!-- ------------------------------------------------
                 BATCH NAME
            ------------------------------------------------- -->
            <div class="form-group">

                <label>
                    Batch Name *
                </label>

                <input
                    type="text"
                    name="batch_name"
                    placeholder="e.g. Math O/L Batch A"
                    required
                >

            </div>


            <!-- ------------------------------------------------
                 SUBJECT + LECTURER
            ------------------------------------------------- -->
            <div class="form-row">

                <!-- Subject Dropdown -->
                <div class="form-group">

                    <label>
                        Subject *
                    </label>

                    <select
                        name="subject_id"
                        required
                    >

                        <option value="">
                            -- Select Subject --
                        </option>

                        <?php
                        // Loop through subject
                        while (
                            $s = mysqli_fetch_assoc($subject)
                        ):
                        ?>

                            <option
                                value="<?php echo $s['subjectID']; ?>"
                            >
                                <?php
                                echo htmlspecialchars(
                                    $s['name']
                                );
                                ?>
                                (
                                <?php
                                echo $s['code'];
                                ?>
                                )
                            </option>

                        <?php endwhile; ?>

                    </select>

                </div>


                <!-- Lecturer Dropdown -->
                <div class="form-group">

                    <label>
                        Lecturer *
                    </label>

                    <select
                        name="lecturer_id"
                        required
                    >

                        <option value="">
                            -- Select Lecturer --
                        </option>

                        <?php
                        while (
                            $l = mysqli_fetch_assoc($lecturers)
                        ):
                        ?>

                            <option
                                value="<?php echo $l['userID']; ?>"
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


            <!-- ------------------------------------------------
                 SCHEDULE + ROOM
            ------------------------------------------------- -->
            <div class="form-row">

                <div class="form-group">

                    <label>
                        Schedule
                    </label>

                    <input
                        type="text"
                        name="schedule"
                        placeholder="e.g. Sat & Sun 9:00 AM"
                    >

                </div>

                <div class="form-group">

                    <label>
                        Room
                    </label>

                    <input
                        type="text"
                        name="room"
                        placeholder="e.g. Room 101"
                    >

                </div>

            </div>


            <!-- ------------------------------------------------
                 CAPACITY + START DATE
            ------------------------------------------------- -->
            <div class="form-row">

                <div class="form-group">

                    <label>
                        Capacity
                    </label>

                    <input
                        type="number"
                        name="capacity"
                        value="30"
                        min="1"
                    >

                </div>

                <div class="form-group">

                    <label>
                        Start Date
                    </label>

                    <input
                        type="date"
                        name="start_date"
                        value="<?php echo date('Y-m-d'); ?>"
                    >

                </div>

            </div>


            <!-- ------------------------------------------------
                 STATUS
            ------------------------------------------------- -->
            <div class="form-group">

                <label>
                    Status
                </label>

                <select name="status">

                    <option value="active">
                        Active
                    </option>

                    <option value="upcoming">
                        Upcoming
                    </option>

                    <option value="completed">
                        Completed
                    </option>

                </select>

            </div>


            <!-- Create Button -->
            <button
                type="submit"
                class="btn btn-primary"
            >
                ✅ Create Batch
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


// Include page footer
include '../../../frontend/assets/footer.php';

?>


<!-- ==========================================================
VARIABLE EXPLANATIONS
==========================================================

$conn
- MySQL database connection.

$_SESSION['user_id']
- Logged-in user's ID.

$_SESSION['role']
- Logged-in user's role.

$subject
- Query result containing all subject.

$lecturers
- Query result containing active lecturers.

$name
- Batch name entered by user.

$sub_id
- Selected subject ID.

$lec_id
- Selected lecturer ID.

$schedule
- Class schedule.

$room
- Assigned room.

$capacity
- Maximum number of students.

$start
- Batch start date.

$status
- Current batch status.

$error
- Validation error message.

$page_title
- Browser page title.

$css_path
- CSS file location.

$root_path
- Root project directory.

$active_page
- Navigation indicator.

$s
- Single subject row inside while loop.

$l
- Single lecturer row inside while loop.

==========================================================
DATABASE QUERIES
==========================================================

1. Load Subjects

SELECT subjectID, name, code
FROM subject
ORDER BY name;

Purpose:
Retrieve all available subject.


2. Load Lecturers

SELECT userID, full_name
FROM users
WHERE role='lecturer'
AND status='active'
ORDER BY full_name;

Purpose:
Retrieve all active lecturers.


3. Insert Batch

INSERT INTO batch
(
    batch_name,
    subject_id,
    lecturer_id,
    schedule,
    room,
    capacity,
    status,
    start_date
)
VALUES
(
    '$name',
    $sub_id,
    $lec_id,
    '$schedule',
    '$room',
    $capacity,
    '$status',
    '$start'
);

Purpose:
Creates a new batch record.

==========================================================
PROGRAM FLOW
==========================================================

1. Start Session
2. Connect Database
3. Check User Permission
4. Load Subjects
5. Load Lecturers
6. Display Form
7. User Enters Data
8. Click Create Batch
9. Validate Inputs
10. Insert New Batch
11. Redirect Dashboard
12. Close Connection

==========================================================
SECURITY IMPROVEMENTS
==========================================================

1. Use Prepared Statements.

2. Validate Capacity > 0.

3. Verify Subject ID exists.

4. Verify Lecturer ID exists.

5. Restrict status values to:
   active, upcoming, completed

========================================================== -->