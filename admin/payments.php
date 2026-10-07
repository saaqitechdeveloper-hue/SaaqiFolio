<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$statusFilter = $_GET['status'] ?? 'All';

$sql = "SELECT p.*, u.name AS user_name, u.email AS user_email FROM payments p JOIN users u ON u.id = p.user_id";
if (in_array($statusFilter, ['Completed', 'Pending', 'Failed'], true)) {
    $sql .= " WHERE p.status = '" . mysqli_real_escape_string($conn, $statusFilter) . "'";
}
$sql .= " ORDER BY p.id DESC";
$payments = mysqli_query($conn, $sql);

$totalCompleted = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE status='Completed'"))['total'];
$countCompleted = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM payments WHERE status='Completed'"))['cnt'];
$countFailed = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM payments WHERE status='Failed'"))['cnt'];
$countPending = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM payments WHERE status='Pending'"))['cnt'];

$activeNav = 'payments';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payments - SaaqiFolio Admin</title>
<?php
$ogTitle = 'Payments & Revenue — SaaqiFolio Admin';
$ogDescription = 'View and audit payment transactions and Pro subscriptions on SaaqiFolio.';
include __DIR__ . '/../includes/og_meta.php';
?>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="alternate icon" type="image/png" href="../assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
<script src="../assets/js/ui.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/ui.js'); ?>"></script>
</head>
<body>

<div class="admin-shell">
  <?php include __DIR__ . '/_navbar.php'; ?>

  <div class="admin-main">
    <div class="page-head">
      <div class="page-title">Payments</div>
      <div class="page-desc">Every JazzCash / Easypaisa transaction attempt across all users.</div>
    </div>

    <div class="admin-stats-grid">
      <div class="admin-stat-card success">
        <div class="stat-label">Total Revenue (Completed)</div>
        <div class="stat-value">Rs. <?php echo number_format($totalCompleted); ?></div>
      </div>
      <div class="admin-stat-card">
        <div class="stat-label">Completed Payments</div>
        <div class="stat-value"><?php echo $countCompleted; ?></div>
      </div>
      <div class="admin-stat-card">
        <div class="stat-label">Pending</div>
        <div class="stat-value"><?php echo $countPending; ?></div>
      </div>
      <div class="admin-stat-card">
        <div class="stat-label">Failed</div>
        <div class="stat-value"><?php echo $countFailed; ?></div>
      </div>
    </div>

    <form method="GET" class="admin-search-row" style="max-width:260px;">
      <div class="form-select" data-autosubmit="1" style="width:100%;">
        <input type="hidden" name="status" value="<?php echo e($statusFilter); ?>">
        <button type="button" class="form-select-trigger" style="height:42px;">
          <span><?php echo $statusFilter === 'All' ? 'All Statuses' : e($statusFilter); ?></span>
          <span class="fs-caret"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 6 8 10 12 6"/></svg></span>
        </button>
        <ul class="form-select-list" hidden>
          <li class="form-select-item <?php echo $statusFilter === 'All' ? 'active' : ''; ?>" data-value="All">All Statuses</li>
          <li class="form-select-item <?php echo $statusFilter === 'Completed' ? 'active' : ''; ?>" data-value="Completed">Completed</li>
          <li class="form-select-item <?php echo $statusFilter === 'Pending' ? 'active' : ''; ?>" data-value="Pending">Pending</li>
          <li class="form-select-item <?php echo $statusFilter === 'Failed' ? 'active' : ''; ?>" data-value="Failed">Failed</li>
        </ul>
      </div>
    </form>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr><th>Date</th><th>User</th><th>Gateway</th><th>Amount</th><th>Status</th><th>Txn Ref</th></tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($payments) === 0): ?>
            <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--text-faint);">No payments found.</td></tr>
          <?php else: ?>
            <?php while ($p = mysqli_fetch_assoc($payments)): ?>
              <tr>
                <td style="color:var(--text-muted);"><?php echo date('d M Y, h:i A', strtotime($p['created_at'])); ?></td>
                <td>
                  <a href="edit_user?id=<?php echo $p['user_id']; ?>" style="color:var(--text);text-decoration:none;font-weight:600;"><?php echo e($p['user_name']); ?></a>
                  <div style="color:var(--text-faint);font-size:11.5px;"><?php echo e($p['user_email']); ?></div>
                </td>
                <td><?php echo e($p['gateway']); ?></td>
                <td>Rs. <?php echo number_format($p['amount']); ?></td>
                <td><span class="pill <?php echo $p['status'] === 'Completed' ? 'pill-active' : 'pill-trial'; ?>"><?php echo e($p['status']); ?></span></td>
                <td style="font-family:var(--font-mono);font-size:11.5px;color:var(--text-faint);"><?php echo e($p['txn_ref']); ?></td>
              </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

</body>
</html>
