<?php
// ============================================================
// edit_user.php
// Purpose: Edit an existing user (admin only)
// ============================================================


// ------------------------------------------------------------
// START SESSION
// ------------------------------------------------------------
session_start();


// ------------------------------------------------------------
// DATABASE CONNECTION
// ------------------------------------------------------------
require '../../config/db.php';


// ------------------------------------------------------------
// AUTHORIZATION CHECK
// ------------------------------------------------------------
// Only admin can edit users
if (
    !isset($_SESSION['user_id']) ||
    $_SESSION['role'] != 'admin'
) {
    header("Location: ../../../frontend/pages/login.php");
    exit();
}


// ------------------------------------------------------------
// GET USER ID
// ------------------------------------------------------------
$id = (int)($_GET['id'] ?? 0);


// ------------------------------------------------------------
// FETCH USER DATA
// ------------------------------------------------------------
$user = $id
    ? mysqli_fetch_assoc(
        mysqli_query(
            $conn,
            "SELECT * FROM users WHERE id=$id"
        )
    )
    : null;


// ------------------------------------------------------------
// ERROR VARIABLE
// ------------------------------------------------------------
$error = '';


// ------------------------------------------------------------
// VALIDATION: USER NOT FOUND
// ------------------------------------------------------------
if (!$user) {
    echo "User not found.";
    exit();
}


// ------------------------------------------------------------
// UPDATE FORM HANDLER
// ------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // --------------------------------------------------------
    // FULL NAME
    // --------------------------------------------------------
    $fullname = mysqli_real_escape_string(
        $conn,
        trim($_POST['full_name'])
    );

    // --------------------------------------------------------
    // EMAIL
    // --------------------------------------------------------
    $email = mysqli_real_escape_string(
        $conn,
        trim($_POST['email'])
    );

    // --------------------------------------------------------
    // PHONE
    // --------------------------------------------------------
    $phone = mysqli_real_escape_string(
        $conn,
        trim($_POST['phone'])
    );

    // --------------------------------------------------------
    // ROLE
    // --------------------------------------------------------
    $role = mysqli_real_escape_string(
        $conn,
        $_POST['role']
    );

    // --------------------------------------------------------
    // STATUS
    // --------------------------------------------------------
    $status = mysqli_real_escape_string(
        $conn,
        $_POST['status']
    );

    // --------------------------------------------------------
    // PASSWORD (OPTIONAL UPDATE)
    // --------------------------------------------------------
    $pass_sql = '';

    if (!empty(trim($_POST['password']))) {

        // Hash new password
        $pass = password_hash(
            trim($_POST['password']),
            PASSWORD_DEFAULT
        );

        // Add password update to SQL
        $pass_sql =
            ", password='" .
            mysqli_real_escape_string($conn, $pass) .
            "'";
    }

    // --------------------------------------------------------
    // UPDATE DATABASE
    // --------------------------------------------------------
    mysqli_query(
        $conn,
        "UPDATE users SET
            full_name='$fullname',
            email='$email',
            phone='$phone',
            role='$role',
            status='$status'
            $pass_sql
         WHERE id=$id"
    );

    // Redirect after update
    header(
        "Location: ../../../frontend/pages/dashboard.php?msg=User+updated"
    );

    exit();
}


// ------------------------------------------------------------
// PAGE SETTINGS
// ------------------------------------------------------------

$page_title = "Edit User";
$css_path   = "../../../frontend/assets/css/style.css";
$root_path  = "../../../";
$active_page = "";

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
        ✏️ Edit User: <?php echo htmlspecialchars($user['full_name']); ?>
    </h2>


    <!-- Form Card -->
    <div class="card">

        <form method="POST">

            <!-- Full Name + Password -->
            <div class="form-row">

                <div class="form-group">

                    <label>
                        Full Name *
                    </label>

                    <input
                        type="text"
                        name="full_name"
                        value="<?php echo htmlspecialchars($user['full_name']); ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label>
                        New Password
                        <small style="color:#94a3b8;">
                            (leave blank = no change)
                        </small>
                    </label>

                    <input
                        type="text"
                        name="password"
                        placeholder="Enter new password"
                    >

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
                        value="<?php echo htmlspecialchars($user['email']); ?>"
                    >

                </div>

                <div class="form-group">

                    <label>
                        Phone
                    </label>

                    <input
                        type="text"
                        name="phone"
                        value="<?php echo htmlspecialchars($user['phone']); ?>"
                    >

                </div>

            </div>


            <!-- Role + Status -->
            <div class="form-row">

                <div class="form-group">

                    <label>
                        Role
                    </label>

                    <select name="role">

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

                            <option
                                value="<?php echo $r; ?>"
                                <?php if ($user['role'] == $r) echo 'selected'; ?>
                            >
                                <?php echo ucfirst($r); ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label>
                        Status
                    </label>

                    <select name="status">

                        <option value="active"
                            <?php if ($user['status'] == 'active') echo 'selected'; ?>
                        >
                            Active
                        </option>

                        <option value="inactive"
                            <?php if ($user['status'] == 'inactive') echo 'selected'; ?>
                        >
                            Inactive
                        </option>

                    </select>

                </div>

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
- User ID from URL

$user
- User record from database

$fullname
- Full name input

$email
- Email input

$phone
- Phone input

$role
- User role

$status
- User status

$pass_sql
- Optional password update SQL string

============================================================
FUNCTION EXPLANATIONS
============================================================

session_start()
- Start session

require()
- Load DB connection

mysqli_fetch_assoc()
- Fetch DB row

mysqli_real_escape_string()
- Prevent SQL injection

trim()
- Remove whitespace

password_hash()
- Hash password securely

mysqli_query()
- Execute SQL

htmlspecialchars()
- Prevent XSS

header()
- Redirect user

exit()
- Stop script

foreach()
- Loop roles list

============================================================
SQL QUERY
============================================================

UPDATE users SET ...
WHERE id=?;

Purpose:
Updates user details

============================================================
PROGRAM FLOW
============================================================

1. Start session
2. Check admin
3. Get user ID
4. Fetch user data
5. Show form
6. Submit update
7. Save to DB
8. Redirect

============================================================
SECURITY IMPROVEMENTS
============================================================

1. Validate email uniqueness

2. Use prepared statements

3. Force strong password rules

4. Log user updates

5. Prevent role escalation attacks

============================================================ */