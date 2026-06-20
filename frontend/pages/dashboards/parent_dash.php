<?php
// ============================================================
//  parent_dash.php — Parent Dashboard
//  Parents can see their child's: batches, results,
//  attendance, performance points, payments, announcements.
// ============================================================

// Get the child linked to this parent
$child_link  = get_one_row($conn, "SELECT student_id FROM parent_student WHERE parent_id = $user_id LIMIT 1");
$child_id    = $child_link ? (int)$child_link['student_id'] : 0;
$child_info  = $child_id ? get_one_row($conn, "SELECT * FROM users WHERE id = $child_id") : null;
$child_name  = $child_info ? $child_info['full_name'] : 'Not linked';

// Get child's batch IDs
$child_batch_ids = [];
$cbr = mysqli_query($conn, "SELECT batch_id FROM enrollments WHERE student_id=$child_id AND status='active'");
while ($r = mysqli_fetch_assoc($cbr)) $child_batch_ids[] = $r['batch_id'];
$batch_ids_str = empty($child_batch_ids) ? '0' : implode(',', $child_batch_ids);

// Child's batches
$child_batches = mysqli_query($conn, "
    SELECT b.*, s.name AS subject_name, u.full_name AS lecturer_name
    FROM enrollments e
    JOIN batches b ON e.batch_id = b.id
    JOIN subjects s ON b.subject_id = s.id
    JOIN users u ON b.lecturer_id = u.id
    WHERE e.student_id = $child_id AND e.status = 'active'
");

// Child's exam results
$child_results = mysqli_query($conn, "
    SELECT r.*, b.batch_name, s.name AS subject_name
    FROM results r
    JOIN batches b ON r.batch_id = b.id
    JOIN subjects s ON b.subject_id = s.id
    WHERE r.student_id = $child_id
    ORDER BY r.exam_date DESC
");

// Child's attendance summary
$child_attendance = mysqli_query($conn, "
    SELECT b.batch_name, s.name AS subject_name,
        COUNT(a.id) AS total,
        SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN a.status='absent'  THEN 1 ELSE 0 END) AS absent,
        SUM(CASE WHEN a.status='late'    THEN 1 ELSE 0 END) AS late
    FROM attendance a
    JOIN batches b ON a.batch_id = b.id
    JOIN subjects s ON b.subject_id = s.id
    WHERE a.student_id = $child_id
    GROUP BY a.batch_id
");

// Child's performance points
$pts_row     = get_one_row($conn, "SELECT SUM(points) AS total FROM performance_points WHERE student_id = $child_id");
$total_pts   = $pts_row ? (int)$pts_row['total'] : 0;
$child_pts   = mysqli_query($conn, "SELECT pp.*, u.full_name AS awarded_by_name, b.batch_name FROM performance_points pp JOIN users u ON pp.awarded_by=u.id LEFT JOIN batches b ON pp.batch_id=b.id WHERE pp.student_id=$child_id ORDER BY pp.id DESC");

// Child's payment history
$child_payments = mysqli_query($conn, "
    SELECT p.*, b.batch_name, s.name AS subject_name
    FROM payments p
    JOIN batches b ON p.batch_id = b.id
    JOIN subjects s ON b.subject_id = s.id
    WHERE p.student_id = $child_id
    ORDER BY p.id DESC
");

// Announcements for all or parents
$announcements = mysqli_query($conn, "
    SELECT a.*, u.full_name AS posted_by_name
    FROM announcements a
    LEFT JOIN users u ON a.posted_by = u.id
    WHERE a.audience IN ('all', 'parents')
    ORDER BY a.created_at DESC LIMIT 10
");

// Class links for child's batches
?>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header"><h3>👨‍👩‍👧 Parent Portal</h3><p><?php echo htmlspecialchars($full_name); ?></p></div>
        <nav class="sidebar-nav">
            <a href="#child_overview" class="active"><span class="sidebar-icon">👦</span> Child Overview</a>
            <a href="#child_batches">               <span class="sidebar-icon">🗓️</span> Batches</a>
            <a href="#child_results">               <span class="sidebar-icon">📊</span> Exam Results</a>
            <a href="#child_attendance">            <span class="sidebar-icon">✅</span> Attendance</a>
            <a href="#child_points">                <span class="sidebar-icon">⭐</span> Points</a>
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
                <div class="stat-label"><?php echo htmlspecialchars($child_name); ?></div>
            </div>
            <div class="stat-card green">  <div class="stat-number"><?php echo count($child_batch_ids); ?></div> <div class="stat-label">Active Batches</div></div>
            <div class="stat-card orange"> <div class="stat-number"><?php echo $total_pts; ?></div>              <div class="stat-label">Total Points ⭐</div></div>
            <?php else: ?>
            <div class="stat-card" style="grid-column:1/-1; text-align:center; padding:30px; color:#64748b;">
                ⚠️ No student is linked to your account yet. Please contact the admin.
            </div>
            <?php endif; ?>
        </div>

        <?php if ($child_id): ?>

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
                        $gc = in_array($r['grade'], ['A+','A','A-']) ? 'badge-green' : ($r['grade'] == 'E' ? 'badge-red' : 'badge-blue');
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
                        $pct = $at['total'] > 0 ? round(($at['present'] / $at['total']) * 100) : 0;
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
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="4" style="text-align:center; color:#64748b;">No points awarded yet.</td></tr>
                <?php endif; ?>
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
                        $pb = $p['status'] == 'approved' ? 'badge-green' : ($p['status'] == 'rejected' ? 'badge-red' : 'badge-yellow');
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['receipt_no'] ?: '—'); ?></td>
                        <td><?php echo htmlspecialchars($p['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($p['pay_month']); ?></td>
                        <td>LKR <?php echo number_format($p['amount'], 2); ?></td>
                        <td><span class="badge <?php echo $pb; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                        <td>
                            <?php if ($p['status'] == 'approved'): ?>
                                <a href="print_receipt.php?id=<?php echo $p['id']; ?>" target="_blank" class="btn btn-small btn-primary">🖨️</a>
                            <?php else: ?><span style="color:#94a3b8; font-size:.78rem;">N/A</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6" style="text-align:center; color:#64748b;">No payment records.</td></tr>
                <?php endif; ?>
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
            <?php endif; ?>
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
