<?php
// edit_result.php — Edit an exam result
session_start();
require '../../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    header("Location: ../../../frontend/pages/login.php"); exit();
}
$id  = (int)($_GET['id'] ?? 0);
$res = $id ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM results WHERE id=$id")) : null;
if (!$res) { echo "Result not found."; exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $exam_name   = mysqli_real_escape_string($conn, trim($_POST['exam_name']));
    $exam_date   = mysqli_real_escape_string($conn, $_POST['exam_date']);
    $marks       = (int)$_POST['marks'];
    $total_marks = (int)$_POST['total_marks'];
    $grade       = mysqli_real_escape_string($conn, $_POST['grade']);
    $comments    = mysqli_real_escape_string($conn, trim($_POST['comments']));
    mysqli_query($conn, "UPDATE results SET exam_name='$exam_name', exam_date='$exam_date', marks=$marks, total_marks=$total_marks, grade='$grade', comments='$comments' WHERE id=$id");
    header("Location: ../../../frontend/pages/dashboard.php?msg=Result+updated");
    exit();
}

$page_title = "Edit Result"; $css_path = "../../../frontend/assets/css/style.css"; $root_path = "../../../"; $active_page = "";
include '../../../frontend/assets/header.php';
?>
<div class="section" style="max-width:520px; margin:0 auto;">
    <h2 class="section-title">✏️ Edit Exam Result</h2>
    <div class="card">
        <form method="POST">
            <div class="form-group"><label>Exam Name</label><input type="text" name="exam_name" value="<?php echo htmlspecialchars($res['exam_name']); ?>" required></div>
            <div class="form-row">
                <div class="form-group"><label>Date</label><input type="date" name="exam_date" value="<?php echo $res['exam_date']; ?>"></div>
                <div class="form-group">
                    <label>Grade</label>
                    <select name="grade">
                        <?php foreach (['A','A-','B+','B','B-','C+','C','F'] as $g): ?>
                            <option value="<?php echo $g; ?>" <?php if ($res['grade']==$g) echo 'selected'; ?>><?php echo $g; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Marks</label><input type="number" name="marks" value="<?php echo $res['marks']; ?>" min="0"></div>
                <div class="form-group"><label>Out of</label><input type="number" name="total_marks" value="<?php echo $res['total_marks']; ?>" min="1"></div>
            </div>
            <div class="form-group"><label>Comments</label><input type="text" name="comments" value="<?php echo htmlspecialchars($res['comments']); ?>"></div>
            <button type="submit" class="btn btn-primary">💾 Save</button>
            <a href="../../../frontend/pages/dashboard.php" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
        </form>
    </div>
</div>
<?php mysqli_close($conn); include '../../../frontend/assets/footer.php'; ?>
