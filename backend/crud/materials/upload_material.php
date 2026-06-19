<?php
// ============================================================
//  upload_material.php — Handle Study Material File Upload
//  Receives the form from the lecturer dashboard.
//  Saves the file to /uploads/materials/ and records in DB.
// ============================================================

session_start();
require '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    header("Location: ../../../frontend/pages/login.php"); exit();
}

$title       = mysqli_real_escape_string($conn, trim($_POST['mat_title']));
$subject     = mysqli_real_escape_string($conn, trim($_POST['mat_subject']));
$batch_id    = (int)$_POST['mat_batch_id'];
$description = mysqli_real_escape_string($conn, trim($_POST['mat_description']));
$uploader    = mysqli_real_escape_string($conn, $_SESSION['full_name']);

if (empty($title) || empty($subject) || !$batch_id) {
    header("Location: ../../../frontend/pages/dashboard.php?mat_error=Title+subject+and+batch+are+required#materials");
    exit();
}

// Check a file was uploaded
if (!isset($_FILES['mat_file']) || $_FILES['mat_file']['error'] != 0) {
    header("Location: ../../../frontend/pages/dashboard.php?mat_error=Please+select+a+file#materials");
    exit();
}

// Only allow certain file types (for security)
$allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt'];
$ext           = strtolower(pathinfo($_FILES['mat_file']['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed_types)) {
    header("Location: ../../../frontend/pages/dashboard.php?mat_error=Invalid+file+type.+Allowed:+PDF+DOC+PPTX+TXT#materials");
    exit();
}

// Check file size (max 5MB)
$max_size = 5 * 1024 * 1024; // 5 megabytes in bytes
if ($_FILES['mat_file']['size'] > $max_size) {
    header("Location: ../../../frontend/pages/dashboard.php?mat_error=File+too+large.+Max+5MB#materials");
    exit();
}

// Create a unique filename so files don't overwrite each other
$filename = 'mat_' . $_SESSION['user_id'] . '_' . time() . '.' . $ext;
$dest     = '../../../uploads/materials/' . $filename;

if (move_uploaded_file($_FILES['mat_file']['tmp_name'], $dest)) {
    // Save record in database
    mysqli_query($conn, "INSERT INTO study_materials (batch_id, title, subject, description, file_path, uploaded_by) VALUES ($batch_id, '$title', '$subject', '$description', '$filename', '$uploader')");
    header("Location: ../../../frontend/pages/dashboard.php?mat_success=Material+uploaded+successfully#materials");
} else {
    header("Location: ../../../frontend/pages/dashboard.php?mat_error=Upload+failed.+Check+folder+permissions#materials");
}
exit();
