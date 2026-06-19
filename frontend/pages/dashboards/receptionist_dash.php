<?php
// ============================================================
//  receptionist_dash.php — Receptionist Dashboard
//  Can: register students, create batches, manage enquiries,
//  enroll students into batches, record payments.
// ============================================================

// --- MESSAGE VARIABLES ---
$reg_msg = $batch_msg = $enq_msg = $enroll_msg = $pay_msg = $upd_msg = $link_parent_msg = '';

// --- COUNT STATS ---
$cnt_pending_enq  = count_rows($conn, 'enquiries',   "status='pending'");
$cnt_enrolled_enq = count_rows($conn, 'enquiries',   "status='enrolled'");
$cnt_active_enr   = count_rows($conn, 'enrollments', "status='active'");
$cnt_students     = count_rows($conn, 'users',       "role='student'");
$cnt_pending_pay  = count_rows($conn, 'payments',    "status='pending'");

// --- LINK PARENT TO STUDENT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['link_parent'])) {
    $lp_parent_id  = (int)$_POST['lp_parent_id'];
    $lp_student_id = (int)$_POST['lp_student_id'];

    if (!$lp_parent_id || !$lp_student_id) {
        $link_parent_msg = "<div style='background:#fee2e2;color:#991b1b;padding:10px;border-radius:7px;margin-bottom:14px;'>❌ Please select both a parent and a student.</div>";
    } else {
        // Check if this parent-student link already exists
        $chk = mysqli_query($conn, "SELECT id FROM parent_student WHERE parent_id=$lp_parent_id AND student_id=$lp_student_id");
        if (mysqli_num_rows($chk) > 0) {
            $link_parent_msg = "<div style='background:#fef3c7;color:#92400e;padding:10px;border-radius:7px;margin-bottom:14px;'>⚠️ This parent is already linked to that student.</div>";
        } else {
            mysqli_query($conn, "INSERT INTO parent_student (parent_id, student_id) VALUES ($lp_parent_id, $lp_student_id)");
            $link_parent_msg = "<div style='background:#dcfce7;color:#166534;padding:10px;border-radius:7px;margin-bottom:14px;'>✅ Parent linked to student successfully!</div>";
        }
    }
}

// --- UNLINK PARENT FROM STUDENT ---
if (isset($_GET['unlink_id'])) {
    $ul_id = (int)$_GET['unlink_id'];
    if ($ul_id > 0) mysqli_query($conn, "DELETE FROM parent_student WHERE id=$ul_id");
    header("Location: dashboard.php#link_parent");
    exit();
}

// --- LOAD DROPDOWNS ---
// Active batches for dropdowns
$batch_rows = [];
$br = mysqli_query($conn, "SELECT b.id, b.batch_name, s.name AS subject_name, u.full_name AS lecturer_name FROM batches b JOIN subjects s ON b.subject_id=s.id JOIN users u ON b.lecturer_id=u.id WHERE b.status='active' ORDER BY b.batch_name");
if ($br) while ($r = mysqli_fetch_assoc($br)) $batch_rows[] = $r;

// Subjects for batch creation form
$subject_rows = [];
$sr = mysqli_query($conn, "SELECT id, name, code FROM subjects ORDER BY name");
if ($sr) while ($r = mysqli_fetch_assoc($sr)) $subject_rows[] = $r;

// Lecturers for batch creation form
$lecturer_rows = [];
$lr = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role='lecturer' AND status='active' ORDER BY full_name");
if ($lr) while ($r = mysqli_fetch_assoc($lr)) $lecturer_rows[] = $r;

// --- REGISTER STUDENT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_student'])) {
    $reg_name  = mysqli_real_escape_string($conn, trim($_POST['reg_name']));
    $reg_user  = mysqli_real_escape_string($conn, trim($_POST['reg_username']));
    $reg_pass  = mysqli_real_escape_string($conn, trim($_POST['reg_password']));
    $reg_email = mysqli_real_escape_string($conn, trim($_POST['reg_email']));
    $reg_phone = mysqli_real_escape_string($conn, trim($_POST['reg_phone']));

    if (empty($reg_name) || empty($reg_user) || empty($reg_pass)) {
        $reg_msg = "<div style='background:#fee2e2;color:#991b1b;padding:10px;border-radius:7px;margin-bottom:14px;'>❌ Name, username and password are required.</div>";
    } else {
        // Check if username already exists
        $chk = mysqli_query($conn, "SELECT id FROM users WHERE username='$reg_user'");
        if (mysqli_num_rows($chk) > 0) {
            $reg_msg = "<div style='background:#fef3c7;color:#92400e;padding:10px;border-radius:7px;margin-bottom:14px;'>⚠️ Username already exists. Please choose another.</div>";
        } else {
            mysqli_query($conn, "INSERT INTO users (username, password, full_name, email, phone, role, status) VALUES ('$reg_user','$reg_pass','$reg_name','$reg_email','$reg_phone','student','active')");
            $reg_msg = "<div style='background:#dcfce7;color:#166534;padding:10px;border-radius:7px;margin-bottom:14px;'>✅ Student <strong>" . htmlspecialchars($reg_name) . "</strong> registered! Login: <strong>" . htmlspecialchars($reg_user) . "</strong></div>";
            $cnt_students++;
        }
    }
}

// --- CREATE BATCH ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_batch'])) {
    $b_name       = mysqli_real_escape_string($conn, trim($_POST['b_name']));
    $b_subject_id = (int)$_POST['b_subject_id'];
    $b_lec_id     = (int)$_POST['b_lecturer_id'];
    $b_schedule   = mysqli_real_escape_string($conn, trim($_POST['b_schedule']));
    $b_room       = mysqli_real_escape_string($conn, trim($_POST['b_room']));
    $b_capacity   = (int)$_POST['b_capacity'];
    $b_start      = mysqli_real_escape_string($conn, $_POST['b_start_date']);
    $b_status     = mysqli_real_escape_string($conn, $_POST['b_status']);

    if (empty($b_name) || !$b_subject_id || !$b_lec_id) {
        $batch_msg = "<div style='background:#fee2e2;color:#991b1b;padding:10px;border-radius:7px;margin-bottom:14px;'>❌ Batch name, subject and lecturer are required.</div>";
    } else {
        mysqli_query($conn, "INSERT INTO batches (batch_name, subject_id, lecturer_id, schedule, room, capacity, status, start_date) VALUES ('$b_name',$b_subject_id,$b_lec_id,'$b_schedule','$b_room',$b_capacity,'$b_status','$b_start')");
        $batch_msg = "<div style='background:#dcfce7;color:#166534;padding:10px;border-radius:7px;margin-bottom:14px;'>✅ Batch <strong>" . htmlspecialchars($b_name) . "</strong> created!</div>";
        // Refresh batch rows
        $br2 = mysqli_query($conn, "SELECT b.id, b.batch_name, s.name AS subject_name, u.full_name AS lecturer_name FROM batches b JOIN subjects s ON b.subject_id=s.id JOIN users u ON b.lecturer_id=u.id WHERE b.status='active' ORDER BY b.batch_name");
        $batch_rows = [];
        if ($br2) while ($r = mysqli_fetch_assoc($br2)) $batch_rows[] = $r;
    }
}

// --- ADD ENQUIRY ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_enquiry'])) {
    $name     = mysqli_real_escape_string($conn, trim($_POST['enq_name']));
    $phone    = mysqli_real_escape_string($conn, trim($_POST['enq_phone']));
    $email    = mysqli_real_escape_string($conn, trim($_POST['enq_email']));
    $interest = mysqli_real_escape_string($conn, $_POST['enq_interest']);
    $notes    = mysqli_real_escape_string($conn, trim($_POST['enq_notes']));

    if (empty($name) || empty($phone)) {
        $enq_msg = "<div style='background:#fee2e2;color:#991b1b;padding:10px;border-radius:7px;margin-bottom:14px;'>❌ Name and phone are required.</div>";
    } else {
        mysqli_query($conn, "INSERT INTO enquiries (name, phone, email, interest, notes, status) VALUES ('$name','$phone','$email','$interest','$notes','pending')");
        $enq_msg = "<div style='background:#dcfce7;color:#166534;padding:10px;border-radius:7px;margin-bottom:14px;'>✅ Enquiry added successfully!</div>";
    }
}

// --- ENROLL STUDENT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['enroll_student'])) {
    $student_id = (int)$_POST['enroll_student_id'];
    $batch_id   = (int)$_POST['enroll_batch_id'];

    if (!$student_id || !$batch_id) {
        $enroll_msg = "<div style='background:#fee2e2;color:#991b1b;padding:10px;border-radius:7px;margin-bottom:14px;'>❌ Please select both a student and a batch.</div>";
    } else {
        // Check if already enrolled
        $chk = mysqli_query($conn, "SELECT id FROM enrollments WHERE student_id=$student_id AND batch_id=$batch_id");
        if (mysqli_num_rows($chk) > 0) {
            $enroll_msg = "<div style='background:#fef3c7;color:#92400e;padding:10px;border-radius:7px;margin-bottom:14px;'>⚠️ Student already enrolled in that batch.</div>";
        } else {
            $date = date('Y-m-d');
            mysqli_query($conn, "INSERT INTO enrollments (student_id, batch_id, enroll_date, status) VALUES ($student_id, $batch_id, '$date', 'active')");
            $enroll_msg = "<div style='background:#dcfce7;color:#166534;padding:10px;border-radius:7px;margin-bottom:14px;'>✅ Student enrolled successfully!</div>";
        }
    }
}

// --- RECORD PAYMENT ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_payment'])) {
    $pay_student = (int)$_POST['pay_student_id'];
    $pay_batch   = (int)$_POST['pay_batch_id'];
    $pay_amount  = (float)$_POST['pay_amount'];
    $pay_month   = mysqli_real_escape_string($conn, $_POST['pay_month']);
    $pay_date    = mysqli_real_escape_string($conn, $_POST['pay_date']);
    // Auto-generate a unique receipt number
    $rec_no      = 'REC-' . strtoupper(substr(md5(uniqid()), 0, 6));

    if (!$pay_student || !$pay_batch || $pay_amount <= 0 || empty($pay_month)) {
        $pay_msg = "<div style='background:#fee2e2;color:#991b1b;padding:10px;border-radius:7px;margin-bottom:14px;'>❌ Please fill in all required payment fields.</div>";
    } else {
        $ins = mysqli_query($conn, "INSERT INTO payments (student_id, batch_id, amount, pay_month, receipt_no, pay_date, status) VALUES ($pay_student,$pay_batch,$pay_amount,'$pay_month','$rec_no','$pay_date','approved')");
        if ($ins) {
            $new_id  = mysqli_insert_id($conn);
            $pay_msg = "<div style='background:#dcfce7;color:#166534;padding:10px;border-radius:7px;margin-bottom:14px;'>✅ Payment recorded! Receipt: <strong>$rec_no</strong> &nbsp; <a href='print_receipt.php?id=$new_id' target='_blank' class='btn btn-small btn-primary'>🖨️ Print Receipt</a></div>";
        } else {
            $pay_msg = "<div style='background:#fee2e2;color:#991b1b;padding:10px;border-radius:7px;margin-bottom:14px;'>❌ Error: " . mysqli_error($conn) . "</div>";
        }
    }
}

// --- UPDATE PAYMENT STATUS ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_pay_status'])) {
    $upd_id = (int)$_POST['upd_pay_id'];
    $upd_st = mysqli_real_escape_string($conn, $_POST['upd_status']);
    if ($upd_id && in_array($upd_st, ['approved', 'rejected', 'pending'])) {
        mysqli_query($conn, "UPDATE payments SET status='$upd_st' WHERE id=$upd_id");
        $upd_msg = "<div style='background:#dcfce7;color:#166534;padding:10px;border-radius:7px;margin-bottom:14px;'>✅ Payment status updated to <strong>" . ucfirst($upd_st) . "</strong>.</div>";
    }
}

// --- LOAD TABLE DATA ---
$enq_result    = mysqli_query($conn, "SELECT * FROM enquiries ORDER BY created_at DESC");
$enroll_result = mysqli_query($conn, "SELECT e.*, u.full_name AS student_name, b.batch_name, s.name AS subject_name FROM enrollments e JOIN users u ON e.student_id=u.id JOIN batches b ON e.batch_id=b.id JOIN subjects s ON b.subject_id=s.id ORDER BY e.id DESC LIMIT 20");
$pay_history   = mysqli_query($conn, "SELECT p.*, u.full_name AS student_name, b.batch_name FROM payments p JOIN users u ON p.student_id=u.id JOIN batches b ON p.batch_id=b.id ORDER BY p.id DESC LIMIT 40");
?>

<div class="dashboard-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header"><h3>🗂️ Receptionist</h3><p><?php echo htmlspecialchars($full_name); ?></p></div>
        <nav class="sidebar-nav">
            <a href="#overview"        class="active"><span class="sidebar-icon">📊</span> Overview</a>
            <a href="#register">                      <span class="sidebar-icon">➕</span> Register Student</a>
            <a href="#create_batch">                  <span class="sidebar-icon">🗓️</span> Create Batch</a>
            <a href="#enquiries">                     <span class="sidebar-icon">📬</span> Enquiries</a>
            <a href="#enroll">                        <span class="sidebar-icon">📝</span> Assign to Batch</a>
            <a href="#enrollments">                   <span class="sidebar-icon">📋</span> Enrollments</a>
            <a href="#link_parent">                   <span class="sidebar-icon">👨‍👩‍👧</span> Link Parent</a>
            <a href="#record_payment">                <span class="sidebar-icon">💳</span> Record Payment</a>
            <a href="#payment_history">               <span class="sidebar-icon">🧾</span> Payment History</a>
        </nav>
    </aside>

    <main class="dashboard-main">
        <h1 class="dashboard-title">Receptionist Panel</h1>
        <p class="dashboard-subtitle">Welcome, <?php echo htmlspecialchars($full_name); ?>. Front desk management.</p>

        <!-- OVERVIEW STATS -->
        <div id="overview" class="stats-grid">
            <div class="stat-card yellow"><div class="stat-number"><?php echo $cnt_pending_enq; ?></div><div class="stat-label">Pending Enquiries</div></div>
            <div class="stat-card green"> <div class="stat-number"><?php echo $cnt_enrolled_enq; ?></div><div class="stat-label">Enquiries Enrolled</div></div>
            <div class="stat-card">       <div class="stat-number"><?php echo $cnt_students; ?></div><div class="stat-label">Total Students</div></div>
            <div class="stat-card orange"><div class="stat-number"><?php echo $cnt_active_enr; ?></div><div class="stat-label">Active Enrollments</div></div>
            <div class="stat-card red">   <div class="stat-number"><?php echo $cnt_pending_pay; ?></div><div class="stat-label">Pending Payments</div></div>
        </div>

        <!-- REGISTER STUDENT -->
        <div id="register" class="panel">
            <div class="panel-title">➕ Register New Student</div>
            <?php echo $reg_msg; ?>
            <div class="card card-accent" style="max-width:600px;">
                <form method="POST" action="dashboard.php#register">
                    <div class="form-row">
                        <div class="form-group"><label>Full Name *</label><input type="text" name="reg_name" placeholder="e.g. Kamal Perera" required></div>
                        <div class="form-group"><label>Username * <small style="color:#64748b;">(for login)</small></label><input type="text" name="reg_username" placeholder="e.g. kamal123" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Password *</label><input type="text" name="reg_password" placeholder="Set initial password" required></div>
                        <div class="form-group"><label>Phone</label><input type="text" name="reg_phone" placeholder="077 xxx xxxx"></div>
                    </div>
                    <div class="form-group"><label>Email</label><input type="email" name="reg_email" placeholder="student@email.com"></div>
                    <button type="submit" name="register_student" class="btn btn-primary">✅ Register Student</button>
                </form>
            </div>
        </div>

        <!-- CREATE BATCH -->
        <div id="create_batch" class="panel">
            <div class="panel-title">🗓️ Create New Batch</div>
            <?php echo $batch_msg; ?>
            <div class="card card-accent" style="max-width:640px;">
                <form method="POST" action="dashboard.php#create_batch">
                    <div class="form-row">
                        <div class="form-group"><label>Batch Name *</label><input type="text" name="b_name" placeholder="e.g. Math O/L Batch C" required></div>
                        <div class="form-group">
                            <label>Subject *</label>
                            <select name="b_subject_id" required>
                                <option value="">-- Select Subject --</option>
                                <?php foreach ($subject_rows as $sub): ?>
                                    <option value="<?php echo $sub['id']; ?>"><?php echo htmlspecialchars($sub['name']); ?> (<?php echo $sub['code']; ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Lecturer *</label>
                            <select name="b_lecturer_id" required>
                                <option value="">-- Select Lecturer --</option>
                                <?php foreach ($lecturer_rows as $lc): ?>
                                    <option value="<?php echo $lc['id']; ?>"><?php echo htmlspecialchars($lc['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="b_status">
                                <option value="active">Active</option>
                                <option value="upcoming">Upcoming</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Schedule</label><input type="text" name="b_schedule" placeholder="e.g. Sat & Sun 9:00 AM"></div>
                        <div class="form-group"><label>Room</label><input type="text" name="b_room" placeholder="e.g. Room 101"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Capacity</label><input type="number" name="b_capacity" value="30" min="1"></div>
                        <div class="form-group"><label>Start Date</label><input type="date" name="b_start_date" value="<?php echo date('Y-m-d'); ?>"></div>
                    </div>
                    <button type="submit" name="create_batch" class="btn btn-primary">🗓️ Create Batch</button>
                </form>
            </div>
        </div>

        <!-- ENQUIRIES -->
        <div id="enquiries" class="panel">
            <div class="panel-title">📬 Student Enquiries</div>
            <?php echo $enq_msg; ?>
            <div class="card card-accent" style="margin-bottom:24px;">
                <h3 style="margin-bottom:14px; color:#1a3a5c; font-size:1rem;">➕ Add New Enquiry</h3>
                <form method="POST" action="dashboard.php#enquiries">
                    <div class="form-row">
                        <div class="form-group"><label>Full Name *</label><input type="text" name="enq_name" required placeholder="e.g. Saman Perera"></div>
                        <div class="form-group"><label>Phone Number *</label><input type="text" name="enq_phone" required placeholder="077 xxx xxxx"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Email</label><input type="email" name="enq_email" placeholder="email@example.com"></div>
                        <div class="form-group">
                            <label>Interested In</label>
                            <select name="enq_interest">
                                <option value="General Enquiry">General Enquiry</option>
                                <?php $subs = mysqli_query($conn, "SELECT name FROM subjects ORDER BY name");
                                while ($s = mysqli_fetch_assoc($subs)): ?>
                                    <option value="<?php echo $s['name']; ?>"><?php echo $s['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group"><label>Notes</label><textarea name="enq_notes" placeholder="Any additional notes..."></textarea></div>
                    <button type="submit" name="add_enquiry" class="btn btn-primary">💾 Save Enquiry</button>
                </form>
            </div>

            <div class="table-wrapper"><table>
                <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Interest</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($enq_result && mysqli_num_rows($enq_result) > 0):
                    while ($e = mysqli_fetch_assoc($enq_result)):
                        $eb = $e['status'] == 'enrolled' ? 'badge-green' : ($e['status'] == 'contacted' ? 'badge-blue' : 'badge-yellow');
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($e['name']); ?></td>
                        <td><?php echo htmlspecialchars($e['phone']); ?></td>
                        <td style="font-size:.82rem;"><?php echo htmlspecialchars($e['email'] ?: '—'); ?></td>
                        <td><?php echo htmlspecialchars($e['interest']); ?></td>
                        <td style="font-size:.82rem;"><?php echo date('d M Y', strtotime($e['created_at'])); ?></td>
                        <td><span class="badge <?php echo $eb; ?>"><?php echo ucfirst($e['status']); ?></span></td>
                        <td>
                            <a href="../../backend/crud/enquiries/edit_enquiry.php?id=<?php echo $e['id']; ?>" class="btn btn-small btn-primary">Edit</a>
                            <a href="../../backend/crud/enquiries/delete_enquiry.php?id=<?php echo $e['id']; ?>" class="btn btn-small btn-red" onclick="return confirm('Delete?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="7" style="text-align:center; color:#64748b;">No enquiries yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table></div>
        </div>

        <!-- ENROLL STUDENT INTO BATCH -->
        <div id="enroll" class="panel">
            <div class="panel-title">📝 Assign Student to Batch</div>
            <?php echo $enroll_msg; ?>
            <form method="POST" action="dashboard.php#enroll" style="max-width:520px;">
                <div class="form-group">
                    <label>Select Student *</label>
                    <select name="enroll_student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php $stu2 = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role='student' ORDER BY full_name");
                        while ($s = mysqli_fetch_assoc($stu2)): ?>
                            <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Select Batch *</label>
                    <select name="enroll_batch_id" required>
                        <option value="">-- Select Batch --</option>
                        <?php foreach ($batch_rows as $b): ?>
                            <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['batch_name']); ?> – <?php echo htmlspecialchars($b['subject_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="enroll_student" class="btn btn-primary">📝 Assign to Batch</button>
            </form>
        </div>

        <!-- ENROLLMENTS LIST -->
        <div id="enrollments" class="panel">
            <div class="panel-title">📋 Recent Enrollments</div>
            <div class="table-wrapper"><table>
                <thead><tr><th>Student</th><th>Subject</th><th>Batch</th><th>Enrolled On</th><th>Status</th></tr></thead>
                <tbody>
                <?php if ($enroll_result && mysqli_num_rows($enroll_result) > 0):
                    while ($er = mysqli_fetch_assoc($enroll_result)):
                        $erb = $er['status'] == 'active' ? 'badge-green' : ($er['status'] == 'dropped' ? 'badge-red' : 'badge-gray');
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($er['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($er['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($er['batch_name']); ?></td>
                        <td><?php echo date('d M Y', strtotime($er['enroll_date'])); ?></td>
                        <td><span class="badge <?php echo $erb; ?>"><?php echo ucfirst($er['status']); ?></span></td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="5" style="text-align:center; color:#64748b;">No enrollments yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table></div>
        </div>

        <!-- LINK PARENT TO STUDENT -->
        <div id="link_parent" class="panel">
            <div class="panel-title">👨‍👩‍👧 Link Parent to Student</div>
            <?php echo $link_parent_msg; ?>

            <!-- Link Form -->
            <div class="card card-accent" style="max-width:520px; margin-bottom:24px;">
                <form method="POST" action="dashboard.php#link_parent">
                    <div class="form-group">
                        <label>Select Parent *</label>
                        <select name="lp_parent_id" required>
                            <option value="">-- Select Parent --</option>
                            <?php $parents = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role='parent' ORDER BY full_name");
                            while ($p = mysqli_fetch_assoc($parents)): ?>
                                <option value="<?php echo $p['id']; ?>"><?php echo htmlspecialchars($p['full_name']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Select Student (Child) *</label>
                        <select name="lp_student_id" required>
                            <option value="">-- Select Student --</option>
                            <?php $stu_lp = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role='student' ORDER BY full_name");
                            while ($s = mysqli_fetch_assoc($stu_lp)): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?></option>
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
                    SELECT ps.id,
                        p.full_name AS parent_name,
                        s.full_name AS student_name
                    FROM parent_student ps
                    JOIN users p ON ps.parent_id  = p.id
                    JOIN users s ON ps.student_id = s.id
                    ORDER BY p.full_name
                ");
                if ($all_links && mysqli_num_rows($all_links) > 0):
                    while ($lnk = mysqli_fetch_assoc($all_links)): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($lnk['parent_name']); ?></td>
                        <td><?php echo htmlspecialchars($lnk['student_name']); ?></td>
                        <td>
                            <a href="dashboard.php?unlink_id=<?php echo $lnk['id']; ?>#link_parent"
                               class="btn btn-small btn-red"
                               onclick="return confirm('Remove this parent-student link?');">
                               🗑️ Unlink
                            </a>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="3" style="text-align:center; color:#64748b;">No links yet. Add one above.</td></tr>
                <?php endif; ?>
                </tbody>
            </table></div>
        </div>

        <!-- RECORD PAYMENT -->
        <div id="record_payment" class="panel">
            <div class="panel-title">💳 Record Payment &amp; Generate Receipt</div>
            <?php echo $pay_msg; ?>
            <div class="card card-accent" style="max-width:620px;">
                <form method="POST" action="dashboard.php#record_payment">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Student *</label>
                            <select name="pay_student_id" required>
                                <option value="">-- Select Student --</option>
                                <?php $stu3 = mysqli_query($conn, "SELECT id, full_name FROM users WHERE role='student' ORDER BY full_name");
                                while ($s = mysqli_fetch_assoc($stu3)): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['full_name']); ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Batch *</label>
                            <select name="pay_batch_id" required>
                                <option value="">-- Select Batch --</option>
                                <?php foreach ($batch_rows as $b): ?>
                                    <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['batch_name']); ?> – <?php echo htmlspecialchars($b['subject_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Amount (LKR) *</label><input type="number" name="pay_amount" placeholder="e.g. 2500" min="1" step="0.01" required></div>
                        <div class="form-group"><label>Payment Month * <small style="color:#64748b;">(YYYY-MM)</small></label><input type="month" name="pay_month" value="<?php echo date('Y-m'); ?>" required></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>Payment Date *</label><input type="date" name="pay_date" value="<?php echo date('Y-m-d'); ?>" required></div>
                        <div class="form-group">
                            <label>Payment Method</label>
                            <select name="pay_method">
                                <option>Cash</option><option>Bank Transfer</option><option>Card</option><option>Online</option>
                            </select>
                        </div>
                    </div>
                    <p style="font-size:.82rem; color:#64748b; margin-bottom:12px;">ℹ️ Receipt number is auto-generated. Payment is saved as Approved.</p>
                    <button type="submit" name="record_payment" class="btn btn-primary">💾 Save Payment &amp; Generate Receipt</button>
                </form>
            </div>
        </div>

        <!-- PAYMENT HISTORY -->
        <div id="payment_history" class="panel">
            <div class="panel-title">🧾 Payment History &amp; Status</div>
            <?php echo $upd_msg; ?>
            <!-- Show message after delete (redirected back with ?msg=) -->
            <?php if (isset($_GET['msg'])): ?>
                <div style="background:#dcfce7; color:#166534; padding:10px 14px; border-radius:8px; margin-bottom:14px;">
                    ✅ <?php echo htmlspecialchars($_GET['msg']); ?>
                </div>
            <?php endif; ?>
            <div class="table-wrapper"><table>
                <thead><tr><th>Receipt No</th><th>Student</th><th>Batch</th><th>Month</th><th>Amount (LKR)</th><th>Date</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php if ($pay_history && mysqli_num_rows($pay_history) > 0):
                    while ($ph = mysqli_fetch_assoc($pay_history)):
                        $pb = $ph['status'] == 'approved' ? 'badge-green' : ($ph['status'] == 'rejected' ? 'badge-red' : 'badge-yellow');
                ?>
                    <tr>
                        <td style="font-weight:600; font-size:.85rem;"><?php echo htmlspecialchars($ph['receipt_no'] ?: '—'); ?></td>
                        <td><?php echo htmlspecialchars($ph['student_name']); ?></td>
                        <td style="font-size:.85rem;"><?php echo htmlspecialchars($ph['batch_name']); ?></td>
                        <td><?php echo htmlspecialchars($ph['pay_month']); ?></td>
                        <td style="font-weight:600;">LKR <?php echo number_format($ph['amount'], 2); ?></td>
                        <td style="font-size:.82rem;"><?php echo $ph['pay_date'] ? date('d M Y', strtotime($ph['pay_date'])) : '—'; ?></td>
                        <td><span class="badge <?php echo $pb; ?>"><?php echo ucfirst($ph['status']); ?></span></td>
                        <td style="white-space:nowrap;">
                            <!-- Inline status update form -->
                            <form method="POST" action="dashboard.php#payment_history" style="display:inline-flex; gap:4px; align-items:center; margin-bottom:4px;">
                                <input type="hidden" name="upd_pay_id" value="<?php echo $ph['id']; ?>">
                                <select name="upd_status" style="font-size:.78rem; padding:3px 6px; border-radius:5px; border:1px solid #cbd5e1;">
                                    <option value="approved" <?php echo $ph['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="pending"  <?php echo $ph['status'] == 'pending'  ? 'selected' : ''; ?>>Pending</option>
                                    <option value="rejected" <?php echo $ph['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                                <button type="submit" name="update_pay_status" class="btn btn-small btn-primary">Save</button>
                            </form>
                            <br>
                            <!-- Print receipt -->
                            <a href="print_receipt.php?id=<?php echo $ph['id']; ?>" target="_blank" class="btn btn-small btn-green" title="Print Receipt">🖨️</a>
                            <!-- Delete payment record -->
                            <a href="../../backend/crud/payments/delete_payment.php?id=<?php echo $ph['id']; ?>"
                               class="btn btn-small btn-red"
                               style="margin-left:2px;"
                               onclick="return confirm('Delete this payment record permanently?');">
                               🗑️ Delete
                            </a>
                        </td>
                    </tr>
                <?php endwhile; else: ?>
                    <tr><td colspan="8" style="text-align:center; color:#64748b;">No payment records yet.</td></tr>
                <?php endif; ?>
                </tbody>
            </table></div>
        </div>

    </main>
</div>

<!-- Sidebar active section highlight -->
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
