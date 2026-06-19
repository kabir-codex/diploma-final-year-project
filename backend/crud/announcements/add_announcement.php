<?php
// ============================================================
//  add_announcement.php — Post a New Announcement
// ============================================================

session_start();
require '../../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','manager','director','lecturer'])) {
    header("Location: ../../../frontend/pages/login.php"); exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title    = mysqli_real_escape_string($conn, trim($_POST['title']));
    $message  = mysqli_real_escape_string($conn, trim($_POST['message']));
    $audience = mysqli_real_escape_string($conn, $_POST['audience']);
    $date     = date('Y-m-d');
    $by       = $_SESSION['user_id'];

    if (empty($title) || empty($message)) {
        $error = "Title and message are required.";
    } else {
        mysqli_query($conn, "INSERT INTO announcements (title, message, audience, post_date, posted_by) VALUES ('$title','$message','$audience','$date',$by)");
        header("Location: ../../../frontend/pages/dashboard.php?msg=Announcement+posted");
        exit();
    }
}

$page_title = "Post Announcement"; $css_path = "../../../frontend/assets/css/style.css"; $root_path = "../../../"; $active_page = "";
include '../../../frontend/assets/header.php';
?>
<div class="section" style="max-width:520px; margin:0 auto;">
    <h2 class="section-title">📢 Post New Announcement</h2>
    <?php if (isset($error)): ?><div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:16px;">❌ <?php echo $error; ?></div><?php endif; ?>
    <div class="card">
        <form method="POST">
            <div class="form-group"><label>Title *</label><input type="text" name="title" required placeholder="e.g. Exam on Saturday"></div>
            <div class="form-group">
                <label>Audience</label>
                <select name="audience">
                    <option value="all">All Users</option>
                    <option value="students">Students Only</option>
                    <option value="parents">Parents Only</option>
                    <option value="staff">Staff Only</option>
                </select>
            </div>
            <div class="form-group"><label>Message *</label><textarea name="message" required placeholder="Type your announcement here..."></textarea></div>
            <button type="submit" class="btn btn-primary">📤 Post Announcement</button>
            <a href="../../../frontend/pages/dashboard.php" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
        </form>
    </div>
</div>
<?php mysqli_close($conn); include '../../../frontend/assets/footer.php'; ?>
