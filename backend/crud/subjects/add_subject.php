<?php
// ============================================================
//  add_subject.php — Add a New Subject
// ============================================================

session_start();
require '../../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','manager'])) {
    header("Location: ../../../frontend/pages/login.php"); exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name  = mysqli_real_escape_string($conn, trim($_POST['name']));
    $code  = mysqli_real_escape_string($conn, trim($_POST['code']));
    $level = mysqli_real_escape_string($conn, $_POST['level']);
    $fee   = (float)$_POST['fee'];
    $desc  = mysqli_real_escape_string($conn, trim($_POST['description']));

    if (empty($name) || empty($code)) {
        $error = "Name and code are required.";
    } else {
        mysqli_query($conn, "INSERT INTO subjects (name, code, level, fee, description) VALUES ('$name','$code','$level',$fee,'$desc')");
        header("Location: ../../../frontend/pages/dashboard.php?msg=Subject+added");
        exit();
    }
}

$page_title = "Add Subject"; $css_path = "../../../frontend/assets/css/style.css"; $root_path = "../../../"; $active_page = "";
include '../../../frontend/assets/header.php';
?>
<div class="section" style="max-width:520px; margin:0 auto;">
    <h2 class="section-title">📚 Add New Subject</h2>
    <?php if (isset($error)): ?><div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:16px;">❌ <?php echo $error; ?></div><?php endif; ?>
    <div class="card">
        <form method="POST">
            <div class="form-row">
                <div class="form-group"><label>Subject Name *</label><input type="text" name="name" placeholder="e.g. Mathematics" required></div>
                <div class="form-group"><label>Code *</label><input type="text" name="code" placeholder="e.g. MATH-OL" required></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Level</label>
                    <select name="level">
                        <option value="O/L">O/L</option><option value="A/L">A/L</option>
                        <option value="Primary">Primary</option><option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group"><label>Monthly Fee (LKR)</label><input type="number" name="fee" value="0" min="0" step="0.01"></div>
            </div>
            <div class="form-group"><label>Description</label><textarea name="description" placeholder="Brief description..."></textarea></div>
            <button type="submit" class="btn btn-primary">✅ Add Subject</button>
            <a href="../../../frontend/pages/dashboard.php" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
        </form>
    </div>
</div>
<?php mysqli_close($conn); include '../../../frontend/assets/footer.php'; ?>
