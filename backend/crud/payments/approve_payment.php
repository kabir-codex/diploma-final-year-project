<?php
// ============================================================
//  approve_payment.php — Approve or Reject a Payment
// ============================================================

session_start();
require '../../config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','manager','receptionist'])) {
    header("Location: ../../../frontend/pages/login.php"); exit();
}

$id     = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

// Only allow 'approve' or 'reject' as valid actions
if ($id > 0 && in_array($action, ['approve', 'reject'])) {
    $status = ($action == 'approve') ? 'approved' : 'rejected';
    mysqli_query($conn, "UPDATE payments SET status='$status' WHERE id=$id");
}

$status_word = ($action == 'approve') ? 'Approved' : 'Rejected';
header("Location: ../../../frontend/pages/dashboard.php?msg=Payment+" . $status_word);
exit();
