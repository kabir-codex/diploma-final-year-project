<?php
// ============================================================
//  login.php — Login Page
//  Users enter their username and password here.
//  If correct, they are sent to the dashboard.
// ============================================================

// Start the session — MUST be the very first thing, before any HTML/output
session_start();

// Bring in $conn (the database connection) — needed to check login details
require '../../backend/config/db.php';

// If the user is ALREADY logged in (session still remembers them),
// don't show the login form again — send them straight to their dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit(); // stop the script here, nothing below should run
}

$error = ""; // will hold an error message IF login fails; stays empty otherwise

// Check if the login form was actually submitted (POST request)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Get what the user typed; trim() removes accidental extra spaces
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Basic validation: both fields must not be empty
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {

        // Escape the username before using it in SQL — prevents SQL injection
        $username_safe = mysqli_real_escape_string($conn, $username);

        // Find the user by USERNAME ONLY. Password is NOT checked in this SQL —
        // it's checked separately below, because passwords are hashed and can't
        // be compared with a plain "=" in SQL.
        $sql    = "SELECT * FROM users WHERE username='$username_safe' AND status='active'";
        $result = mysqli_query($conn, $sql);

        $login_ok = false;

        // If exactly ONE matching row was found, grab it as an array; else null
        $user = ($result && mysqli_num_rows($result) == 1) ? mysqli_fetch_assoc($result) : null;

        // Only proceed to password checking if a matching active user was found
        if ($user) {
            $stored = $user['password']; // the password value currently in the database

            // Bcrypt hashes always start with $2y$, $2a$, or $2b$
            // preg_match() checks if the stored value matches that pattern
            if (preg_match('/^\$2[aby]\$/', $stored)) {
                // Already hashed → use PHP's safe built-in verification function
                $login_ok = password_verify($password, $stored);
            } else {
                // NOT hashed yet (leftover plain-text demo data from database.sql)
                // hash_equals() safely compares two plain strings
                if (hash_equals($stored, $password)) {
                    $login_ok = true;

                    // Upgrade this password to a real bcrypt hash right now,
                    // so it's never stored/compared in plain text again
                    $new_hash = password_hash($password, PASSWORD_DEFAULT);

                    // userID is this table's primary key column name
                    $uid = (int)$user['userID'];

                    // Save the new hashed password back into the database for this user
                    mysqli_query($conn, "UPDATE users SET password='" . mysqli_real_escape_string($conn, $new_hash) . "' WHERE userID=$uid");
                }
            }
        }

        // Based on the result above, either log the user in or show an error
        if ($login_ok) {
            // Save key user info into the SESSION — this IS what "being logged in" means.
            // Every other page in the system checks these $_SESSION values.
            $_SESSION['user_id']   = $user['userID'];   // primary key from the users table
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];     // used later for role-based access control

            header("Location: dashboard.php"); // send them to the router (next file in the recipe)
            exit();
        } else {
            $error = "❌ Wrong username or password. Please try again.";
        }
    }
}

// These 4 variables are REQUIRED by header.php (the shared layout file) before including it
$page_title  = "Login";                                    // shown in the browser tab title
$css_path    = "../../frontend/assets/css/style.css";       // path to the stylesheet from here
$root_path   = "../../";                                    // path back to the project root
$active_page = "";                                          // no nav link highlighted on this page
include '../../frontend/assets/header.php';
?>

<!-- Everything below only matters if login hasn't succeeded yet (form still shown) -->
<div class="login-page">

    <!-- LEFT: Purely decorative branding panel (SVG graphics, no logic) -->
    <div class="login-split-left">
        <svg class="login-illustration" viewBox="0 0 280 280" xmlns="http://www.w3.org/2000/svg">
            <!-- decorative shapes: background circles, an open book, a graduation cap, dots -->
            <circle cx="140" cy="140" r="130" fill="rgba(255,255,255,0.08)"/>
            <circle cx="140" cy="140" r="98" fill="rgba(255,255,255,0.10)"/>
            <path d="M40 150 L140 130 L240 150 L240 195 L140 178 L40 195 Z" fill="#ffffff" opacity="0.95"/>
            <path d="M140 130 L140 178" stroke="#cbd5e1" stroke-width="2"/>
            <path d="M55 158 L130 144" stroke="#cbd5e1" stroke-width="2"/>
            <path d="M55 172 L130 160" stroke="#cbd5e1" stroke-width="2"/>
            <path d="M150 144 L225 158" stroke="#cbd5e1" stroke-width="2"/>
            <path d="M150 160 L225 172" stroke="#cbd5e1" stroke-width="2"/>
            <path d="M140 70 L210 100 L140 130 L70 100 Z" fill="#f59e0b"/>
            <path d="M105 108 L105 132 C105 140 175 140 175 132 L175 108" fill="none" stroke="#f59e0b" stroke-width="4" stroke-linecap="round"/>
            <circle cx="208" cy="100" r="4" fill="#f59e0b"/>
            <path d="M208 100 L208 124" stroke="#f59e0b" stroke-width="3"/>
            <circle cx="208" cy="128" r="5" fill="#f59e0b"/>
            <circle cx="55" cy="90" r="4" fill="#ffffff" opacity="0.7"/>
            <circle cx="230" cy="200" r="5" fill="#ffffff" opacity="0.6"/>
            <circle cx="75" cy="220" r="3" fill="#ffffff" opacity="0.6"/>
        </svg>
        <h1 class="login-brand-title">🎓 Activate Academy</h1>
        <p class="login-brand-tag">Institute Management System</p>
    </div>

    <!-- RIGHT: The actual login form -->
    <div class="login-split-right">
        <div class="login-form-card">
            <h2>Welcome Back</h2>
            <p>Enter your details to sign in to your account</p>

            <!-- Only show this box if $error has text in it -->
            <?php if ($error): ?>
                <div style="background:#fee2e2; color:#991b1b; padding:10px 14px; border-radius:7px; margin-bottom:16px; font-size:0.88rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Form submits back to this same file (login.php) via POST -->
            <form method="POST" action="login.php">
                <div class="form-group">
                    <label>Username</label>
                    <!-- If login failed, re-fill what they typed so they don't retype it.
                         htmlspecialchars() escapes it safely to prevent XSS when echoing it back -->
                    <input type="text" name="username" placeholder="Enter your username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="login_password" placeholder="Enter your password" required>
                    <!-- Checkbox to toggle showing the password as plain text -->
                    <label class="show-password-check">
                        <input type="checkbox" id="login_password_checkbox" onchange="aaTogglePassword()">
                        Show Password
                    </label>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; padding:13px; font-size:1rem; border:none; cursor:pointer;">
                    Login →
                </button>
            </form>
            <p class="login-footnote">Forgot your password? Contact the receptionist.</p>
            <p class="login-footnote"><a href="../../index.php">← Back to Home Page</a></p>
        </div>
    </div>
</div>

<!-- Small JS function: switches the password input between type="password" and type="text" -->
<script>
function aaTogglePassword() {
    var input    = document.getElementById('login_password');
    var checkbox = document.getElementById('login_password_checkbox');
    input.type = checkbox.checked ? 'text' : 'password'; // checked = show as plain text
}
</script>

<?php
// Close the database connection now that we're done with it (good practice)
mysqli_close($conn);

// Include the shared footer (closes </body></html> etc.)
include '../../frontend/assets/footer.php';
?>
