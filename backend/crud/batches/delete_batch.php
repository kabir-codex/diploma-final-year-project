<?php
// delete_batch.php
session_start();
require '../../config/db.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','manager'])) {
    header("Location: ../../../frontend/pages/login.php"); exit();
}
$id = (int)($_GET['id'] ?? 0);
if ($id > 0) mysqli_query($conn, "DELETE FROM batches WHERE id=$id");
header("Location: ../../../frontend/pages/dashboard.php?msg=Batch+deleted");
exit();
