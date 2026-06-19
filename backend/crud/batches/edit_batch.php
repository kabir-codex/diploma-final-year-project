<?php
session_start();
require '../../config/db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','manager','receptionist'])) {
    header("Location: ../../../frontend/pages/login.php"); exit();
}
$id    = (int)($_GET['id'] ?? 0);
$batch = $id ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM batches WHERE id=$id")) : null;
if (!$batch) { echo "Batch not found."; exit(); }

$subjects  = mysqli_query($conn, "SELECT id, name FROM subjects ORDER BY name");
$lecturers = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role='lecturer' ORDER BY full_name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name     = mysqli_real_escape_string($conn, trim($_POST['batch_name']));
    $sub_id   = (int)$_POST['subject_id'];
    $lec_id   = (int)$_POST['lecturer_id'];
    $schedule = mysqli_real_escape_string($conn, trim($_POST['schedule']));
    $room     = mysqli_real_escape_string($conn, trim($_POST['room']));
    $capacity = (int)$_POST['capacity'];
    $status   = mysqli_real_escape_string($conn, $_POST['status']);
    mysqli_query($conn, "UPDATE batches SET batch_name='$name', subject_id=$sub_id, lecturer_id=$lec_id, schedule='$schedule', room='$room', capacity=$capacity, status='$status' WHERE id=$id");
    header("Location: ../../../frontend/pages/dashboard.php?msg=Batch+updated");
    exit();
}

$page_title = "Edit Batch"; $css_path = "../../../frontend/assets/css/style.css"; $root_path = "../../../"; $active_page = "";
include '../../../frontend/assets/header.php';
?>
<div class="section" style="max-width:580px; margin:0 auto;">
    <h2 class="section-title">✏️ Edit Batch: <?php echo htmlspecialchars($batch['batch_name']); ?></h2>
    <div class="card">
        <form method="POST">
            <div class="form-group"><label>Batch Name *</label><input type="text" name="batch_name" value="<?php echo htmlspecialchars($batch['batch_name']); ?>" required></div>
            <div class="form-row">
                <div class="form-group">
                    <label>Subject</label>
                    <select name="subject_id">
                        <?php while ($s = mysqli_fetch_assoc($subjects)): ?>
                            <option value="<?php echo $s['id']; ?>" <?php if ($batch['subject_id']==$s['id']) echo 'selected'; ?>><?php echo htmlspecialchars($s['name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lecturer</label>
                    <select name="lecturer_id">
                        <?php while ($l = mysqli_fetch_assoc($lecturers)): ?>
                            <option value="<?php echo $l['id']; ?>" <?php if ($batch['lecturer_id']==$l['id']) echo 'selected'; ?>><?php echo htmlspecialchars($l['full_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Schedule</label><input type="text" name="schedule" value="<?php echo htmlspecialchars($batch['schedule']); ?>"></div>
                <div class="form-group"><label>Room</label><input type="text" name="room" value="<?php echo htmlspecialchars($batch['room']); ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Capacity</label><input type="number" name="capacity" value="<?php echo $batch['capacity']; ?>"></div>
                <div class="form-group">
                    <label>Status</label>
                    <select name="status">
                        <?php foreach (['active','upcoming','completed'] as $st): ?>
                            <option value="<?php echo $st; ?>" <?php if ($batch['status']==$st) echo 'selected'; ?>><?php echo ucfirst($st); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">💾 Save Changes</button>
            <a href="../../../frontend/pages/dashboard.php" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
        </form>
    </div>
</div>
<?php mysqli_close($conn); include '../../../frontend/assets/footer.php'; ?>
