<?php
// Expects $activeNav to be set by the including page ('dashboard' | 'users' | 'payments').
$adminName = $_SESSION['admin_name'] ?? 'Super Admin';
?>
<!-- Mobile Top Navigation Header for Admin -->
<header class="mobile-topbar">
  <div class="mobile-brand">
    <div class="brand-mark-glow">
      <div class="brand-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
      </div>
    </div>
    <span class="brand-name">SaaqiFolio</span>
    <span class="badge-studio" style="background:var(--grad-primary);color:#fff;">ADMIN</span>
  </div>
  <div class="mobile-actions">
    <button type="button" class="mobile-icon-btn" id="mobileMenuBtn" aria-label="Toggle menu" onclick="document.body.classList.toggle('mobile-menu-open');">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
    </button>
  </div>
</header>

<!-- Mobile Overlay Backdrop -->
<div class="mobile-drawer-backdrop" onclick="document.body.classList.remove('mobile-menu-open');"></div>

<!-- Desktop Luxury Admin Sidebar -->
<aside class="sidebar admin-sidebar" id="appSidebar">
  <div class="brand-row">
    <div class="brand-mark-glow">
      <div class="brand-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
      </div>
    </div>
    <div class="brand-text-col">
      <div class="brand-name-wrap">
        <span class="brand-name">SaaqiFolio</span>
        <span class="badge-studio" style="background:var(--grad-primary);color:#fff;font-weight:700;">SUPER ADMIN</span>
      </div>
      <span class="brand-sub">Platform Management</span>
    </div>
  </div>

  <div class="side-nav">
    <div class="nav-group-label">ADMINISTRATION</div>

    <a href="dashboard.php" class="nav-btn <?php echo ($activeNav ?? '') === 'dashboard' ? 'active' : ''; ?>">
      <span class="nav-icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><rect width="7" height="7" x="3" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="14" rx="1.5"/><rect width="7" height="7" x="3" y="14" rx="1.5"/></svg>
      </span>
      <span class="nav-text">Dashboard</span>
      <?php if (($activeNav ?? '') === 'dashboard'): ?><span class="active-dot"></span><?php endif; ?>
    </a>

    <a href="users.php" class="nav-btn <?php echo ($activeNav ?? '') === 'users' ? 'active' : ''; ?>">
      <span class="nav-icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
      </span>
      <span class="nav-text">Users &amp; Creators</span>
      <?php if (($activeNav ?? '') === 'users'): ?><span class="active-dot"></span><?php endif; ?>
    </a>

    <a href="payments.php" class="nav-btn <?php echo ($activeNav ?? '') === 'payments' ? 'active' : ''; ?>">
      <span class="nav-icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" x2="10"/><path d="M7 15h.01"/><path d="M11 15h2"/></svg>
      </span>
      <span class="nav-text">Payments &amp; Revenue</span>
      <?php if (($activeNav ?? '') === 'payments'): ?><span class="active-dot"></span><?php endif; ?>
    </a>

    <div class="nav-group-label" style="margin-top:20px;">QUICK ACCESS</div>

    <a href="../dashboard/profile.php" target="_blank" class="nav-btn live-link">
      <span class="nav-icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
      </span>
      <span class="nav-text">Creator Dashboard</span>
      <svg class="ext-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
    </a>
  </div>

  <div class="side-foot">
    <div class="admin-badge-card" style="margin-bottom:12px;background:rgba(139,92,246,0.08);border:1px solid rgba(139,92,246,0.22);border-radius:12px;padding:12px 14px;">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px;">
        <span style="width:7px;height:7px;border-radius:50%;background:#10b981;box-shadow:0 0 8px #10b981;"></span>
        <span style="font-size:11.5px;font-weight:600;color:var(--text-main);letter-spacing:0.02em;">SYSTEM ONLINE</span>
      </div>
      <div style="font-size:11px;color:var(--text-muted);">Root access privileges active.</div>
    </div>

    <!-- Admin Profile Strip -->
    <div class="side-user-card">
      <div class="side-user-avatar" style="background:var(--grad-primary);color:#fff;display:flex;align-items:center;justify-content:center;border-radius:10px;width:38px;height:38px;flex-shrink:0;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </div>
      <div class="side-user-info" style="min-width:0;flex:1;">
        <div class="side-user-name" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis;font-size:13px;font-weight:600;"><?php echo e($adminName); ?></div>
        <div class="side-user-sub" style="font-size:11px;color:var(--accent-purple-light);">Super Admin</div>
      </div>
      <a href="logout.php" class="side-logout-btn" title="Sign out" style="padding:6px;border-radius:8px;color:var(--text-muted);display:flex;align-items:center;justify-content:center;text-decoration:none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="17" height="17"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </a>
    </div>
  </div>
</aside>
