<?php
// ============================================================
//  edit_enquiry.php — Update Enquiry Status
// ============================================================

session_start();
require '../../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','manager','receptionist'])) {
    header("Location: ../../../frontend/pages/login.php"); exit();
}

$id  = (int)($_GET['id'] ?? 0);
$enq = $id ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM enquiries WHERE id=$id")) : null;
if (!$enq) { echo "Enquiry not found."; exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $notes  = mysqli_real_escape_string($conn, trim($_POST['notes']));
    mysqli_query($conn, "UPDATE enquiries SET status='$status', notes='$notes' WHERE id=$id");
    header("Location: ../../../frontend/pages/dashboard.php?msg=Enquiry+updated");
    exit();
}

$page_title = "Edit Enquiry"; $css_path = "../../../frontend/assets/css/style.css"; $root_path = "../../../"; $active_page = "";
include '../../../frontend/assets/header.php';
?>
<div class="section" style="max-width:480px; margin:0 auto;">
    <h2 class="section-title">📬 Update Enquiry: <?php echo htmlspecialchars($enq['name']); ?></h2>
    <div class="card">
        <p style="margin-bottom:6px;"><strong>Phone:</strong> <?php echo htmlspecialchars($enq['phone']); ?></p>
        <p style="margin-bottom:14px;"><strong>Interest:</strong> <?php echo htmlspecialchars($enq['interest']); ?></p>
        <form method="POST">
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <?php foreach (['pending','contacted','enrolled','not_interested'] as $s): ?>
                        <option value="<?php echo $s; ?>" <?php if ($enq['status']==$s) echo 'selected'; ?>><?php echo ucfirst(str_replace('_',' ',$s)); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Notes</label><textarea name="notes"><?php echo htmlspecialchars($enq['notes']); ?></textarea></div>
            <button type="submit" class="btn btn-primary">💾 Update</button>
            <a href="../../../frontend/pages/dashboard.php" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
        </form>
    </div>
</div>
<?php mysqli_close($conn); include '../../../frontend/assets/footer.php'; ?>
