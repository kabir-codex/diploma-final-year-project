<?php
// ============================================================
//  add_user.php — Add a New User
//  Only admins can access this page.
// ============================================================

session_start();
require '../../config/db.php';

// Only admins can add users
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../frontend/pages/login.php");
    exit();
}

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and clean inputs
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = mysqli_real_escape_string($conn, trim($_POST['password']));
    $fullname = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone    = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $role     = mysqli_real_escape_string($conn, $_POST['role']);
    $status   = mysqli_real_escape_string($conn, $_POST['status']);

    if (empty($username) || empty($password) || empty($fullname) || empty($role)) {
        $error = "Username, password, name and role are required.";
    } else {
        // Check if username already taken
        $chk = mysqli_query($conn, "SELECT id FROM users WHERE username='$username'");
        if (mysqli_num_rows($chk) > 0) {
            $error = "Username already exists. Choose a different one.";
        } else {
            // Insert the new user
            mysqli_query($conn, "INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES ('$username','$password','$fullname','$email','$phone','$role','$status')");
            header("Location: ../../../frontend/pages/dashboard.php?msg=User+added+successfully");
            exit();
        }
    }
}

$page_title = "Add User"; $css_path = "../../../frontend/assets/css/style.css"; $root_path = "../../../"; $active_page = "";
include '../../../frontend/assets/header.php';
?>
<div class="section" style="max-width:560px; margin:0 auto;">
    <h2 class="section-title">➕ Add New User</h2>
    <?php if ($error): ?><div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:16px;">❌ <?php echo $error; ?></div><?php endif; ?>
    <div class="card">
        <form method="POST">
            <div class="form-row">
                <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" required></div>
                <div class="form-group"><label>Username *</label><input type="text" name="username" required></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Password *</label><input type="text" name="password" required></div>
                <div class="form-group">
                    <label>Role *</label>
                    <select name="role" required>
                        <?php foreach (['admin','manager','director','lecturer','receptionist','student','parent'] as $r): ?>
                            <option value="<?php echo $r; ?>"><?php echo ucfirst($r); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Email</label><input type="email" name="email"></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone"></div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status"><option value="active">Active</option><option value="inactive">Inactive</option></select>
            </div>
            <button type="submit" class="btn btn-primary">✅ Add User</button>
            <a href="../../../frontend/pages/dashboard.php" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
        </form>
    </div>
</div>
<?php mysqli_close($conn); include '../../../frontend/assets/footer.php'; ?>
