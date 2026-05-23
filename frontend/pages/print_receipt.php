<?php
// ============================================================
//  print_receipt.php — Print Payment Receipt
//  Opens in a new tab showing just the receipt.
//  No nav bar or footer — clean print layout.
// ============================================================

session_start();
require '../../backend/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); exit();
}

$id  = (int)($_GET['id'] ?? 0);
$res = $id ? mysqli_fetch_assoc(mysqli_query($conn, "SELECT p.*, u.full_name AS student_name, b.batch_name, s.name AS subject_name FROM payments p JOIN users u ON p.student_id=u.id JOIN batches b ON p.batch_id=b.id JOIN subjects s ON b.subject_id=s.id WHERE p.id=$id")) : null;

if (!$res) { echo "Receipt not found."; exit(); }
?>
<!DOCTYPE html>
<html>
<head>
    <title>Receipt <?php echo htmlspecialchars($res['receipt_no']); ?></title>
    <style>
        /* Simple receipt styling */
        body       { font-family: Arial, sans-serif; max-width: 420px; margin: 40px auto; padding: 24px; border: 2px solid #1a3a5c; border-radius: 10px; background: white; }
        h2         { color: #1a3a5c; text-align: center; margin-bottom: 4px; }
        .sub       { text-align: center; color: #64748b; font-size: 0.85rem; margin-bottom: 20px; }
        table      { width: 100%; border-collapse: collapse; }
        td         { padding: 8px 4px; border-bottom: 1px solid #e2e8f0; font-size: 0.92rem; }
        td:first-child { color: #64748b; width: 45%; }
        td:last-child  { font-weight: 600; }
        .total td  { font-size: 1.1rem; color: #1a3a5c; }
        .footer    { text-align: center; margin-top: 18px; color: #64748b; font-size: 0.82rem; }
        .btn-row   { text-align: center; margin-top: 20px; display: flex; gap: 10px; justify-content: center; }
        button     { padding: 8px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9rem; font-weight: 600; }
        .btn-print { background: #1a3a5c; color: white; }
        .btn-close { background: #e2e8f0; color: #1e293b; }
        @media print { .btn-row { display: none; } }
    </style>
</head>
<body>
    <h2>🎓 Activate Academy</h2>
    <p class="sub">Official Fee Receipt</p>

    <table>
        <tr><td>Receipt No</td>   <td><?php echo htmlspecialchars($res['receipt_no'] ?: '—'); ?></td></tr>
        <tr><td>Student</td>      <td><?php echo htmlspecialchars($res['student_name']); ?></td></tr>
        <tr><td>Batch</td>        <td><?php echo htmlspecialchars($res['batch_name']); ?></td></tr>
        <tr><td>Subject</td>      <td><?php echo htmlspecialchars($res['subject_name']); ?></td></tr>
        <tr><td>Month</td>        <td><?php echo htmlspecialchars($res['pay_month']); ?></td></tr>
        <tr><td>Payment Date</td> <td><?php echo $res['pay_date'] ? date('d M Y', strtotime($res['pay_date'])) : '—'; ?></td></tr>
        <tr class="total">
            <td>Amount Paid</td>
            <td>LKR <?php echo number_format($res['amount'], 2); ?></td>
        </tr>
        <tr><td>Status</td>       <td><?php echo ucfirst($res['status']); ?></td></tr>
    </table>

    <p class="footer">Thank you for your payment!<br>Activate Academy</p>

    <div class="btn-row">
        <button class="btn-print" onclick="window.print()">🖨️ Print</button>
        <button class="btn-close" onclick="window.close()">Close</button>
    </div>
</body>
</html>
