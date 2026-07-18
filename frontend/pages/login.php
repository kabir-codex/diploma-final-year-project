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
                    $uid       = (int)$user['userID'];
                    mysqli_query($conn, "UPDATE users SET password='" . mysqli_real_escape_string($conn, $new_hash) . "' WHERE userID=$uid");
                }
            }
        }

        if ($login_ok) {
            // User found — save their info in the session
            $_SESSION['user_id']   = $user['userID'];
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

    <!-- LEFT: Branding / Illustration panel -->
    <div class="login-split-left">
        <svg class="login-illustration" viewBox="0 0 280 280" xmlns="http://www.w3.org/2000/svg">
            <circle cx="140" cy="140" r="130" fill="rgba(255,255,255,0.08)"/>
            <circle cx="140" cy="140" r="98" fill="rgba(255,255,255,0.10)"/>
            <!-- Open book -->
            <path d="M40 150 L140 130 L240 150 L240 195 L140 178 L40 195 Z" fill="#ffffff" opacity="0.95"/>
            <path d="M140 130 L140 178" stroke="#cbd5e1" stroke-width="2"/>
            <path d="M55 158 L130 144" stroke="#cbd5e1" stroke-width="2"/>
            <path d="M55 172 L130 160" stroke="#cbd5e1" stroke-width="2"/>
            <path d="M150 144 L225 158" stroke="#cbd5e1" stroke-width="2"/>
            <path d="M150 160 L225 172" stroke="#cbd5e1" stroke-width="2"/>
            <!-- Graduation cap -->
            <path d="M140 70 L210 100 L140 130 L70 100 Z" fill="#f59e0b"/>
            <path d="M105 108 L105 132 C105 140 175 140 175 132 L175 108" fill="none" stroke="#f59e0b" stroke-width="4" stroke-linecap="round"/>
            <circle cx="208" cy="100" r="4" fill="#f59e0b"/>
            <path d="M208 100 L208 124" stroke="#f59e0b" stroke-width="3"/>
            <circle cx="208" cy="128" r="5" fill="#f59e0b"/>
            <!-- Decorative dots -->
            <circle cx="55" cy="90" r="4" fill="#ffffff" opacity="0.7"/>
            <circle cx="230" cy="200" r="5" fill="#ffffff" opacity="0.6"/>
            <circle cx="75" cy="220" r="3" fill="#ffffff" opacity="0.6"/>
        </svg>
        <h1 class="login-brand-title">🎓 Activate Academy</h1>
        <p class="login-brand-tag">Institute Management System</p>
    </div>

    <!-- RIGHT: Login form panel -->
    <div class="login-split-right">
        <div class="login-form-card">
            <h2>Welcome Back</h2>
            <p>Enter your details to sign in to your account</p>

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
            <p class="login-footnote">Forgot your password? Contact the receptionist.</p>
            <p class="login-footnote"><a href="../../index.php">← Back to Home Page</a></p>
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

