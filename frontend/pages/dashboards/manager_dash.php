<?php
// ============================================================
//  manager_dash.php — Manager Dashboard
//  View-only monitoring: batches, students, lecturers,
//  attendance, performance, and revenue.
// ============================================================

$cnt_students  = count_rows($conn, 'users',   "role='student'");
$cnt_lecturers = count_rows($conn, 'users',   "role='lecturer'");
$cnt_batches   = count_rows($conn, 'batches', "status='active'");
$rev           = get_one_row($conn, "SELECT SUM(amount) AS total FROM payments WHERE status='approved'");
$total_revenue = $rev ? (float)$rev['total'] : 0;

// Batches with enrollment count and attendance %
$batches = mysqli_query($conn, "
    SELECT b.*, s.name AS subject_name, u.full_name AS lecturer_name,
        (SELECT COUNT(*) FROM enrollments e WHERE e.batch_id = b.id AND e.status = 'active') AS enrolled,
        (SELECT COUNT(*) FROM attendance a WHERE a.batch_id = b.id AND a.status = 'present') AS present_count,
        (SELECT COUNT(*) FROM attendance a WHERE a.batch_id = b.id) AS total_att
    FROM batches b
    JOIN subjects s ON b.subject_id = s.id
    JOIN users u ON b.lecturer_id = u.id
    ORDER BY b.status DESC, b.batch_name
");

// All students with how many batches they are in
$students = mysqli_query($conn, "
    SELECT u.id, u.full_name, u.email, u.phone, u.status,
        COUNT(DISTINCT e.id) AS batch_count
    FROM users u
    LEFT JOIN enrollments e ON u.id = e.student_id AND e.status = 'active'
    WHERE u.role = 'student'
    GROUP BY u.id
    ORDER BY u.full_name
");

// All lecturers with their batch count
$lecturers = mysqli_query($conn, "
    SELECT u.id, u.full_name, u.email, u.phone, u.status,
        COUNT(DISTINCT b.id) AS batch_count
    FROM users u
    LEFT JOIN batches b ON u.id = b.lecturer_id AND b.status = 'active'
    WHERE u.role = 'lecturer'
    GROUP BY u.id
    ORDER BY u.full_name
");

// Attendance summary per student per batch
$attendance = mysqli_query($conn, "
    SELECT b.batch_name, u.full_name AS student_name,
        COUNT(a.id) AS total_classes,
        SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) AS present_count,
        SUM(CASE WHEN a.status = 'absent'  THEN 1 ELSE 0 END) AS absent_count,
        SUM(CASE WHEN a.status = 'late'    THEN 1 ELSE 0 END) AS late_count
    FROM attendance a
    JOIN users u ON a.student_id = u.id
    JOIN batches b ON a.batch_id = b.id
    GROUP BY a.student_id, a.batch_id
    ORDER BY b.batch_name, u.full_name
");

// Student performance summary
$performance = mysqli_query($conn, "
    SELECT u.full_name, b.batch_name, s.name AS subject_name,
        COUNT(r.id) AS exam_count,
        ROUND(AVG(r.marks), 1) AS avg_marks,
        MAX(r.marks) AS best_marks,
        MIN(r.marks) AS lowest_marks
    FROM users u
    LEFT JOIN results r ON u.id = r.student_id
    LEFT JOIN batches b ON r.batch_id = b.id
    LEFT JOIN subjects s ON b.subject_id = s.id
    WHERE u.role = 'student'
    GROUP BY u.id, r.batch_id
    HAVING exam_count > 0
    ORDER BY avg_marks DESC
");

// Revenue by month and status
$revenue = mysqli_query($conn, "
    SELECT pay_month, SUM(amount) AS total, status
    FROM payments
    GROUP BY pay_month, status
    ORDER BY pay_month DESC
    LIMIT 12
");
?>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header"><h3>📋 Manager</h3><p><?php echo $full_name; ?></p></div>
        <nav class="sidebar-nav">
            <a href="#overview"    class="active"><span class="sidebar-icon">📊</span> Overview</a>
            <a href="#batches">                   <span class="sidebar-icon">🗓️</span> Batches</a>
            <a href="#students">                  <span class="sidebar-icon">🎓</span> Students</a>
            <a href="#lecturers">                 <span class="sidebar-icon">👨‍🏫</span> Lecturers</a>
            <a href="#attendance">                <span class="sidebar-icon">✅</span> Attendance</a>
            <a href="#performance">               <span class="sidebar-icon">📈</span> Performance</a>
            <a href="#revenue">                   <span class="sidebar-icon">💰</span> Revenue</a>
            <a href="#reports">                   <span class="sidebar-icon">📑</span> Reports</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <h1 class="dashboard-title">Manager Dashboard</h1>
        <p class="dashboard-subtitle">Welcome, <?php echo $full_name; ?>.</p>

        <?php if (isset($_GET['msg'])): ?>
            <div style="background:#dcfce7; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:20px;">✅ <?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>

        <!-- OVERVIEW STATS -->
        <div id="overview" class="stats-grid">
            <div class="stat-card">       <div class="stat-number"><?php echo $cnt_students; ?></div> <div class="stat-label">Total Students</div></div>
            <div class="stat-card green"> <div class="stat-number"><?php echo $cnt_lecturers; ?></div><div class="stat-label">Lecturers</div></div>
            <div class="stat-card orange"><div class="stat-number"><?php echo $cnt_batches; ?></div>  <div class="stat-label">Active Batches</div></div>
            <div class="stat-card green"> <div class="stat-number">LKR <?php echo number_format($total_revenue / 1000, 1); ?>K</div><div class="stat-label">Revenue Collected</div></div>
        </div>

        <!-- BATCH MONITORING -->
        <div id="batches" class="panel">
            <div class="panel-title">🗓️ Batch Monitoring</div>
            <div class="table-wrapper"><table>
                <thead><tr><th>Batch</th><th>Subject</th><th>Lecturer</th><th>Enrolled</th><th>Capacity</th><th>Attendance %</th><th>Status</th></tr></thead>
                <tbody>
                <?php if ($batches && mysqli_num_rows($batches) > 0):
                    while ($b = mysqli_fetch_assoc($batches)):
                        $att_pct = $b['total_att'] > 0 ? round(($b['present_count'] / $b['total_att']) * 100) : 0;
                        $bar     = $att_pct >= 90 ? 'green' : ($att_pct >= 75 ? '' : 'orange');
                        $badge   = $b['status'] == 'active' ? 'badge-green' : ($b['status'] == 'upcoming' ? 'badge-yellow' : 'badge-gray');
                ?>
                    <tr>
                        <td><?php echo $b['batch_name']; ?></td>
                        <td><?php echo $b['subject_name']; ?></td>
                        <td><?php echo $b['lecturer_name']; ?></td>
                        <td><?php echo $b['enrolled']; ?></td>
                        <td><?php echo $b['capacity']; ?></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="flex:1;"><div class="progress-bar-wrapper"><div class="progress-bar-fill <?php echo $bar; ?>" style="width:<?php echo $att_pct; ?>%;"></div></div></div>
                                <span style="font-size:0.8rem; font-weight:600;"><?php echo $att_pct; ?>%</span>
                            </div>
                        </td>
                        <td><span class="badge <?php echo $badge; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="7" style="text-align:center; color:#64748b;">No batches found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table></div>
        </div>

        <!-- STUDENT DETAILS -->
        <div id="students" class="panel">
            <div class="panel-title">🎓 Student Details</div>
            <div class="table-wrapper"><table>
                <thead><tr><th>#</th><th>Full Name</th><th>Email</th><th>Phone</th><th>Active Batches</th><th>Status</th></tr></thead>
                <tbody>
                <?php $sno = 1;
                if ($students && mysqli_num_rows($students) > 0):
                    while ($st = mysqli_fetch_assoc($students)):
                        $stb = $st['status'] == 'active' ? 'badge-green' : 'badge-red';
                ?>
                    <tr>
                        <td><?php echo $sno++; ?></td>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($st['full_name']); ?></td>
                        <td style="font-size:0.82rem;"><?php echo htmlspecialchars($st['email'] ?: '—'); ?></td>
                        <td><?php echo htmlspecialchars($st['phone'] ?: '—'); ?></td>
                        <td style="text-align:center;"><?php echo $st['batch_count']; ?></td>
                        <td><span class="badge <?php echo $stb; ?>"><?php echo ucfirst($st['status']); ?></span></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6" style="text-align:center; color:#64748b;">No students found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table></div>
        </div>

        <!-- LECTURER DETAILS -->
        <div id="lecturers" class="panel">
            <div class="panel-title">👨‍🏫 Lecturer Details</div>
            <div class="table-wrapper"><table>
                <thead><tr><th>#</th><th>Full Name</th><th>Email</th><th>Phone</th><th>Active Batches</th><th>Status</th></tr></thead>
                <tbody>
                <?php $lno = 1;
                if ($lecturers && mysqli_num_rows($lecturers) > 0):
                    while ($lc = mysqli_fetch_assoc($lecturers)):
                        $lcb = $lc['status'] == 'active' ? 'badge-green' : 'badge-red';
                ?>
                    <tr>
                        <td><?php echo $lno++; ?></td>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($lc['full_name']); ?></td>
                        <td style="font-size:0.82rem;"><?php echo htmlspecialchars($lc['email'] ?: '—'); ?></td>
                        <td><?php echo htmlspecialchars($lc['phone'] ?: '—'); ?></td>
                        <td style="text-align:center;"><?php echo $lc['batch_count']; ?></td>
                        <td><span class="badge <?php echo $lcb; ?>"><?php echo ucfirst($lc['status']); ?></span></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="6" style="text-align:center; color:#64748b;">No lecturers found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table></div>
        </div>

        <!-- ATTENDANCE MONITOR -->
        <div id="attendance" class="panel">
            <div class="panel-title">✅ Monitor Attendance</div>
            <div class="table-wrapper"><table>
                <thead><tr><th>Student</th><th>Batch</th><th>Total Classes</th><th>Present</th><th>Absent</th><th>Late</th><th>Attendance %</th></tr></thead>
                <tbody>
                <?php if ($attendance && mysqli_num_rows($attendance) > 0):
                    while ($at = mysqli_fetch_assoc($attendance)):
                        $att_pct    = $at['total_classes'] > 0 ? round(($at['present_count'] / $at['total_classes']) * 100) : 0;
                        $pct_colour = $att_pct >= 80 ? '#16a34a' : ($att_pct >= 60 ? '#d97706' : '#dc2626');
                ?>
                    <tr>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($at['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($at['batch_name']); ?></td>
                        <td style="text-align:center;"><?php echo $at['total_classes']; ?></td>
                        <td style="text-align:center; color:#16a34a; font-weight:600;"><?php echo $at['present_count']; ?></td>
                        <td style="text-align:center; color:#dc2626; font-weight:600;"><?php echo $at['absent_count']; ?></td>
                        <td style="text-align:center; color:#d97706; font-weight:600;"><?php echo $at['late_count']; ?></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="flex:1;"><div class="progress-bar-wrapper"><div class="progress-bar-fill" style="width:<?php echo $att_pct; ?>%; background:<?php echo $pct_colour; ?>;"></div></div></div>
                                <span style="font-size:0.8rem; font-weight:600; color:<?php echo $pct_colour; ?>;"><?php echo $att_pct; ?>%</span>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="7" style="text-align:center; color:#64748b;">No attendance records found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table></div>
        </div>

        <!-- PERFORMANCE MONITOR -->
        <div id="performance" class="panel">
            <div class="panel-title">📈 Monitor Student Performance</div>
            <div class="table-wrapper"><table>
                <thead><tr><th>Student</th><th>Subject</th><th>Batch</th><th>Exams</th><th>Avg Marks</th><th>Best</th><th>Lowest</th><th>Rating</th></tr></thead>
                <tbody>
                <?php if ($performance && mysqli_num_rows($performance) > 0):
                    while ($pf = mysqli_fetch_assoc($performance)):
                        $mark_colour = $pf['avg_marks'] >= 75 ? '#16a34a' : ($pf['avg_marks'] >= 50 ? '#d97706' : '#dc2626');
                        $rating = $pf['avg_marks'] >= 85 ? '⭐ Excellent' : ($pf['avg_marks'] >= 70 ? '👍 Good' : ($pf['avg_marks'] >= 50 ? '⚠️ Average' : '❌ Needs Help'));
                ?>
                    <tr>
                        <td style="font-weight:600;"><?php echo htmlspecialchars($pf['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($pf['subject_name'] ?: '—'); ?></td>
                        <td style="font-size:0.85rem;"><?php echo htmlspecialchars($pf['batch_name'] ?: '—'); ?></td>
                        <td style="text-align:center;"><?php echo $pf['exam_count']; ?></td>
                        <td style="font-weight:700; color:<?php echo $mark_colour; ?>;"><?php echo $pf['avg_marks']; ?>%</td>
                        <td style="color:#16a34a;"><?php echo $pf['best_marks']; ?></td>
                        <td style="color:#dc2626;"><?php echo $pf['lowest_marks']; ?></td>
                        <td><?php echo $rating; ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="8" style="text-align:center; color:#64748b;">No performance data available.</td></tr>
                <?php endif; ?>
                </tbody>
            </table></div>
        </div>

        <!-- REVENUE SUMMARY -->
        <div id="revenue" class="panel">
            <div class="panel-title">💰 Revenue Summary</div>
            <div class="table-wrapper"><table>
                <thead><tr><th>Month</th><th>Amount (LKR)</th><th>Status</th></tr></thead>
                <tbody>
                <?php if ($revenue && mysqli_num_rows($revenue) > 0):
                    while ($rv = mysqli_fetch_assoc($revenue)):
                        $rb = $rv['status'] == 'approved' ? 'badge-green' : ($rv['status'] == 'rejected' ? 'badge-red' : 'badge-yellow');
                ?>
                    <tr>
                        <td><?php echo $rv['pay_month']; ?></td>
                        <td style="font-weight:600;">LKR <?php echo number_format($rv['total'], 2); ?></td>
                        <td><span class="badge <?php echo $rb; ?>"><?php echo ucfirst($rv['status']); ?></span></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="3" style="text-align:center; color:#64748b;">No payment records yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table></div>
        </div>

        <!-- REPORTS -->
        <div id="reports" class="panel">
            <div class="panel-title">📈 Reports</div>
            <div class="card-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
                <div class="card card-accent" style="text-align:center; padding:28px 20px;">
                    <div style="font-size:2.5rem; margin-bottom:12px;">💰</div><h3>Financial Report</h3>
                    <a href="report_financial.php" class="btn btn-primary" style="display:block; margin-top:16px;">Open Report</a>
                </div>
                <div class="card card-accent" style="text-align:center; padding:28px 20px;">
                    <div style="font-size:2.5rem; margin-bottom:12px;">📚</div><h3>Academic Report</h3>
                    <a href="report_academic.php" class="btn btn-primary" style="display:block; margin-top:16px;">Open Report</a>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Sidebar: highlight the section currently visible on screen -->
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
        var scrollY   = window.scrollY + 120;
        var current   = panels[0];
        panels.forEach(function(p) { if (p.el.offsetTop <= scrollY) current = p; });
        links.forEach(function(l) { l.classList.remove('active'); });
        if (current) current.link.classList.add('active');
    }
    window.addEventListener('scroll', setActive, { passive: true });
    setActive();
})();
</script>
