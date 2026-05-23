<?php
// ============================================================
//  edit_subject.php — Edit an Existing Subject
// ============================================================

session_start();
require '../../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','manager'])) {
    header("Location: ../../../frontend/pages/login.php"); exit();
}

$id      = (int)($_GET['id'] ?? 0);
$subject = $id ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM subjects WHERE id=$id")) : null;
if (!$subject) { echo "Subject not found."; exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name  = mysqli_real_escape_string($conn, trim($_POST['name']));
    $code  = mysqli_real_escape_string($conn, trim($_POST['code']));
    $level = mysqli_real_escape_string($conn, $_POST['level']);
    $fee   = (float)$_POST['fee'];
    $desc  = mysqli_real_escape_string($conn, trim($_POST['description']));
    mysqli_query($conn, "UPDATE subjects SET name='$name', code='$code', level='$level', fee=$fee, description='$desc' WHERE id=$id");
    header("Location: ../../../frontend/pages/dashboard.php?msg=Subject+updated");
    exit();
}

$page_title = "Edit Subject"; $css_path = "../../../frontend/assets/css/style.css"; $root_path = "../../../"; $active_page = "";
include '../../../frontend/assets/header.php';
?>
<div class="section" style="max-width:520px; margin:0 auto;">
    <h2 class="section-title">✏️ Edit Subject</h2>
    <div class="card">
        <form method="POST">
            <div class="form-row">
                <div class="form-group"><label>Name *</label><input type="text" name="name" value="<?php echo htmlspecialchars($subject['name']); ?>" required></div>
                <div class="form-group"><label>Code *</label><input type="text" name="code" value="<?php echo htmlspecialchars($subject['code']); ?>" required></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Level</label>
                    <select name="level">
                        <?php foreach (['O/L','A/L','Primary','Other'] as $l): ?>
                            <option value="<?php echo $l; ?>" <?php if ($subject['level']==$l) echo 'selected'; ?>><?php echo $l; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Fee (LKR)</label><input type="number" name="fee" value="<?php echo $subject['fee']; ?>" min="0" step="0.01"></div>
            </div>
            <div class="form-group"><label>Description</label><textarea name="description"><?php echo htmlspecialchars($subject['description']); ?></textarea></div>
            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
            <a href="../../../frontend/pages/dashboard.php" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
        </form>
    </div>
</div>
<?php mysqli_close($conn); include '../../../frontend/assets/footer.php'; ?>
