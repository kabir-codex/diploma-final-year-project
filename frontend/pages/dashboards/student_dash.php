<?php
// ============================================================
//  student_dash.php — Student Dashboard
//  Students can: view their batches, results, attendance,
//  payments, announcements, class links, and study materials.
//  They can also upload payment receipts.
// ============================================================

// --- HANDLE PAYMENT UPLOAD ---
$pay_msg = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_payment'])) {
    $batch_id  = (int)$_POST['pay_batch_id'];
    $amount    = (float)$_POST['pay_amount'];
    $pay_month = mysqli_real_escape_string($conn, $_POST['pay_month']);
    $pay_date  = date('Y-m-d');
    $rec_no    = 'STU-' . strtoupper(substr(md5(uniqid()), 0, 6));

    // Handle file upload (optional receipt image)
    $filename = '';
    if (isset($_FILES['receipt_file']) && $_FILES['receipt_file']['error'] == 0) {
        $allowed    = ['jpg', 'jpeg', 'png', 'pdf'];
        $ext        = strtolower(pathinfo($_FILES['receipt_file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $filename = 'receipt_' . $user_id . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['receipt_file']['tmp_name'], '../../uploads/receipts/' . $filename);
        }
    }

    if (!$batch_id || $amount <= 0 || empty($pay_month)) {
        $pay_msg = "<div style='background:#fee2e2;color:#991b1b;padding:10px;border-radius:7px;margin-bottom:14px;'>❌ Batch, amount and month are required.</div>";
    } else {
        mysqli_query($conn, "INSERT INTO payments (student_id, batch_id, amount, pay_month, receipt_no, pay_date, receipt_file, status) VALUES ($user_id, $batch_id, $amount, '$pay_month', '$rec_no', '$pay_date', '$filename', 'pending')");
        $pay_msg = "<div style='background:#dcfce7;color:#166534;padding:10px;border-radius:7px;margin-bottom:14px;'>✅ Payment submitted for approval! Receipt: <strong>$rec_no</strong></div>";
    }
}

// --- LOAD DATA ---

// Batches this student is enrolled in
$my_batches = mysqli_query($conn, "
    SELECT e.id AS enroll_id, b.*, s.name AS subject_name, u.full_name AS lecturer_name, e.status AS enroll_status
    FROM enrollments e
    JOIN batches b ON e.batch_id = b.id
    JOIN subjects s ON b.subject_id = s.id
    JOIN users u ON b.lecturer_id = u.id
    WHERE e.student_id = $user_id AND e.status = 'active'
    ORDER BY b.batch_name
");

// Collect batch IDs as array for later queries
$my_batch_ids = [];
$batch_rows   = [];
$bk = mysqli_query($conn, "SELECT e.batch_id, b.batch_name, s.name AS subject_name FROM enrollments e JOIN batches b ON e.batch_id=b.id JOIN subjects s ON b.subject_id=s.id WHERE e.student_id=$user_id AND e.status='active'");
while ($r = mysqli_fetch_assoc($bk)) {
    $my_batch_ids[] = $r['batch_id'];
    $batch_rows[]   = $r;
}
$batch_ids_str = empty($my_batch_ids) ? '0' : implode(',', $my_batch_ids);

// My exam results
$my_results = mysqli_query($conn, "
    SELECT r.*, b.batch_name, s.name AS subject_name
    FROM results r
    JOIN batches b ON r.batch_id = b.id
    JOIN subjects s ON b.subject_id = s.id
    WHERE r.student_id = $user_id
    ORDER BY r.exam_date DESC
");

// My attendance summary per batch
$my_attendance = mysqli_query($conn, "
    SELECT b.batch_name, s.name AS subject_name,
        COUNT(a.id) AS total,
        SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) AS present,
        SUM(CASE WHEN a.status='absent'  THEN 1 ELSE 0 END) AS absent,
        SUM(CASE WHEN a.status='late'    THEN 1 ELSE 0 END) AS late
    FROM attendance a
    JOIN batches b ON a.batch_id = b.id
    JOIN subjects s ON b.subject_id = s.id
    WHERE a.student_id = $user_id
    GROUP BY a.batch_id
");

// My performance points
$my_points_row = get_one_row($conn, "SELECT SUM(points) AS total FROM performance_points WHERE student_id = $user_id");
$total_points  = $my_points_row ? (int)$my_points_row['total'] : 0;
$my_points     = mysqli_query($conn, "SELECT pp.*, u.full_name AS awarded_by_name, b.batch_name FROM performance_points pp JOIN users u ON pp.awarded_by=u.id LEFT JOIN batches b ON pp.batch_id=b.id WHERE pp.student_id=$user_id ORDER BY pp.id DESC");

// My payment history
$my_payments = mysqli_query($conn, "
    SELECT p.*, b.batch_name, s.name AS subject_name
    FROM payments p
    JOIN batches b ON p.batch_id = b.id
    JOIN subjects s ON b.subject_id = s.id
    WHERE p.student_id = $user_id
    ORDER BY p.id DESC
");

// Announcements for all or students
$announcements = mysqli_query($conn, "
    SELECT a.*, u.full_name AS posted_by_name
    FROM announcements a
    LEFT JOIN users u ON a.posted_by = u.id
    WHERE a.audience IN ('all', 'students')
    ORDER BY a.created_at DESC
    LIMIT 10
");

// Class links for my batches
$class_links = mysqli_query($conn, "
    SELECT cl.*, b.batch_name, s.name AS subject_name, u.full_name AS lecturer_name
    FROM class_links cl
    JOIN batches b ON cl.batch_id = b.id
    JOIN subjects s ON b.subject_id = s.id
    JOIN users u ON cl.lecturer_id = u.id
    WHERE cl.batch_id IN ($batch_ids_str)
    ORDER BY cl.class_date DESC
    LIMIT 20
");

// Study materials for my batches
$materials = mysqli_query($conn, "
    SELECT sm.*, b.batch_name, s.name AS subject_name
    FROM study_materials sm
    LEFT JOIN batches b ON sm.batch_id = b.id
    LEFT JOIN subjects s ON b.subject_id = s.id
    WHERE sm.batch_id IN ($batch_ids_str)
    ORDER BY sm.created_at DESC
");
?>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header"><h3>🎓 Student Portal</h3><p><?php echo htmlspecialchars($full_name); ?></p></div>
        <nav class="sidebar-nav">
            <a href="#my_batches"     class="active"><span class="sidebar-icon">🗓️</span> My Batches</a>
            <a href="#results">                      <span class="sidebar-icon">📊</span> My Results</a>
            <a href="#attendance">                   <span class="sidebar-icon">✅</span> My Attendance</a>
            <a href="#points">                       <span class="sidebar-icon">⭐</span> My Points</a>
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
            mysqli_data_seek($my_batches, 0);
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
            </div>
            <?php else: ?>
                <div style="text-align:center; padding:40px; color:#64748b;">
                    <div style="font-size:3rem; margin-bottom:12px;">📭</div>
                    <p>You are not enrolled in any batches yet. Please contact the receptionist.</p>
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
                        $gc = in_array($r['grade'], ['A','A-']) ? 'badge-green' : ($r['grade'] == 'F' ? 'badge-red' : 'badge-blue');
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($r['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['batch_name']); ?></td>
                        <td><?php echo htmlspecialchars($r['exam_name']); ?></td>
                        <td><?php echo date('d M Y', strtotime($r['exam_date'])); ?></td>
                        <td><strong><?php echo $r['marks']; ?></strong>/<?php echo $r['total_marks']; ?></td>
                        <td><span class="badge <?php echo $gc; ?>"><?php echo $r['grade']; ?></span></td>
                        <td style="font-size:.82rem; color:#64748b;"><?php echo htmlspecialchars($r['comments'] ?: '—'); ?></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="7" style="text-align:center; color:#64748b;">No results uploaded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table></div>
        </div>

        <!-- MY ATTENDANCE -->
        <div id="attendance" class="panel">
            <div class="panel-title">✅ My Attendance Summary</div>
            <div class="table-wrapper"><table>
                <thead><tr><th>Subject</th><th>Batch</th><th>Total Classes</th><th>Present</th><th>Absent</th><th>Late</th><th>Attendance %</th></tr></thead>
                <tbody>
                <?php if ($my_attendance && mysqli_num_rows($my_attendance) > 0):
                    while ($at = mysqli_fetch_assoc($my_attendance)):
                        $pct = $at['total'] > 0 ? round(($at['present'] / $at['total']) * 100) : 0;
                        $bar = $pct >= 80 ? 'green' : ($pct >= 60 ? '' : 'orange');
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($at['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($at['batch_name']); ?></td>
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
                <?php endwhile; else: ?>
                    <tr><td colspan="7" style="text-align:center; color:#64748b;">No attendance records yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table></div>
        </div>

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
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#64748b;">No points awarded yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table></div>
        </div>

        <!-- PAYMENTS -->
        <div id="payments" class="panel">
            <div class="panel-title">💳 Payments</div>
            <?php echo $pay_msg; ?>

            <!-- Upload Payment Form -->
            <div class="card card-accent" style="margin-bottom:24px;">
                <h3 style="font-size:1rem; margin-bottom:14px; color:#1a3a5c;">📤 Submit Payment</h3>
                <form method="POST" action="dashboard.php#payments" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Batch *</label>
                            <select name="pay_batch_id" required>
                                <option value="">-- Select Batch --</option>
                                <?php foreach ($batch_rows as $br): ?>
                                    <option value="<?php echo $br['batch_id']; ?>"><?php echo htmlspecialchars($br['batch_name']); ?> – <?php echo htmlspecialchars($br['subject_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group"><label>Amount (LKR) *</label><input type="number" name="pay_amount" placeholder="e.g. 2500" min="1" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Payment Month *</label><input type="month" name="pay_month" value="<?php echo date('Y-m'); ?>" required></div>
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
                        $pb = $p['status'] == 'approved' ? 'badge-green' : ($p['status'] == 'rejected' ? 'badge-red' : 'badge-yellow');
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
                                <a href="print_receipt.php?id=<?php echo $p['id']; ?>" target="_blank" class="btn btn-small btn-primary">🖨️</a>
                            <?php else: ?>
                                <span style="font-size:.78rem; color:#94a3b8;">N/A</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="8" style="text-align:center; color:#64748b;">No payment records yet.</td></tr>
                <?php endif; ?>
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
                </div>
            <?php endwhile; else: ?>
                <p style="color:#64748b;">No announcements at this time.</p>
            <?php endif; ?>
        </div>

        <!-- CLASS LINKS -->
        <div id="classlinks" class="panel">
            <div class="panel-title">🔗 Class Links (Online Sessions)</div>
            <?php if ($class_links && mysqli_num_rows($class_links) > 0): ?>
            <div class="table-wrapper"><table>
                <thead><tr><th>Title</th><th>Subject</th><th>Batch</th><th>Lecturer</th><th>Class Date</th><th>Link</th></tr></thead>
                <tbody>
                <?php while ($lnk = mysqli_fetch_assoc($class_links)):
                    $is_today   = date('Y-m-d') == $lnk['class_date'];
                    $is_upcoming = $lnk['class_date'] >= date('Y-m-d');
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
                            <?php else: ?>
                                <span style="font-size:.82rem; color:#94a3b8;">Session passed</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
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
