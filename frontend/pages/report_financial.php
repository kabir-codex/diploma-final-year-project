<?php
// ============================================================
//  report_financial.php — Financial Report
//  Accessible by admin, manager, and director.
//  Shows all payments with filter options.
// ============================================================

session_start();
require '../../backend/config/db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin','manager','director'])) {
    header("Location: login.php"); exit();
}

// Read filter values from URL (e.g. ?month=2024-06&status=approved)
$filter_month  = isset($_GET['month'])  ? mysqli_real_escape_string($conn, $_GET['month'])  : '';
$filter_status = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Build WHERE clause based on filters
$where = "WHERE 1=1"; // "1=1" is always true — safe base for adding more conditions
if ($filter_month  != '') $where .= " AND p.pay_month = '$filter_month'";
if ($filter_status != '') $where .= " AND p.status = '$filter_status'";

// All payments (filtered)
$payments = mysqli_query($conn, "SELECT p.*, u.full_name AS student_name, b.batch_name, s.name AS subject_name FROM payments p JOIN users u ON p.student_id=u.id JOIN batches b ON p.batch_id=b.id JOIN subjects s ON b.subject_id=s.id $where ORDER BY p.pay_month DESC, p.id DESC");

// Revenue grouped by subject
$by_subject = mysqli_query($conn, "SELECT s.name AS subject_name, SUM(p.amount) AS total FROM payments p JOIN batches b ON p.batch_id=b.id JOIN subjects s ON b.subject_id=s.id WHERE p.status='approved' GROUP BY s.name ORDER BY total DESC");

// Revenue grouped by month (last 12 months)
$by_month = mysqli_query($conn, "SELECT pay_month, SUM(amount) AS total, COUNT(*) AS count FROM payments WHERE status='approved' GROUP BY pay_month ORDER BY pay_month DESC LIMIT 12");

// All available months for the filter dropdown
$months = mysqli_query($conn, "SELECT DISTINCT pay_month FROM payments ORDER BY pay_month DESC");

// Overall totals
$row_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(amount) AS t, COUNT(*) AS c FROM payments WHERE status='approved'"));
$row_pend  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS c, SUM(amount) AS t FROM payments WHERE status='pending'"));

$grand_total = $row_total['t'] ?? 0;
$pend_amount = $row_pend['t']  ?? 0;

$page_title = "Financial Report"; $css_path = "../../frontend/assets/css/style.css"; $root_path = "../../"; $active_page = "";
include '../../frontend/assets/header.php';
?>

<div class="section" style="max-width:900px; margin:0 auto;">

    <!-- Header Row -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:24px;">
        <div>
            <h1 style="color:#1a3a5c; font-size:1.6rem; font-weight:800;">💰 Financial Report</h1>
            <p style="color:#64748b; font-size:0.88rem;">Activate Academy | Generated on <?php echo date('d M Y, h:i A'); ?></p>
        </div>
        <button onclick="window.print();" class="btn btn-primary">🖨️ Print / Save as PDF</button>
    </div>

    <!-- Summary Stats -->
    <div class="stats-grid" style="margin-bottom:28px;">
        <div class="stat-card green"> <div class="stat-number">LKR <?php echo number_format($grand_total / 1000, 1); ?>K</div><div class="stat-label">Total Revenue Collected</div></div>
        <div class="stat-card">       <div class="stat-number"><?php echo $row_total['c']; ?></div><div class="stat-label">Approved Payments</div></div>
        <div class="stat-card yellow"><div class="stat-number"><?php echo $row_pend['c']; ?></div><div class="stat-label">Pending Payments</div></div>
        <div class="stat-card orange"><div class="stat-number">LKR <?php echo number_format($pend_amount); ?></div><div class="stat-label">Pending Amount</div></div>
    </div>

    <!-- Filter Form (hidden when printing) -->
    <div class="card card-accent no-print" style="margin-bottom:24px;">
        <h3 style="font-size:1rem; margin-bottom:12px; color:#1a3a5c;">🔍 Filter Payments</h3>
        <form method="GET" action="report_financial.php" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
            <div class="form-group" style="margin-bottom:0; min-width:160px;">
                <label>Month</label>
                <select name="month">
                    <option value="">All Months</option>
                    <?php while ($m = mysqli_fetch_assoc($months)): ?>
                        <option value="<?php echo $m['pay_month']; ?>" <?php if ($filter_month == $m['pay_month']) echo 'selected'; ?>>
                            <?php echo $m['pay_month']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:0; min-width:140px;">
                <label>Status</label>
                <select name="status">
                    <option value="">All Statuses</option>
                    <?php foreach (['pending','approved','rejected'] as $st): ?>
                        <option value="<?php echo $st; ?>" <?php if ($filter_status == $st) echo 'selected'; ?>><?php echo ucfirst($st); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <button type="submit" class="btn btn-primary">Apply Filter</button>
                <a href="report_financial.php" class="btn btn-outline" style="color:#1a3a5c; border-color:#1a3a5c; margin-left:8px;">Clear</a>
            </div>
        </form>
    </div>

    <!-- Payment Records Table -->
    <div class="panel">
        <div class="panel-title">📋 Payment Records</div>
        <div class="table-wrapper">
            <table>
                <thead><tr><th>Student</th><th>Subject</th><th>Batch</th><th>Month</th><th>Amount</th><th>Receipt No.</th><th>Status</th></tr></thead>
                <tbody>
                <?php
                $total_filtered = 0;
                $has_rows       = false;
                while ($p = mysqli_fetch_assoc($payments)):
                    $has_rows = true;
                    if ($p['status'] == 'approved') $total_filtered += $p['amount'];
                    $pb = $p['status'] == 'approved' ? 'badge-green' : ($p['status'] == 'rejected' ? 'badge-red' : 'badge-yellow');
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p['student_name']); ?></td>
                        <td><?php echo htmlspecialchars($p['subject_name']); ?></td>
                        <td><?php echo htmlspecialchars($p['batch_name']); ?></td>
                        <td><?php echo $p['pay_month']; ?></td>
                        <td>LKR <?php echo number_format($p['amount'], 2); ?></td>
                        <td><?php echo htmlspecialchars($p['receipt_no']); ?></td>
                        <td><span class="badge <?php echo $pb; ?>"><?php echo ucfirst($p['status']); ?></span></td>
                    </tr>
                <?php endwhile;
                if (!$has_rows): ?>
                    <tr><td colspan="7" style="text-align:center; color:#64748b;">No records match the filter.</td></tr>
                <?php endif;
                // Show filtered total row if filters applied
                if ($has_rows && ($filter_month || $filter_status)): ?>
                    <tr style="background:#f0fdf4; font-weight:700;">
                        <td colspan="4" style="text-align:right;">Filtered Total (Approved):</td>
                        <td>LKR <?php echo number_format($total_filtered, 2); ?></td>
                        <td colspan="2"></td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Revenue Breakdown Tables (two columns) -->
    <div style="display:grid; grid-template-columns:1fr 1fr; gap:24px; margin-top:24px;" class="report-cols">
        <!-- Revenue by Subject -->
        <div class="panel">
            <div class="panel-title">📚 Revenue by Subject</div>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Subject</th><th>Revenue (LKR)</th></tr></thead>
                    <tbody>
                    <?php while ($bs = mysqli_fetch_assoc($by_subject)): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($bs['subject_name']); ?></td>
                            <td style="font-weight:600;"><?php echo number_format($bs['total'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Revenue by Month -->
        <div class="panel">
            <div class="panel-title">📅 Revenue by Month</div>
            <div class="table-wrapper">
                <table>
                    <thead><tr><th>Month</th><th>Payments</th><th>Revenue (LKR)</th></tr></thead>
                    <tbody>
                    <?php while ($bm = mysqli_fetch_assoc($by_month)): ?>
                        <tr>
                            <td><?php echo $bm['pay_month']; ?></td>
                            <td><?php echo $bm['count']; ?></td>
                            <td style="font-weight:600;"><?php echo number_format($bm['total'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="no-print" style="margin-top:20px;">
        <a href="dashboard.php" style="color:#2563eb; font-size:0.88rem;">← Back to Dashboard</a>
    </div>
</div>

<!-- Print styles: hide nav, filter form, and buttons when printing -->
<style>
@media print { nav, footer, .no-print { display:none !important; } body { background:white !important; } .panel { box-shadow:none !important; border:1px solid #e2e8f0; } }
@media (max-width:768px) { .report-cols { grid-template-columns:1fr !important; } }
</style>

<?php mysqli_close($conn); include '../../frontend/assets/footer.php'; ?>
