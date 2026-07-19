<?php
// ============================================================
//  student_dash.php — Student Dashboard
//  Students can view their batches, results, attendance, points,
//  payments, announcements, class links, and study materials.
//  They can also SUBMIT a payment (but not approve it themselves).
// ============================================================

// --- HANDLE PAYMENT UPLOAD (runs only if the payment form below was submitted) ---
$pay_msg = ''; // will hold a success/error message shown after the form is submitted

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_payment'])) {
    // isset($_POST['submit_payment']) checks specifically for THIS form's submit button name,
    // so this block doesn't accidentally run if some other form on the page was submitted

    $batch_id  = (int)$_POST['pay_batch_id'];                          // force to a whole number — basic safety against junk input
    $amount    = (float)$_POST['pay_amount'];                          // force to a decimal number
    $pay_month = mysqli_real_escape_string($conn, $_POST['pay_month']); // escape text before using it in SQL
    $pay_date  = date('Y-m-d');                                        // today's date, generated on the SERVER (never trust a date sent from the browser)
    $rec_no    = 'STU-' . strtoupper(substr(md5(uniqid()), 0, 6));     
    // uniqid() makes a string based on the current time (almost always unique),
    // md5() scrambles it into a fixed-length hash,
    // substr(...,0,6) keeps only the first 6 characters,
    // strtoupper() makes it look neat — end result looks like "STU-3F9A2B"

    // --- Handle the optional uploaded receipt file ---
    $filename = ''; // stays blank if no file was attached
    if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] == 0) {
        // $_FILES is PHP's superglobal holding info about any uploaded file
        // error == 0 means "no upload errors happened"
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];  // whitelist of acceptable file types
        $ext = strtolower(pathinfo($_FILES['receipt_file']['name'], PATHINFO_EXTENSION)); 
        // pathinfo(...) extracts just the extension part of the filename, e.g. "PNG" from "receipt.PNG"
        // strtolower() normalises it to lowercase so "PNG" and "png" are treated the same

        if (in_array($ext, $allowed)) {
            // only proceed if the extension is one of the allowed types — rejects things like .exe
            $filename = 'receipt_' . $user_id . '_' . time() . '.' . $ext;
            // builds a unique filename using the student's user_id + the current timestamp,
            // so two different uploads never accidentally overwrite each other
            move_uploaded_file($_FILES['receipt_file']['tmp_name'], '../../uploads/receipts/' . $filename);
            // moves the file from PHP's temporary upload location into the real uploads/receipts folder
        }
    }

    // --- Basic required-field validation ---
    if (!$batch_id || $amount <= 0 || empty($pay_month)) {
        $pay_msg = "<div style='background:#fee2e2;color:#991b1b;padding:10px;border-radius:7px;margin-bottom:14px;'>❌ Batch, amount and month are required.</div>";
    } else {
        // --- Save the payment record — always starts as 'pending' ---
        mysqli_query($conn, "INSERT INTO payment (student_id, batch_id, amount, pay_month, receipt_no, pay_date, receipt_file, status) 
                              VALUES ($user_id, $batch_id, $amount, '$pay_month', '$rec_no', '$pay_date', '$filename', 'pending')");
        // status is hardcoded to 'pending' — a student can never set their own payment to 'approved'.
        // Only staff (admin/receptionist) can change that, in a separate CRUD file.
        $pay_msg = "<div style='background:#dcfce7;color:#166534;padding:10px;border-radius:7px;margin-bottom:14px;'>✅ Payment submitted for approval! Receipt: <strong>$rec_no</strong></div>";
    }
}

// ============================================================
//  LOAD ALL THE DATA THIS PAGE WILL DISPLAY (queries run before any HTML)
// ============================================================

// --- The student's own active batches, with subject + lecturer names attached ---
$my_batches = mysqli_query($conn, "
    SELECT e.enrollmentID AS enroll_id, b.*, s.name AS subject_name, u.full_name AS lecturer_name, e.status AS enroll_status
    FROM enrollments e
    JOIN batch b ON e.batch_id = b.batchID        -- get the batch's own details
    JOIN subject s ON b.subject_id = s.subjectID  -- get the subject name via the batch
    JOIN users u ON b.lecturer_id = u.userID      -- get the lecturer's name via the batch
    WHERE e.student_id = $user_id                 -- only THIS student's enrollments
      AND e.status = 'active'                     -- ignore dropped/completed ones
    ORDER BY b.batch_name                          -- alphabetical order
");

// --- Same batch info again, but built into a plain PHP array for reuse elsewhere ---
$my_batch_ids = []; // just the numeric IDs, e.g. [3, 7, 9]
$batch_rows   = []; // the full rows, reused later to build the payment form's dropdown
$bk = mysqli_query($conn, "
    SELECT e.batch_id, b.batch_name, s.name AS subject_name 
    FROM enrollments e 
    JOIN batch b ON e.batch_id=b.batchID 
    JOIN subject s ON b.subject_id=s.subjectID 
    WHERE e.student_id=$user_id AND e.status='active'
");
while ($r = mysqli_fetch_assoc($bk)) {
    $my_batch_ids[] = $r['batch_id']; // collect just the ID
    $batch_rows[]   = $r;             // collect the full row too
}
// Turn the array of IDs into a comma-joined string like "3,7,9" —
// ready to be dropped straight into an SQL "IN (...)" clause further down
$batch_ids_str = empty($my_batch_ids) ? '0' : implode(',', $my_batch_ids);
// if the student has zero batches, use '0' instead of an empty string,
// so "WHERE batch_id IN (0)" is still valid SQL (and simply matches nothing)

// --- The student's exam results ---
$my_results = mysqli_query($conn, "
    SELECT r.*, b.batch_name, s.name AS subject_name
    FROM result r
    JOIN batch b ON r.batch_id = b.batchID
    JOIN subject s ON b.subject_id = s.subjectID
    WHERE r.student_id = $user_id
    ORDER BY r.exam_date DESC   -- most recent exam first
");

// --- Attendance summarised PER BATCH (used for the overview % cards) ---
$my_attendance = mysqli_query($conn, "
    SELECT b.batchID AS batch_id, b.batch_name, s.name AS subject_name,
        COUNT(a.attendanceID) AS total,                                  -- total classes recorded
        SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) AS present,  -- count only 'present' rows
        SUM(CASE WHEN a.status='absent'  THEN 1 ELSE 0 END) AS absent,   -- count only 'absent' rows
        SUM(CASE WHEN a.status='late'    THEN 1 ELSE 0 END) AS late      -- count only 'late' rows
    FROM attendance a
    JOIN batch b ON a.batch_id = b.batchID
    JOIN subject s ON b.subject_id = s.subjectID
    WHERE a.student_id = $user_id
    GROUP BY a.batch_id   -- one summary row per batch, not per individual class date
");

// --- Turn that summary into a lookup array + a reusable plain array ---
$my_attendance_pct  = []; // maps batch_id -> percentage, e.g. [3 => 85, 7 => 60]
$my_attendance_rows = []; // the full rows, reused later for the overview cards and the filter dropdown
while ($row = mysqli_fetch_assoc($my_attendance)) {
    $my_attendance_pct[$row['batch_id']] = $row['total'] > 0 ? round(($row['present'] / $row['total']) * 100) : 0;
    // guard against divide-by-zero if a batch has no attendance recorded yet
    $my_attendance_rows[] = $row;
}

// --- Detailed, one-row-per-date attendance records (for the searchable table) ---
$my_attendance_records = mysqli_query($conn, "
    SELECT a.attend_date, a.status, b.batchID AS batch_id, b.batch_name, s.name AS subject_name
    FROM attendance a
    JOIN batch b ON a.batch_id = b.batchID
    JOIN subject s ON b.subject_id = s.subjectID
    WHERE a.student_id = $user_id
    ORDER BY a.attend_date DESC   -- most recent class date first
");
// This is deliberately a SEPARATE query from the summary above:
// the summary gives one row PER BATCH (for the % cards),
// this one gives one row PER ATTENDANCE DATE (for the detailed searchable table)

// --- Performance points: lifetime total + full history ---
$my_points_row = get_one_row($conn, "SELECT SUM(points) AS total FROM performance_points WHERE student_id = $user_id");
$total_points  = $my_points_row ? (int)$my_points_row['total'] : 0; // falls back to 0 if never awarded any points
$my_points     = mysqli_query($conn, "
    SELECT pp.*, u.full_name AS awarded_by_name, b.batch_name 
    FROM performance_points pp 
    JOIN users u ON pp.awarded_by=u.userID 
    LEFT JOIN batch b ON pp.batch_id=b.batchID 
    WHERE pp.student_id=$user_id 
    ORDER BY pp.performancePointID DESC   -- most recently awarded first
");

// --- School-wide leaderboard, ranking every student by total points ---
$leaderboard_result = mysqli_query($conn, "
    SELECT u.userID, u.full_name, COALESCE(SUM(pp.points), 0) AS total_points
    FROM users u
    LEFT JOIN performance_points pp ON pp.student_id = u.userID   -- LEFT JOIN so students with 0 points still appear
    WHERE u.role = 'student'
    GROUP BY u.userID, u.full_name
    ORDER BY total_points DESC, u.full_name ASC   -- highest scorer first; alphabetical if tied
");
$leaderboard_rows = []; // plain array so we can loop it once here AND reuse it in the HTML below
$my_rank          = 0;  // 0 = "not found" — shouldn't normally happen for a logged-in student
$lb_rank          = 1;  // counts upward as we walk the already-sorted result
while ($lb = mysqli_fetch_assoc($leaderboard_result)) {
    $lb['rank'] = $lb_rank;
    if ($lb['userID'] == $user_id) $my_rank = $lb_rank; // this row IS the logged-in student — remember their rank
    $leaderboard_rows[] = $lb;
    $lb_rank++;
}

// --- The student's own payment history ---
$my_payments = mysqli_query($conn, "
    SELECT p.*, b.batch_name, s.name AS subject_name
    FROM payment p
    JOIN batch b ON p.batch_id = b.batchID
    JOIN subject s ON b.subject_id = s.subjectID
    WHERE p.student_id = $user_id
    ORDER BY p.paymentID DESC   -- most recent payment first
");

// --- Announcements meant for everyone or specifically for students ---
$announcements = mysqli_query($conn, "
    SELECT a.*, u.full_name AS posted_by_name
    FROM announcement a
    LEFT JOIN users u ON a.posted_by = u.userID   -- LEFT JOIN in case the poster's account was later deleted
    WHERE a.audience IN ('all', 'students')
    ORDER BY a.created_at DESC
    LIMIT 10   -- cap it so the page doesn't get overloaded with old announcements
");

// --- Online class-session links, only for batches this student is actually in ---
$class_sessions = mysqli_query($conn, "
    SELECT cl.*, b.batch_name, s.name AS subject_name, u.full_name AS lecturer_name
    FROM class_sessions cl
    JOIN batch b ON cl.batch_id = b.batchID
    JOIN subject s ON b.subject_id = s.subjectID
    JOIN users u ON cl.lecturer_id = u.userID
    WHERE cl.batch_id IN ($batch_ids_str)   -- this is exactly why $batch_ids_str was built earlier
    ORDER BY cl.class_date DESC
    LIMIT 20
");

// --- Study materials, same "only my batches" filtering trick ---
$materials = mysqli_query($conn, "
    SELECT sm.*, b.batch_name, s.name AS subject_name
    FROM study_materials sm
    LEFT JOIN batch b ON sm.batch_id = b.batchID       -- LEFT JOIN in case a material isn't linked to a specific batch
    LEFT JOIN subject s ON b.subject_id = s.subjectID
    WHERE sm.batch_id IN ($batch_ids_str)
    ORDER BY sm.created_at DESC                       -- newest upload first
");
?>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header"><h3>🎓 Student Portal</h3><p><?php echo htmlspecialchars($full_name); ?></p></div>
        <nav class="sidebar-nav">
            <!-- Each link is a same-page anchor (#id) — clicking jumps straight to that panel below -->
            <a href="#my_batches"     class="active"><span class="sidebar-icon">🗓️</span> My Batches</a>
            <a href="#results">                      <span class="sidebar-icon">📊</span> My Results</a>
            <a href="#attendance">                   <span class="sidebar-icon">✅</span> My Attendance</a>
            <a href="#points">                       <span class="sidebar-icon">⭐</span> My Points</a>
            <a href="#leaderboard">                  <span class="sidebar-icon">🏆</span> Leaderboard</a>
            <a href="#payments">                     <span class="sidebar-icon">💳</span> Payments</a>
            <a href="#announcements">                <span class="sidebar-icon">📢</span> Announcements</a>
            <a href="#classlinks">                   <span class="sidebar-icon">🔗</span> Class Links</a>
            <a href="#materials">                    <span class="sidebar-icon">📁</span> Study Materials</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <h1 class="dashboard-title">Student Portal</h1>
        <p class="dashboard-subtitle">Welcome, <?php echo htmlspecialchars($full_name); ?>! You have <strong><?php echo count($my_batch_ids); ?></strong> active batch(es). Total Points: <strong><?php echo $total_points; ?> ⭐</strong></p>

        <!-- MY BATCHES -->
        <div id="my_batches" class="panel">
            <div class="panel-title">🗓️ My Batches &amp; Schedule</div>
            <?php
            // Reset pointer
            mysqli_data_seek($my_batches, 0); // $my_batches was already counted with mysqli_num_rows above; rewind it so the while loop below starts from the first row
            if (mysqli_num_rows($my_batches) > 0): ?>
            <div class="card-grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:16px;">
                <?php while ($b = mysqli_fetch_assoc($my_batches)): ?>
                <div class="card card-accent" style="padding:18px 20px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                        <div>
                            <div style="font-weight:700; font-size:1rem; color:#1a3a5c;"><?php echo htmlspecialchars($b['batch_name']); ?></div>
                            <div style="font-size:0.85rem; color:#2563eb; font-weight:600; margin-top:2px;"><?php echo htmlspecialchars($b['subject_name']); ?></div>
                        </div>
                        <span class="badge <?php echo $b['status'] == 'active' ? 'badge-green' : 'badge-gray'; ?>"><?php echo ucfirst($b['status']); ?></span>
                    </div>
                    <div style="font-size:0.82rem; color:#374151; display:flex; flex-direction:column; gap:4px;">
                        <div>👨‍🏫 <?php echo htmlspecialchars($b['lecturer_name']); ?></div>
                        <div>🕒 <?php echo htmlspecialchars($b['schedule']); ?></div>
                        <div>🏠 Room: <?php echo htmlspecialchars($b['room']); ?></div>
                    </div>
                </div>
                <?php endwhile; ?>
                <!-- One card per batch this student is actively enrolled in -->
            </div>
            <?php else: ?>
                <div style="text-align:center; padding:40px; color:#64748b;">
                    <div style="font-size:3rem; margin-bottom:12px;">📭</div>
                    <p>You are not enrolled in any batches yet. Please contact the receptionist.</p>
                    <!-- Shown only if this student has zero active enrollments -->
                </div>
            <?php endif; ?>
        </div>

        <!-- MY RESULTS -->
        <div id="results" class="panel">
            <div class="panel-title">📊 My Exam Results</div>
            <div class="table-wrapper"><table>
                <thead><tr><th>Subject</th><th>Batch</th><th>Exam</th><th>Date</th><th>Marks</th><th>Grade</th><th>Comments</th></tr></thead>
                <tbody>
                <?php if ($my_results && mysqli_num_rows($my_results) > 0):
                    while ($r = mysqli_fetch_assoc($my_results)):
                        $gc = in_array($r['grade'], ['A+','A','A-']) ? 'badge-green' : ($r['grade'] == 'E' ? 'badge-red' : 'badge-blue'); // Colour-code the grade pill
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['batch_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['exam_name']); ?></td>
                        <td><?php echo date('d M Y', strtotime($r['exam_date'])); ?></td>
                        <td><strong><?php echo $r['marks']; ?></strong>/<?php echo $r['total_marks']; ?></td>
                        <td><span class="badge <?php echo $gc; ?>"><?php echo $r['grade']; ?></span></td>
                        <td style="font-size:.82rem; color:#64748b;"><?php echo htmlspecialchars($r['comments'] ?: '—'); ?></td>
                        <!-- Falls back to an em-dash if the lecturer left no comment -->
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="7" style="text-align:center; color:#64748b;">No results uploaded yet.</td></tr>
                <?php endif; ?>
                <!-- One row per exam result this student has received -->
                </tbody>
            </table></div>
        </div>

        <!-- MY ATTENDANCE -->
        <div id="attendance" class="panel">
            <div class="panel-title">✅ My Attendance Summary</div>

            <!-- Quick per-batch overview cards -->
            <?php if (!empty($my_attendance_rows)): ?>
            <div class="card-grid" style="margin-bottom:24px;">
                <?php foreach ($my_attendance_rows as $at):
                    $pct = $at['total'] > 0 ? round(($at['present'] / $at['total']) * 100) : 0; // Avoid divide-by-zero if no attendance recorded yet
                    $bar = $pct >= 80 ? 'green' : ($pct >= 60 ? '' : 'orange');                  // Colour-code the progress bar
                ?>
                    <div class="card">
                        <h3 style="font-size:0.92rem;"><?php echo htmlspecialchars($at['subject_name']); ?> — <?php echo htmlspecialchars($at['batch_name']); ?></h3>
                        <div style="display:flex; align-items:center; gap:8px; margin-top:8px;">
                            <div style="flex:1;"><div class="progress-bar-wrapper"><div class="progress-bar-fill <?php echo $bar; ?>" style="width:<?php echo $pct; ?>%;"></div></div></div>
                            <strong style="font-size:.85rem;"><?php echo $pct; ?>%</strong>
                        </div>
                        <p style="font-size:0.78rem; color:#64748b; margin-top:6px;">
                            <?php echo $at['present']; ?> present · <?php echo $at['absent']; ?> absent · <?php echo $at['late']; ?> late · <?php echo $at['total']; ?> total
                        </p>
                    </div>
                <?php endforeach; ?>
                <!-- One card per batch this student has attendance records for -->
            </div>
            <?php endif; ?>

            <!-- Filter + Search -->
            <div class="form-row" style="margin-bottom:14px;">
                <div class="form-group">
                    <label>Filter by Batch</label>
                    <select id="stu_att_batch_filter">
                        <option value="">All Batches</option>
                        <?php foreach ($my_attendance_rows as $at): ?>
                            <option value="<?php echo $at['batch_id']; ?>"><?php echo htmlspecialchars($at['batch_name']); ?> – <?php echo htmlspecialchars($at['subject_name']); ?></option>
                            <!-- One option per batch this student has attendance records for -->
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Search</label>
                    <input type="text" id="stu_att_search" placeholder="Search by batch or subject...">
                </div>
            </div>

            <!-- Detailed per-record table -->
            <div class="table-wrapper"><table id="stu_att_records_table">
                <thead><tr><th>Batch</th><th>Subject</th><th>Date</th><th>Attendance Status</th></tr></thead>
                <tbody>
                <?php if (!$my_attendance_records || mysqli_num_rows($my_attendance_records) == 0): ?>
                    <tr><td colspan="4" style="text-align:center; color:#64748b;">No attendance records yet.</td></tr>
                <?php else: while ($ar = mysqli_fetch_assoc($my_attendance_records)):
                    $status_badge = $ar['status'] == 'present' ? 'badge-green' : ($ar['status'] == 'absent' ? 'badge-red' : 'badge-yellow'); // Colour-code the status pill
                    $status_icon  = $ar['status'] == 'present' ? '✅' : ($ar['status'] == 'absent' ? '❌' : '⏰');                            // Matching emoji
                ?>
                    <tr data-batch-id="<?php echo $ar['batch_id']; ?>" data-search="<?php echo htmlspecialchars(strtolower($ar['batch_name'] . ' ' . $ar['subject_name'])); ?>">
                        <!-- data-* attributes are read by the JS filter below; no server round-trip needed to filter -->
                        <td><?php echo htmlspecialchars($ar['batch_name']); ?></td>
                        <td><?php echo htmlspecialchars($ar['subject_name']); ?></td>
                        <td style="font-size:0.85rem;"><?php echo date('d M Y', strtotime($ar['attend_date'])); ?></td>
                        <td><span class="badge <?php echo $status_badge; ?>"><?php echo $status_icon . ' ' . ucfirst($ar['status']); ?></span></td>
                    </tr>
                <?php endwhile; endif; ?>
                <!-- One row per individual attendance record this student has -->
                </tbody>
            </table></div>
            <p id="stu_att_records_empty" style="text-align:center; color:#64748b; padding:14px; display:none;">No matching attendance records.</p>
            <!-- Hidden by default; shown by the JS below only when a filter/search matches nothing -->
        </div>

        <script>
        (function() {
            var batchFilter = document.getElementById('stu_att_batch_filter');       // The batch dropdown
            var search      = document.getElementById('stu_att_search');             // The free-text search box
            var table       = document.getElementById('stu_att_records_table');     // The records table itself
            if (!batchFilter || !search || !table) return;                          // Bail out safely if any element is missing
            var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr[data-batch-id]')); // All data rows (excludes the "no records" placeholder row)

            function applyFilter() {
                var batchVal = batchFilter.value;                  // Currently selected batch id ('' means "all")
                var term     = search.value.trim().toLowerCase();  // Currently typed search text, lowercased for case-insensitive matching
                var visible  = 0;                                   // Counts how many rows remain visible after filtering
                rows.forEach(function(row) {
                    var matchesBatch = !batchVal || row.getAttribute('data-batch-id') === batchVal;        // True if no batch filter, or it matches this row's batch
                    var matchesTerm  = !term || row.getAttribute('data-search').indexOf(term) !== -1;      // True if no search term, or it's found in the row's batch/subject text
                    var show = matchesBatch && matchesTerm;          // Row is shown only if it satisfies BOTH filters
                    row.style.display = show ? '' : 'none';          // Toggle visibility directly via CSS
                    if (show) visible++;
                });
                var emptyMsg = document.getElementById('stu_att_records_empty');
                if (emptyMsg) emptyMsg.style.display = (rows.length > 0 && visible === 0) ? '' : 'none'; // Show "no matches" only when filtering hid every row
            }
            batchFilter.addEventListener('change', applyFilter); // Re-filter whenever the batch dropdown changes
            search.addEventListener('input', applyFilter);       // Re-filter on every keystroke in the search box
        })();
        </script>

        <!-- MY PERFORMANCE POINTS -->
        <div id="points" class="panel">
            <div class="panel-title">⭐ My Performance Points — Total: <strong style="color:#d97706;"><?php echo $total_points; ?> pts</strong></div>
            <div class="table-wrapper"><table>
                <thead><tr><th>Date</th><th>Points</th><th>Awarded By</th><th>Batch</th><th>Reason</th></tr></thead>
                <tbody>
                <?php if ($my_points && mysqli_num_rows($my_points) > 0):
                    while ($pt = mysqli_fetch_assoc($my_points)): ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($pt['award_date'])); ?></td>
                        <td><span style="color:#d97706; font-weight:700; font-size:1rem;">+<?php echo $pt['points']; ?></span></td>
                        <td><?php echo htmlspecialchars($pt['awarded_by_name']); ?></td>
                        <td><?php echo htmlspecialchars($pt['batch_name'] ?: '—'); ?></td>
                        <td><?php echo htmlspecialchars($pt['reason'] ?: '—'); ?></td>
                        <!-- Falls back to an em-dash if no reason was given -->
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#64748b;">No points awarded yet.</td></tr>
                <?php endif; ?>
                <!-- One row per performance points award this student has received -->
                </tbody>
            </table></div>
        </div>

        <!-- LEADERBOARD ENTRY -->
        <div id="leaderboard" class="panel">
            <div class="panel-title">🏆 Leaderboard Entry</div>
            <?php if ($my_rank > 0): ?>
                <p style="margin-bottom:14px;">Your Overall Rank: <strong style="color:#d97706; font-size:1.2rem;">#<?php echo $my_rank; ?></strong> of <?php echo count($leaderboard_rows); ?> students</p>
            <?php endif; ?>
            <div class="table-wrapper"><table>
                <thead><tr><th>Rank</th><th>Student</th><th>Total Points</th></tr></thead>
                <tbody>
                <?php if (!empty($leaderboard_rows)):
                    foreach ($leaderboard_rows as $lb):
                        $is_me = $lb['userID'] == $user_id; // Highlight the logged-in student's own row
                ?>
                    <tr <?php if ($is_me) echo 'style="background:#fef3c7; font-weight:700;"'; ?>>
                        <td>#<?php echo $lb['rank']; ?></td>
                        <td><?php echo htmlspecialchars($lb['full_name']); ?> <?php if ($is_me) echo '<span class="badge badge-yellow">You</span>'; ?></td>
                        <td style="color:#d97706; font-weight:700;"><?php echo $lb['total_points']; ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="3" style="text-align:center; color:#64748b;">No leaderboard data available yet.</td></tr>
                <?php endif; ?>
                <!-- One row per student in the system, ranked by total performance points across all their batches -->
                </tbody>
            </table></div>
        </div>

        <!-- PAYMENTS -->
        <div id="payments" class="panel">
            <div class="panel-title">💳 Payments</div>
            <?php echo $pay_msg; ?> <!-- Success/error message from the handler at the top of the file -->

            <!-- Upload Payment Form -->
            <div class="card card-accent" style="margin-bottom:24px;">
                <h3 style="font-size:1rem; margin-bottom:14px; color:#1a3a5c;">📤 Submit Payment</h3>
                <form method="POST" action="dashboard.php#payments" enctype="multipart/form-data">
                    <!-- enctype="multipart/form-data" is required whenever a form includes a file input -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>Batch *</label>
                            <select name="pay_batch_id" required>
                                <option value="">-- Select Batch --</option>
                                <?php foreach ($batch_rows as $br): ?>
                                    <option value="<?php echo $br['batch_id']; ?>"><?php echo htmlspecialchars($br['batch_name']); ?> – <?php echo htmlspecialchars($br['subject_name']); ?></option>
                                    <!-- One option per batch this student is enrolled in -->
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Amount (LKR) *</label><input type="number" name="pay_amount" placeholder="e.g. 2500" min="1" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Payment Month *</label><input type="month" name="pay_month" value="<?php echo date('Y-m'); ?>" required></div>
                        <!-- Defaults to the current month -->
                        <div class="form-group"><label>📎 Receipt Image (optional)</label><input type="file" name="receipt_file" accept=".jpg,.jpeg,.png,.pdf" style="padding:7px; background:#f8fafc; border:1.5px dashed #94a3b8; border-radius:7px; width:100%;"></div>
                    </div>
                    <p style="font-size:.82rem; color:#64748b; margin-bottom:10px;">ℹ️ Payment will be reviewed and approved by the admin.</p>
                    <button type="submit" name="submit_payment" class="btn btn-primary">📤 Submit Payment</button>
                </form>
            </div>

            <!-- Payment History Table -->
            <div class="table-wrapper"><table>
                <thead><tr><th>Receipt No.</th><th>Batch</th><th>Subject</th><th>Month</th><th>Amount</th><th>Date</th><th>Status</th><th>Print</th></tr></thead>
                <tbody>
                <?php if ($my_payments && mysqli_num_rows($my_payments) > 0):
                    while ($p = mysqli_fetch_assoc($my_payments)):
                        $pb = $p['status'] == 'approved' ? 'badge-green' : ($p['status'] == 'rejected' ? 'badge-red' : 'badge-yellow'); // Colour-code the status pill
                ?>
                    <tr>
                        <td style="font-size:.85rem;"><?php echo htmlspecialchars($p['receipt_no'] ?: '—'); ?></td>
                        <td><?php echo htmlspecialchars($p['batch_name']); ?></td>
                        <td><?php echo htmlspecialchars($p['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($p['pay_month']); ?></td>
                        <td style="font-weight:600;">LKR <?php echo number_format($p['amount'], 2); ?></td>
                        <td style="font-size:.82rem;"><?php echo $p['pay_date'] ? date('d M Y', strtotime($p['pay_date'])) : '—'; ?></td>
                        <td><span class="badge <?php echo $pb; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                        <td>
                            <?php if ($p['status'] == 'approved'): ?>
                                <a href="print_receipt.php?id=<?php echo $p['paymentID']; ?>" target="_blank" class="btn btn-small btn-primary">🖨️</a>
                                <!-- Only meaningful once a payment is approved -->
                            <?php else: ?>
                                <span style="font-size:.78rem; color:#94a3b8;">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="8" style="text-align:center; color:#64748b;">No payment records yet.</td></tr>
                <?php endif; ?>
                <!-- One row per payment this student has ever submitted -->
                </tbody>
            </table></div>
        </div>

        <!-- ANNOUNCEMENTS -->
        <div id="announcements" class="panel">
            <div class="panel-title">📢 Announcements</div>
            <?php if ($announcements && mysqli_num_rows($announcements) > 0):
                while ($ann = mysqli_fetch_assoc($announcements)): ?>
                <div class="announcement">
                    <h4>📢 <?php echo htmlspecialchars($ann['title']); ?></h4>
                    <p><?php echo htmlspecialchars($ann['message']); ?></p>
                    <p class="ann-date">Posted by <?php echo htmlspecialchars($ann['posted_by_name'] ?: 'Admin'); ?> on <?php echo date('d M Y', strtotime($ann['post_date'])); ?></p>
                    <!-- Falls back to "Admin" if the poster's account was deleted -->
                </div>
            <?php endwhile; else: ?>
                <p style="color:#64748b;">No announcements at this time.</p>
                <!-- Shown only if there are zero announcements aimed at 'all' or 'students' -->
            <?php endif; ?>
        </div>

        <!-- CLASS LINKS -->
        <div id="classlinks" class="panel">
            <div class="panel-title">🔗 Class Links (Online Sessions)</div>
            <?php if ($class_sessions && mysqli_num_rows($class_sessions) > 0): ?>
            <div class="table-wrapper"><table>
                <thead><tr><th>Title</th><th>Subject</th><th>Batch</th><th>Lecturer</th><th>Class Date</th><th>Link</th></tr></thead>
                <tbody>
                <?php while ($lnk = mysqli_fetch_assoc($class_sessions)):
                    $is_today   = date('Y-m-d') == $lnk['class_date'];    // Highlight today's session
                    $is_upcoming = $lnk['class_date'] >= date('Y-m-d');   // Only let students "join" a session that hasn't already happened
                ?>
                    <tr <?php if ($is_today) echo 'style="background:#f0fdf4;"'; ?>>
                        <td style="font-weight:600;">
                            <?php echo htmlspecialchars($lnk['title']); ?>
                            <?php if ($is_today): ?><span class="badge badge-green" style="margin-left:6px; font-size:.72rem;">TODAY</span><?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($lnk['subject_name']); ?></td>
                        <td style="font-size:.85rem;"><?php echo htmlspecialchars($lnk['batch_name']); ?></td>
                        <td style="font-size:.85rem;"><?php echo htmlspecialchars($lnk['lecturer_name']); ?></td>
                        <td><?php echo date('d M Y', strtotime($lnk['class_date'])); ?></td>
                        <td>
                            <?php if ($is_upcoming): ?>
                                <a href="<?php echo htmlspecialchars($lnk['link_url']); ?>" target="_blank" rel="noopener" class="btn btn-small btn-primary">🔗 Join Class</a>
                                <!-- rel="noopener" prevents the opened tab from being able to access/control this dashboard tab -->
                            <?php else: ?>
                                <span style="font-size:.82rem; color:#94a3b8;">Session passed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <!-- One row per class link saved for any batch this student is in, capped at 20 by the query above -->
                </tbody>
            </table></div>
            <?php else: ?>
                <p style="color:#64748b;">No class links available yet. Check back later.</p>
            <?php endif; ?>
        </div>

        <!-- STUDY MATERIALS -->
        <div id="materials" class="panel">
            <div class="panel-title">📁 Study Materials</div>
            <?php if ($materials && mysqli_num_rows($materials) > 0): ?>
            <div class="table-wrapper"><table>
                <thead><tr><th>Title</th><th>Subject</th><th>Batch</th><th>Description</th><th>Uploaded</th><th>Download</th></tr></thead>
                <tbody>
                <?php while ($m = mysqli_fetch_assoc($materials)): ?>
                    <tr>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($m['title']); ?></td>
                        <td><?php echo htmlspecialchars($m['subject']); ?></td>
                        <td style="font-size:.85rem;"><?php echo htmlspecialchars($m['batch_name'] ?: '—'); ?></td>
                        <td style="font-size:.82rem; color:#64748b;"><?php echo htmlspecialchars($m['description'] ?: '—'); ?></td>
                        <td style="font-size:.82rem;"><?php echo date('d M Y', strtotime($m['created_at'])); ?></td>
                        <td><a href="../../uploads/materials/<?php echo urlencode($m['file_path']); ?>" target="_blank" class="btn btn-small btn-primary">📥 Download</a></td>
                    </tr>
                <?php endwhile; ?>
                <!-- One row per material uploaded for any batch this student is in -->
                </tbody>
            </table></div>
            <?php else: ?>
                <p style="color:#64748b;">No study materials uploaded yet.</p>
            <?php endif; ?>
        </div>

    </main>
</div>

<!-- Sidebar active section highlight on scroll -->
<script>
(function() {
    var links  = document.querySelectorAll('.sidebar-nav a'); // Every link in the sidebar
    var panels = [];                                          // Will hold {el, link} pairs — the panel each link points to
    links.forEach(function(link) {
        var id = link.getAttribute('href').replace('#', ''); // Strip the leading "#" to get the plain panel id
        var el = document.getElementById(id);                // Find the actual panel with that id
        if (el) panels.push({ el: el, link: link });         // Only track links that point to a real panel on the page
    });
    function setActive() {
        var scrollY = window.scrollY + 120;       // Small offset so a panel counts as "current" slightly before it reaches the very top
        var current = panels[0];                   // Default to the first panel
        panels.forEach(function(p) { if (p.el.offsetTop <= scrollY) current = p; }); // The last panel scrolled past is the "current" one
        links.forEach(function(l) { l.classList.remove('active'); }); // Clear the active highlight from every link first
        if (current) current.link.classList.add('active');             // Then highlight only the current one
    }
    window.addEventListener('scroll', setActive, { passive: true }); // Re-check on every scroll
    setActive(); // Also run once immediately on page load
})();
</script>
