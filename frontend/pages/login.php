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
        $username = mysqli_real_escape_string($conn, $username);
        $password = mysqli_real_escape_string($conn, $password);

        // Look for a matching active user in the database
        $sql    = "SELECT * FROM users WHERE username='$username' AND password='$password' AND status='active'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) == 1) {
            // User found — save their info in the session
            $user = mysqli_fetch_assoc($result);
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
    <div style="width:100%; max-width:900px;">

        <!-- Title -->
        <div style="text-align:center; margin-bottom:24px;">
            <h1 style="color:white; font-size:1.8rem; font-weight:800;">🎓 Activate Academy</h1>
            <p style="color:#bfdbfe;">Institute Management System – Login Portal</p>
        </div>

        <!-- Two-column layout: login form + demo accounts -->
        <!-- Need when using demo table in the Login_Ui
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px;" class="login-grid"> -->
        
        <div style="display:grid; grid-template-columns:1fr; gap:24px;" class="login-grid">

            <!-- LOGIN FORM -->
            <div class="login-box">
                <h2>Sign In</h2>
                <p>Enter your credentials to access your portal.</p>

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
                        <input type="password" name="password" placeholder="Enter your password" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%; padding:13px; font-size:1rem; border:none; cursor:pointer;">
                        Login →
                    </button>
                </form>
                <p style="font-size:0.75rem; color:#94a3b8; text-align:center; margin-top:12px;">Forgot your password? Contact the receptionist.</p>
            </div>
            
            
            <!-- DEMO ACCOUNTS TABLE Commented out for now, but can be useful for testing and demos
            <div class="login-box">
                <h2>Demo Accounts</h2>
                <p>Use these to test different portals.</p>
                <div class="table-wrapper" style="box-shadow:none; margin-top:0; border-radius:8px;">
                    <table style="font-size:0.82rem;">
                        <thead><tr><th>Username</th><th>Password</th><th>Role</th></tr></thead>
                        <tbody>
                            <tr><td>admin</td><td>admin123</td><td>Admin</td></tr>
                            <tr><td>manager</td><td>manager123</td><td>Manager</td></tr>
                            <tr><td>director</td><td>director123</td><td>Director</td></tr>
                            <tr><td>lec_math</td><td>math123</td><td>Math Lecturer</td></tr>
                            <tr><td>lec_eng</td><td>eng123</td><td>Eng Lecturer</td></tr>
                            <tr><td>receptionist</td><td>recep123</td><td>Receptionist</td></tr>
                            <tr><td>student1</td><td>kabir123</td><td>Student</td></tr>
                            <tr><td>student2</td><td>ishfaq123</td><td>Student</td></tr>
                            <tr><td>parent1</td><td>parent123</td><td>Parent</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
            -->

        <div style="text-align:center; margin-top:16px;">
            <a href="../../index.php" style="color:#bfdbfe; font-size:0.88rem; text-decoration:none;">← Back to Home Page</a>
        </div>
    </div>
</div>

<style>@media (max-width:768px) { .login-grid { grid-template-columns:1fr !important; } }</style>

<?php
mysqli_close($conn);
include '../../frontend/assets/footer.php';
?>
