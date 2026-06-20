<?php
// ============================================================
//  login.php — Login Page
//  Users enter their username and password here.
//  If correct, they are sent to the dashboard.
// ============================================================

session_start();
require '../../backend/config/db.php';

// If already logged in, go straight to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = ""; // Will hold any login error message

// When the form is submitted (POST request)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Check that both fields are filled
    if (empty($username) || empty($password)) {
        $error = "Please enter both username and password.";
    } else {
        // Escape the input to prevent SQL injection
        $username_safe = mysqli_real_escape_string($conn, $username);

        // Look up the user by username only — the password is checked in PHP below,
        // never compared directly in the SQL query.
        $sql    = "SELECT * FROM users WHERE username='$username_safe' AND status='active'";
        $result = mysqli_query($conn, $sql);

        $login_ok = false;
        $user     = ($result && mysqli_num_rows($result) == 1) ? mysqli_fetch_assoc($result) : null;

        if ($user) {
            $stored = $user['password'];

            // password_hash() output always starts with $2y$, $2a$, or $2b$ (bcrypt).
            // If the stored value already looks like a hash, verify against it normally.
            if (preg_match('/^\$2[aby]\$/', $stored)) {
                $login_ok = password_verify($password, $stored);
            } else {
                // Legacy plain-text password (e.g. demo data from database.sql).
                // Compare directly, and if it matches, transparently upgrade it to
                // a proper bcrypt hash so it never has to be checked in plain text again.
                if (hash_equals($stored, $password)) {
                    $login_ok  = true;
                    $new_hash  = password_hash($password, PASSWORD_DEFAULT);
                    $uid       = (int)$user['id'];
                    mysqli_query($conn, "UPDATE users SET password='" . mysqli_real_escape_string($conn, $new_hash) . "' WHERE id=$uid");
                }
            }
        }

        if ($login_ok) {
            // User found — save their info in the session
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role']      = $user['role'];

            // Redirect to dashboard
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "❌ Wrong username or password. Please try again.";
        }
    }
}

$page_title  = "Login";
$css_path    = "../../frontend/assets/css/style.css";
$root_path   = "../../";
$active_page = "";
include '../../frontend/assets/header.php';
?>

<!-- Login Page Layout -->
<div class="login-page">
    <div class="login-card-wrap">

        <!-- Title -->
        <div style="text-align:center; margin-bottom:24px;">
            <h1 style="color:white; font-size:1.8rem; font-weight:800;">🎓 Activate Academy</h1>
            <p style="color:#bfdbfe;">Institute Management System – Login Portal</p>
        </div>

        <!-- LOGIN FORM -->
        <div class="login-box">
            <div class="login-icon-badge">🔐</div>
            <h2 style="text-align:center;">Welcome Back</h2>
            <p style="text-align:center;">Enter your credentials to access your portal.</p>

            <!-- Show error if login failed -->
            <?php if ($error): ?>
                <div style="background:#fee2e2; color:#991b1b; padding:10px 14px; border-radius:7px; margin-bottom:16px; font-size:0.88rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label>Username</label>
                    <!-- Keep the typed username if login fails -->
                    <input type="text" name="username" placeholder="Enter your username"
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" id="login_password" placeholder="Enter your password" required>
                    <label class="show-password-check">
                        <input type="checkbox" id="login_password_checkbox" onchange="aaTogglePassword()">
                        Show Password
                    </label>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; padding:13px; font-size:1rem; border:none; cursor:pointer;">
                    Login →
                </button>
            </form>
            <p style="font-size:0.75rem; color:#94a3b8; text-align:center; margin-top:12px;">Forgot your password? Contact the receptionist.</p>
        </div>

        <div style="text-align:center; margin-top:16px;">
            <a href="../../index.php" style="color:#bfdbfe; font-size:0.88rem; text-decoration:none;">← Back to Home Page</a>
        </div>
    </div>
</div>

<script>
function aaTogglePassword() {
    var input    = document.getElementById('login_password');
    var checkbox = document.getElementById('login_password_checkbox');
    input.type = checkbox.checked ? 'text' : 'password';
}
</script>

<?php
mysqli_close($conn);
include '../../frontend/assets/footer.php';
?>
