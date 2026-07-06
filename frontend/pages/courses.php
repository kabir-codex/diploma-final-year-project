<?php
// ============================================================
//  courses.php — Courses/Batches Public Page
// ============================================================

session_start();
require '../../backend/config/db.php';

// Get all subjects
$subjects = mysqli_query($conn, "SELECT * FROM subjects ORDER BY name");

// Get all batches with subject and lecturer names
$batches = mysqli_query($conn, "
    SELECT b.*, s.name AS subject_name, u.full_name AS lecturer_name
    FROM batches b
    JOIN subjects s ON b.subject_id = s.id
    JOIN users u ON b.lecturer_id = u.id
    ORDER BY b.status DESC, b.batch_name
");

// Count how many students are active in each batch
$enroll_counts = [];
$ec = mysqli_query($conn, "SELECT batch_id, COUNT(*) AS cnt FROM enrollments WHERE status='active' GROUP BY batch_id");
while ($row = mysqli_fetch_assoc($ec)) {
    $enroll_counts[$row['batch_id']] = $row['cnt'];
}

$page_title = "Courses"; $css_path = "../../frontend/assets/css/style.css"; $root_path = "../../"; $active_page = "courses";
include '../../frontend/assets/header.php';
?>

<!-- Hero Banner -->
<div class="about-hero">
    <h1>Subjects &amp; Courses</h1>
    <p>Explore our wide range of subjects offered across different batches and levels.</p>
</div>

<!-- Active Batches Table -->
<div class="section">
    <h2 class="section-title">Active Batches</h2>
    <p class="section-subtitle">Current running batches for academic year <?php echo date('Y'); ?>.</p>
    <div class="table-wrapper">
        <table>
            <thead><tr><th>Batch Name</th><th>Subject</th><th>Lecturer</th><th>Schedule</th><th>Room</th><th>Students</th><th>Status</th></tr></thead>
            <tbody>
            <?php if ($batches && mysqli_num_rows($batches) > 0):
                while ($b = mysqli_fetch_assoc($batches)):
                    $enrolled = $enroll_counts[$b['id']] ?? 0;
                    $badge    = $b['status'] == 'active' ? 'badge-green' : ($b['status'] == 'upcoming' ? 'badge-yellow' : 'badge-gray');
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($b['batch_name']); ?></td>
                    <td><?php echo htmlspecialchars($b['subject_name']); ?></td>
                    <td><?php echo htmlspecialchars($b['lecturer_name']); ?></td>
                    <td><?php echo htmlspecialchars($b['schedule']); ?></td>
                    <td><?php echo htmlspecialchars($b['room']); ?></td>
                    <td><?php echo $enrolled; ?> / <?php echo $b['capacity']; ?></td>
                    <td><span class="badge <?php echo $badge; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                </tr>
            <?php endwhile; else: ?>
                <tr><td colspan="7" style="text-align:center; color:#64748b;">No batches found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Subject Cards -->
<div style="background-color:#e8eef5; padding:10px 0;">
    <div class="section">
        <h2 class="section-title">Subject Details</h2>
        <p class="section-subtitle">All subjects offered at Activate Academy.</p>
        <div class="card-grid">
        <?php
        // Cycle through icons for visual variety
        $icons = ['📐','📝','🔬','💻','📊','🗣️','🎨','🧪'];
        $i     = 0;
        while ($s = mysqli_fetch_assoc($subjects)):
        ?>
            <div class="course-card">
                <div class="course-header">
                    <h3><?php echo $icons[$i % count($icons)]; ?> <?php echo htmlspecialchars($s['name']); ?></h3>
                    <p><?php echo htmlspecialchars($s['level']); ?> Level</p>
                </div>
                <div class="course-body">
                    <div class="course-meta">
                        <span>💰 LKR <?php echo number_format($s['fee'], 0); ?>/month</span>
                        <span>🏷️ <?php echo htmlspecialchars($s['code']); ?></span>
                    </div>
                    <p style="font-size:0.85rem; color:#374151; margin-bottom:12px;"><?php echo htmlspecialchars($s['description'] ?: 'Course details coming soon.'); ?></p>
                    <a href="enquiry.php?subject=<?php echo urlencode($s['name']); ?>" class="btn btn-primary btn-small">Enquire Now</a>
                </div>
            </div>
        <?php $i++; endwhile; ?>
        </div>
    </div>
</div>

<!-- Call to Action -->
<div class="section" style="text-align:center;">
    <h2 class="section-title" style="text-align:center;">Ready to Join?</h2>
    <p style="color:#64748b; margin-bottom:24px;">Contact reception or log in to enroll in a batch.</p>
    <a href="login.php" class="btn btn-primary">Go to Login</a>
    &nbsp;&nbsp;
    <a href="feedback.php" class="btn btn-outline" style="color:#1a3a5c; border-color:#1a3a5c;">Give Feedback</a>
</div>

<?php mysqli_close($conn); include '../../frontend/assets/footer.php'; ?>

