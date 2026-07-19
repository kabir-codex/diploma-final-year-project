<?php
// ============================================================
//  lecturer_dash.php — Lecturer Dashboard
//  Lecturers can: mark attendance, upload exam results,
//  award performance points, share class links,
//  upload study materials, and post announcements.
// ============================================================

// Note: delete_ann and delete_link are handled in dashboard.php BEFORE HTML output.


// --- MESSAGE VARIABLES: one per form on this page, all start empty ---
$ann_msg  = ''; // holds the result message after posting an announcement
$link_msg = ''; // holds the result message after saving a class link
$att_msg  = ''; // holds the result message after marking attendance
$res_msg  = ''; // holds the result message after uploading a result
$pts_msg  = ''; // holds the result message after awarding points
$mat_msg  = ''; // holds the result message after uploading study material

// Study material uploads happen through a SEPARATE file (upload_material.php),
// which redirects back here with ?mat_success=... or ?mat_error=... in the URL
if (isset($_GET['mat_success'])) $mat_msg = "<div style='background:#dcfce7; color:#166534; padding:10px; border-radius:7px; margin-bottom:14px;'>✅ " . htmlspecialchars($_GET['mat_success']) . "</div>"; // Comes from upload_material.php's redirect
if (isset($_GET['mat_error']))   $mat_msg = "<div style='background:#fee2e2; color:#991b1b; padding:10px; border-radius:7px; margin-bottom:14px;'>❌ " . htmlspecialchars($_GET['mat_error']) . "</div>";   // Comes from upload_material.php's redirect

// --- ANNOUNCEMENT: Post ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_announcement'])) {
    $ann_title    = mysqli_real_escape_string($conn, trim($_POST['ann_title']));   // Announcement title
    $ann_message  = mysqli_real_escape_string($conn, trim($_POST['ann_message'])); // Announcement body
    $ann_audience = mysqli_real_escape_string($conn, $_POST['ann_audience']);      // Who should see it
    $ann_date     = date('Y-m-d');                                                 // Today's date, used as the post date
    
    if (empty($ann_title) || empty($ann_message)) { // if announcement title and message is empty
        $ann_msg = "<div style='background:#fee2e2; color:#991b1b; padding:10px; border-radius:7px; margin-bottom:14px;'>❌ Title and message are required.</div>"; // Required fields check
    } else {
        mysqli_query($conn, "INSERT INTO announcement (title, message, audience, post_date, posted_by) VALUES ('$ann_title', '$ann_message', '$ann_audience', '$ann_date', $user_id)"); // Save the announcement
        $ann_msg = "<div style='background:#dcfce7; color:#166534; padding:10px; border-radius:7px; margin-bottom:14px;'>✅ Announcement posted!</div>";
    }
}

// --- CLASS LINK: Save ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_class_link'])) {
    $link_title    = mysqli_real_escape_string($conn, trim($_POST['link_title'])); // e.g. "Zoom Class - Algebra"
    $link_url      = mysqli_real_escape_string($conn, trim($_POST['link_url']));   // The actual meeting URL
    $link_date     = mysqli_real_escape_string($conn, $_POST['link_date']);        // Which date this class link is for
    $link_batch_id = (int)$_POST['link_batch_id'];                                  // Which batch this link belongs to

    //if all the fields are empty
    if (empty($link_title) || empty($link_url) || empty($link_date) || $link_batch_id <= 0) {
        $link_msg = "<div style='background:#fee2e2; color:#991b1b; padding:10px; border-radius:7px; margin-bottom:14px;'>❌ All fields are required.</div>"; // Required fields check
    } else {
        $ok = mysqli_query($conn, "INSERT INTO class_sessions (lecturer_id, batch_id, title, link_url, class_date) VALUES ($user_id, $link_batch_id, '$link_title', '$link_url', '$link_date')"); // Save the link
        $link_msg = $ok
            ? "<div style='background:#dcfce7; color:#166534; padding:10px; border-radius:7px; margin-bottom:14px;'>✅ Class link saved!</div>"
            : "<div style='background:#fee2e2; color:#991b1b; padding:10px; border-radius:7px; margin-bottom:14px;'>❌ Error: " . mysqli_error($conn) . "</div>"; // Shows the actual MySQL error if the insert failed
    }
}

// --- ATTENDANCE: Mark ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {
    $att_batch = (int)$_POST['att_batch_id'];                          // Which batch this attendance is for
    $att_date  = mysqli_real_escape_string($conn, $_POST['att_date']); // Which date this attendance is for
    
    // $_POST['att_status'] is an ARRAY here, not a single value —
    // the HTML form names each dropdown att_status[studentID], so PHP automatically
    // collects them all into one array: [studentID => 'present', studentID2 => 'absent', ...]
    foreach ($_POST['att_status'] as $sid => $status) {
        $sid    = (int)$sid;                                 // this loop iteration's student ID
        $status = mysqli_real_escape_string($conn, $status);  // this student's chosen status: present/absent/late
        
        // Check if attendance for this student/batch/date already exists
        $chk = mysqli_query($conn, "SELECT attendanceID FROM attendance WHERE student_id=$sid AND batch_id=$att_batch AND attend_date='$att_date'");
        if (mysqli_num_rows($chk) > 0) {
            $existing = mysqli_fetch_assoc($chk);                                                   // Already marked for this date — update it instead of duplicating
            mysqli_query($conn, "UPDATE attendance SET status='$status' WHERE attendanceID=" . $existing['attendanceID']);
        } else {
            mysqli_query($conn, "INSERT INTO attendance (student_id, batch_id, attend_date, status, marked_by) VALUES ($sid, $att_batch, '$att_date', '$status', $user_id)"); // First time marking this student for this date
        }
    }
    $att_msg = "<div style='background:#dcfce7; color:#166534; padding:10px; border-radius:7px; margin-bottom:14px;'>✅ Attendance saved!</div>";
}

// --- RESULT: Upload ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_result'])) {
    $res_batch   = (int)$_POST['res_batch_id'];                                // Which batch this exam was for
    $res_student = (int)$_POST['res_student_id'];                              // Which student this result belongs to
    $exam_name   = mysqli_real_escape_string($conn, $_POST['exam_name']);      // e.g. "Mid-Term Exam"
    $exam_date   = mysqli_real_escape_string($conn, $_POST['exam_date']);      // When the exam took place
    $marks       = (int)$_POST['marks'];                                       // Marks scored, out of 100
    $total_marks = 100; // Total marks is fixed at 100 for all results
    $grade       = mysqli_real_escape_string($conn, calc_grade($marks));       // Server-side grade calculation — never trusts a client-submitted grade
    $comments    = mysqli_real_escape_string($conn, $_POST['comments']);       // Optional lecturer note
    $ok = mysqli_query($conn, "INSERT INTO result (student_id, batch_id, exam_name, exam_date, marks, total_marks, grade, comments, uploaded_by) VALUES ($res_student, $res_batch, '$exam_name', '$exam_date', $marks, $total_marks, '$grade', '$comments', $user_id)");
    $res_msg = $ok
        ? "<div style='background:#dcfce7; color:#166534; padding:10px; border-radius:7px; margin-bottom:14px;'>✅ Result uploaded!</div>"
        : "<div style='background:#fee2e2; color:#991b1b; padding:10px; border-radius:7px; margin-bottom:14px;'>❌ Error: " . mysqli_error($conn) . "</div>";
}

// --- PERFORMANCE POINTS: Award ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['award_points'])) {
    $pt_student = (int)($_POST['pt_student_id'] ?? 0); // ?? 0 guards against the dependent select submitting no value at all/empty value if the dependent dropdown wasn't populated in time
    $pt_batch   = (int)($_POST['pt_batch_id'] ?? 0);   // Which batch this award is tied to
    $pt_points  = (int)$_POST['pt_points'];             // How many points to award
    $pt_reason  = mysqli_real_escape_string($conn, $_POST['pt_reason']); // Optional note, e.g. "Best in class"
    $pt_date    = date('Y-m-d');                        // Today's date
    if (!$pt_batch || !$pt_student || $pt_points <= 0) {
        $pts_msg = "<div style='background:#fee2e2; color:#991b1b; padding:10px; border-radius:7px; margin-bottom:14px;'>❌ Select a batch, a student, and enter valid points.</div>"; // Required fields check
    } else {
        $ok = mysqli_query($conn, "INSERT INTO performance_points (student_id, awarded_by, batch_id, points, reason, award_date) VALUES ($pt_student, $user_id, $pt_batch, $pt_points, '$pt_reason', '$pt_date')"); // Save the award
        $pts_msg = $ok
            ? "<div style='background:#dcfce7; color:#166534; padding:10px; border-radius:7px; margin-bottom:14px;'>✅ Points awarded!</div>"
            : "<div style='background:#fee2e2; color:#991b1b; padding:10px; border-radius:7px; margin-bottom:14px;'>❌ Error: " . mysqli_error($conn) . "</div>";
    }
}

// --- LOAD DATA ---

// This lecturer's batches
$batches_result = mysqli_query($conn, "SELECT b.*, s.name AS subject_name FROM batch b JOIN subject s ON b.subject_id = s.subjectID WHERE b.lecturer_id = $user_id ORDER BY b.batch_name");
$batch_rows = [];                                                        // Plain PHP array version of the result above, so it can be looped multiple times in the HTML below
while ($br = mysqli_fetch_assoc($batches_result)) $batch_rows[] = $br;

// Which batch is selected for attendance
$selected_batch_id = !empty($batch_rows) ? $batch_rows[0]['batchID'] : 0;     // Defaults to the lecturer's first batch
if (isset($_POST['att_batch_id'])) $selected_batch_id = (int)$_POST['att_batch_id']; // Unless they just submitted the attendance form for a specific batch

// Students in the selected batch
$students_result = mysqli_query($conn, "SELECT u.userID, u.full_name FROM enrollments e JOIN users u ON e.student_id = u.userID WHERE e.batch_id = $selected_batch_id AND e.status = 'active' ORDER BY u.full_name");

// Which batch is selected for Upload Result (separate from the attendance one above —
// starts empty on first page load, since a lecturer must pick a batch before seeing students)
$selected_result_batch_id = isset($_POST['res_batch_id']) ? (int)$_POST['res_batch_id'] : 0;

// Students in that batch only (bug fix: this used to list ALL students across every
// batch this lecturer teaches, regardless of which batch was picked above)
$result_students = null;
if ($selected_result_batch_id > 0) {
    $result_students = mysqli_query($conn, "SELECT DISTINCT u.userID, u.full_name FROM enrollments e JOIN users u ON e.student_id = u.userID WHERE e.batch_id = $selected_result_batch_id AND e.status = 'active' ORDER BY u.full_name");
}

// Attendance records this lecturer has marked, across all their batches (most recent first)
$my_attendance_records = mysqli_query($conn, "
    SELECT a.attendanceID, a.attend_date, a.status, b.batchID AS batch_id, b.batch_name, u.full_name AS student_name
    FROM attendance a
    JOIN batch b ON a.batch_id = b.batchID
    JOIN users u ON a.student_id = u.userID
    WHERE a.marked_by = $user_id
    ORDER BY a.attend_date DESC, b.batch_name, u.full_name
"); // Used to build the "My Saved Attendance Records" table further down

// All students in this lecturer's batches (for dropdowns)
$all_students = mysqli_query($conn, "SELECT DISTINCT u.userID, u.full_name FROM enrollments e JOIN users u ON e.student_id = u.userID JOIN batch b ON e.batch_id = b.batchID WHERE b.lecturer_id = $user_id ORDER BY u.full_name");

// Recently uploaded results
$my_results = mysqli_query($conn, "SELECT r.*, u.full_name AS student_name, b.batch_name FROM result r JOIN users u ON r.student_id = u.userID JOIN batch b ON r.batch_id = b.batchID WHERE r.uploaded_by = $user_id ORDER BY r.resultID DESC LIMIT 10");

// Recently awarded points
$my_points = mysqli_query($conn, "SELECT pp.*, u.full_name AS student_name, b.batch_name FROM performance_points pp JOIN users u ON pp.student_id = u.userID LEFT JOIN batch b ON pp.batch_id = b.batchID WHERE pp.awarded_by = $user_id ORDER BY pp.performancePointID DESC LIMIT 10");

// Students grouped by batch (for the Award Performance Points batch -> student filter)
$pt_batch_students = []; // batch_id -> array of {id, name} students, used to build the JS lookup map on the points form
$pt_bs_result = mysqli_query($conn, "SELECT e.batch_id, u.userID, u.full_name FROM enrollments e JOIN users u ON e.student_id = u.userID JOIN batch b ON e.batch_id = b.batchID WHERE b.lecturer_id = $user_id AND e.status = 'active' ORDER BY u.full_name");
while ($pbs = mysqli_fetch_assoc($pt_bs_result)) {
    $pt_batch_students[$pbs['batch_id']][] = ['id' => $pbs['userID'], 'name' => $pbs['full_name']]; // Group each student under their batch's id
}

// Class links uploaded by this lecturer
$my_links = mysqli_query($conn, "SELECT cl.*, b.batch_name FROM class_sessions cl LEFT JOIN batch b ON cl.batch_id = b.batchID WHERE cl.lecturer_id = $user_id ORDER BY cl.class_date DESC");

// Study materials uploaded by this lecturer (uploaded_by_lecturer_id is a
// proper FK to users.userID now, so we filter by id instead of matching a name)
$my_materials = mysqli_query($conn, "SELECT sm.*, b.batch_name FROM study_materials sm LEFT JOIN batch b ON sm.batch_id = b.batchID WHERE sm.uploaded_by_lecturer_id = $user_id ORDER BY sm.created_at DESC");

// Announcements posted by this lecturer
$my_announcements = mysqli_query($conn, "SELECT * FROM announcement WHERE posted_by = $user_id ORDER BY created_at DESC");
?>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header"><h3>🎓 Lecturer Portal</h3><p><?php echo $full_name; ?></p></div>
        <nav class="sidebar-nav">
            <!-- Each link is a same-page anchor (#id) — clicking jumps straight to that panel below -->
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
                    <!-- Shown only if this lecturer has no batches assigned -->
                <?php else: foreach ($batch_rows as $b):
                    $badge = $b['status'] == 'active' ? 'badge-green' : ($b['status'] == 'upcoming' ? 'badge-yellow' : 'badge-gray'); // Colour-code the status pill
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
            <?php echo $att_msg; ?> <!-- Success/error message from the handler at the top of the file -->
            <form method="POST" action="dashboard.php#attendance">
                <div class="form-row">
                    <div class="form-group">
                        <label>Select Batch</label>
                        <!-- When batch changes, form auto-submits to reload students for that batch -->
                        <select name="att_batch_id" onchange="this.form.submit()">
                            <?php foreach ($batch_rows as $b): ?>
                                <option value="<?php echo $b['batchID']; ?>" <?php if ($selected_batch_id == $b['batchID']) echo 'selected'; ?>>
                                    <?php echo $b['batch_name']; ?> – <?php echo $b['subject_name']; ?>
                                </option>
                                <!-- Marks the currently selected batch as already selected -->
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="att_date" value="<?php echo date('Y-m-d'); ?>" required>
                        <!-- Defaults to today's date -->
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
                                <select name="att_status[<?php echo $s['userID']; ?>]">
                                    <!-- Array-style name (att_status[student_id]) lets PHP receive one status per student in a single $_POST array -->
                                    <option value="present">✅ Present</option>
                                    <option value="absent">❌ Absent</option>
                                    <option value="late">⏰ Late</option>
                                </select>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    <!-- One row per student currently enrolled in the selected batch -->
                    </tbody>
                </table></div>
                <button type="submit" name="mark_attendance" class="btn btn-primary" style="margin-top:14px;">💾 Save Attendance</button>
                <?php else: ?>
                    <p style="color:#64748b;">No students enrolled in this batch.</p>
                    <!-- Shown only if the selected batch has zero active enrollments -->
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
                                <option value="<?php echo $b['batchID']; ?>"><?php echo htmlspecialchars($b['batch_name']); ?> – <?php echo htmlspecialchars($b['subject_name']); ?></option>
                                <!-- One option per batch this lecturer teaches -->
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
                        <!-- Shown only if this lecturer hasn't marked any attendance at all yet -->
                    <?php else: while ($ar = mysqli_fetch_assoc($my_attendance_records)):
                        $status_badge = $ar['status'] == 'present' ? 'badge-green' : ($ar['status'] == 'absent' ? 'badge-red' : 'badge-yellow'); // Colour-code the status pill
                        $status_icon  = $ar['status'] == 'present' ? '✅' : ($ar['status'] == 'absent' ? '❌' : '⏰');                            // Matching emoji
                    ?>
                        <tr data-batch-id="<?php echo $ar['batch_id']; ?>" data-student-name="<?php echo htmlspecialchars(strtolower($ar['student_name'])); ?>">
                            <!-- data-* attributes are read by the JS filter below; no server round-trip needed to filter -->
                            <td><?php echo htmlspecialchars($ar['batch_name']); ?></td>
                            <td><?php echo htmlspecialchars($ar['student_name']); ?></td>
                            <td style="font-size:0.85rem;"><?php echo date('d M Y', strtotime($ar['attend_date'])); ?></td>
                            <td><span class="badge <?php echo $status_badge; ?>"><?php echo $status_icon . ' ' . ucfirst($ar['status']); ?></span></td>
                        </tr>
                    <?php endwhile; endif; ?>
                    <!-- One row per attendance record this lecturer has ever saved, across all their batches -->
                    </tbody>
                </table></div>
                <p id="att_records_empty" style="text-align:center; color:#64748b; padding:14px; display:none;">No matching attendance records.</p>
                <!-- Hidden by default; shown by the JS below only when a filter/search matches nothing -->
            </div>
        </div>

        <script>
        (function() {
            var batchFilter = document.getElementById('att_record_batch_filter');       // The batch dropdown
            var search      = document.getElementById('att_record_search');             // The free-text search box
            var table       = document.getElementById('att_records_table');             // The records table itself
            if (!batchFilter || !search || !table) return;                              // Bail out safely if any element is missing
            var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr[data-batch-id]')); // All data rows (excludes the "no records" placeholder row)

            function applyFilter() {
                var batchVal = batchFilter.value;                  // Currently selected batch id ('' means "all")
                var term     = search.value.trim().toLowerCase();  // Currently typed search text, lowercased for case-insensitive matching
                var visible  = 0;                                   // Counts how many rows remain visible after filtering
                rows.forEach(function(row) {
                    var matchesBatch = !batchVal || row.getAttribute('data-batch-id') === batchVal;             // True if no batch filter, or it matches this row's batch
                    var matchesName  = !term || row.getAttribute('data-student-name').indexOf(term) !== -1;     // True if no search term, or the student name contains it
                    var show = matchesBatch && matchesName;          // Row is shown only if it satisfies BOTH filters
                    row.style.display = show ? '' : 'none';          // Toggle visibility directly via CSS
                    if (show) visible++;
                });
                var emptyMsg = document.getElementById('att_records_empty');
                if (emptyMsg) emptyMsg.style.display = (rows.length > 0 && visible === 0) ? '' : 'none'; // Show "no matches" only when filtering hid every row
            }
            batchFilter.addEventListener('change', applyFilter); // Re-filter whenever the batch dropdown changes
            search.addEventListener('input', applyFilter);       // Re-filter on every keystroke in the search box
        })();
        </script>

        <!-- UPLOAD EXAM RESULT -->
        <div id="results" class="panel">
            <div class="panel-title">📊 Upload Exam Result</div>
            <?php echo $res_msg; ?> <!-- Success/error message from the handler at the top of the file -->
            <form method="POST" action="dashboard.php#results">
                <div class="form-row">
                    <div class="form-group">
                        <label>Batch</label>
                        <!-- When batch changes, form auto-submits to reload the student list for that batch -->
                        <select name="res_batch_id" required onchange="this.form.submit()">
                            <option value="">-- Select --</option>
                            <?php foreach ($batch_rows as $b): ?>
                                <option value="<?php echo $b['batchID']; ?>" <?php if ($selected_result_batch_id == $b['batchID']) echo 'selected'; ?>><?php echo $b['batch_name']; ?></option>
                                <!-- One option per batch this lecturer teaches -->
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Student</label>
                        <select name="res_student_id" required>
                            <?php if ($selected_result_batch_id == 0): ?>
                                <option value="">-- Select a batch first --</option>
                            <?php elseif ($result_students && mysqli_num_rows($result_students) > 0): ?>
                                <option value="">-- Select --</option>
                                <?php while ($s = mysqli_fetch_assoc($result_students)): ?>
                                    <option value="<?php echo $s['userID']; ?>"><?php echo $s['full_name']; ?></option>
                                    <!-- One option per student enrolled in the batch selected above -->
                                <?php endwhile; ?>
                            <?php else: ?>
                                <option value="">-- No students enrolled in this batch --</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Exam Name *</label><input type="text" name="exam_name" placeholder="e.g. Midterm Exam" required></div>
                    <div class="form-group"><label>Exam Date</label><input type="date" name="exam_date" value="<?php echo date('Y-m-d'); ?>"></div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Marks (out of 100) *</label><input type="number" name="marks" id="res_marks" min="0" max="100" required oninput="aaUpdateGrade()"></div>
                    <!-- oninput recalculates the grade live, on every keystroke -->
                    <div class="form-group">
                        <label>Total Marks</label>
                        <input type="number" value="100" readonly disabled style="background:#f1f5f9; cursor:not-allowed;">
                        <!-- Visually locked at 100; disabled inputs don't submit, so... -->
                        <input type="hidden" name="total_marks" value="100">
                        <!-- ...this hidden field is what actually gets sent to the server -->
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Grade <small style="color:#64748b;">(calculated automatically)</small></label>
                        <input type="text" id="res_grade_display" readonly disabled style="background:#f1f5f9; cursor:not-allowed; font-weight:700;">
                        <!-- Display-only; filled in live by aaUpdateGrade() below -->
                        <input type="hidden" name="grade" id="res_grade_hidden">
                        <!-- The hidden field that actually submits — but the server still recalculates the grade itself for safety -->
                    </div>
                    <div class="form-group"><label>Comments</label><input type="text" name="comments" placeholder="Optional comment"></div>
                </div>
                <button type="submit" name="upload_result" class="btn btn-primary">📤 Upload Result</button>
            </form>
            <script>
            function aaCalcGrade(marks) {
                if (marks === '' || isNaN(marks)) return ''; // Nothing typed yet, or not a number — show no grade
                marks = Number(marks);                       // Make sure we're comparing a real number
                if (marks >= 85) return 'A+';  // 85-100
                if (marks >= 70) return 'A';   // 70-84
                if (marks >= 65) return 'A-';  // 65-69
                if (marks >= 60) return 'B+';  // 60-64
                if (marks >= 55) return 'B';   // 55-59
                if (marks >= 50) return 'B-';  // 50-54
                if (marks >= 45) return 'C+';  // 45-49
                if (marks >= 40) return 'C';   // 40-44
                if (marks >= 35) return 'C-';  // 35-39
                if (marks >= 30) return 'D+';  // 30-34
                if (marks >= 25) return 'D';   // 25-29
                return 'E';                    // 0-24 (fail)
            }
            function aaUpdateGrade() {
                var marksInput = document.getElementById('res_marks');                    // The marks the lecturer just typed
                var grade = aaCalcGrade(marksInput.value);                                 // Work out the matching grade
                document.getElementById('res_grade_display').value = grade;               // Show it to the lecturer
                document.getElementById('res_grade_hidden').value  = grade;                // Also stash it in the hidden field that actually submits
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
                    <!-- Shown only if this lecturer hasn't uploaded any results at all -->
                <?php else: while ($r = mysqli_fetch_assoc($my_results)): ?>
                    <tr>
                        <td><?php echo $r['student_name']; ?></td>
                        <td><?php echo $r['batch_name']; ?></td>
                        <td><?php echo $r['exam_name']; ?></td>
                        <td><?php echo $r['marks']; ?>/<?php echo $r['total_marks']; ?></td>
                        <td><span class="badge badge-blue"><?php echo $r['grade']; ?></span></td>
                        <td>
                            <a href="../../backend/crud/results/edit_result.php?id=<?php echo $r['resultID']; ?>" class="btn btn-small btn-primary">Edit</a>
                            <a href="../../backend/crud/results/delete_result.php?id=<?php echo $r['resultID']; ?>" class="btn btn-small btn-red" onclick="return confirm('Delete?');">Delete</a>
                            <!-- confirm() pops a native browser dialog; clicking Cancel cancels the navigation entirely -->
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                <!-- One row per result this lecturer has uploaded, capped at the last 10 by the query above -->
                </tbody>
            </table></div>
        </div>

        <!-- AWARD PERFORMANCE POINTS -->
        <div id="points" class="panel">
            <div class="panel-title">⭐ Award Performance Points</div>
            <?php echo $pts_msg; ?> <!-- Success/error message from the handler at the top of the file -->
            <form method="POST" action="dashboard.php#points" style="max-width:520px;">
                <div class="form-row">
                    <div class="form-group">
                        <label>Batch *</label>
                        <select name="pt_batch_id" id="pt_batch_select" required onchange="aaUpdatePtStudents()">
                            <!-- onchange repopulates the Student dropdown below using the JS map further down -->
                            <option value="">-- Select Batch --</option>
                            <?php foreach ($batch_rows as $b): ?>
                                <option value="<?php echo $b['batchID']; ?>"><?php echo htmlspecialchars($b['batch_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Student *</label>
                        <select name="pt_student_id" id="pt_student_select" required disabled>
                            <option value="">-- Select Batch First --</option>
                            <!-- Starts disabled/empty; JS fills this in once a batch is picked -->
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group"><label>Points *</label><input type="number" name="pt_points" min="1" max="100" placeholder="e.g. 10" required></div>
                    <div class="form-group"><label>Reason</label><input type="text" name="pt_reason" placeholder="e.g. Best in class"></div>
                </div>
                <button type="submit" name="award_points" class="btn btn-primary">⭐ Award Points</button>
            </form>
            <script>
            var aaPtBatchStudents = <?php echo json_encode($pt_batch_students); ?>; // PHP map (batch_id -> students) turned into a JS object the browser can read
            function aaUpdatePtStudents() {
                var batchId  = document.getElementById('pt_batch_select').value;       // Which batch was just picked
                var studentSelect = document.getElementById('pt_student_select');      // The dropdown we're about to rebuild
                studentSelect.innerHTML = '';                                          // Clear out whatever options were there before
                var students = aaPtBatchStudents[batchId] || [];                       // Look up this batch's students, or an empty list if none
                if (!batchId) {
                    studentSelect.appendChild(new Option('-- Select Batch First --', '')); // No batch chosen yet
                    studentSelect.disabled = true;
                } else if (students.length === 0) {
                    studentSelect.appendChild(new Option('-- No students in this batch --', '')); // Batch exists but has zero active enrollments
                    studentSelect.disabled = true;
                } else {
                    studentSelect.appendChild(new Option('-- Select --', ''));
                    students.forEach(function(s) {
                        studentSelect.appendChild(new Option(s.name, s.id)); // One <option> per student in the chosen batch
                    });
                    studentSelect.disabled = false; // Now safe to pick a student
                }
            }
            </script>

            <!-- Recently Awarded Points Table -->
            <div style="margin-top:20px;">
                <p style="font-weight:600; color:#1a3a5c; margin-bottom:10px;">Recently Awarded Points</p>
                <div class="table-wrapper"><table>
                    <thead><tr><th>Student</th><th>Batch</th><th>Points</th><th>Reason</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php if (!$my_points || mysqli_num_rows($my_points) == 0): ?>
                        <tr><td colspan="5" style="text-align:center; color:#64748b;">No points awarded yet.</td></tr>
                        <!-- Shown only if this lecturer hasn't awarded any points at all -->
                    <?php else: while ($pt = mysqli_fetch_assoc($my_points)): ?>
                        <tr>
                            <td><?php echo $pt['student_name']; ?></td>
                            <td><?php echo $pt['batch_name'] ?: '—'; ?></td>
                            <!-- Falls back to an em-dash if the batch was somehow left blank -->
                            <td><span style="color:#16a34a; font-weight:700;">+<?php echo $pt['points']; ?></span></td>
                            <td><?php echo $pt['reason']; ?></td>
                            <td><?php echo date('d M Y', strtotime($pt['award_date'])); ?></td>
                        </tr>
                    <?php endwhile; endif; ?>
                    <!-- One row per award, capped at the last 10 by the query above -->
                    </tbody>
                </table></div>
            </div>
        </div>

        <!-- CLASS LINK UPLOAD -->
        <div id="classlinks" class="panel">
            <div class="panel-title">🔗 Class Link Upload</div>
            <?php echo $link_msg; ?> <!-- Success/error message from the handler at the top of the file -->
            <form method="POST" action="dashboard.php#classlinks" style="margin-bottom:28px;">
                <div class="form-row">
                    <div class="form-group"><label>Link Title *</label><input type="text" name="link_title" placeholder="e.g. Week 3 Zoom Class" required></div>
                    <div class="form-group"><label>Class Date *</label><input type="date" name="link_date" value="<?php echo date('Y-m-d'); ?>" required></div>
                </div>
                <div class="form-group">
                    <label>Class Link URL * <small style="color:#64748b;">(Zoom, Google Meet, Teams, etc.)</small></label>
                    <input type="url" name="link_url" placeholder="https://zoom.us/j/1234567890" required style="width:100%;">
                    <!-- type="url" gives basic native browser validation that it at least looks like a URL -->
                </div>
                <div class="form-group">
                    <label>Batch *</label>
                    <select name="link_batch_id" required>
                        <option value="">-- Select Batch --</option>
                        <?php foreach ($batch_rows as $b): ?>
                            <option value="<?php echo $b['batchID']; ?>"><?php echo htmlspecialchars($b['batch_name']); ?> – <?php echo htmlspecialchars($b['subject_name']); ?></option>
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
                    <!-- Shown only if this lecturer hasn't saved any class links -->
                <?php else: while ($lnk = mysqli_fetch_assoc($my_links)): ?>
                    <tr>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($lnk['title']); ?></td>
                        <td style="font-size:0.85rem;"><?php echo htmlspecialchars($lnk['batch_name'] ?? '—'); ?></td>
                        <td style="font-size:0.85rem;"><?php echo date('d M Y', strtotime($lnk['class_date'])); ?></td>
                        <td><a href="<?php echo htmlspecialchars($lnk['link_url']); ?>" target="_blank" class="btn btn-small btn-primary">🔗 Open Link</a></td>
                        <!-- target="_blank" opens the class link in a new tab so the dashboard stays open -->
                        <td>
                            <a href="dashboard.php?delete_link=<?php echo $lnk['classSessionID']; ?>#classlinks" class="btn btn-small btn-red" onclick="return confirm('Delete this link?');">🗑️ Delete</a>
                            <!-- Handled by the delete_link pre-HTML redirect block in dashboard.php -->
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                <!-- One row per class link this lecturer has saved -->
                </tbody>
            </table></div>
        </div>

        <!-- STUDY MATERIALS -->
        <div id="materials" class="panel">
            <div class="panel-title">📁 Study Materials</div>
            <?php echo $mat_msg; ?> <!-- Success/error message; built from ?mat_success/?mat_error at the top of the file -->

            <!-- Upload Form -->
            <form method="POST" action="../../backend/crud/materials/upload_material.php" enctype="multipart/form-data" style="margin-bottom:28px;">
                <!-- enctype="multipart/form-data" is required whenever a form includes a file input -->
                <div class="form-row">
                    <div class="form-group"><label>Title *</label><input type="text" name="mat_title" placeholder="e.g. Chapter 3 Notes" required></div>
                    <div class="form-group"><label>Subject *</label><input type="text" name="mat_subject" placeholder="e.g. Mathematics" required></div>
                </div>
                <div class="form-group">
                    <label>Batch * <small style="color:#64748b;">(students in this batch will see this material)</small></label>
                    <select name="mat_batch_id" required>
                        <option value="">-- Select Batch --</option>
                        <?php foreach ($batch_rows as $b): ?>
                            <option value="<?php echo $b['batchID']; ?>"><?php echo htmlspecialchars($b['batch_name']); ?> – <?php echo htmlspecialchars($b['subject_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Description <small style="color:#94a3b8;">(optional)</small></label><input type="text" name="mat_description" placeholder="Brief note about this file"></div>
                <div class="form-group">
                    <label>📎 File * <small style="color:#64748b;">(PDF, DOC, DOCX, PPT, PPTX, TXT — max 5MB)</small></label>
                    <input type="file" name="mat_file" accept=".pdf,.doc,.docx,.ppt,.pptx,.txt" required style="padding:8px; background:#f8fafc; border:1.5px dashed #94a3b8; border-radius:7px; width:100%;">
                    <!-- accept=... is just a UI hint for the file picker; upload_material.php re-checks the extension server-side regardless -->
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
                    <!-- Shown only if this lecturer hasn't uploaded any materials -->
                <?php else: while ($m = mysqli_fetch_assoc($my_materials)): ?>
                    <tr>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($m['title']); ?></td>
                        <td><?php echo htmlspecialchars($m['subject']); ?></td>
                        <td style="font-size:0.85rem;"><?php echo htmlspecialchars($m['batch_name'] ?? '—'); ?></td>
                        <td style="font-size:0.82rem; color:#64748b;"><?php echo htmlspecialchars($m['description']) ?: '—'; ?></td>
                        <td style="font-size:0.82rem;"><?php echo date('d M Y', strtotime($m['created_at'])); ?></td>
                        <td>
                            <a href="../../uploads/materials/<?php echo urlencode($m['file_path']); ?>" target="_blank" class="btn btn-small btn-primary">📥 View</a>
                            <a href="../../backend/crud/materials/delete_material.php?id=<?php echo $m['studyMaterialID']; ?>" class="btn btn-small btn-red" onclick="return confirm('Delete this material?');">🗑️ Delete</a>
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                <!-- One row per material this lecturer has uploaded -->
                </tbody>
            </table></div>
        </div>

        <!-- ANNOUNCEMENTS -->
        <div id="announcements" class="panel">
            <div class="panel-title">📢 Post Announcements</div>
            <?php echo $ann_msg; ?> <!-- Success/error message from the handler at the top of the file -->

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
                    <!-- Shown only if this lecturer hasn't posted any announcements -->
                <?php else: while ($ann = mysqli_fetch_assoc($my_announcements)): ?>
                    <tr>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($ann['title']); ?></td>
                        <td><span class="badge badge-blue"><?php echo ucfirst($ann['audience']); ?></span></td>
                        <td style="font-size:0.82rem;"><?php echo date('d M Y', strtotime($ann['post_date'])); ?></td>
                        <td style="font-size:0.82rem; color:#64748b;"><?php echo htmlspecialchars(substr($ann['message'], 0, 60)) . (strlen($ann['message']) > 60 ? '...' : ''); ?></td>
                        <!-- Truncates the message to 60 characters and adds "..." only if it was actually cut off -->
                        <td>
                            <a href="dashboard.php?delete_ann=<?php echo $ann['announcementID']; ?>#announcements" class="btn btn-small btn-red" onclick="return confirm('Delete this announcement?');">🗑️ Delete</a>
                            <!-- Handled by the delete_ann pre-HTML redirect block in dashboard.php -->
                        </td>
                    </tr>
                <?php endwhile; endif; ?>
                <!-- One row per announcement this lecturer has posted -->
                </tbody>
            </table></div>
        </div>

    </main>
</div>

<!-- Sidebar active section highlight -->
<script>
(function() {
    var links  = document.querySelectorAll('.sidebar-nav a'); // Every link in the sidebar
    var panels = [];                                          // Will hold {el, link} pairs — the panel each link points to
    links.forEach(function(link) {
        var id = link.getAttribute('href').replace('#', ''); // Strip the leading "#" from e.g. "#attendance" to get "attendance"
        var el = document.getElementById(id);                // Find the actual panel with that id
        if (el) panels.push({ el: el, link: link });         // Only track links that point to a real panel on the page
    });
    function setActive() {
        var scrollY = window.scrollY + 120;       // Add a small offset so a panel counts as "current" slightly before it reaches the very top
        var current = panels[0];                   // Default to the first panel
        panels.forEach(function(p) { if (p.el.offsetTop <= scrollY) current = p; }); // The last panel whose top has been scrolled past is the "current" one
        links.forEach(function(l) { l.classList.remove('active'); }); // Clear the active highlight from every link first
        if (current) current.link.classList.add('active');             // Then highlight only the current one
    }
    window.addEventListener('scroll', setActive, { passive: true }); // Re-check on every scroll; passive:true improves scroll performance
    setActive(); // Also run once immediately on page load
})();
</script>
