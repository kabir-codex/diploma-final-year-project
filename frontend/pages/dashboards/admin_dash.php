<?php
// ============================================================
//  admin_dash.php — Admin Dashboard
//  The admin can manage: users, subjects, batches, payments,
//  announcements, and enquiries.
// ============================================================

// --- LINK PARENT TO STUDENT ---
$link_parent_msg = ''; // Will hold a success/warning/error message after the form below is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['link_parent'])) {
    $lp_parent_id  = (int)$_POST['lp_parent_id'];  // Which parent account to link
    $lp_student_id = (int)$_POST['lp_student_id']; // Which student account to link them to
    if (!$lp_parent_id || !$lp_student_id) {
        $link_parent_msg = "<div style='background:#fee2e2;color:#991b1b;padding:10px;border-radius:7px;margin-bottom:14px;'>❌ Please select both a parent and a student.</div>"; // Required fields check
    } else {
        $chk = mysqli_query($conn, "SELECT parentStudentID FROM parent_student WHERE parent_id=$lp_parent_id AND student_id=$lp_student_id"); // Check this exact pair isn't already linked
        if (mysqli_num_rows($chk) > 0) {
            $link_parent_msg = "<div style='background:#fef3c7;color:#92400e;padding:10px;border-radius:7px;margin-bottom:14px;'>⚠️ This parent is already linked to that student.</div>"; // Avoid duplicate links
        } else {
            $link_ins = mysqli_query($conn, "INSERT INTO parent_student (parent_id, student_id) VALUES ($lp_parent_id, $lp_student_id)"); // Create the link
            // BUG FIX: this insert's result used to be ignored, so if it failed
            // (e.g. the selected account was missing its parent/student subtype
            // row) the page still showed a green success message even though no
            // link was actually created. Now we check the result and self-heal
            // the missing subtype row if that's what went wrong, then retry once.
            if (!$link_ins) {
                ensure_subtype_row($conn, $lp_parent_id, 'parent');
                ensure_subtype_row($conn, $lp_student_id, 'student');
                $link_ins = mysqli_query($conn, "INSERT INTO parent_student (parent_id, student_id) VALUES ($lp_parent_id, $lp_student_id)");
            }
            if ($link_ins) {
                $link_parent_msg = "<div style='background:#dcfce7;color:#166534;padding:10px;border-radius:7px;margin-bottom:14px;'>✅ Parent linked to student successfully!</div>";
            } else {
                $link_parent_msg = "<div style='background:#fee2e2;color:#991b1b;padding:10px;border-radius:7px;margin-bottom:14px;'>❌ Could not link parent to student. Please try again.</div>";
            }
        }
    }
}

// ----- COUNT STATS FOR THE OVERVIEW CARDS -----
$cnt_students  = count_rows($conn, 'users',    "role='student'");    // Total student accounts
$cnt_lecturers = count_rows($conn, 'users',    "role='lecturer'");   // Total lecturer accounts
$cnt_batches   = count_rows($conn, 'batch',    "status='active'");   // Currently active batches
$cnt_pending   = count_rows($conn, 'payment',  "status='pending'");  // Payments awaiting approval
$cnt_enquiries = count_rows($conn, 'enquiries', "status='pending'"); // Enquiries not yet followed up on

// Calculate total approved revenue
$rev = get_one_row($conn, "SELECT SUM(amount) AS total FROM payment WHERE status='approved'"); // Sum of every approved payment
$total_revenue = $rev ? (float)$rev['total'] : 0; // Falls back to 0 if there are no approved payments at all yet

// ----- LOAD DATA FOR EACH SECTION -----

// All users (for the users table)
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY role, full_name"); // Grouped by role, alphabetical within each role

// All subjects
$subjects = mysqli_query($conn, "SELECT * FROM subject ORDER BY name");

// All batches with subject name and lecturer name (joined)
$batches = mysqli_query($conn, "
    SELECT b.*, s.name AS subject_name, u.full_name AS lecturer_name
    FROM batch b
    JOIN subject s ON b.subject_id = s.subjectID
    JOIN users u ON b.lecturer_id = u.userID
    ORDER BY b.batch_name
");

// All payments with student and batch info (joined)
$payments = mysqli_query($conn, "
    SELECT p.*, u.full_name AS student_name, b.batch_name
    FROM payment p
    JOIN users u ON p.student_id = u.userID
    JOIN batch b ON p.batch_id = b.batchID
    ORDER BY p.paymentID DESC
");

// All announcements with who posted them
$announcements = mysqli_query($conn, "
    SELECT a.*, u.full_name AS posted_by_name
    FROM announcement a
    LEFT JOIN users u ON a.posted_by = u.userID
    ORDER BY a.created_at DESC
"); // LEFT JOIN so an announcement still shows even if its poster's account was later deleted

// All enquiries
$enquiries = mysqli_query($conn, "SELECT * FROM enquiries ORDER BY created_at DESC");
?>

<div class="dashboard-wrapper">

    <!-- SIDEBAR NAVIGATION -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h3>⚙️ Admin Panel</h3>
            <p><?php echo $full_name; ?></p>
        </div>
        <nav class="sidebar-nav">
            <!-- Each link is a same-page anchor (#id) — clicking jumps straight to that panel below -->
            <a href="#overview"      class="active"><span class="sidebar-icon">📊</span> Overview</a>
            <a href="#users">                       <span class="sidebar-icon">👥</span> Users</a>
            <a href="#subjects">                    <span class="sidebar-icon">📚</span> Subjects</a>
            <a href="#batches">                     <span class="sidebar-icon">🗓️</span> Batches</a>
            <!-- DISABLED: Payment Approval & Management feature is temporarily turned off.
                 To re-enable, uncomment this link AND remove the "if (false)" wrapper
                 around the #payments panel further down in this file.
            <a href="#payments">                    <span class="sidebar-icon">💳</span> Payments</a>
            -->
            <a href="#announcements">               <span class="sidebar-icon">📢</span> Announcements</a>
            <a href="#enquiries">                   <span class="sidebar-icon">📬</span> Enquiries</a>
            <a href="#link_parent">                  <span class="sidebar-icon">👨‍👩‍👧</span> Link Parent</a>
            <a href="#reports">                      <span class="sidebar-icon">📈</span> Reports</a>
        </nav>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="dashboard-main">
        <h1 class="dashboard-title">Admin Panel</h1>
        <p class="dashboard-subtitle">Welcome, <?php echo $full_name; ?>. Full system control.</p>

        <!-- Success message after an action (e.g. "User added") -->
        <?php if (isset($_GET['msg'])): ?>
            <div style="background:#dcfce7; color:#166534; padding:12px 16px; border-radius:8px; margin-bottom:20px; border:1px solid #86efac;">
                ✅ <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
            <!-- Comes from a redirect like dashboard.php?msg=User+added, set by the various CRUD scripts -->
        <?php endif; ?>

        <!-- ===== OVERVIEW STATS ===== -->
        <div id="overview" class="stats-grid">
            <div class="stat-card">        <div class="stat-number"><?php echo $cnt_students; ?></div> <div class="stat-label">Total Students</div></div>
            <div class="stat-card green">  <div class="stat-number"><?php echo $cnt_lecturers; ?></div><div class="stat-label">Lecturers</div></div>
            <div class="stat-card orange"> <div class="stat-number"><?php echo $cnt_batches; ?></div>  <div class="stat-label">Active Batches</div></div>
            <div class="stat-card yellow"> <div class="stat-number"><?php echo $cnt_pending; ?></div>  <div class="stat-label">Pending Payments</div></div>
            <div class="stat-card red">    <div class="stat-number"><?php echo $cnt_enquiries; ?></div><div class="stat-label">New Enquiries</div></div>
            <div class="stat-card green">  <div class="stat-number">LKR <?php echo number_format($total_revenue / 1000, 1); ?>K</div><div class="stat-label">Revenue Collected</div></div>
            <!-- Divides by 1000 and shows one decimal, e.g. "245.5K" instead of "245,500" -->
        </div>

        <!-- ===== USERS TABLE ===== -->
        <div id="users" class="panel">
            <div class="panel-title">
                👥 User Management
                <a href="../../backend/crud/users/add_user.php" class="btn btn-primary btn-small" style="float:right;">+ Add User</a>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Email</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php if ($users && mysqli_num_rows($users) > 0): ?>
                        <?php while ($u = mysqli_fetch_assoc($users)): ?>
                        <tr>
                            <td><?php echo $u['userID']; ?></td>
                            <td><?php echo $u['username']; ?></td>
                            <td><?php echo $u['full_name']; ?></td>
                            <td><span class="badge badge-blue"><?php echo $u['role']; ?></span></td>
                            <td><?php echo $u['email']; ?></td>
                            <td>
                                <span class="badge <?php echo $u['status'] == 'active' ? 'badge-green' : 'badge-red'; ?>">
                                    <?php echo ucfirst($u['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="../../backend/crud/users/edit_user.php?id=<?php echo $u['userID']; ?>" class="btn btn-small btn-primary">Edit</a>
                                <a href="../../backend/crud/users/delete_user.php?id=<?php echo $u['userID']; ?>" class="btn btn-small btn-red" onclick="return confirm('Delete this user?');">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <!-- One row per user account in the system, grouped by role -->
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; color:#64748b;">No users found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== SUBJECTS TABLE ===== -->
        <div id="subjects" class="panel">
            <div class="panel-title">
                📚 Subjects
                <a href="../../backend/crud/subjects/add_subject.php" class="btn btn-primary btn-small" style="float:right;">+ Add Subject</a>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Code</th><th>Name</th><th>Level</th><th>Fee (LKR)</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php if ($subjects && mysqli_num_rows($subjects) > 0): ?>
                        <?php while ($s = mysqli_fetch_assoc($subjects)): ?>
                        <tr>
                            <td><?php echo $s['code']; ?></td>
                            <td><?php echo $s['name']; ?></td>
                            <td><?php echo $s['level']; ?></td>
                            <td><?php echo number_format($s['fee'], 2); ?></td>
                            <td>
                                <a href="../../backend/crud/subjects/edit_subject.php?id=<?php echo $s['subjectID']; ?>" class="btn btn-small btn-primary">Edit</a>
                                <a href="../../backend/crud/subjects/delete_subject.php?id=<?php echo $s['subjectID']; ?>" class="btn btn-small btn-red" onclick="return confirm('Delete subject?');">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <!-- One row per subject in the system -->
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; color:#64748b;">No subjects found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== BATCHES TABLE ===== -->
        <div id="batches" class="panel">
            <div class="panel-title">
                🗓️ Batches
                <a href="../../backend/crud/batches/add_batch.php" class="btn btn-primary btn-small" style="float:right;">+ Create Batch</a>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Name</th><th>Subject</th><th>Lecturer</th><th>Schedule</th><th>Capacity</th><th>Status</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php if ($batches && mysqli_num_rows($batches) > 0): ?>
                        <?php while ($b = mysqli_fetch_assoc($batches)):
                            $badge = $b['status'] == 'active' ? 'badge-green' : ($b['status'] == 'upcoming' ? 'badge-yellow' : 'badge-gray'); // Colour-code the status pill
                        ?>
                        <tr>
                            <td><?php echo $b['batch_name']; ?></td>
                            <td><?php echo $b['subject_name']; ?></td>
                            <td><?php echo $b['lecturer_name']; ?></td>
                            <td><?php echo $b['schedule']; ?></td>
                            <td><?php echo $b['capacity']; ?></td>
                            <td><span class="badge <?php echo $badge; ?>"><?php echo ucfirst($b['status']); ?></span></td>
                            <td>
                                <a href="../../backend/crud/batches/edit_batch.php?id=<?php echo $b['batchID']; ?>" class="btn btn-small btn-primary">Edit</a>
                                <a href="../../backend/crud/batches/delete_batch.php?id=<?php echo $b['batchID']; ?>" class="btn btn-small btn-red" onclick="return confirm('Delete batch?');">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <!-- One row per batch in the system -->
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align:center; color:#64748b;">No batches found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== PAYMENTS TABLE ===== -->
        <!--
            DISABLED: Payment Approval & Management feature is temporarily
            turned off in the Admin Portal. All code below is untouched —
            to re-enable, change "if (false)" to "if (true)" on the next
            line (and uncomment the nav link near the top of this file).
        -->
        <?php if (false): ?>
        <div id="payments" class="panel">
            <div class="panel-title">💳 Payment Approval &amp; Management</div>

            <!-- Show success message after status update or delete -->
            <?php if (isset($_GET['pay_msg'])): ?>
                <div style="background:#dcfce7; color:#166534; padding:10px 14px; border-radius:8px; margin-bottom:14px;">
                    ✅ <?php echo htmlspecialchars($_GET['pay_msg']); ?>
                </div>
                <!-- Comes from update_payment.php / approve_payment.php / delete_payment.php's redirect -->
            <?php endif; ?>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Student</th><th>Batch</th><th>Amount</th><th>Month</th>
                            <th>Receipt No.</th><th>File</th><th>Status</th><th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($payments && mysqli_num_rows($payments) > 0): ?>
                        <?php while ($p = mysqli_fetch_assoc($payments)):
                            $pb = $p['status'] == 'approved' ? 'badge-green' : ($p['status'] == 'rejected' ? 'badge-red' : 'badge-yellow'); // Colour-code the status pill
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($p['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($p['batch_name']); ?></td>
                            <td>LKR <?php echo number_format($p['amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($p['pay_month']); ?></td>
                            <td style="font-size:.82rem;"><?php echo htmlspecialchars($p['receipt_no']); ?></td>
                            <td>
                                <?php if (!empty($p['receipt_file'])): ?>
                                    <a href="../../uploads/receipts/<?php echo $p['receipt_file']; ?>" target="_blank" style="color:#2563eb; font-size:0.78rem;">📎 View</a>
                                    <!-- Only shown if a receipt was actually uploaded for this payment -->
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-size:0.78rem;">No file</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge <?php echo $pb; ?>"><?php echo ucfirst($p['status']); ?></span></td>

                            <!-- ACTIONS: inline status dropdown + delete -->
                            <td style="white-space:nowrap;">
                                <!-- Inline form to change status to any value -->
                                <form method="POST" action="../../backend/crud/payments/update_payment.php" style="display:inline-flex; gap:4px; align-items:center; margin-bottom:4px;">
                                    <input type="hidden" name="pay_id" value="<?php echo $p['paymentID']; ?>">
                                    <select name="new_status" style="font-size:.78rem; padding:3px 6px; border-radius:5px; border:1px solid #cbd5e1;">
                                        <option value="pending"  <?php if ($p['status']=='pending')  echo 'selected'; ?>>Pending</option>
                                        <option value="approved" <?php if ($p['status']=='approved') echo 'selected'; ?>>Approved</option>
                                        <option value="rejected" <?php if ($p['status']=='rejected') echo 'selected'; ?>>Rejected</option>
                                        <!-- Marks the payment's current status as already selected -->
                                    </select>
                                    <button type="submit" class="btn btn-small btn-primary">Save</button>
                                </form>
                                <br>
                                <!-- Delete button -->
                                <a href="../../backend/crud/payments/delete_payment.php?id=<?php echo $p['paymentID']; ?>"
                                   class="btn btn-small btn-red"
                                   onclick="return confirm('Delete this payment record permanently?');">
                                    🗑️ Delete
                                </a>
                                <!-- Print receipt (only for approved) -->
                                <?php if ($p['status'] == 'approved'): ?>
                                    <a href="print_receipt.php?id=<?php echo $p['paymentID']; ?>" target="_blank" class="btn btn-small btn-green" style="margin-left:2px;">🖨️</a>
                                    <!-- Only meaningful once a payment is approved -->
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <!-- One row per payment record in the system -->
                    <?php else: ?>
                        <tr><td colspan="8" style="text-align:center; color:#64748b;">No payments found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        <!-- END DISABLED: Payment Approval & Management -->

        <!-- ===== ANNOUNCEMENTS TABLE ===== -->
        <div id="announcements" class="panel">
            <div class="panel-title">
                📢 Announcements
                <a href="../../backend/crud/announcements/add_announcement.php" class="btn btn-primary btn-small" style="float:right;">+ Post New</a>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Title</th><th>Audience</th><th>Posted By</th><th>Date</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php if ($announcements && mysqli_num_rows($announcements) > 0): ?>
                        <?php while ($a = mysqli_fetch_assoc($announcements)): ?>
                        <tr>
                            <td><?php echo $a['title']; ?></td>
                            <td><span class="badge badge-blue"><?php echo $a['audience']; ?></span></td>
                            <td><?php echo $a['posted_by_name'] ?: '—'; ?></td>
                            <!-- Falls back to an em-dash if the poster's account was deleted -->
                            <td><?php echo $a['post_date']; ?></td>
                            <td>
                                <a href="../../backend/crud/announcements/edit_announcement.php?id=<?php echo $a['announcementID']; ?>" class="btn btn-small btn-primary">Edit</a>
                                <a href="../../backend/crud/announcements/delete_announcement.php?id=<?php echo $a['announcementID']; ?>" class="btn btn-small btn-red" onclick="return confirm('Delete?');">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <!-- One row per announcement ever posted (any role) -->
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; color:#64748b;">No announcements found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== ENQUIRIES TABLE ===== -->
        <div id="enquiries" class="panel">
            <div class="panel-title">📬 Enquiries</div>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Name</th><th>Phone</th><th>Interest</th><th>Date</th><th>Status</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php if ($enquiries && mysqli_num_rows($enquiries) > 0): ?>
                        <?php while ($e = mysqli_fetch_assoc($enquiries)):
                            $eb = $e['status'] == 'enrolled' ? 'badge-green' : ($e['status'] == 'contacted' ? 'badge-blue' : 'badge-yellow'); // Colour-code the status pill
                        ?>
                        <tr>
                            <td><?php echo $e['name']; ?></td>
                            <td><?php echo $e['phone']; ?></td>
                            <td><?php echo $e['interest']; ?></td>
                            <td><?php echo date('d M Y', strtotime($e['created_at'])); ?></td>
                            <td><span class="badge <?php echo $eb; ?>"><?php echo ucfirst($e['status']); ?></span></td>
                            <td>
                                <a href="../../backend/crud/enquiries/edit_enquiry.php?id=<?php echo $e['enquiryID']; ?>" class="btn btn-small btn-primary">Update</a>
                                <a href="../../backend/crud/enquiries/delete_enquiry.php?id=<?php echo $e['enquiryID']; ?>" class="btn btn-small btn-red" onclick="return confirm('Delete?');">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <!-- One row per public enquiry submitted via enquiry.php or logged by reception -->
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; color:#64748b;">No enquiries found.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ===== LINK PARENT TO STUDENT ===== -->
        <div id="link_parent" class="panel">
            <div class="panel-title">👨‍👩‍👧 Link Parent to Student</div>
            <?php echo $link_parent_msg; ?> <!-- Success/warning/error message from the handler at the top of the file -->

            <!-- Link Form -->
            <div class="card card-accent" style="max-width:520px; margin-bottom:24px;">
                <form method="POST" action="dashboard.php#link_parent">
                    <div class="form-group">
                        <label>Select Parent *</label>
                        <select name="lp_parent_id" required>
                            <option value="">-- Select Parent --</option>
                            <?php $parents = mysqli_query($conn, "SELECT userID, full_name FROM users WHERE role='parent' ORDER BY full_name"); // Fresh query on every page load, so newly added parents show up immediately
                            while ($p = mysqli_fetch_assoc($parents)): ?>
                                <option value="<?php echo $p['userID']; ?>"><?php echo htmlspecialchars($p['full_name']); ?></option>
                                <!-- One option per parent account in the system -->
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Select Student (Child) *</label>
                        <select name="lp_student_id" required>
                            <option value="">-- Select Student --</option>
                            <?php $stu_lp = mysqli_query($conn, "SELECT userID, full_name FROM users WHERE role='student' ORDER BY full_name"); // Fresh query on every page load, so newly added students show up immediately
                            while ($s = mysqli_fetch_assoc($stu_lp)): ?>
                                <option value="<?php echo $s['userID']; ?>"><?php echo htmlspecialchars($s['full_name']); ?></option>
                                <!-- One option per student account in the system -->
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <button type="submit" name="link_parent" class="btn btn-primary">🔗 Link Parent to Student</button>
                </form>
            </div>

            <!-- Existing Links Table -->
            <p style="font-weight:600; color:#1a3a5c; margin-bottom:10px;">Existing Parent–Student Links</p>
            <div class="table-wrapper"><table>
                <thead><tr><th>Parent Name</th><th>Student (Child) Name</th><th>Action</th></tr></thead>
                <tbody>
                <?php
                $all_links = mysqli_query($conn, "
                    SELECT ps.parentStudentID,
                        p.full_name AS parent_name,
                        s.full_name AS student_name
                    FROM parent_student ps
                    JOIN users p ON ps.parent_id  = p.userID
                    JOIN users s ON ps.student_id = s.userID
                    ORDER BY p.full_name
                "); // Every existing parent-student link, with names resolved via joins
                if ($all_links && mysqli_num_rows($all_links) > 0):
                    while ($lnk = mysqli_fetch_assoc($all_links)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($lnk['parent_name']); ?></td>
                        <td><?php echo htmlspecialchars($lnk['student_name']); ?></td>
                        <td>
                            <a href="dashboard.php?unlink_id=<?php echo $lnk['parentStudentID']; ?>#link_parent"
                               class="btn btn-small btn-red"
                               onclick="return confirm('Remove this parent-student link?');">
                               🗑️ Unlink
                            </a>
                            <!-- Handled by the unlink_id pre-HTML redirect block in dashboard.php; only removes the link, not either account -->
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="3" style="text-align:center; color:#64748b;">No links yet. Add one above.</td></tr>
                <?php endif; ?>
                <!-- One row per existing parent-student link -->
                </tbody>
            </table></div>
        </div>

        <!-- ===== REPORTS LINKS ===== -->
        <div id="reports" class="panel">
            <div class="panel-title">📈 Reports</div>
            <div class="card-grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
                <div class="card card-accent" style="text-align:center; padding:28px 20px;">
                    <div style="font-size:2.5rem; margin-bottom:12px;">💰</div>
                    <h3>Financial Report</h3>
                    <p style="color:#64748b; font-size:0.85rem; margin-bottom:16px;">View all payments, revenue by subject and by month.</p>
                    <a href="report_financial.php" class="btn btn-primary" style="display:block;">Open Report</a>
                    <!-- Links straight to report_financial.php -->
                </div>
                <div class="card card-accent" style="text-align:center; padding:28px 20px;">
                    <div style="font-size:2.5rem; margin-bottom:12px;">📚</div>
                    <h3>Academic Report</h3>
                    <p style="color:#64748b; font-size:0.85rem; margin-bottom:16px;">View exam results, pass rates and top students.</p>
                    <a href="report_academic.php" class="btn btn-primary" style="display:block;">Open Report</a>
                    <!-- Links straight to report_academic.php -->
                </div>
            </div>
        </div>

    </main>
</div>
