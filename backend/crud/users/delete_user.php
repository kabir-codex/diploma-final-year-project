<?php
// ============================================================
//  delete_user.php — Delete a User
//  Prevents deleting your own account.
// ============================================================

session_start();
require '../../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../../../frontend/pages/login.php"); exit();
}

$id = (int)($_GET['id'] ?? 0);

// Do not allow deleting yourself
if ($id == $_SESSION['user_id']) {
    header("Location: ../../../frontend/pages/dashboard.php?msg=Cannot+delete+your+own+account");
    exit();
}

if ($id > 0) {
    mysqli_query($conn, "DELETE FROM users WHERE id=$id");
}

header("Location: ../../../frontend/pages/dashboard.php?msg=User+deleted");
exit();
