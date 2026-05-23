<?php
// ============================================================
//  delete_payment.php — Delete a Payment Record
//  Used by Admin dashboard and Receptionist dashboard.
//  Permanently removes the payment row from the database.
//  If there's an uploaded receipt file, it is also deleted.
// ============================================================

session_start();
require '../../config/db.php';

// Only admin, manager, and receptionist can delete payments
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'manager', 'receptionist'])) {
    header("Location: ../../../frontend/pages/login.php");
    exit();
}

$id = (int)($_GET['id'] ?? 0);

if ($id > 0) {
    // First fetch the payment so we can delete the receipt file too (if any)
    $pay = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM payments WHERE id=$id"));

    if ($pay) {
        // If there's an uploaded receipt image, delete the file from the uploads folder
        if (!empty($pay['receipt_file'])) {
            $file_path = '../../../uploads/receipts/' . $pay['receipt_file'];
            if (file_exists($file_path)) {
                unlink($file_path); // Delete the physical file
            }
        }

        // Now delete the database record
        mysqli_query($conn, "DELETE FROM payments WHERE id=$id");
    }
}

// Figure out where to redirect based on who is deleting
$role = $_SESSION['role'];
if ($role == 'receptionist') {
    // Receptionist goes back to their payment history section
    header("Location: ../../../frontend/pages/dashboard.php?msg=Payment+deleted#payment_history");
} else {
    // Admin/manager goes back to admin payments section
    header("Location: ../../../frontend/pages/dashboard.php?pay_msg=Payment+deleted#payments");
}
exit();
