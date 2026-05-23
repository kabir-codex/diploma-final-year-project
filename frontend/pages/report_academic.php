<?php
// ============================================================
//  report_academic.php — Academic Report
//  Shows exam results, attendance, and top students.
//  Accessible by admin, manager, and director.
// ============================================================

session_start();
require '../../backend/config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','manager','director'])) {
    header("Location: login.php"); exit();
}

// Filter by batch
$filter_batch = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : 0;
$where_batch  = $filter_batch ? "AND r.batch_id = $filter_batch" : "";

// All batches for filter dropdown
$batches_dd = mysqli_query($conn, "SELECT b.id, b.batch_name, s.name AS subject_name FROM batches b JOIN subjects s ON b.subject_id=s.id ORDER BY b.batch_name");

// All exam results (with optional batch filter)
$results = mysqli_query($conn, "
    SELECT r.*, u.full_name AS student_name, b.batch_name, s.name AS subject_name
    FROM results r
    JOIN users u ON r.student_id = u.id
    JOIN batches b ON r.batch_id = b.id
    JOIN subjects s ON b.subject_id = s.id
    WHERE 1=1 $where_batch
    ORDER BY r.exam_date DESC
");

// Top 5 students by average score (with optional batch filter)
$top_students = mysqli_query($conn, "
    SELECT u.full_name, ROUND(AVG(r.marks),1) AS avg_score, COUNT(r.id) AS exams
    FROM results r
    JOIN users u ON r.student_id = u.id
    WHERE 1=1 $where_batch
    GROUP BY r.student_id
    ORDER BY avg_score DESC
    LIMIT 5
");

// Attendance summary per batch
$attendance = mysqli_query($conn, "
    SELECT b.batch_name, s.name AS subject_name,
        COUNT(a.id) AS total,
        SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN a.status='absent'  THEN 1 ELSE 0 END) AS absent,
        SUM(CASE WHEN a.status='late'    THEN 1 ELSE 0 END) AS late
    FROM attendance a
    JOIN batches b ON a.batch_id = b.id
    JOIN subjects s ON b.subject_id = s.id
    GROUP BY a.batch_id
    ORDER BY b.batch_name
");

// Grade distribution — count how many A, B, C, F grades exist
$grade_dist = mysqli_query($conn, "SELECT grade, COUNT(*) AS cnt FROM results WHERE grade IS NOT NULL GROUP BY grade ORDER BY grade");
$grade_data = [];
while ($gd = mysqli_fetch_assoc($grade_dist)) {
    $grade_data[$gd['grade']] = $gd['cnt'];
}
$total_graded = array_sum($grade_data);

// Pass rate — marks >= 50 considered pass
$pass_row  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM results WHERE marks >= 50"));
$fail_row  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM results WHERE marks < 50"));
$total_res = ($pass_row['cnt'] ?? 0) + ($fail_row['cnt'] ?? 0);
$pass_pct  = $total_res > 0 ? round(($pass_row['cnt'] / $total_res) * 100) : 0;

$page_title = "Academic Report"; $css_path = "../../frontend/assets/css/style.css"; $root_path = "../../"; $active_page = "";
include '../../frontend/assets/header.php';
?>

<div class="section" style="max-width:900px; margin:0 auto;">

    <!-- Header -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:24px;">
        <div>
            <h1 style="color:#1a3a5c; font-size:1.6rem; font-weight:800;">📚 Academic Report</h1>
            <p style="color:#64748b; font-size:0.88rem;">Activate Academy | Generated on <?php echo date('d M Y, h:i A'); ?></p>
        </div>
        <button onclick="window.print();" class="btn btn-primary">🖨️ Print / Save PDF</button>
    </div>

    <!-- Summary Stats -->
    <div class="stats-grid" style="margin-bottom:28px;">
        <div class="stat-card green">  <div class="stat-number"><?php echo $pass_pct; ?>%</div><div class="stat-label">Overall Pass Rate</div></div>
        <div class="stat-card">        <div class="stat-number"><?php echo $total_res; ?></div><div class="stat-label">Total Exam Entries</div></div>
        <div class="stat-card orange"> <div class="stat-number"><?php echo $pass_row['cnt']; ?></div><div class="stat-label">Passed</div></div>
        <div class="stat-card red">    <div class="stat-number"><?php echo $fail_row['cnt']; ?></div><div class="stat-label">Failed</div></div>
    </div>

    <!-- Filter by Batch -->
    <div class="card card-accent no-print" style="margin-bottom:24px;">
        <h3 style="font-size:1rem; margin-bottom:12px; color:#1a3a5c;">🔍 Filter by Batch</h3>
        <form method="GET" action="report_academic.php" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
            <div class="form-group" style="margin-bottom:0; min-width:240px;">
                <label>Batch</label>
                <select name="batch_id">
                    <option value="0">All Batches</option>
                    <?php while ($bd = mysqli_fetch_assoc($batches_dd)): ?>
                        <option value="<?php echo $bd['id']; ?>" <?php if ($filter_batch == $bd['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($bd['batch_name']); ?> – <?php echo htmlspecialchars($bd['subject_name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Apply Filter</button>
                <a href="report_academic.php" class="btn btn-outline" style="color:#1a3a5c; border-color:#1a3a5c; margin-left:8px;">Clear</a>
            </div>
        </form>
    </div>

    <!-- Grade Distribution + Pass Rate (side by side) -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-bottom:24px;" class="report-cols">

        <!-- Grade Distribution -->
        <div class="panel">
            <div class="panel-title">📊 Grade Distribution</div>
            <?php if ($total_graded > 0):
                foreach ($grade_data as $grade => $cnt):
                    $pct   = round(($cnt / $total_graded) * 100);
                    $color = in_array($grade, ['A','A-']) ? 'green' : ($grade == 'F' ? 'orange' : '');
            ?>
                <div style="margin-bottom:12px;">
                    <div style="display:flex; justify-content:space-between; font-size:.85rem; margin-bottom:4px;">
                        <strong><?php echo $grade; ?></strong>
                        <span><?php echo $cnt; ?> students (<?php echo $pct; ?>%)</span>
                    </div>
                    <div class="progress-bar-wrapper">
                        <div class="progress-bar-fill <?php echo $color; ?>" style="width:<?php echo $pct; ?>%;"></div>
                    </div>
                </div>
            <?php endforeach; else: ?>
                <p style="color:#64748b;">No grade data available yet.</p>
            <?php endif; ?>
        </div>

        <!-- Pass vs Fail -->
        <div class="panel">
            <div class="panel-title">✅ Pass vs ❌ Fail</div>
            <div style="text-align:center; padding:20px 0;">
                <div style="font-size:3rem; font-weight:800; color:#16a34a;"><?php echo $pass_pct; ?>%</div>
                <div style="color:#64748b; margin-bottom:20px;">Pass Rate (marks ≥ 50)</div>
                <div class="progress-bar-wrapper" style="height:18px; border-radius:9px; margin-bottom:12px;">
                    <div class="progress-bar-fill green" style="width:<?php echo $pass_pct; ?>%; height:18px; border-radius:9px;"></div>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:.85rem;">
                    <span style="color:#16a34a; font-weight:700;">✅ Passed: <?php echo $pass_row['cnt']; ?></span>
                    <span style="color:#dc2626; font-weight:700;">❌ Failed: <?php echo $fail_row['cnt']; ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Students -->
    <div class="panel" style="margin-bottom:24px;">
        <div class="panel-title">🏆 Top 5 Students by Average Score</div>
        <?php
        $medals = ['🥇','🥈','🥉'];
        $rank   = 1;
        if ($top_students && mysqli_num_rows($top_students) > 0):
            while ($ts = mysqli_fetch_assoc($top_students)):
                $medal = $medals[$rank-1] ?? "#$rank";
        ?>
        <div style="display:flex; align-items:center; gap:16px; padding:12px 0; border-bottom:1px solid #f1f5f9; flex-wrap:wrap;">
            <span style="font-size:1.6rem;"><?php echo $medal; ?></span>
            <div style="flex:1; min-width:120px;">
                <div style="font-weight:700;"><?php echo htmlspecialchars($ts['full_name']); ?></div>
                <div style="font-size:.8rem; color:#64748b;"><?php echo $ts['exams']; ?> exams taken</div>
            </div>
            <div style="font-size:1.3rem; font-weight:800; color:#1a3a5c;"><?php echo $ts['avg_score']; ?>%</div>
        </div>
        <?php $rank++; endwhile;
        else: ?>
            <p style="color:#64748b;">No exam data yet.</p>
        <?php endif; ?>
    </div>

    <!-- Exam Results Table -->
    <div class="panel" style="margin-bottom:24px;">
        <div class="panel-title">📋 Exam Results</div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Student</th><th>Subject</th><th>Batch</th><th>Exam</th><th>Date</th><th>Marks</th><th>Grade</th></tr></thead>
                <tbody>
                <?php
                $has_rows = false;
                while ($r = mysqli_fetch_assoc($results)):
                    $has_rows = true;
                    $gc = in_array($r['grade'], ['A','A-']) ? 'badge-green' : ($r['grade'] == 'F' ? 'badge-red' : 'badge-blue');
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['batch_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['exam_name']); ?></td>
                        <td><?php echo date('d M Y', strtotime($r['exam_date'])); ?></td>
                        <td><strong><?php echo $r['marks']; ?></strong>/<?php echo $r['total_marks']; ?></td>
                        <td><span class="badge <?php echo $gc; ?>"><?php echo $r['grade']; ?></span></td>
                    </tr>
                <?php endwhile;
                if (!$has_rows): ?>
                    <tr><td colspan="7" style="text-align:center; color:#64748b;">No exam results found.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Attendance Summary per Batch -->
    <div class="panel">
        <div class="panel-title">✅ Attendance Summary by Batch</div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Batch</th><th>Subject</th><th>Total Classes</th><th>Present</th><th>Absent</th><th>Late</th><th>Attendance %</th></tr></thead>
                <tbody>
                <?php
                $has_att = false;
                while ($at = mysqli_fetch_assoc($attendance)):
                    $has_att = true;
                    $pct = $at['total'] > 0 ? round(($at['present'] / $at['total']) * 100) : 0;
                    $bar = $pct >= 80 ? 'green' : ($pct >= 60 ? '' : 'orange');
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($at['batch_name']); ?></td>
                        <td><?php echo htmlspecialchars($at['subject_name']); ?></td>
                        <td style="text-align:center;"><?php echo $at['total']; ?></td>
                        <td style="color:#16a34a; font-weight:600; text-align:center;"><?php echo $at['present']; ?></td>
                        <td style="color:#dc2626; font-weight:600; text-align:center;"><?php echo $at['absent']; ?></td>
                        <td style="color:#d97706; font-weight:600; text-align:center;"><?php echo $at['late']; ?></td>
                        <td>
                            <div style="display:flex; align-items:center; gap:8px;">
                                <div style="flex:1;"><div class="progress-bar-wrapper"><div class="progress-bar-fill <?php echo $bar; ?>" style="width:<?php echo $pct; ?>%;"></div></div></div>
                                <strong style="font-size:.85rem;"><?php echo $pct; ?>%</strong>
                            </div>
                        </td>
                    </tr>
                <?php endwhile;
                if (!$has_att): ?>
                    <tr><td colspan="7" style="text-align:center; color:#64748b;">No attendance records yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="no-print" style="margin-top:20px;">
        <a href="dashboard.php" style="color:#2563eb; font-size:0.88rem;">← Back to Dashboard</a>
    </div>
</div>

<style>
@media print { nav, footer, .no-print { display:none !important; } body { background:white !important; } .panel { box-shadow:none !important; border:1px solid #e2e8f0; } }
@media (max-width:768px) { .report-cols { grid-template-columns:1fr !important; } }
</style>

<?php mysqli_close($conn); include '../../frontend/assets/footer.php'; ?>
