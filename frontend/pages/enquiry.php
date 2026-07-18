<?php
// ============================================================
//  enquiry.php — Public Student Enquiry Form
//  Anyone can fill this form (no login needed).
//  The enquiry is saved in the database and the receptionist
//  and admin can see it in their dashboards immediately.
// ============================================================

session_start();
require '../../backend/config/db.php';

// Pre-fill the subject if it was passed in the URL
// e.g. enquiry.php?subject=Mathematics
$pre_subject = isset($_GET['subject']) ? htmlspecialchars($_GET['subject']) : '';

$success = $error = '';

// When the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect and clean all inputs
    $name     = mysqli_real_escape_string($conn, trim($_POST['name']));
    $phone    = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['email']));
    $interest = mysqli_real_escape_string($conn, trim($_POST['interest']));
    $notes    = mysqli_real_escape_string($conn, trim($_POST['notes']));

    // Basic validation
    if (empty($name) || empty($phone)) {
        $error = "Your name and phone number are required.";
    } else {
        // Save to the enquiries table
        // Status = 'pending' means the receptionist hasn't contacted them yet
        mysqli_query($conn, "INSERT INTO enquiries (name, phone, email, interest, notes, status)
                             VALUES ('$name', '$phone', '$email', '$interest', '$notes', 'pending')");

        $success = "Thank you, <strong>" . htmlspecialchars($name) . "</strong>! We have received your enquiry about <strong>" . htmlspecialchars($interest) . "</strong>. Our team will contact you at <strong>" . htmlspecialchars($phone) . "</strong> soon.";
    }
}

// Get all subjects for the interest dropdown
$subjects = mysqli_query($conn, "SELECT name FROM subject ORDER BY name");

$page_title  = "Student Enquiry";
$css_path    = "../../frontend/assets/css/style.css";
$root_path   = "../../";
$active_page = "courses";
include '../../frontend/assets/header.php';
?>

<!-- Page Banner -->
<div class="about-hero">
    <h1>📬 Student Enquiry</h1>
    <p>Interested in joining Activate Academy? Fill in the form below and we'll get back to you!</p>
</div>

<div class="section">
    <div style="display:grid; grid-template-columns:1.2fr 0.8fr; gap:30px;" class="enquiry-layout">

        <!-- ENQUIRY FORM -->
        <div>
            <?php if ($success): ?>
                <!-- Show big success message after submission -->
                <div style="background:#dcfce7; color:#166534; padding:24px; border-radius:12px; margin-bottom:24px; border:1px solid #86efac; font-size:1rem; line-height:1.6;">
                    ✅ <?php echo $success; ?>
                    <div style="margin-top:16px;">
                        <a href="courses.php" class="btn btn-primary">← Back to Courses</a>
                        &nbsp;
                        <a href="enquiry.php" class="btn btn-outline" style="color:#166534; border-color:#166534;">Submit Another Enquiry</a>
                    </div>
                </div>
            <?php else: ?>

                <?php if ($error): ?>
                    <div style="background:#fee2e2; color:#991b1b; padding:12px; border-radius:8px; margin-bottom:16px;">
                        ❌ <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <div class="card card-accent">
                    <h2 style="font-size:1.2rem; margin-bottom:6px; color:#1a3a5c;">Fill in Your Details</h2>
                    <p style="color:#64748b; font-size:0.88rem; margin-bottom:20px;">All fields marked * are required. We'll contact you within 24 hours.</p>

                    <form method="POST" action="enquiry.php">
                        <!-- Keep the subject in URL so page reloads correctly on error -->
                        <input type="hidden" name="from_subject" value="<?php echo htmlspecialchars($pre_subject); ?>">

                        <div class="form-row">
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="name"
                                       placeholder="e.g. Kamal Perera"
                                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                                       required>
                            </div>
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="text" name="phone"
                                       placeholder="e.g. 077 123 4567"
                                       value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                       required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Email Address <small style="color:#94a3b8;">(optional)</small></label>
                            <input type="email" name="email"
                                   placeholder="your@email.com"
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label>Subject / Course You're Interested In *</label>
                            <select name="interest" required>
                                <!-- If coming from a subject card, pre-select that subject -->
                                <option value="General Enquiry" <?php if (!$pre_subject) echo 'selected'; ?>>General Enquiry</option>
                                <?php
                                // Reset the subjects result pointer
                                mysqli_data_seek($subjects, 0);
                                while ($s = mysqli_fetch_assoc($subjects)):
                                    // Pre-select the subject that was clicked on courses page
                                    $selected = ($s['name'] == $pre_subject || (isset($_POST['interest']) && $_POST['interest'] == $s['name'])) ? 'selected' : '';
                                ?>
                                    <option value="<?php echo htmlspecialchars($s['name']); ?>" <?php echo $selected; ?>>
                                        <?php echo htmlspecialchars($s['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Who is the enquiry for?</label>
                            <select name="notes">
                                <option value="Enquiring for myself">Myself (I am the student)</option>
                                <option value="Enquiring for my child">My child (I am the parent)</option>
                                <option value="General information request">Just want information</option>
                            </select>
                        </div>

                        <div style="background:#f0f9ff; border:1px solid #bae6fd; border-radius:8px; padding:12px 14px; font-size:0.83rem; color:#0369a1; margin-bottom:16px;">
                            ℹ️ Your enquiry will be sent directly to our reception team. They will call you to discuss enrolment details.
                        </div>

                        <button type="submit" class="btn btn-primary" style="width:100%; padding:13px; font-size:1rem; border:none; cursor:pointer;">
                            📤 Submit Enquiry
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- SIDEBAR INFO -->
        <div>
            <!-- What happens next -->
            <div class="card" style="margin-bottom:20px; border-top:4px solid #2563eb;">
                <h3 style="margin-bottom:14px; color:#1a3a5c;">📋 What Happens Next?</h3>
                <div style="display:flex; flex-direction:column; gap:12px;">
                    <div style="display:flex; gap:12px; align-items:flex-start;">
                        <div style="background:#dbeafe; color:#1e40af; border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem; flex-shrink:0;">1</div>
                        <div style="font-size:0.88rem; color:#374151;">You submit this form with your details.</div>
                    </div>
                    <div style="display:flex; gap:12px; align-items:flex-start;">
                        <div style="background:#dbeafe; color:#1e40af; border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem; flex-shrink:0;">2</div>
                        <div style="font-size:0.88rem; color:#374151;">Our receptionist receives your enquiry instantly in their dashboard.</div>
                    </div>
                    <div style="display:flex; gap:12px; align-items:flex-start;">
                        <div style="background:#dbeafe; color:#1e40af; border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem; flex-shrink:0;">3</div>
                        <div style="font-size:0.88rem; color:#374151;">We call you within 24 hours to discuss enrolment and fees.</div>
                    </div>
                    <div style="display:flex; gap:12px; align-items:flex-start;">
                        <div style="background:#dcfce7; color:#166534; border-radius:50%; width:28px; height:28px; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:0.85rem; flex-shrink:0;">✓</div>
                        <div style="font-size:0.88rem; color:#374151;">You get enrolled and receive your login details!</div>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="card" style="border-top:4px solid #16a34a;">
                <h3 style="margin-bottom:12px; color:#1a3a5c;">📞 Contact Us Directly</h3>
                <p style="font-size:0.85rem; color:#374151; margin-bottom:8px;"><strong>Phone:</strong><br>+94 11 234 5678</p>
                <p style="font-size:0.85rem; color:#374151; margin-bottom:8px;"><strong>Email:</strong><br>info@activateacademy.lk</p>
                <p style="font-size:0.85rem; color:#374151;"><strong>Walk In:</strong><br>123 Education Lane, Colombo<br>Mon–Sat: 8:00 AM – 7:00 PM</p>
            </div>
        </div>

    </div>
</div>

<style>@media (max-width:768px) { .enquiry-layout { grid-template-columns:1fr !important; } }</style>

<?php mysqli_close($conn); include '../../frontend/assets/footer.php'; ?>

