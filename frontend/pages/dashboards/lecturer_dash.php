<?php
// ============================================================
//  lecturer_dash.php — Lecturer Dashboard
//  Lecturers can: mark attendance, upload exam results,
//  award performance points, share class links,
//  upload study materials, and post announcements.
// ============================================================

// Note: delete_ann and delete_link are handled in dashboard.php BEFORE HTML output.

// --- MESSAGE VARIABLES ---
$ann_msg  = '';
$link_msg = '';
$att_msg  = '';
$res_msg  = '';
$pts_msg  = '';
$mat_msg  = '';

// Show material upload success/error from redirect
if (isset($_GET['mat_success'])) $mat_msg = "<div style='background:#dcfce7; color:#166534; padding:10px; border-radius:7px; margin-bottom:14px;'>✅ " . htmlspecialchars($_GET['mat_success']) . "</div>";
if (isset($_GET['mat_error']))   $mat_msg = "<div style='background:#fee2e2; color:#991b1b; padding:10px; border-radius:7px; margin-bottom:14px;'>❌ " . htmlspecialchars($_GET['mat_error']) . "</div>";

// --- ANNOUNCEMENT: Post ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_announcement'])) {
    $ann_title    = mysqli_real_escape_string($conn, trim($_POST['ann_title']));
    $ann_message  = mysqli_real_escape_string($conn, trim($_POST['ann_message']));
    $ann_audience = mysqli_real_escape_string($conn, $_POST['ann_audience']);
    $ann_date     = date('Y-m-d');
    if (empty($ann_title) || empty($ann_message)) {
        $ann_msg = "<div style='background:#fee2e2; color:#991b1b; padding:10px; border-radius:7px; margin-bottom:14px;'>❌ Title and message are required.</div>";
    } else {
        mysqli_query($conn, "INSERT INTO announcements (title, message, audience, post_date, posted_by) VALUES ('$ann_title', '$ann_message', '$ann_audience', '$ann_date', $user_id)");
        $ann_msg = "<div style='background:#dcfce7; color:#166534; padding:10px; border-radius:7px; margin-bottom:14px;'>✅ Announcement posted!</div>";
    }
}

// --- CLASS LINK: Save ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_class_link'])) {
    $link_title    = mysqli_real_escape_string($conn, trim($_POST['link_title']));
    $link_url      = mysqli_real_escape_string($conn, trim($_POST['link_url']));
    $link_date     = mysqli_real_escape_string($conn, $_POST['link_date']);
    $link_batch_id = (int)$_POST['link_batch_id'];
    if (empty($link_title) || empty($link_url) || empty($link_date) || $link_batch_id <= 0) {
        $link_msg = "<div style='background:#fee2e2; color:#991b1b; padding:10px; border-radius:7px; margin-bottom:14px;'>❌ All fields are required.</div>";
    } else {
        $ok = mysqli_query($conn, "INSERT INTO class_links (lecturer_id, batch_id, title, link_url, class_date) VALUES ($user_id, $link_batch_id, '$link_title', '$link_url', '$link_date')");
        $link_msg = $ok
            ? "<div style='background:#dcfce7; color:#166534; padding:10px; border-radius:7px; margin-bottom:14px;'>✅ Class link saved!</div>"
            : "<div style='background:#fee2e2; color:#991b1b; padding:10px; border-radius:7px; margin-bottom:14px;'>❌ Error: " . mysqli_error($conn) . "</div>";
    }
}

// --- ATTENDANCE: Mark ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {
    $att_batch = (int)$_POST['att_batch_id'];
    $att_date  = mysqli_real_escape_string($conn, $_POST['att_date']);
    foreach ($_POST['att_status'] as $sid => $status) {
        $sid    = (int)$sid;
        $status = mysqli_real_escape_string($conn, $status);
        // Check if attendance for this student/batch/date already exists
        $chk = mysqli_query($conn, "SELECT id FROM attendance WHERE student_id=$sid AND batch_id=$att_batch AND attend_date='$att_date'");
        if (mysqli_num_rows($chk) > 0) {
            $existing = mysqli_fetch_assoc($chk);
            mysqli_query($conn, "UPDATE attendance SET status='$status' WHERE id=" . $existing['id']);
        } else {
            mysqli_query($conn, "INSERT INTO attendance (student_id, batch_id, attend_date, status, marked_by) VALUES ($sid, $att_batch, '$att_date', '$status', $user_id)");
        }
    }
    $att_msg = "<div style='background:#dcfce7; color:#166534; padding:10px; border-radius:7px; margin-bottom:14px;'>✅ Attendance saved!</div>";
}

// --- RESULT: Upload ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_result'])) {
    $res_batch   = (int)$_POST['res_batch_id'];
    $res_student = (int)$_POST['res_student_id'];
    $exam_name   = mysqli_real_escape_string($conn, $_POST['exam_name']);
    $exam_date   = mysqli_real_escape_string($conn, $_POST['exam_date']);
    $marks       = (int)$_POST['marks'];
    $total_marks = 100; // Total marks is fixed at 100 for all results
    $grade       = mysqli_real_escape_string($conn, calc_grade($marks));
    $comments    = mysqli_real_escape_string($conn, $_POST['comments']);
    $ok = mysqli_query($conn, "INSERT INTO results (student_id, batch_id, exam_name, exam_date, marks, total_marks, grade, comments, uploaded_by) VALUES ($res_student, $res_batch, '$exam_name', '$exam_date', $marks, $total_marks, '$grade', '$comments', $user_id)");
    $res_msg = $ok
        ? "<div style='background:#dcfce7; color:#166534; padding:10px; border-radius:7px; margin-bottom:14px;'>✅ Result uploaded!</div>"
        : "<div style='background:#fee2e2; color:#991b1b; padding:10px; border-radius:7px; margin-bottom:14px;'>❌ Error: " . mysqli_error($conn) . "</div>";
}

// --- PERFORMANCE POINTS: Award ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['award_points'])) {
    $pt_student = (int)$_POST['pt_student_id'];
    $pt_batch   = (int)$_POST['pt_batch_id'];
    $pt_points  = (int)$_POST['pt_points'];
    $pt_reason  = mysqli_real_escape_string($conn, $_POST['pt_reason']);
    $pt_date    = date('Y-m-d');
    if (!$pt_student || $pt_points <= 0) {
        $pts_msg = "<div style='background:#fee2e2; color:#991b1b; padding:10px; border-radius:7px; margin-bottom:14px;'>❌ Select a student and enter valid points.</div>";
    } else {
        $ok = mysqli_query($conn, "INSERT INTO performance_points (student_id, awarded_by, batch_id, points, reason, award_date) VALUES ($pt_student, $user_id, $pt_batch, $pt_points, '$pt_reason', '$pt_date')");
        $pts_msg = $ok
            ? "<div style='background:#dcfce7; color:#166534; padding:10px; border-radius:7px; margin-bottom:14px;'>✅ Points awarded!</div>"
            : "<div style='background:#fee2e2; color:#991b1b; padding:10px; border-radius:7px; margin-bottom:14px;'>❌ Error: " . mysqli_error($conn) . "</div>";
    }
}

// --- LOAD DATA ---

// This lecturer's batches
$batches_result = mysqli_query($conn, "SELECT b.*, s.name AS subject_name FROM batches b JOIN subjects s ON b.subject_id = s.id WHERE b.lecturer_id = $user_id ORDER BY b.batch_name");
$batch_rows = [];
while ($br = mysqli_fetch_assoc($batches_result)) $batch_rows[] = $br;

// Which batch is selected for attendance
$selected_batch_id = !empty($batch_rows) ? $batch_rows[0]['id'] : 0;
if (isset($_POST['att_batch_id'])) $selected_batch_id = (int)$_POST['att_batch_id'];

// Students in the selected batch
$students_result = mysqli_query($conn, "SELECT u.id, u.full_name FROM enrollments e JOIN users u ON e.student_id = u.id WHERE e.batch_id = $selected_batch_id AND e.status = 'active' ORDER BY u.full_name");

// Attendance records this lecturer has marked, across all their batches (most recent first)
$my_attendance_records = mysqli_query($conn, "
    SELECT a.id, a.attend_date, a.status, b.id AS batch_id, b.batch_name, u.full_name AS student_name
    FROM attendance a
    JOIN batches b ON a.batch_id = b.id
    JOIN users u ON a.student_id = u.id
    WHERE a.marked_by = $user_id
    ORDER BY a.attend_date DESC, b.batch_name, u.full_name
");

// All students in this lecturer's batches (for dropdowns)
$all_students = mysqli_query($conn, "SELECT DISTINCT u.id, u.full_name FROM enrollments e JOIN users u ON e.student_id = u.id JOIN batches b ON e.batch_id = b.id WHERE b.lecturer_id = $user_id ORDER BY u.full_name");

// Recently uploaded results
$my_results = mysqli_query($conn, "SELECT r.*, u.full_name AS student_name, b.batch_name FROM results r JOIN users u ON r.student_id = u.id JOIN batches b ON r.batch_id = b.id WHERE r.uploaded_by = $user_id ORDER BY r.id DESC LIMIT 10");

// Recently awarded points
$my_points = mysqli_query($conn, "SELECT pp.*, u.full_name AS student_name, b.batch_name FROM performance_points pp JOIN users u ON pp.student_id = u.id LEFT JOIN batches b ON pp.batch_id = b.id WHERE pp.awarded_by = $user_id ORDER BY pp.id DESC LIMIT 10");

// Class links uploaded by this lecturer
$my_links = mysqli_query($conn, "SELECT cl.*, b.batch_name FROM class_links cl LEFT JOIN batches b ON cl.batch_id = b.id WHERE cl.lecturer_id = $user_id ORDER BY cl.class_date DESC");

// Study materials uploaded by this lecturer
$uploader_name = mysqli_real_escape_string($conn, $full_name);
$my_materials  = mysqli_query($conn, "SELECT sm.*, b.batch_name FROM study_materials sm LEFT JOIN batches b ON sm.batch_id = b.id WHERE sm.uploaded_by = '$uploader_name' ORDER BY sm.created_at DESC");

// Announcements posted by this lecturer
$my_announcements = mysqli_query($conn, "SELECT * FROM announcements WHERE posted_by = $user_id ORDER BY created_at DESC");
?>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header"><h3>🎓 Lecturer Portal</h3><p><?php echo $full_name; ?></p></div>
        <nav class="sidebar-nav">
            <a href="#batches"       class="active"><span class="sidebar-icon">🗓️</span> My Batches</a>
            <a href="#attendance">                  <span class="sidebar-icon">✅</span> Attendance</a>
            <a href="#results">                     <span class="sidebar-icon">📊</span> Upload Results</a>
            <a href="#my_results">                  <span class="sidebar-icon">📋</span> View Results</a>
            <a href="#points">                      <span class="sidebar-icon">⭐</span> Award Points</a>
            <a href="#classlinks">                  <span class="sidebar-icon">🔗</span> Class Links</a>
            <a href="#materials">                   <span class="sidebar-icon">📁</span> Study Materials</a>
            <a href="#announcements">               <span class="sidebar-icon">📢</span> Announcements</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <h1 class="dashboard-title">Lecturer Portal</h1>
        <p class="dashboard-subtitle">Welcome, <?php echo $full_name; ?>.</p>

        <!-- MY BATCHES TABLE -->
        <div id="batches" class="panel">
            <div class="panel-title">🗓️ My Batches</div>
            <div class="table-wrapper"><table>
                <thead><tr><th>Batch Name</th><th>Subject</th><th>Schedule</th><th>Room</th><th>Status</th></tr></thead>
                <tbody>
                <?php if (empty($batch_rows)): ?>
                    <tr><td colspan="5" style="text-align:center; color:#64748b;">No batches assigned yet.</td></tr>
                <?php else: foreach ($batch_rows as $b):
                    $badge = $b['status'] == 'active' ? 'badge-green' : ($b['status'] == 'upcoming' ? 'badge-yellow' : 'badge-gray');
                ?>
                    <tr>
                        <td><?php echo $b['batch_name']; ?></td>
                        <td><?php echo $b['subject_name']; ?></td>
                        <td><?php echo $b['schedule']; ?></td>
                        <td><?php echo $b['room']; ?></td>
                        <td><span class="badge <?php echo $badge; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table></div>
        </div>

        <!-- MARK ATTENDANCE -->
        <div id="attendance" class="panel">
            <div class="panel-title">✅ Mark Attendance</div>
            <?php echo $att_msg; ?>
            <form method="POST" action="dashboard.php#attendance">
                <div class="form-row">
                    <div class="form-group">
                        <label>Select Batch</label>
                        <!-- When batch changes, form auto-submits to reload students for that batch -->
                        <select name="att_batch_id" onchange="this.form.submit()">
                            <?php foreach ($batch_rows as $b): ?>
                                <option value="<?php echo $b['id']; ?>" <?php if ($selected_batch_id == $b['id']) echo 'selected'; ?>>
                                    <?php echo $b['batch_name']; ?> – <?php echo $b['subject_name']; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="att_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                </div>

                <?php if ($students_result && mysqli_num_rows($students_result) > 0): ?>
                <div class="table-wrapper"><table>
                    <thead><tr><th>Student Name</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php while ($s = mysqli_fetch_assoc($students_result)): ?>
                        <tr>
                            <td><?php echo $s['full_name']; ?></td>
                            <td>
                                <select name="att_status[<?php echo $s['id']; ?>]">
                                    <option value="present">✅ Present</option>
                                    <option value="absent">❌ Absent</option>
                                    <option value="late">⏰ Late</option>
                                </select>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table></div>
                <button type="submit" name="mark_attendance" class="btn btn-primary" style="margin-top:14px;">💾 Save Attendance</button>
                <?php else: ?>
                    <p style="color:#64748b;">No students enrolled in this batch.</p>
                <?php endif; ?>
            </form>

            <!-- SAVED ATTENDANCE RECORDS -->
            <div style="margin-top:28px;">
                <p style="font-weight:600; color:#1a3a5c; margin-bottom:10px;">My Saved Attendance Records</p>

                <div class="form-row" style="margin-bottom:14px;">
                    <div class="form-group">
                        <label>Filter by Batch</label>
                        <select id="att_record_batch_filter">
                            <option value="">All Batches</option>
                            <?php foreach ($batch_rows as $b): ?>
                                <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['batch_name']); ?> – <?php echo htmlspecialchars($b['subject_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Search Student</label>
                        <input type="text" id="att_record_search" placeholder="Type a student name...">
                    </div>
                </div>

                <div class="table-wrapper"><table id="att_records_table">
                    <thead><tr><th>Batch Name</th><th>Student Name</th><th>Date</th><th>Attendance Status</th></tr></thead>
                    <tbody>
                    <?php if (!$my_attendance_records || mysqli_num_rows($my_attendance_records) == 0): ?>
                        <tr><td colspan="4" style="text-align:center; color:#64748b;">No attendance records saved yet.</td></tr>
                    <?php else: while ($ar = mysqli_fetch_assoc($my_attendance_records)):
                        $status_badge = $ar['status'] == 'present' ? 'badge-green' : ($ar['status'] == 'absent' ? 'badge-red' : 'badge-yellow');
                        $status_icon  = $ar['status'] == 'present' ? '✅' : ($ar['status'] == 'absent' ? '❌' : '⏰');
                    ?>
                        <tr data-batch-id="<?php echo $ar['batch_id']; ?>" data-student-name="<?php echo htmlspecialchars(strtolower($ar['student_name'])); ?>">
                            <td><?php echo htmlspecialchars($ar['batch_name']); ?></td>
                            <td><?php echo htmlspecialchars($ar['student_name']); ?></td>
                            <td style="font-size:0.85rem;"><?php echo date('d M Y', strtotime($ar['attend_date'])); ?></td>
                            <td><span class="badge <?php echo $status_badge; ?>"><?php echo $status_icon . ' ' . ucfirst($ar['status']); ?></span></td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table></div>
                <p id="att_records_empty" style="text-align:center; color:#64748b; padding:14px; display:none;">No matching attendance records.</p>
            </div>
        </div>

        <script>
        (function() {
            var batchFilter = document.getElementById('att_record_batch_filter');
            var search      = document.getElementById('att_record_search');
            var table       = document.getElementById('att_records_table');
            if (!batchFilter || !search || !table) return;
            var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr[data-batch-id]'));

            function applyFilter() {
                var batchVal = batchFilter.value;
                var term     = search.value.trim().toLowerCase();
                var visible  = 0;
                rows.forEach(function(row) {
                    var matchesBatch = !batchVal || row.getAttribute('data-batch-id') === batchVal;
                    var matchesName  = !term || row.getAttribute('data-student-name').indexOf(term) !== -1;
                    var show = matchesBatch && matchesName;
                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });
                var emptyMsg = document.getElementById('att_records_empty');
                if (emptyMsg) emptyMsg.style.display = (rows.length > 0 && visible === 0) ? '' : 'none';
            }
            batchFilter.addEventListener('change', applyFilter);
            search.addEventListener('input', applyFilter);
        })();
        </script>

        <!-- UPLOAD EXAM RESULT -->
        <div id="results" class="panel">
            <div class="panel-title">📊 Upload Exam Result</div>
            <?php echo $res_msg; ?>
            <form method="POST" action="dashboard.php#results">
                <div class="form-row">
                    <div class="form-group">
                        <label>Batch</label>
                        <select name="res_batch_id" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($batch_rows as $b): ?>
                                <option value="<?php echo $b['id']; ?>"><?php echo $b['batch_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Student</label>
                        <select name="res_student_id" required>
                            <option value="">-- Select --</option>
                            <?php
                            // Re-fetch since $all_students pointer may be used later
                            $stu_dd = mysqli_query($conn, "SELECT DISTINCT u.id, u.full_name FROM enrollments e JOIN users u ON e.student_id = u.id JOIN batches b ON e.batch_id = b.id WHERE b.lecturer_id = $user_id ORDER BY u.full_name");
                            while ($s = mysqli_fetch_assoc($stu_dd)): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['full_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Exam Name *</label><input type="text" name="exam_name" placeholder="e.g. Midterm Exam" required></div>
                    <div class="form-group"><label>Exam Date</label><input type="date" name="exam_date" value="<?php echo date('Y-m-d'); ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Marks (out of 100) *</label><input type="number" name="marks" id="res_marks" min="0" max="100" required oninput="aaUpdateGrade()"></div>
                    <div class="form-group">
                        <label>Total Marks</label>
                        <input type="number" value="100" readonly disabled style="background:#f1f5f9; cursor:not-allowed;">
                        <input type="hidden" name="total_marks" value="100">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Grade <small style="color:#64748b;">(calculated automatically)</small></label>
                        <input type="text" id="res_grade_display" readonly disabled style="background:#f1f5f9; cursor:not-allowed; font-weight:700;">
                        <input type="hidden" name="grade" id="res_grade_hidden">
                    </div>
                    <div class="form-group"><label>Comments</label><input type="text" name="comments" placeholder="Optional comment"></div>
                </div>
                <button type="submit" name="upload_result" class="btn btn-primary">📤 Upload Result</button>
            </form>
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
                var marksInput = document.getElementById('res_marks');
                var grade = aaCalcGrade(marksInput.value);
                document.getElementById('res_grade_display').value = grade;
                document.getElementById('res_grade_hidden').value  = grade;
            }
            </script>
        </div>

        <!-- VIEW RECENT RESULTS -->
        <div id="my_results" class="panel">
            <div class="panel-title">📋 Recently Uploaded Results</div>
            <div class="table-wrapper"><table>
                <thead><tr><th>Student</th><th>Batch</th><th>Exam</th><th>Marks</th><th>Grade</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (!$my_results || mysqli_num_rows($my_results) == 0): ?>
                    <tr><td colspan="6" style="text-align:center; color:#64748b;">No results uploaded yet.</td></tr>
                <?php else: while ($r = mysqli_fetch_assoc($my_results)): ?>
                    <tr>
                        <td><?php echo $r['student_name']; ?></td>
                        <td><?php echo $r['batch_name']; ?></td>
                        <td><?php echo $r['exam_name']; ?></td>
                        <td><?php echo $r['marks']; ?>/<?php echo $r['total_marks']; ?></td>
                        <td><span class="badge badge-blue"><?php echo $r['grade']; ?></span></td>
                        <td>
                            <a href="../../backend/crud/results/edit_result.php?id=<?php echo $r['id']; ?>" class="btn btn-small btn-primary">Edit</a>
                            <a href="../../backend/crud/results/delete_result.php?id=<?php echo $r['id']; ?>" class="btn btn-small btn-red" onclick="return confirm('Delete?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table></div>
        </div>

        <!-- AWARD PERFORMANCE POINTS -->
        <div id="points" class="panel">
            <div class="panel-title">⭐ Award Performance Points</div>
            <?php echo $pts_msg; ?>
            <form method="POST" action="dashboard.php#points" style="max-width:520px;">
                <div class="form-row">
                    <div class="form-group">
                        <label>Student *</label>
                        <select name="pt_student_id" required>
                            <option value="">-- Select --</option>
                            <?php $stu_dd2 = mysqli_query($conn, "SELECT DISTINCT u.id, u.full_name FROM enrollments e JOIN users u ON e.student_id = u.id JOIN batches b ON e.batch_id = b.id WHERE b.lecturer_id = $user_id ORDER BY u.full_name");
                            while ($s = mysqli_fetch_assoc($stu_dd2)): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo $s['full_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Batch</label>
                        <select name="pt_batch_id">
                            <option value="">-- Select --</option>
                            <?php foreach ($batch_rows as $b): ?>
                                <option value="<?php echo $b['id']; ?>"><?php echo $b['batch_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Points *</label><input type="number" name="pt_points" min="1" max="100" placeholder="e.g. 10" required></div>
                    <div class="form-group"><label>Reason</label><input type="text" name="pt_reason" placeholder="e.g. Best in class"></div>
                </div>
                <button type="submit" name="award_points" class="btn btn-primary">⭐ Award Points</button>
            </form>

            <!-- Recently Awarded Points Table -->
            <div style="margin-top:20px;">
                <p style="font-weight:600; color:#1a3a5c; margin-bottom:10px;">Recently Awarded Points</p>
                <div class="table-wrapper"><table>
                    <thead><tr><th>Student</th><th>Batch</th><th>Points</th><th>Reason</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php if (!$my_points || mysqli_num_rows($my_points) == 0): ?>
                        <tr><td colspan="5" style="text-align:center; color:#64748b;">No points awarded yet.</td></tr>
                    <?php else: while ($pt = mysqli_fetch_assoc($my_points)): ?>
                        <tr>
                            <td><?php echo $pt['student_name']; ?></td>
                            <td><?php echo $pt['batch_name'] ?: '—'; ?></td>
                            <td><span style="color:#16a34a; font-weight:700;">+<?php echo $pt['points']; ?></span></td>
                            <td><?php echo $pt['reason']; ?></td>
                            <td><?php echo date('d M Y', strtotime($pt['award_date'])); ?></td>
                        </tr>
                    <?php endwhile; endif; ?>
                    </tbody>
                </table></div>
            </div>
        </div>

        <!-- CLASS LINK UPLOAD -->
        <div id="classlinks" class="panel">
            <div class="panel-title">🔗 Class Link Upload</div>
            <?php echo $link_msg; ?>
            <form method="POST" action="dashboard.php#classlinks" style="margin-bottom:28px;">
                <div class="form-row">
                    <div class="form-group"><label>Link Title *</label><input type="text" name="link_title" placeholder="e.g. Week 3 Zoom Class" required></div>
                    <div class="form-group"><label>Class Date *</label><input type="date" name="link_date" value="<?php echo date('Y-m-d'); ?>" required></div>
                </div>
                <div class="form-group">
                    <label>Class Link URL * <small style="color:#64748b;">(Zoom, Google Meet, Teams, etc.)</small></label>
                    <input type="url" name="link_url" placeholder="https://zoom.us/j/1234567890" required style="width:100%;">
                </div>
                <div class="form-group">
                    <label>Batch *</label>
                    <select name="link_batch_id" required>
                        <option value="">-- Select Batch --</option>
                        <?php foreach ($batch_rows as $b): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['batch_name']); ?> – <?php echo htmlspecialchars($b['subject_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="save_class_link" class="btn btn-primary">🔗 Save Class Link</button>
            </form>

            <p style="font-weight:600; color:#1a3a5c; margin-bottom:10px;">My Uploaded Class Links</p>
            <div class="table-wrapper"><table>
                <thead><tr><th>Title</th><th>Batch</th><th>Class Date</th><th>Link</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (!$my_links || mysqli_num_rows($my_links) == 0): ?>
                    <tr><td colspan="5" style="text-align:center; color:#64748b;">No class links uploaded yet.</td></tr>
                <?php else: while ($lnk = mysqli_fetch_assoc($my_links)): ?>
                    <tr>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($lnk['title']); ?></td>
                        <td style="font-size:0.85rem;"><?php echo htmlspecialchars($lnk['batch_name'] ?? '—'); ?></td>
                        <td style="font-size:0.85rem;"><?php echo date('d M Y', strtotime($lnk['class_date'])); ?></td>
                        <td><a href="<?php echo htmlspecialchars($lnk['link_url']); ?>" target="_blank" class="btn btn-small btn-primary">🔗 Open Link</a></td>
                        <td>
                            <a href="dashboard.php?delete_link=<?php echo $lnk['id']; ?>#classlinks" class="btn btn-small btn-red" onclick="return confirm('Delete this link?');">🗑️ Delete</a>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table></div>
        </div>

        <!-- STUDY MATERIALS -->
        <div id="materials" class="panel">
            <div class="panel-title">📁 Study Materials</div>
            <?php echo $mat_msg; ?>

            <!-- Upload Form -->
            <form method="POST" action="../../backend/crud/materials/upload_material.php" enctype="multipart/form-data" style="margin-bottom:28px;">
                <div class="form-row">
                    <div class="form-group"><label>Title *</label><input type="text" name="mat_title" placeholder="e.g. Chapter 3 Notes" required></div>
                    <div class="form-group"><label>Subject *</label><input type="text" name="mat_subject" placeholder="e.g. Mathematics" required></div>
                </div>
                <div class="form-group">
                    <label>Batch * <small style="color:#64748b;">(students in this batch will see this material)</small></label>
                    <select name="mat_batch_id" required>
                        <option value="">-- Select Batch --</option>
                        <?php foreach ($batch_rows as $b): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['batch_name']); ?> – <?php echo htmlspecialchars($b['subject_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Description <small style="color:#94a3b8;">(optional)</small></label><input type="text" name="mat_description" placeholder="Brief note about this file"></div>
                <div class="form-group">
                    <label>📎 File * <small style="color:#64748b;">(PDF, DOC, DOCX, PPT, PPTX, TXT — max 5MB)</small></label>
                    <input type="file" name="mat_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt" required style="padding:8px; background:#f8fafc; border:1.5px dashed #94a3b8; border-radius:7px; width:100%;">
                </div>
                <button type="submit" name="upload_material" class="btn btn-primary">📤 Upload Material</button>
            </form>

            <!-- Materials Table -->
            <p style="font-weight:600; color:#1a3a5c; margin-bottom:10px;">My Uploaded Materials</p>
            <div class="table-wrapper"><table>
                <thead><tr><th>Title</th><th>Subject</th><th>Batch</th><th>Description</th><th>Uploaded</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (!$my_materials || mysqli_num_rows($my_materials) == 0): ?>
                    <tr><td colspan="6" style="text-align:center; color:#64748b;">No materials uploaded yet.</td></tr>
                <?php else: while ($m = mysqli_fetch_assoc($my_materials)): ?>
                    <tr>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($m['title']); ?></td>
                        <td><?php echo htmlspecialchars($m['subject']); ?></td>
                        <td style="font-size:0.85rem;"><?php echo htmlspecialchars($m['batch_name'] ?? '—'); ?></td>
                        <td style="font-size:0.82rem; color:#64748b;"><?php echo htmlspecialchars($m['description']) ?: '—'; ?></td>
                        <td style="font-size:0.82rem;"><?php echo date('d M Y', strtotime($m['created_at'])); ?></td>
                        <td>
                            <a href="../../uploads/materials/<?php echo urlencode($m['file_path']); ?>" target="_blank" class="btn btn-small btn-primary">📥 View</a>
                            <a href="../../backend/crud/materials/delete_material.php?id=<?php echo $m['id']; ?>" class="btn btn-small btn-red" onclick="return confirm('Delete this material?');">🗑️ Delete</a>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table></div>
        </div>

        <!-- ANNOUNCEMENTS -->
        <div id="announcements" class="panel">
            <div class="panel-title">📢 Post Announcements</div>
            <?php echo $ann_msg; ?>

            <!-- Post Form -->
            <div class="card card-accent" style="margin-bottom:24px;">
                <h3 style="margin-bottom:14px; color:#1a3a5c; font-size:1rem;">➕ New Announcement</h3>
                <form method="POST" action="dashboard.php#announcements">
                    <div class="form-group"><label>Title *</label><input type="text" name="ann_title" placeholder="e.g. Exam on Saturday" required></div>
                    <div class="form-group">
                        <label>Audience</label>
                        <select name="ann_audience">
                            <option value="all">All Users</option>
                            <option value="students">Students Only</option>
                            <option value="parents">Parents Only</option>
                            <option value="staff">Staff Only</option>
                        </select>
                    </div>
                    <div class="form-group"><label>Message *</label><textarea name="ann_message" placeholder="Type the announcement here..." required></textarea></div>
                    <button type="submit" name="post_announcement" class="btn btn-primary">📤 Post Announcement</button>
                </form>
            </div>

            <!-- My Announcements Table -->
            <p style="font-weight:600; color:#1a3a5c; margin-bottom:10px;">My Posted Announcements</p>
            <div class="table-wrapper"><table>
                <thead><tr><th>Title</th><th>Audience</th><th>Date</th><th>Message Preview</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if (!$my_announcements || mysqli_num_rows($my_announcements) == 0): ?>
                    <tr><td colspan="5" style="text-align:center; color:#64748b;">No announcements posted yet.</td></tr>
                <?php else: while ($ann = mysqli_fetch_assoc($my_announcements)): ?>
                    <tr>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($ann['title']); ?></td>
                        <td><span class="badge badge-blue"><?php echo ucfirst($ann['audience']); ?></span></td>
                        <td style="font-size:0.82rem;"><?php echo date('d M Y', strtotime($ann['post_date'])); ?></td>
                        <td style="font-size:0.82rem; color:#64748b;"><?php echo htmlspecialchars(substr($ann['message'], 0, 60)) . (strlen($ann['message']) > 60 ? '...' : ''); ?></td>
                        <td>
                            <a href="dashboard.php?delete_ann=<?php echo $ann['id']; ?>#announcements" class="btn btn-small btn-red" onclick="return confirm('Delete this announcement?');">🗑️ Delete</a>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                </tbody>
            </table></div>
        </div>

    </main>
</div>

<!-- Sidebar active section highlight -->
<script>
(function() {
    var links  = document.querySelectorAll('.sidebar-nav a');
    var panels = [];
    links.forEach(function(link) {
        var id = link.getAttribute('href').replace('#', '');
        var el = document.getElementById(id);
        if (el) panels.push({ el: el, link: link });
    });
    function setActive() {
        var scrollY = window.scrollY + 120;
        var current = panels[0];
        panels.forEach(function(p) { if (p.el.offsetTop <= scrollY) current = p; });
        links.forEach(function(l) { l.classList.remove('active'); });
        if (current) current.link.classList.add('active');
    }
    window.addEventListener('scroll', setActive, { passive: true });
    setActive();
})();
</script>
