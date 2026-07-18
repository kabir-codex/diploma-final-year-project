<?php
// ============================================================
// add_user.php
// Purpose: Create a new system user (admin only)
// ============================================================


// ------------------------------------------------------------
// START SESSION
// ------------------------------------------------------------
// Enables session handling:
// $_SESSION['user_id'], $_SESSION['role']
session_start();


// ------------------------------------------------------------
// DATABASE CONNECTION
// ------------------------------------------------------------
// Provides $conn (MySQL connection)
require '../../config/db.php';


// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
// Only ADMIN can create users.
//
// If unauthorized → redirect to login page
if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] != 'admin'
) {
    header("Location: ../../../frontend/pages/login.php");
    exit();
}


// ------------------------------------------------------------
// INITIAL MESSAGES
// ------------------------------------------------------------
// Stores error or success messages
$error = $success = '';


// ------------------------------------------------------------
// FORM HANDLER
// ------------------------------------------------------------
// Runs when form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --------------------------------------------------------
    // INPUT COLLECTION
    // --------------------------------------------------------

    // Username
    $username = mysqli_real_escape_string(
        $conn,
        trim($_POST['username'])
    );

    // Raw password (before hashing)
    $password_raw = trim($_POST['password']);

    // Hashed password (secure storage)
    $password = mysqli_real_escape_string(
        $conn,
        password_hash($password_raw, PASSWORD_DEFAULT)
    );

    // Full name
    $fullname = mysqli_real_escape_string(
        $conn,
        trim($_POST['full_name'])
    );

    // Email
    $email = mysqli_real_escape_string(
        $conn,
        trim($_POST['email'])
    );

    // Phone
    $phone = mysqli_real_escape_string(
        $conn,
        trim($_POST['phone'])
    );

    // Role
    $role = mysqli_real_escape_string(
        $conn,
        $_POST['role']
    );

    // Status
    $status = mysqli_real_escape_string(
        $conn,
        $_POST['status']
    );


    // --------------------------------------------------------
    // VALIDATION CHECK
    // --------------------------------------------------------
    if (
        empty($username) ||
        empty($password_raw) ||
        empty($fullname) ||
        empty($role)
    ) {

        $error =
            "Username, password, name and role are required.";

    } else {

        // ----------------------------------------------------
        // CHECK DUPLICATE USERNAME
        // ----------------------------------------------------
        $chk = mysqli_query(
            $conn,
            "SELECT userID FROM users WHERE username='$username'"
        );

        if (mysqli_num_rows($chk) > 0) {

            $error =
                "Username already exists. Choose a different one.";

        } else {

            // ------------------------------------------------
            // INSERT NEW USER + MATCHING SUBTYPE ROW
            // ------------------------------------------------
            // Every role has its own subtype table (student, lecturer,
            // parent, receptionist, manager, admin, director) holding
            // extra fields for that role. Both inserts must succeed
            // together, so we wrap them in a transaction: if the
            // subtype insert fails, the users insert is undone too,
            // instead of leaving a user with no matching row.
            mysqli_begin_transaction($conn);

            $insert_ok = mysqli_query(
                $conn,
                "INSERT INTO users
                (
                    username,
                    password,
                    full_name,
                    email,
                    phone,
                    role,
                    status
                )
                VALUES
                (
                    '$username',
                    '$password',
                    '$fullname',
                    '$email',
                    '$phone',
                    '$role',
                    '$status'
                )"
            );

            if ($insert_ok) {

                // The new user's id, needed to create the matching
                // subtype row (subtype's own ID = users.userID, same value).
                $new_user_id = mysqli_insert_id($conn);

                // Map each role to its subtype table AND that table's
                // own primary key column name (each subtype table names
                // its primary key differently, e.g. studentID, lecturerID).
                $subtype_tables = [
                    'student'      => ['table' => 'student',      'pk' => 'studentID'],
                    'lecturer'     => ['table' => 'lecturer',     'pk' => 'lecturerID'],
                    'parent'       => ['table' => 'parent',       'pk' => 'parentID'],
                    'receptionist' => ['table' => 'receptionist', 'pk' => 'receptionistID'],
                    'manager'      => ['table' => 'manager',      'pk' => 'managerID'],
                    'admin'        => ['table' => 'admin',        'pk' => 'adminID'],
                    'director'     => ['table' => 'director',     'pk' => 'directorID'],
                ];

                if (isset($subtype_tables[$role])) {

                    $subtype_table = $subtype_tables[$role]['table'];
                    $subtype_pk    = $subtype_tables[$role]['pk'];

                    $insert_ok = mysqli_query(
                        $conn,
                        "INSERT INTO $subtype_table ($subtype_pk) VALUES ($new_user_id)"
                    );
                }
            }

            if ($insert_ok) {
                mysqli_commit($conn);

                // Redirect on success
                header(
                    "Location: ../../../frontend/pages/dashboard.php?msg=User+added+successfully"
                );

                exit();

            } else {
                // Something failed (e.g. the subtype insert) — undo
                // the users insert too, so we never end up with a
                // user that has no matching subtype row.
                mysqli_rollback($conn);

                $error =
                    "Could not create user. Please try again.";
            }
        }
    }
}


// ------------------------------------------------------------
// PAGE SETTINGS
// ------------------------------------------------------------

// Page title
$page_title = "Add User";

// CSS file path
$css_path = "../../../frontend/assets/css/style.css";

// Root path
$root_path = "../../../";

// Active menu item
$active_page = "";


// Include header layout
include '../../../frontend/assets/header.php';
?>


<!-- ==========================================================
PAGE UI
========================================================== -->

<div
    class="section"
    style="max-width:560px; margin:0 auto;"
>

    <!-- Title -->
    <h2 class="section-title">
        ➕ Add New User
    </h2>


    <!-- Error Message -->
    <?php if ($error): ?>
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

            <!-- Full Name + Username -->
            <div class="form-row">

                <div class="form-group">

                    <label>
                        Full Name *
                    </label>

                    <input
                        type="text"
                        name="full_name"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        Username *
                    </label>

                    <input
                        type="text"
                        name="username"
                        required
                    >

                </div>

            </div>


            <!-- Password + Role -->
            <div class="form-row">

                <div class="form-group">

                    <label>
                        Password *
                    </label>

                    <input
                        type="text"
                        name="password"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        Role *
                    </label>

                    <select
                        name="role"
                        required
                    >

                        <?php foreach (
                            [
                                'admin',
                                'manager',
                                'director',
                                'lecturer',
                                'receptionist',
                                'student',
                                'parent'
                            ] as $r
                        ): ?>

                            <option value="<?php echo $r; ?>">
                                <?php echo ucfirst($r); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

            </div>


            <!-- Email + Phone -->
            <div class="form-row">

                <div class="form-group">

                    <label>
                        Email
                    </label>

                    <input
                        type="email"
                        name="email"
                    >

                </div>

                <div class="form-group">

                    <label>
                        Phone
                    </label>

                    <input
                        type="text"
                        name="phone"
                    >

                </div>

            </div>


            <!-- Status -->
            <div class="form-group">

                <label>
                    Status
                </label>

                <select name="status">

                    <option value="active">
                        Active
                    </option>

                    <option value="inactive">
                        Inactive
                    </option>

                </select>

            </div>


            <!-- Submit -->
            <button
                type="submit"
                class="btn btn-primary"
            >
                ✅ Add User
            </button>


            <!-- Cancel -->
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




/* ============================================================
VARIABLE EXPLANATIONS
============================================================

$conn
- Database connection object

$username
- Login username

$password_raw
- Plain password input

$password
- Hashed password (bcrypt)

$fullname
- Full name of user

$email
- Email address

$phone
- Phone number

$role
- User role (admin, lecturer, etc.)

$status
- Account status (active/inactive)

$error
- Error message

$chk
- Username duplicate check result

============================================================
FUNCTION EXPLANATIONS
============================================================

session_start()
- Starts session

require()
- Loads DB connection

trim()
- Removes whitespace

mysqli_real_escape_string()
- Prevents SQL injection

password_hash()
- Securely hashes password

mysqli_query()
- Executes SQL query

mysqli_num_rows()
- Counts query results

header()
- Redirects browser

exit()
- Stops script

ucfirst()
- Capitalizes first letter

htmlspecialchars()
- Prevents XSS

============================================================
SQL QUERY
============================================================

INSERT INTO users
(username, password, full_name, email, phone, role, status)
VALUES (...)

Purpose:
Creates new system user

============================================================
PROGRAM FLOW
============================================================

1. Start Session
2. Check Admin Role
3. Load Form
4. User submits data
5. Validate input
6. Check duplicate username
7. Hash password
8. Insert user
9. Redirect dashboard

============================================================
SECURITY IMPROVEMENTS
============================================================

1. Force strong passwords

2. Use prepared statements

3. Add email uniqueness check

4. Log user creation events

5. Avoid storing password in logs

============================================================ 
*/

?>