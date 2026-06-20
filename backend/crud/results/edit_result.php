<?php
// edit_result.php — Edit an exam result
session_start();
require '../../config/db.php';
require '../../config/helpers.php';
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
    $total_marks = 100; // Total marks is fixed at 100 for all results
    $grade       = mysqli_real_escape_string($conn, calc_grade($marks));
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
                    <label>Grade <small style="color:#64748b;">(calculated automatically)</small></label>
                    <input type="text" id="res_grade_display" value="<?php echo htmlspecialchars($res['grade']); ?>" readonly disabled style="background:#f1f5f9; cursor:not-allowed; font-weight:700;">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Marks (out of 100)</label><input type="number" id="res_marks" name="marks" value="<?php echo $res['marks']; ?>" min="0" max="100" oninput="aaUpdateGrade()"></div>
                <div class="form-group">
                    <label>Total Marks</label>
                    <input type="number" value="100" readonly disabled style="background:#f1f5f9; cursor:not-allowed;">
                </div>
            </div>
            <div class="form-group"><label>Comments</label><input type="text" name="comments" value="<?php echo htmlspecialchars($res['comments']); ?>"></div>
            <button type="submit" class="btn btn-primary">💾 Save</button>
            <a href="../../../frontend/pages/dashboard.php" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
        </form>
    </div>
</div>
<script>
function aaCalcGrade(marks) {
    if (marks === '' || isNaN(marks)) return '';
    marks = Number(marks);
    if (marks >= 85) return 'A+';
    if (marks >= 70) return 'A';
    if (marks >= 65) return 'A-';
    if (marks >= 60) return 'B+';
    if (marks >= 55) return 'B';
    if (marks >= 50) return 'B-';
    if (marks >= 45) return 'C+';
    if (marks >= 40) return 'C';
    if (marks >= 35) return 'C-';
    if (marks >= 30) return 'D+';
    if (marks >= 25) return 'D';
    return 'E';
}
function aaUpdateGrade() {
    document.getElementById('res_grade_display').value = aaCalcGrade(document.getElementById('res_marks').value);
}
</script>
<?php mysqli_close($conn); include '../../../frontend/assets/footer.php'; ?>
