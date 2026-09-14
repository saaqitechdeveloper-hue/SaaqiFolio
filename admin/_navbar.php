<?php
// Expects $activeNav to be set by the including page ('dashboard' | 'users' | 'payments').
?>
<div class="admin-topbar">
  <div class="brand-row">
    <div class="brand-mark"></div>
    <div class="brand-name">Folivo <span style="color:var(--text-muted);font-weight:400;font-size:13px;">Admin</span></div>
  </div>
  <nav class="admin-nav">
    <a href="dashboard.php" class="<?php echo $activeNav === 'dashboard' ? 'active' : ''; ?>">Dashboard</a>
    <a href="users.php" class="<?php echo $activeNav === 'users' ? 'active' : ''; ?>">Users</a>
    <a href="payments.php" class="<?php echo $activeNav === 'payments' ? 'active' : ''; ?>">Payments</a>
  </nav>
  <div style="display:flex;align-items:center;gap:14px;">
    <span class="admin-name">👤 <?php echo e($_SESSION['admin_name'] ?? 'Admin'); ?></span>
    <a href="logout.php" class="btn btn-ghost" style="width:auto;padding:7px 14px;font-size:13px;text-decoration:none;">Logout</a>
  </div>
</div>
