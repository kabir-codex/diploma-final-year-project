<?php
// ============================================================
//  edit_user.php — Edit an Existing User
// ============================================================

session_start();
require '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../frontend/pages/login.php"); exit();
}

$id    = (int)($_GET['id'] ?? 0);
$user  = $id ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$id")) : null;
$error = '';

if (!$user) { echo "User not found."; exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone    = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $role     = mysqli_real_escape_string($conn, $_POST['role']);
    $status   = mysqli_real_escape_string($conn, $_POST['status']);
    // Only update password if a new one is entered
    $pass_sql = '';
    if (!empty(trim($_POST['password']))) {
        $pass = mysqli_real_escape_string($conn, trim($_POST['password']));
        $pass_sql = ", password='$pass'";
    }
    mysqli_query($conn, "UPDATE users SET full_name='$fullname', email='$email', phone='$phone', role='$role', status='$status' $pass_sql WHERE id=$id");
    header("Location: ../../../frontend/pages/dashboard.php?msg=User+updated");
    exit();
}

$page_title = "Edit User"; $css_path = "../../../frontend/assets/css/style.css"; $root_path = "../../../"; $active_page = "";
include '../../../frontend/assets/header.php';
?>
<div class="section" style="max-width:560px; margin:0 auto;">
    <h2 class="section-title">✏️ Edit User: <?php echo htmlspecialchars($user['full_name']); ?></h2>
    <div class="card">
        <form method="POST">
            <div class="form-row">
                <div class="form-group"><label>Full Name *</label><input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required></div>
                <div class="form-group"><label>New Password <small style="color:#94a3b8;">(leave blank = no change)</small></label><input type="text" name="password" placeholder="Enter new password"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Email</label><input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>"></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Role</label>
                    <select name="role">
                        <?php foreach (['admin','manager','director','lecturer','receptionist','student','parent'] as $r): ?>
                            <option value="<?php echo $r; ?>" <?php if ($user['role']==$r) echo 'selected'; ?>><?php echo ucfirst($r); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <option value="active"   <?php if ($user['status']=='active')   echo 'selected'; ?>>Active</option>
                        <option value="inactive" <?php if ($user['status']=='inactive') echo 'selected'; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
            <a href="../../../frontend/pages/dashboard.php" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
        </form>
    </div>
</div>
<?php mysqli_close($conn); include '../../../frontend/assets/footer.php'; ?>
