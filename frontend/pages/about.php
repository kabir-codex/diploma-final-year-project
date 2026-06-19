<?php
// ============================================================
//  about.php — About Us Page (Public)
// ============================================================

session_start();
require '../../backend/config/db.php';

// Get all staff members to show on the team section
$team = mysqli_query($conn, "SELECT full_name, role FROM users WHERE role IN ('director','manager','lecturer') ORDER BY FIELD(role,'director','manager','lecturer'), full_name");

$page_title = "About Us"; $css_path = "../../frontend/assets/css/style.css"; $root_path = "../../"; $active_page = "about";
include '../../frontend/assets/header.php';
?>

<!-- Hero Banner -->
<div class="about-hero">
    <h1>About Activate Academy</h1>
    <p>Dedicated to building knowledge, character, and careers since 2010.</p>
</div>

<!-- Mission & Vision -->
<div class="section">
    <div class="card-grid" style="grid-template-columns:1fr 1fr;">
        <div class="card card-accent">
            <div class="card-icon">🎯</div>
            <h3>Our Mission</h3>
            <p>To provide affordable, high-quality education that empowers students with the knowledge and skills they need to succeed in academics and professional life.</p>
        </div>
        <div class="card card-accent">
            <div class="card-icon">🔭</div>
            <h3>Our Vision</h3>
            <p>To become the most trusted and impactful educational institution in the region, known for academic excellence and holistic student development.</p>
        </div>
    </div>
</div>

<!-- Meet the Team -->
<div style="background-color:#e8eef5; padding:10px 0;">
    <div class="section">
        <h2 class="section-title">Meet Our Team</h2>
        <p class="section-subtitle">The dedicated people behind Activate Academy.</p>
        <div class="card-grid">
        <?php while ($m = mysqli_fetch_assoc($team)):
            // Pick gradient colour based on role
            $colours = $m['role'] == 'director' ? '#1a3a5c,#2563eb' : ($m['role'] == 'manager' ? '#166534,#16a34a' : '#92400e,#d97706');
            $initial = strtoupper(substr($m['full_name'], 0, 1));
            $badge   = $m['role'] == 'lecturer' ? 'badge-blue' : 'badge-green';
        ?>
            <div class="card" style="text-align:center; padding:28px 20px;">
                <!-- Coloured circle avatar with initial -->
                <div style="width:70px; height:70px; border-radius:50%; background:linear-gradient(135deg,<?php echo $colours; ?>); display:flex; align-items:center; justify-content:center; margin:0 auto 14px; color:white; font-size:1.6rem; font-weight:700;">
                    <?php echo $initial; ?>
                </div>
                <h3 style="margin-bottom:4px;"><?php echo htmlspecialchars($m['full_name']); ?></h3>
                <span class="badge <?php echo $badge; ?>"><?php echo ucfirst($m['role']); ?></span>
            </div>
        <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Core Values -->
<div class="section">
    <h2 class="section-title">Our Core Values</h2>
    <div class="card-grid">
        <div class="card"><div class="card-icon">🤝</div><h3>Integrity</h3><p>We act with honesty and transparency in all our dealings.</p></div>
        <div class="card"><div class="card-icon">📈</div><h3>Excellence</h3><p>We continuously improve the quality of education we deliver.</p></div>
        <div class="card"><div class="card-icon">❤️</div><h3>Respect</h3><p>Every student, parent, and staff member is treated with dignity.</p></div>
        <div class="card"><div class="card-icon">💡</div><h3>Innovation</h3><p>We embrace new teaching methods to keep learning engaging.</p></div>
    </div>
</div>

<?php mysqli_close($conn); include '../../frontend/assets/footer.php'; ?>
