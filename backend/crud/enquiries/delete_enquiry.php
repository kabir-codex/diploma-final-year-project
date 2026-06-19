<?php
session_start();
require '../../config/db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','manager','receptionist'])) {
    header("Location: ../../../frontend/pages/login.php"); exit();
}
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) mysqli_query($conn, "DELETE FROM enquiries WHERE id=$id");
header("Location: ../../../frontend/pages/dashboard.php?msg=Enquiry+deleted");
exit();
