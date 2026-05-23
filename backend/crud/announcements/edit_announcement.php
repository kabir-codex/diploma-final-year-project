<?php
// edit_announcement.php
session_start();
require '../../config/db.php';
if (!isset($_SESSION['user_id'])) { header("Location: ../../../frontend/pages/login.php"); exit(); }

$id  = (int)($_GET['id'] ?? 0);
$ann = $id ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM announcements WHERE id=$id")) : null;
if (!$ann) { echo "Not found."; exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title    = mysqli_real_escape_string($conn, trim($_POST['title']));
    $message  = mysqli_real_escape_string($conn, trim($_POST['message']));
    $audience = mysqli_real_escape_string($conn, $_POST['audience']);
    mysqli_query($conn, "UPDATE announcements SET title='$title', message='$message', audience='$audience' WHERE id=$id");
    header("Location: ../../../frontend/pages/dashboard.php?msg=Announcement+updated");
    exit();
}

$page_title = "Edit Announcement"; $css_path = "../../../frontend/assets/css/style.css"; $root_path = "../../../"; $active_page = "";
include '../../../frontend/assets/header.php';
?>
<div class="section" style="max-width:520px; margin:0 auto;">
    <h2 class="section-title">✏️ Edit Announcement</h2>
    <div class="card">
        <form method="POST">
            <div class="form-group"><label>Title</label><input type="text" name="title" value="<?php echo htmlspecialchars($ann['title']); ?>" required></div>
            <div class="form-group">
                <label>Audience</label>
                <select name="audience">
                    <?php foreach (['all','students','parents','staff'] as $a): ?>
                        <option value="<?php echo $a; ?>" <?php if ($ann['audience']==$a) echo 'selected'; ?>><?php echo ucfirst($a); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group"><label>Message</label><textarea name="message" required><?php echo htmlspecialchars($ann['message']); ?></textarea></div>
            <button type="submit" class="btn btn-primary">💾 Save</button>
            <a href="../../../frontend/pages/dashboard.php" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
        </form>
    </div>
</div>
<?php mysqli_close($conn); include '../../../frontend/assets/footer.php'; ?>
