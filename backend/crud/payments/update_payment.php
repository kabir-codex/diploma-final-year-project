<?php
// ============================================================
//  update_payment.php — Update Payment Status
//  Used by the Admin dashboard inline status dropdown.
//  Admin can change any payment to: pending / approved / rejected
// ============================================================

session_start();
require '../../config/db.php';

// Only admin and manager can change payment status
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager'])) {
    header("Location: ../../../frontend/pages/login.php");
    exit();
}

// Get the payment ID and new status from the form
$pay_id     = (int)$_POST['pay_id'];
$new_status = mysqli_real_escape_string($conn, $_POST['new_status']);

// Only allow valid status values (security check)
$allowed = ['pending', 'approved', 'rejected'];

if ($pay_id > 0 && in_array($new_status, $allowed)) {
    mysqli_query($conn, "UPDATE payments SET status='$new_status' WHERE id=$pay_id");
    $msg = "Payment status updated to " . ucfirst($new_status);
} else {
    $msg = "Invalid request";
}

// Redirect back to admin dashboard payments section
header("Location: ../../../frontend/pages/dashboard.php?pay_msg=" . urlencode($msg) . "#payments");
exit();
