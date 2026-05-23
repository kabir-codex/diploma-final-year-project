<?php
// ============================================================
//  delete_material.php — Delete a Study Material
//  Also removes the actual file from /uploads/materials/
// ============================================================

session_start();
require '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    header("Location: ../../../frontend/pages/login.php"); exit();
}

$id  = (int)($_GET['id'] ?? 0);
$mat = $id ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM study_materials WHERE id=$id")) : null;

if ($mat) {
    // Delete the physical file from the uploads folder
    $file = '../../../uploads/materials/' . $mat['file_path'];
    if (file_exists($file)) {
        unlink($file);
    }
    // Delete the database record
    mysqli_query($conn, "DELETE FROM study_materials WHERE id=$id");
}

header("Location: ../../../frontend/pages/dashboard.php?msg=Material+deleted#materials");
exit();
