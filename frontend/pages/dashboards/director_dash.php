<?php
// ============================================================
//  director_dash.php — Director Dashboard
//  Read-only overview: key stats, top students, batch summary.
// ============================================================

// Stats
$cnt_students  = count_rows($conn, 'users',   "role='student'");   // Total student accounts
$cnt_lecturers = count_rows($conn, 'users',   "role='lecturer'");  // Total lecturer accounts
$cnt_batches   = count_rows($conn, 'batches', "status='active'");  // Currently active batches
$cnt_subjects  = count_rows($conn, 'subjects');                    // Total subjects offered
$rev           = get_one_row($conn, "SELECT SUM(amount) AS total FROM payments WHERE status='approved'"); // Sum of every approved payment
$total_revenue = $rev ? (float)$rev['total'] : 0; // Falls back to 0 if there are no approved payments yet

// Top 5 students by average exam score
$top_students = mysqli_query($conn, "
    SELECT u.full_name, u.id,
        ROUND(AVG(r.marks), 1) AS avg_score,
        COUNT(r.id) AS exams_taken,
        (SELECT SUM(pp.points) FROM performance_points pp WHERE pp.student_id = u.id) AS total_pts
    FROM users u
    LEFT JOIN results r ON r.student_id = u.id
    WHERE u.role = 'student'
    GROUP BY u.id, u.full_name
    HAVING avg_score IS NOT NULL
    ORDER BY avg_score DESC
    LIMIT 5
"); // HAVING avg_score IS NOT NULL excludes students who haven't taken any exams yet (AVG of nothing is NULL)

// Batch summary with attendance percentage
$batches = mysqli_query($conn, "
    SELECT b.batch_name, s.name AS subject_name, u.full_name AS lecturer_name,
        (SELECT COUNT(*) FROM enrollments e WHERE e.batch_id = b.id AND e.status = 'active') AS enrolled,
        (SELECT ROUND(AVG(r.marks), 1) FROM results r WHERE r.batch_id = b.id) AS avg_score,
        (SELECT COUNT(*) FROM attendance a WHERE a.batch_id = b.id AND a.status = 'present') AS present_count,
        (SELECT COUNT(*) FROM attendance a WHERE a.batch_id = b.id) AS total_att,
        b.status
    FROM batches b
    JOIN subjects s ON b.subject_id = s.id
    JOIN users u ON b.lecturer_id = u.id
    ORDER BY b.status DESC
"); // Each metric (enrolled, avg_score, attendance counts) is its own subquery — keeps the main query simple to read

// Medal icons for top students
$medals = ['🥇', '🥈', '🥉']; // Only the top 3 get a medal; 4th/5th place fall back to "#4"/"#5"

// Podium card styles for top 3
$podium_styles = [
    1 => 'background:linear-gradient(135deg,#fef3c7,#fde68a); border:2px solid #f59e0b;', // Gold for 1st
    2 => 'background:linear-gradient(135deg,#f1f5f9,#e2e8f0); border:2px solid #94a3b8;', // Silver for 2nd
    3 => 'background:linear-gradient(135deg,#fde8d8,#fed7aa); border:2px solid #ea580c;', // Bronze for 3rd
];
?>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header"><h3>🏛️ Director</h3><p><?php echo $full_name; ?></p></div>
        <nav class="sidebar-nav">
            <!-- Each link is a same-page anchor (#id) — clicking jumps straight to that panel below -->
            <a href="#stats"       class="active"><span class="sidebar-icon">📊</span> Key Stats</a>
            <a href="#leaderboard">               <span class="sidebar-icon">🏆</span> Top Students</a>
            <a href="#batch_rpt">                 <span class="sidebar-icon">📋</span> Batch Report</a>
            <a href="#reports">                   <span class="sidebar-icon">📈</span> Reports</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <h1 class="dashboard-title">Director Dashboard</h1>
        <p class="dashboard-subtitle">Welcome, <?php echo $full_name; ?>. Institute Overview.</p>

        <?php if (isset($_GET['msg'])): ?>
            <div style="background:#dcfce7; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:20px;">✅ <?php echo htmlspecialchars($_GET['msg']); ?></div>
            <!-- Comes from a redirect like dashboard.php?msg=..., set by various CRUD scripts -->
        <?php endif; ?>

        <!-- STATS -->
        <div id="stats" class="stats-grid">
            <div class="stat-card">       <div class="stat-number"><?php echo $cnt_students; ?></div> <div class="stat-label">Total Students</div></div>
            <div class="stat-card green"> <div class="stat-number"><?php echo $cnt_lecturers; ?></div><div class="stat-label">Lecturers</div></div>
            <div class="stat-card orange"><div class="stat-number"><?php echo $cnt_batches; ?></div>  <div class="stat-label">Active Batches</div></div>
            <div class="stat-card">       <div class="stat-number"><?php echo $cnt_subjects; ?></div> <div class="stat-label">Subjects</div></div>
            <div class="stat-card green"> <div class="stat-number">LKR <?php echo number_format($total_revenue / 1000, 1); ?>K</div><div class="stat-label">Revenue Collected</div></div>
            <!-- Divides by 1000 and shows one decimal, e.g. "245.5K" instead of "245,500" -->
        </div>

        <!-- TOP STUDENTS LEADERBOARD -->
        <div id="leaderboard" class="panel">
            <div class="panel-title">🏆 Top Students – By Average Score</div>
            <?php if ($top_students && mysqli_num_rows($top_students) > 0):
                $rank = 1; // Tracks each student's position in the list as we loop
                while ($ts = mysqli_fetch_assoc($top_students)):
                    $style = $podium_styles[$rank] ?? 'border:1px solid #e2e8f0;'; // Gold/silver/bronze styling for top 3, plain border after that
                    $medal = $medals[$rank - 1] ?? '#' . $rank;                    // $medals[0] = 1st place, etc.; falls back to "#4"/"#5"
                    $pts   = $ts['total_pts'] ?? 0;                                 // Falls back to 0 if the student has never been awarded points
            ?>
            <div style="<?php echo $style; ?> border-radius:10px; padding:16px 20px; margin-bottom:12px; display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                <div style="font-size:1.8rem;"><?php echo $medal; ?></div>
                <div style="flex:1; min-width:120px;">
                    <div style="font-weight:700; font-size:0.95rem;"><?php echo $ts['full_name']; ?></div>
                    <div style="font-size:0.78rem; color:#64748b;"><?php echo $ts['exams_taken']; ?> exams taken</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:1.2rem; font-weight:800; color:#1a3a5c;"><?php echo $ts['avg_score']; ?>%</div>
                    <div style="font-size:0.75rem; color:#64748b;">avg score</div>
                </div>
                <div style="text-align:right;">
                    <div style="font-size:1.2rem; font-weight:800; color:#d97706;"><?php echo $pts; ?></div>
                    <div style="font-size:0.75rem; color:#64748b;">points</div>
                </div>
            </div>
            <?php $rank++; endwhile; // Move to the next rank for the next student
            else: ?>
                <p style="color:#64748b;">No exam results yet.</p>
                <!-- Shown only if there are zero graded results in the system -->
            <?php endif; ?>
            <a href="report_academic.php" class="btn btn-primary btn-small" style="margin-top:8px;">View Full Academic Report →</a>
        </div>

        <!-- BATCH SUMMARY -->
        <div id="batch_rpt" class="panel">
            <div class="panel-title">📋 Batch Summary</div>
            <div class="table-wrapper"><table>
                <thead><tr><th>Batch</th><th>Subject</th><th>Lecturer</th><th>Students</th><th>Avg Score</th><th>Attendance %</th><th>Status</th></tr></thead>
                <tbody>
                <?php if ($batches && mysqli_num_rows($batches) > 0):
                    while ($br = mysqli_fetch_assoc($batches)):
                        // Calculate attendance percentage
                        $apct  = $br['total_att'] > 0 ? round(($br['present_count'] / $br['total_att']) * 100) : 0; // Avoid divide-by-zero if a batch has no attendance records yet
                        $badge = $br['status'] == 'active' ? 'badge-green' : ($br['status'] == 'upcoming' ? 'badge-yellow' : 'badge-gray'); // Colour-code the status pill
                        $bar   = $apct >= 90 ? 'green' : ($apct >= 75 ? '' : 'orange'); // Colour-code the attendance progress bar
                ?>
                    <tr>
                        <td><?php echo $br['batch_name']; ?></td>
                        <td><?php echo $br['subject_name']; ?></td>
                        <td><?php echo $br['lecturer_name']; ?></td>
                        <td><?php echo $br['enrolled']; ?></td>
                        <td><?php echo $br['avg_score'] ? $br['avg_score'] . '%' : '—'; ?></td>
                        <!-- Falls back to an em-dash if the batch has no exam results yet -->
                        <td>
                            <?php if ($br['total_att'] > 0): ?>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="flex:1;"><div class="progress-bar-wrapper"><div class="progress-bar-fill <?php echo $bar; ?>" style="width:<?php echo $apct; ?>%;"></div></div></div>
                                <span style="font-size:0.8rem; font-weight:600;"><?php echo $apct; ?>%</span>
                            </div>
                            <?php else: ?><span style="color:#94a3b8; font-size:0.82rem;">No data</span><?php endif; ?>
                            <!-- Shows the bar only if the batch actually has attendance records to calculate from -->
                        </td>
                        <td><span class="badge <?php echo $badge; ?>"><?php echo ucfirst($br['status']); ?></span></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="7" style="text-align:center; color:#64748b;">No batches found.</td></tr>
                <?php endif; ?>
                <!-- One row per batch in the system -->
                </tbody>
            </table></div>
        </div>

        <!-- REPORTS LINKS -->
        <div id="reports" class="panel">
            <div class="panel-title">📈 Generate Reports</div>
            <div class="card-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
                <div class="card" style="border-top:4px solid #16a34a; text-align:center; padding:28px 20px;">
                    <div style="font-size:2.5rem; margin-bottom:12px;">💰</div>
                    <h3>Financial Report</h3>
                    <p style="color:#64748b; font-size:0.85rem; margin-bottom:16px;">All payment records and revenue.</p>
                    <a href="report_financial.php" class="btn btn-primary" style="display:block;">Open Financial Report</a>
                </div>
                <div class="card" style="border-top:4px solid #2563eb; text-align:center; padding:28px 20px;">
                    <div style="font-size:2.5rem; margin-bottom:12px;">📚</div>
                    <h3>Academic Report</h3>
                    <p style="color:#64748b; font-size:0.85rem; margin-bottom:16px;">Exam results, attendance and top students.</p>
                    <a href="report_academic.php" class="btn btn-primary" style="display:block;">Open Academic Report</a>
                </div>
            </div>
        </div>
    </main>
</div>
