<?php
// ============================================================
//  add_batch.php — Create a New Batch
// ============================================================

session_start();
require '../../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','manager','receptionist'])) {
    header("Location: ../../../frontend/pages/login.php"); exit();
}

$subjects  = mysqli_query($conn, "SELECT id, name, code FROM subjects ORDER BY name");
$lecturers = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role='lecturer' AND status='active' ORDER BY full_name");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name     = mysqli_real_escape_string($conn, trim($_POST['batch_name']));
    $sub_id   = (int)$_POST['subject_id'];
    $lec_id   = (int)$_POST['lecturer_id'];
    $schedule = mysqli_real_escape_string($conn, trim($_POST['schedule']));
    $room     = mysqli_real_escape_string($conn, trim($_POST['room']));
    $capacity = (int)$_POST['capacity'];
    $start    = mysqli_real_escape_string($conn, $_POST['start_date']);
    $status   = mysqli_real_escape_string($conn, $_POST['status']);

    if (empty($name) || !$sub_id || !$lec_id) {
        $error = "Batch name, subject and lecturer are required.";
    } else {
        mysqli_query($conn, "INSERT INTO batches (batch_name, subject_id, lecturer_id, schedule, room, capacity, status, start_date) VALUES ('$name',$sub_id,$lec_id,'$schedule','$room',$capacity,'$status','$start')");
        header("Location: ../../../frontend/pages/dashboard.php?msg=Batch+created");
        exit();
    }
}

$page_title = "Add Batch"; $css_path = "../../../frontend/assets/css/style.css"; $root_path = "../../../"; $active_page = "";
include '../../../frontend/assets/header.php';
?>
<div class="section" style="max-width:580px; margin:0 auto;">
    <h2 class="section-title">🗓️ Create New Batch</h2>
    <?php if (isset($error)): ?><div style="background:#fee2e2;color:#991b1b;padding:12px;border-radius:8px;margin-bottom:16px;">❌ <?php echo $error; ?></div><?php endif; ?>
    <div class="card">
        <form method="POST">
            <div class="form-group"><label>Batch Name *</label><input type="text" name="batch_name" placeholder="e.g. Math O/L Batch A" required></div>
            <div class="form-row">
                <div class="form-group">
                    <label>Subject *</label>
                    <select name="subject_id" required>
                        <option value="">-- Select Subject --</option>
                        <?php while ($s = mysqli_fetch_assoc($subjects)): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?> (<?php echo $s['code']; ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lecturer *</label>
                    <select name="lecturer_id" required>
                        <option value="">-- Select Lecturer --</option>
                        <?php while ($l = mysqli_fetch_assoc($lecturers)): ?>
                            <option value="<?php echo $l['id']; ?>"><?php echo htmlspecialchars($l['full_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Schedule</label><input type="text" name="schedule" placeholder="e.g. Sat & Sun 9:00 AM"></div>
                <div class="form-group"><label>Room</label><input type="text" name="room" placeholder="e.g. Room 101"></div>
            </div>
            <div class="form-row">
                <div class="form-group"><label>Capacity</label><input type="number" name="capacity" value="30" min="1"></div>
                <div class="form-group"><label>Start Date</label><input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>"></div>
            </div>
            <div class="form-group">
                <label>Status</label>
                <select name="status">
                    <option value="active">Active</option>
                    <option value="upcoming">Upcoming</option>
                    <option value="completed">Completed</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">✅ Create Batch</button>
            <a href="../../../frontend/pages/dashboard.php" class="btn btn-outline" style="margin-left:8px;">Cancel</a>
        </form>
    </div>
</div>
<?php mysqli_close($conn); include '../../../frontend/assets/footer.php'; ?>
