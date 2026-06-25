<?php
// ============================================================
//  index.php — Home Page (Public)
//  This is the first page visitors see.
//  It shows stats, features, and recent announcements.
// ============================================================

session_start();

// Include the database connection and shared helper functions
require 'backend/config/db.php';
require 'backend/config/helpers.php';

// Count stats to show on the home page
$total_students  = count_rows($conn, 'users',   "role='student'");
$total_lecturers = count_rows($conn, 'users',   "role='lecturer'");
$total_subjects  = count_rows($conn, 'subjects');
$total_batches   = count_rows($conn, 'batches', "status='active'");

// Get latest 3 announcements
$announcements = mysqli_query($conn, "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3");

// Set page info for the header
$page_title  = "Home";
$css_path    = "frontend/assets/css/style.css";
$root_path   = "";
$active_page = "home";

include 'frontend/assets/header.php';
?>

<!-- Hero Section: Big welcome banner -->
<section class="hero">
    <h1>🎓 Welcome to Activate Academy</h1>
    <p>A modern learning institute dedicated to academic excellence and student success.</p>
    <a href="frontend/pages/courses.php" class="btn btn-primary">Explore Courses</a>
    &nbsp;&nbsp;
    <a href="frontend/pages/login.php" class="btn btn-outline">Login</a
    <!-- Quick stats shown in the hero -->
    <div class="hero-stats">
        <div class="hero-stat"><h3><?php echo $total_students; ?>+</h3><p>Students Enrolled</p></div>
        <div class="hero-stat"><h3><?php echo $total_lecturers; ?>+</h3><p>Expert Lecturers</p></div>
        <div class="hero-stat"><h3><?php echo $total_subjects; ?>+</h3><p>Subjects Offered</p></div>
        <div class="hero-stat"><h3><?php echo $total_batches; ?></h3><p>Active Batches</p></div>
    </div>
</section>

<!-- Why Choose Us Section -->
<div class="section">
    <h2 class="section-title">Why Choose Activate Academy?</h2>
    <p class="section-subtitle">Quality education with experienced staff and modern management.</p>
    <div class="card-grid">
        <div class="card card-accent"><div class="card-icon">📚</div><h3>Expert Lecturers</h3><p>Our <?php echo $total_lecturers; ?> lecturers are qualified professionals with years of teaching experience.</p></div>
        <div class="card card-accent"><div class="card-icon">🏆</div><h3>Proven Results</h3><p>Consistently high exam pass rates with many students achieving top grades each year.</p></div>
        <div class="card card-accent"><div class="card-icon">📊</div><h3>Progress Tracking</h3><p>Students and parents can monitor attendance, results, and performance in real time.</p></div>
        <div class="card card-accent"><div class="card-icon">💬</div><h3>Supportive Community</h3><p>A welcoming environment where every student is encouraged and motivated.</p></div>
    </div>
</div>

<!-- Announcements Section -->
<div class="section">
    <h2 class="section-title">Latest Announcements</h2>
    <p class="section-subtitle">Stay updated with what's happening at Activate Academy.</p>

    <?php if ($announcements && mysqli_num_rows($announcements) > 0): ?>
        <?php while ($ann = mysqli_fetch_assoc($announcements)): ?>
            <div class="announcement">
                <h4>📢 <?php echo $ann['title']; ?></h4>
                <p><?php echo $ann['message']; ?></p>
                <p class="ann-date">Posted: <?php echo date('d M Y', strtotime($ann['post_date'])); ?></p>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="color:#64748b;">No announcements at this time.</p>
    <?php endif; ?>
</div>

<?php
mysqli_close($conn);
include 'frontend/assets/footer.php';
?>
