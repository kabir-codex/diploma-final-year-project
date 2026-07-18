<?php
// ============================================================
//  feedback.php — Public Feedback Form
// ============================================================

session_start();
require '../../backend/config/db.php';

$success = $error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name      = mysqli_real_escape_string($conn, trim($_POST['name']));
    $role      = mysqli_real_escape_string($conn, $_POST['role']);
    $subject   = mysqli_real_escape_string($conn, $_POST['subject']);
    $email     = mysqli_real_escape_string($conn, trim($_POST['email']));
    $rating    = (int)$_POST['rating'];
    $comments  = mysqli_real_escape_string($conn, trim($_POST['comments']));
    $recommend = mysqli_real_escape_string($conn, $_POST['recommend']);

    if (empty($name) || empty($comments) || $rating < 1 || $rating > 5) {
        $error = "Please fill in your name, rating and comments.";
    } else {
        mysqli_query($conn, "INSERT INTO feedback (name, role, subject, email, rating, comments, recommend) VALUES ('$name','$role','$subject','$email',$rating,'$comments','$recommend')");
        $success = "✅ Thank you, " . htmlspecialchars($name) . "! Your feedback has been saved.";
    }
}

// Get recent feedback (last 3 to show)
$recent_feedback = mysqli_query($conn, "SELECT * FROM feedback ORDER BY created_at DESC LIMIT 3");

// Get rating distribution for the stats bar
$stats = mysqli_query($conn, "SELECT rating, COUNT(*) AS cnt FROM feedback GROUP BY rating ORDER BY rating DESC");
$rating_counts = [];
$total_fb = 0;
while ($sr = mysqli_fetch_assoc($stats)) {
    $rating_counts[$sr['rating']] = $sr['cnt'];
    $total_fb += $sr['cnt'];
}

// Get subjects for the dropdown
$subjects = mysqli_query($conn, "SELECT name FROM subject ORDER BY name");

$page_title = "Feedback"; $css_path = "../../frontend/assets/css/style.css"; $root_path = "../../"; $active_page = "feedback";
include '../../frontend/assets/header.php';
?>

<!-- Hero Banner -->
<div class="about-hero">
    <h1>Student &amp; Parent Feedback</h1>
    <p>Your opinions help us grow. We value every piece of feedback.</p>
</div>

<div class="section">
    <div style="display:grid; grid-template-columns:1.2fr 0.8fr; gap:30px; align-items:start;" class="feedback-layout">

        <!-- FEEDBACK FORM -->
        <div class="card card-accent">
            <h2 class="section-title" style="font-size:1.3rem;">Share Your Feedback</h2>

            <?php if ($success): ?>
                <div style="background:#dcfce7; color:#166534; padding:12px; border-radius:8px; margin-bottom:18px;"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div style="background:#fee2e2; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:18px;">❌ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="feedback.php">
                <div class="form-group">
                    <label>Your Full Name *</label>
                    <!-- Keep name if form failed (validation error) -->
                    <input type="text" name="name" placeholder="e.g. Kabir Mohamed" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Your Role</label>
                        <select name="role">
                            <option>Student</option><option>Parent</option><option>Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Subject / Batch</label>
                        <select name="subject">
                            <option value="General / Overall">General / Overall</option>
                            <?php while ($s = mysqli_fetch_assoc($subjects)): ?>
                                <option value="<?php echo htmlspecialchars($s['name']); ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email Address (optional)</label>
                    <input type="email" name="email" placeholder="your@email.com" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Overall Rating (1–5) *</label>
                    <select name="rating" required>
                        <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                        <option value="4">⭐⭐⭐⭐ Very Good</option>
                        <option value="3">⭐⭐⭐ Good</option>
                        <option value="2">⭐⭐ Fair</option>
                        <option value="1">⭐ Needs Improvement</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Your Comments *</label>
                    <textarea name="comments" placeholder="Share your experience or suggestions..." required><?php echo isset($_POST['comments']) ? htmlspecialchars($_POST['comments']) : ''; ?></textarea>
                </div>
                <div class="form-group">
                    <label>Would you recommend Activate Academy?</label>
                    <select name="recommend">
                        <option value="yes">✅ Yes, definitely!</option>
                        <option value="maybe">🤔 Maybe</option>
                        <option value="no">❌ No</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%; padding:13px;">Submit Feedback</button>
            </form>
        </div>

        <!-- SIDEBAR: RATING STATS + CONTACT -->
        <div>
            <div class="card" style="margin-bottom:20px;">
                <h3 style="margin-bottom:16px; color:#1a3a5c;">📊 Overall Ratings</h3>
                <?php if ($total_fb > 0):
                    for ($star = 5; $star >= 1; $star--):
                        $cnt   = $rating_counts[$star] ?? 0;
                        $pct   = round(($cnt / $total_fb) * 100);
                        $stars = str_repeat('⭐', $star);
                        $bar   = $star >= 4 ? 'green' : ($star == 3 ? '' : 'orange');
                ?>
                    <div style="margin-bottom:12px;">
                        <div style="display:flex; justify-content:space-between; font-size:0.82rem; margin-bottom:4px;">
                            <span><?php echo $stars; ?></span>
                            <span style="font-weight:700;"><?php echo $pct; ?>%</span>
                        </div>
                        <div class="progress-bar-wrapper">
                            <div class="progress-bar-fill <?php echo $bar; ?>" style="width:<?php echo $pct; ?>%;"></div>
                        </div>
                    </div>
                <?php endfor; else: ?>
                    <p style="color:#64748b; font-size:0.88rem;">No ratings yet. Be the first!</p>
                <?php endif; ?>
                <p style="font-size:0.78rem; color:#64748b; margin-top:8px;">Based on <?php echo $total_fb; ?> total responses</p>
            </div>

            <div class="card">
                <h3 style="margin-bottom:12px; color:#1a3a5c;">📞 Contact Us</h3>
                <p style="font-size:0.85rem; color:#374151; margin-bottom:8px;"><strong>Address:</strong><br>123 Education Lane, Colombo</p>
                <p style="font-size:0.85rem; color:#374151; margin-bottom:8px;"><strong>Phone:</strong><br>+94 11 234 5678</p>
                <p style="font-size:0.85rem; color:#374151; margin-bottom:8px;"><strong>Email:</strong><br>info@activateacademy.lk</p>
                <p style="font-size:0.85rem; color:#374151;"><strong>Office Hours:</strong><br>Mon – Sat: 8:00 AM – 7:00 PM</p>
            </div>
        </div>
    </div>
</div>

<!-- Recent Feedback Display -->
<?php if ($recent_feedback && mysqli_num_rows($recent_feedback) > 0): ?>
<div style="background-color:#e8eef5; padding:10px 0;">
    <div class="section">
        <h2 class="section-title">What People Are Saying</h2>
        <p class="section-subtitle">Recent feedback from our community.</p>
        <div class="card-grid">
        <?php while ($fb = mysqli_fetch_assoc($recent_feedback)): ?>
            <div class="feedback-card">
                <div class="quote">"</div>
                <p><?php echo htmlspecialchars($fb['comments']); ?></p>
                <div class="feedback-author">
                    <div class="author-avatar"><?php echo strtoupper(substr($fb['name'], 0, 1)); ?></div>
                    <div>
                        <div class="author-name"><?php echo htmlspecialchars($fb['name']); ?></div>
                        <div class="author-role"><?php echo htmlspecialchars($fb['role']); ?> – <?php echo htmlspecialchars($fb['subject']); ?></div>
                    </div>
                    <span class="badge badge-green" style="margin-left:auto;"><?php echo str_repeat('⭐', $fb['rating']); ?></span>
                </div>
            </div>
        <?php endwhile; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<style>@media (max-width:768px) { .feedback-layout { grid-template-columns:1fr !important; } }</style>

<?php mysqli_close($conn); include '../../frontend/assets/footer.php'; ?>

