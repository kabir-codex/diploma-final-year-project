<?php
// ============================================================
//  parent_dash.php — Parent Dashboard
//  Parents can see their child's: batches, results,
//  attendance, performance points, payments, announcements.
// ============================================================

// --- Step 1: find WHICH student this parent is linked to ---
// parent_student is a linking table connecting a parent's userID to their child's userID
$child_link  = get_one_row($conn, "SELECT student_id FROM parent_student WHERE parent_id = $user_id LIMIT 1"); // A parent could theoretically have multiple children, but this dashboard only shows the first linked one
$child_id    = $child_link ? (int)$child_link['student_id'] : 0;     // 0 means "no child linked yet"
$child_info  = $child_id ? get_one_row($conn, "SELECT * FROM users WHERE userID = $child_id") : null; // The child's user account
$child_name  = $child_info ? $child_info['full_name'] : 'Not linked'; // Shown in the welcome message either way

// Get child's batch IDs
$child_batch_ids = []; // Used elsewhere if we need a plain list of the batches this child is in
$cbr = mysqli_query($conn, "SELECT batch_id FROM enrollments WHERE student_id=$child_id AND status='active'");
while ($r = mysqli_fetch_assoc($cbr)) $child_batch_ids[] = $r['batch_id']; // Build the list, one batch_id per active enrollment
$batch_ids_str = empty($child_batch_ids) ? '0' : implode(',', $child_batch_ids); // Comma-joined string, ready to drop into an "IN (...)" SQL clause if ever needed

// Child's batches
$child_batches = mysqli_query($conn, "
SELECT b.*, s.name AS subject_name, u.full_name AS lecturer_name
FROM enrollments e
JOIN batch b ON e.batch_id = b.batchID        -- match each enrollment to its actual batch
JOIN subject s ON b.subject_id = s.subjectID  -- get the subject name for that batch
JOIN users u ON b.lecturer_id = u.userID      -- get the lecturer's name for that batch
WHERE e.student_id = $child_id                -- only batches THIS child is enrolled in
  AND e.status = 'active'                     -- only currently-active enrollments
");

// The child's exam results, newest first
$child_results = mysqli_query($conn, "
SELECT r.*, b.batch_name, s.name AS subject_name
FROM result r
JOIN batch b ON r.batch_id = b.batchID         -- attach batch name to each result
JOIN subject s ON b.subject_id = s.subjectID   -- attach subject name via the batch
WHERE r.student_id = $child_id                 -- only this child's results
ORDER BY r.exam_date DESC                      -- most recent exam shown first
");

// Attendance, summarised into present/absent/late counts PER BATCH
$child_attendance = mysqli_query($conn, "
SELECT b.batch_name, s.name AS subject_name,
    COUNT(a.attendanceID) AS total,                                   -- total classes recorded for this batch
    SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) AS present,   -- count only the 'present' rows
    SUM(CASE WHEN a.status='absent'  THEN 1 ELSE 0 END) AS absent,    -- count only the 'absent' rows
    SUM(CASE WHEN a.status='late'    THEN 1 ELSE 0 END) AS late       -- count only the 'late' rows
FROM attendance a
JOIN batch b ON a.batch_id = b.batchID         -- get the batch name
JOIN subject s ON b.subject_id = s.subjectID   -- get the subject name via the batch
WHERE a.student_id = $child_id                 -- only this child's attendance records
GROUP BY a.batch_id                            -- one summary row PER batch, not per individual class
");

// Total performance points the child has ever earned + the full history list
$pts_row     = get_one_row($conn, "SELECT SUM(points) AS total FROM performance_points WHERE student_id = $child_id");   //add up every point award this child has ever received, across all batches
$total_pts   = $pts_row ? (int)$pts_row['total'] : 0; // Falls back to 0 if the child has never been awarded points
$child_pts   = mysqli_query($conn, "SELECT pp.*, u.full_name AS awarded_by_name, b.batch_name FROM performance_points pp JOIN users u ON pp.awarded_by=u.userID LEFT JOIN batch b ON pp.batch_id=b.batchID WHERE pp.student_id=$child_id ORDER BY pp.performancePointID DESC");

// Leaderboard: every student ranked by total performance points earned across all their batches
$leaderboard_result = mysqli_query($conn, "
SELECT u.userID, u.full_name, 
    COALESCE(SUM(pp.points), 0) AS total_points   -- add up all points per student; if none exist, show 0 instead of NULL
FROM users u
LEFT JOIN performance_points pp ON pp.student_id = u.userID   -- LEFT JOIN so students with ZERO points still appear (with 0)
WHERE u.role = 'student'            -- only rank actual students, not staff/parents
GROUP BY u.userID, u.full_name       -- one row per student, summing all their point records together
ORDER BY total_points DESC,          -- highest scorer first
         u.full_name ASC             -- if two students are tied, break the tie alphabetically by name

         
"); // Points from every batch a student attended count toward their rank, regardless of which lecturer awarded them
$leaderboard_rows = []; // Plain array version, so it can be looped once for the table and reused to find the child's rank
$child_rank       = 0;  // 0 means "not found" (e.g. no child linked yet)
$lb_rank          = 1;  // Running rank counter as we walk the already-sorted result
while ($lb = mysqli_fetch_assoc($leaderboard_result)) {
    $lb['rank'] = $lb_rank;
    if ($lb['userID'] == $child_id) $child_rank = $lb_rank; // This is the linked child's row — remember their rank
    $leaderboard_rows[] = $lb;
    $lb_rank++;
}

// Why loop it manually into an array instead of using mysqli_num_rows() twice?
// Because we need BOTH the child's exact rank AND the full table later in the HTML —
// looping once and storing it avoids running the same query twice.

// Child's payment history
$child_payments = mysqli_query($conn, "
SELECT p.*, b.batch_name, s.name AS subject_name
FROM payment p
JOIN batch b ON p.batch_id = b.batchID         -- get the batch this payment was for
JOIN subject s ON b.subject_id = s.subjectID   -- get the subject name via the batch
WHERE p.student_id = $child_id                 -- only this child's payments
ORDER BY p.paymentID DESC                      -- most recent payment shown first
");

// Announcements for all or parents
$announcements = mysqli_query($conn, "    
SELECT a.*, u.full_name AS posted_by_name
FROM announcement a
LEFT JOIN users u ON a.posted_by = u.userID   -- LEFT JOIN in case an announcement has no author recorded
WHERE a.audience IN ('all', 'parents')         -- only show announcements meant for everyone or specifically parents
ORDER BY a.created_at DESC                     -- newest announcement first
LIMIT 10                                       -- cap it at the 10 most recent, don't overload the page
"); // Only shows announcements meant for everyone or specifically for parents

// Class links for child's batches
?>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header"><h3>👨‍👩‍👧 Parent Portal</h3><p><?php echo htmlspecialchars($full_name); ?></p></div>
        <nav class="sidebar-nav">
            <!-- Each link is a same-page anchor (#id) — clicking jumps straight to that panel below -->
            <a href="#child_overview" class="active"><span class="sidebar-icon">👦</span> Child Overview</a>
            <a href="#child_batches">               <span class="sidebar-icon">🗓️</span> Batches</a>
            <a href="#child_results">               <span class="sidebar-icon">📊</span> Exam Results</a>
            <a href="#child_attendance">            <span class="sidebar-icon">✅</span> Attendance</a>
            <a href="#child_points">                <span class="sidebar-icon">⭐</span> Points</a>
            <a href="#leaderboard">                 <span class="sidebar-icon">🏆</span> Leaderboard</a>
            <a href="#child_payments">              <span class="sidebar-icon">💳</span> Payments</a>
            <a href="#announcements">               <span class="sidebar-icon">📢</span> Announcements</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <h1 class="dashboard-title">Parent Portal</h1>
        <p class="dashboard-subtitle">Welcome, <?php echo htmlspecialchars($full_name); ?>. Monitoring: <strong><?php echo htmlspecialchars($child_name); ?></strong></p>

        <!-- CHILD OVERVIEW CARD -->
        <div id="child_overview" class="stats-grid">
            <?php if ($child_info): ?>
            <div class="stat-card">
                <div style="font-size:2.5rem; text-align:center; margin-bottom:8px;"><?php echo strtoupper(substr($child_name, 0, 1)); ?></div>
                <!-- First letter of the child's name, used as a simple avatar -->
                <div class="stat-label"><?php echo htmlspecialchars($child_name); ?></div>
            </div>
            <div class="stat-card green">  <div class="stat-number"><?php echo count($child_batch_ids); ?></div> <div class="stat-label">Active Batches</div></div>
            <div class="stat-card orange"> <div class="stat-number"><?php echo $total_pts; ?></div>              <div class="stat-label">Total Points ⭐</div></div>
            <?php else: ?>
            <div class="stat-card" style="grid-column:1/-1; text-align:center; padding:30px; color:#64748b;">
                ⚠️ No student is linked to your account yet. Please contact the admin.
                <!-- Shown only if the admin hasn't linked this parent to a student yet -->
            </div>
            <?php endif; ?>
        </div>

        <?php if ($child_id): // Everything below only makes sense once a child is actually linked ?>

        <!-- CHILD'S BATCHES -->
        <div id="child_batches" class="panel">
            <div class="panel-title">🗓️ <?php echo htmlspecialchars($child_name); ?>'s Batches</div>
            <?php if ($child_batches && mysqli_num_rows($child_batches) > 0): ?>
            <div class="card-grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr));">
                <?php while ($b = mysqli_fetch_assoc($child_batches)): ?>
                <div class="card card-accent" style="padding:18px;">
                    <div style="font-weight:700; color:#1a3a5c; margin-bottom:6px;"><?php echo htmlspecialchars($b['batch_name']); ?></div>
                    <div style="color:#2563eb; font-size:.88rem; margin-bottom:8px; font-weight:600;"><?php echo htmlspecialchars($b['subject_name']); ?></div>
                    <div style="font-size:.82rem; color:#374151;">👨‍🏫 <?php echo htmlspecialchars($b['lecturer_name']); ?></div>
                    <div style="font-size:.82rem; color:#374151;">🕒 <?php echo htmlspecialchars($b['schedule']); ?></div>
                </div>
                <?php endwhile; ?>
                <!-- One card per batch the child is actively enrolled in -->
            </div>
            <?php else: ?><p style="color:#64748b;">Not enrolled in any batch yet.</p><?php endif; ?>
        </div>

        <!-- CHILD'S RESULTS -->
        <div id="child_results" class="panel">
            <div class="panel-title">📊 Exam Results</div>
            <div class="table-wrapper"><table>
                <thead><tr><th>Subject</th><th>Exam</th><th>Date</th><th>Marks</th><th>Grade</th></tr></thead>
                <tbody>
                <?php if ($child_results && mysqli_num_rows($child_results) > 0):
                    while ($r = mysqli_fetch_assoc($child_results)):
                        $gc = in_array($r['grade'], ['A+','A','A-']) ? 'badge-green' : ($r['grade'] == 'E' ? 'badge-red' : 'badge-blue'); // Colour-code the grade pill
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['exam_name']); ?></td>
                        <td><?php echo date('d M Y', strtotime($r['exam_date'])); ?></td>
                        <td><strong><?php echo $r['marks']; ?></strong>/<?php echo $r['total_marks']; ?></td>
                        <td><span class="badge <?php echo $gc; ?>"><?php echo $r['grade']; ?></span></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#64748b;">No results yet.</td></tr>
                <?php endif; ?>
                <!-- One row per exam result the child has received -->
                </tbody>
            </table></div>
        </div>

        <!-- CHILD'S ATTENDANCE -->
        <div id="child_attendance" class="panel">
            <div class="panel-title">✅ Attendance Summary</div>
            <div class="table-wrapper"><table>
                <thead><tr><th>Subject</th><th>Total</th><th>Present</th><th>Absent</th><th>Late</th><th>%</th></tr></thead>
                <tbody>
                <?php if ($child_attendance && mysqli_num_rows($child_attendance) > 0):
                    while ($at = mysqli_fetch_assoc($child_attendance)):
                        $pct = $at['total'] > 0 ? round(($at['present'] / $at['total']) * 100) : 0; // Avoid divide-by-zero if no attendance has been recorded for a batch yet
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($at['subject_name']); ?> – <?php echo htmlspecialchars($at['batch_name']); ?></td>
                        <td style="text-align:center;"><?php echo $at['total']; ?></td>
                        <td style="color:#16a34a; font-weight:600; text-align:center;"><?php echo $at['present']; ?></td>
                        <td style="color:#dc2626; font-weight:600; text-align:center;"><?php echo $at['absent']; ?></td>
                        <td style="color:#d97706; font-weight:600; text-align:center;"><?php echo $at['late']; ?></td>
                        <td style="font-weight:700;"><?php echo $pct; ?>%</td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6" style="text-align:center; color:#64748b;">No attendance records yet.</td></tr>
                <?php endif; ?>
                <!-- One row per batch the child is in, summarised by attendance status -->
                </tbody>
            </table></div>
        </div>

        <!-- CHILD'S POINTS -->
        <div id="child_points" class="panel">
            <div class="panel-title">⭐ Performance Points — Total: <strong style="color:#d97706;"><?php echo $total_pts; ?></strong></div>
            <div class="table-wrapper"><table>
                <thead><tr><th>Date</th><th>Points</th><th>Awarded By</th><th>Reason</th></tr></thead>
                <tbody>
                <?php if ($child_pts && mysqli_num_rows($child_pts) > 0):
                    while ($pt = mysqli_fetch_assoc($child_pts)): ?>
                    <tr>
                        <td><?php echo date('d M Y', strtotime($pt['award_date'])); ?></td>
                        <td><span style="color:#d97706; font-weight:700;">+<?php echo $pt['points']; ?></span></td>
                        <td><?php echo htmlspecialchars($pt['awarded_by_name']); ?></td>
                        <td><?php echo htmlspecialchars($pt['reason'] ?: '—'); ?></td>
                        <!-- Falls back to an em-dash if no reason was given -->
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="4" style="text-align:center; color:#64748b;">No points awarded yet.</td></tr>
                <?php endif; ?>
                <!-- One row per performance points award the child has received -->
                </tbody>
            </table></div>
        </div>

        <!-- LEADERBOARD ENTRY -->
        <div id="leaderboard" class="panel">
            <div class="panel-title">🏆 Leaderboard Entry</div>
            <?php if ($child_rank > 0): ?>
                <p style="margin-bottom:14px;"><?php echo htmlspecialchars($child_name); ?>'s Overall Rank: <strong style="color:#d97706; font-size:1.2rem;">#<?php echo $child_rank; ?></strong> of <?php echo count($leaderboard_rows); ?> students</p>
            <?php endif; ?>
            <div class="table-wrapper"><table>
                <thead><tr><th>Rank</th><th>Student</th><th>Total Points</th></tr></thead>
                <tbody>
                <?php if (!empty($leaderboard_rows)):
                    foreach ($leaderboard_rows as $lb):
                        $is_child = $lb['userID'] == $child_id; // Highlight the linked child's own row
                ?>
                    <tr <?php if ($is_child) echo 'style="background:#fef3c7; font-weight:700;"'; ?>>
                        <td>#<?php echo $lb['rank']; ?></td>
                        <td><?php echo htmlspecialchars($lb['full_name']); ?> <?php if ($is_child) echo '<span class="badge badge-yellow">Your Child</span>'; ?></td>
                        <td style="color:#d97706; font-weight:700;"><?php echo $lb['total_points']; ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="3" style="text-align:center; color:#64748b;">No leaderboard data available yet.</td></tr>
                <?php endif; ?>
                <!-- One row per student in the system, ranked by total performance points across all their batches -->
                </tbody>
            </table></div>
        </div>

        <!-- CHILD'S PAYMENTS -->
        <div id="child_payments" class="panel">
            <div class="panel-title">💳 Payment History</div>
            <div class="table-wrapper"><table>
                <thead><tr><th>Receipt No.</th><th>Subject</th><th>Month</th><th>Amount</th><th>Status</th><th>Print</th></tr></thead>
                <tbody>
                <?php if ($child_payments && mysqli_num_rows($child_payments) > 0):
                    while ($p = mysqli_fetch_assoc($child_payments)):
                        $pb = $p['status'] == 'approved' ? 'badge-green' : ($p['status'] == 'rejected' ? 'badge-red' : 'badge-yellow'); // Colour-code the status pill
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['receipt_no'] ?: '—'); ?></td>
                        <td><?php echo htmlspecialchars($p['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($p['pay_month']); ?></td>
                        <td>LKR <?php echo number_format($p['amount'], 2); ?></td>
                        <td><span class="badge <?php echo $pb; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                        <td>
                            <?php if ($p['status'] == 'approved'): ?>
                                <a href="print_receipt.php?id=<?php echo $p['paymentID']; ?>" target="_blank" class="btn btn-small btn-primary">🖨️</a>
                                <!-- Only meaningful once a payment is approved -->
                            <?php else: ?><span style="color:#94a3b8; font-size:.78rem;">N/A</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6" style="text-align:center; color:#64748b;">No payment records.</td></tr>
                <?php endif; ?>
                <!-- One row per payment ever made for this child -->
                </tbody>
            </table></div>
        </div>

        <?php endif; // end if($child_id) ?>

        <!-- ANNOUNCEMENTS -->
        <div id="announcements" class="panel">
            <div class="panel-title">📢 Announcements</div>
            <?php if ($announcements && mysqli_num_rows($announcements) > 0):
                while ($ann = mysqli_fetch_assoc($announcements)): ?>
                <div class="announcement">
                    <h4>📢 <?php echo htmlspecialchars($ann['title']); ?></h4>
                    <p><?php echo htmlspecialchars($ann['message']); ?></p>
                    <p class="ann-date">Posted on <?php echo date('d M Y', strtotime($ann['post_date'])); ?></p>
                </div>
            <?php endwhile; else: ?>
                <p style="color:#64748b;">No announcements at this time.</p>
                <!-- Shown only if there are zero announcements aimed at 'all' or 'parents' -->
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Sidebar active section highlight -->
<script>
(function() {
    var links  = document.querySelectorAll('.sidebar-nav a'); // Every link in the sidebar
    var panels = [];                                          // Will hold {el, link} pairs — the panel each link points to
    links.forEach(function(link) {
        var id = link.getAttribute('href').replace('#', ''); // Strip the leading "#" from e.g. "#child_results" to get "child_results"
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
