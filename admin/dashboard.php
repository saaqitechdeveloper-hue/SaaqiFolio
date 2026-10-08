<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

/* ── User stats ── */
$totalUsers    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM users"))['cnt'];
$proUsers      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM users WHERE is_subscribed=1"))['cnt'];
$trialUsers    = $totalUsers - $proUsers;
$newThisMonth  = mysqli_fetch_assoc(mysqli_query($conn,
    "SELECT COUNT(*) AS cnt FROM users WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())"))['cnt'];

/* ── Revenue stats ── */
function revenue_sum($conn, $where) {
    $res = mysqli_query($conn, "SELECT COALESCE(SUM(amount),0) AS total FROM payments WHERE status='Completed' AND $where");
    return (float) mysqli_fetch_assoc($res)['total'];
}
$revenueToday   = revenue_sum($conn, "DATE(created_at)=CURDATE()");
$revenueWeek    = revenue_sum($conn, "YEARWEEK(created_at,1)=YEARWEEK(CURDATE(),1)");
$revenueMonth   = revenue_sum($conn, "MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())");
$revenueYear    = revenue_sum($conn, "YEAR(created_at)=YEAR(CURDATE())");
$revenueAllTime = revenue_sum($conn, "1=1");

$totalImages    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM portfolio_images"))['cnt'];

/* ── Recent data ── */
$recentUsers    = mysqli_query($conn, "SELECT id,name,email,is_subscribed,created_at FROM users ORDER BY id DESC LIMIT 8");
$recentPayments = mysqli_query($conn, "SELECT p.*,u.name AS user_name FROM payments p JOIN users u ON u.id=p.user_id ORDER BY p.id DESC LIMIT 8");

$activeNav = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — SaaqiFolio</title>
<?php
$ogTitle = 'Admin Dashboard — SaaqiFolio';
$ogDescription = 'Super Admin control panel for managing users, portfolios, and subscriptions on SaaqiFolio.';
include __DIR__ . '/../includes/og_meta.php';
?>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="alternate icon" type="image/png" href="../assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
<?php include __DIR__ . '/../includes/theme_head.php'; ?>
<script src="../assets/js/ui.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/ui.js'); ?>"></script>
</head>
<body>

<div class="admin-shell">
  <?php include __DIR__ . '/_navbar.php'; ?>

  <div class="admin-main">

    <!-- Page Header -->
    <div class="page-head">
      <div class="page-head-row">
        <div>
          <div class="page-title">
            <span class="grad-text">Admin</span> Dashboard
          </div>
          <div class="page-desc">Platform overview — users, subscriptions, and revenue.</div>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
          <?php include __DIR__ . '/../includes/theme_switcher.php'; ?>
          <a href="users" class="btn btn-ghost btn-sm" style="width:auto;text-decoration:none;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            Manage Users
          </a>
          <a href="payments" class="btn btn-ghost btn-sm" style="width:auto;text-decoration:none;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            Payments
          </a>
        </div>
      </div>
    </div>

    <!-- User Stats -->
    <div class="section-label">Users Overview</div>
    <div class="admin-stats-grid">
      <div class="admin-stat-card">
        <div class="stat-label">Total Signups</div>
        <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
        <div class="stat-sub" style="margin-top:8px;font-size:12px;color:var(--text-faint);">All time</div>
      </div>
      <div class="admin-stat-card accent">
        <div class="stat-label">Pro Subscribers</div>
        <div class="stat-value"><?php echo number_format($proUsers); ?></div>
        <div class="stat-sub" style="margin-top:8px;">
          <span class="pill pill-pro" style="font-size:10px;"><?php echo $totalUsers > 0 ? round($proUsers/$totalUsers*100) : 0; ?>% of users</span>
        </div>
      </div>
      <div class="admin-stat-card">
        <div class="stat-label">Trial Users</div>
        <div class="stat-value"><?php echo number_format($trialUsers); ?></div>
      </div>
      <div class="admin-stat-card success">
        <div class="stat-label">New This Month</div>
        <div class="stat-value"><?php echo number_format($newThisMonth); ?></div>
      </div>
      <div class="admin-stat-card">
        <div class="stat-label">Portfolio Images</div>
        <div class="stat-value"><?php echo number_format($totalImages); ?></div>
      </div>
    </div>

    <!-- Revenue Stats -->
    <div class="section-label">Revenue (Completed Payments)</div>
    <div class="admin-stats-grid">
      <div class="admin-stat-card">
        <div class="stat-label">Today</div>
        <div class="stat-value">Rs. <?php echo number_format($revenueToday); ?></div>
      </div>
      <div class="admin-stat-card">
        <div class="stat-label">This Week</div>
        <div class="stat-value">Rs. <?php echo number_format($revenueWeek); ?></div>
      </div>
      <div class="admin-stat-card accent">
        <div class="stat-label">This Month</div>
        <div class="stat-value">Rs. <?php echo number_format($revenueMonth); ?></div>
      </div>
      <div class="admin-stat-card">
        <div class="stat-label">This Year</div>
        <div class="stat-value">Rs. <?php echo number_format($revenueYear); ?></div>
      </div>
      <div class="admin-stat-card success">
        <div class="stat-label">All Time</div>
        <div class="stat-value">Rs. <?php echo number_format($revenueAllTime); ?></div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="section-label">Quick Actions</div>
    <div class="quick-actions" style="margin-bottom:28px;">
      <a href="users" class="quick-action-card" style="text-decoration:none;">
        <div class="qa-icon" style="background:var(--accent-soft);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div>
          <div class="qa-title">All Users</div>
          <div class="qa-desc"><?php echo $totalUsers; ?> accounts registered</div>
        </div>
      </a>
      <a href="payments" class="quick-action-card" style="text-decoration:none;">
        <div class="qa-icon" style="background:rgba(61,220,151,0.12);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--success)" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        </div>
        <div>
          <div class="qa-title">Payments</div>
          <div class="qa-desc">Rs. <?php echo number_format($revenueAllTime); ?> earned total</div>
        </div>
      </a>
      <a href="users?plan=Pro" class="quick-action-card" style="text-decoration:none;">
        <div class="qa-icon" style="background:rgba(255,107,74,0.1);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent-2)" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
        </div>
        <div>
          <div class="qa-title">Pro Users</div>
          <div class="qa-desc"><?php echo $proUsers; ?> active subscriptions</div>
        </div>
      </a>
      <a href="tutorial_video" class="quick-action-card" style="text-decoration:none;">
        <div class="qa-icon" style="background:rgba(124,92,252,0.15);">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--accent-purple-light)" stroke-width="2"><polygon points="5 3 19 12 5 21 5 3"/></svg>
        </div>
        <div>
          <div class="qa-title">Tutorial Video</div>
          <div class="qa-desc">Configure &amp; preview popup video</div>
        </div>
      </a>
    </div>

    <!-- Recent Activity -->
    <div class="admin-recent-grid">

      <!-- Recent Signups -->
      <div>
        <div class="section-label">Recent Signups</div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Plan</th>
                <th>Joined</th>
              </tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($recentUsers) === 0): ?>
                <tr><td colspan="3" style="text-align:center;padding:28px;color:var(--text-faint);">No users yet.</td></tr>
              <?php else: ?>
                <?php while ($u = mysqli_fetch_assoc($recentUsers)): ?>
                  <tr>
                    <td>
                      <a href="edit_user?id=<?php echo $u['id']; ?>" style="color:var(--text);text-decoration:none;font-weight:600;">
                        <?php echo e($u['name']); ?>
                      </a>
                    </td>
                    <td><span class="pill <?php echo $u['is_subscribed'] ? 'pill-pro' : 'pill-trial'; ?>"><?php echo $u['is_subscribed'] ? 'Pro' : 'Trial'; ?></span></td>
                    <td style="color:var(--text-muted);font-size:12.5px;"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                  </tr>
                <?php endwhile; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div style="padding:12px 16px;border-top:1px solid var(--border);">
          <a href="users" style="font-size:12.5px;color:var(--accent);text-decoration:none;font-weight:600;">View all users →</a>
        </div>
      </div>

      <!-- Recent Payments -->
      <div>
        <div class="section-label">Recent Payments</div>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>User</th>
                <th>Gateway</th>
                <th>Amount</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (mysqli_num_rows($recentPayments) === 0): ?>
                <tr><td colspan="4" style="text-align:center;padding:28px;color:var(--text-faint);">No payments yet.</td></tr>
              <?php else: ?>
                <?php while ($p = mysqli_fetch_assoc($recentPayments)): ?>
                  <tr>
                    <td style="font-weight:600;"><?php echo e($p['user_name']); ?></td>
                    <td style="color:var(--text-muted);font-size:12.5px;"><?php echo e($p['gateway']); ?></td>
                    <td style="font-family:var(--font-mono);font-size:13px;">Rs. <?php echo number_format($p['amount']); ?></td>
                    <td><span class="pill <?php echo $p['status'] === 'Completed' ? 'pill-active' : 'pill-trial'; ?>"><?php echo e($p['status']); ?></span></td>
                  </tr>
                <?php endwhile; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div style="padding:12px 16px;border-top:1px solid var(--border);">
          <a href="payments" style="font-size:12.5px;color:var(--accent);text-decoration:none;font-weight:600;">View all payments →</a>
        </div>
      </div>

    </div>

  </div>
</div>

</body>
</html>
