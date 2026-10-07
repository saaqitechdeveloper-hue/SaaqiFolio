<?php
// Expects $user (array) and $activeNav ('profile' | 'portfolio' | 'upgrade') to be set by the including page.
$imageCount = (int) ($imageCount ?? 0);
$freeImgLimit = defined('FREE_IMAGE_LIMIT') ? FREE_IMAGE_LIMIT : 20;
$freePdfLimit = defined('FREE_PDF_LIMIT') ? FREE_PDF_LIMIT : 3;
$pct = min(100, round(($imageCount / $freeImgLimit) * 100));
$isSubscribed = !empty($user['is_subscribed']);
$pdfUsed = (int) ($user['pdf_downloads_count'] ?? 0);
$pdfPct = min(100, round(($pdfUsed / $freePdfLimit) * 100));
?>
<?php if (!empty($_SESSION['admin_mode'])): ?>
<!-- Admin Mode Impersonation Topbar -->
<div class="admin-mode-topbar">
  <div class="admin-mode-container">
    <div class="admin-mode-info">
      <span class="admin-mode-glow-dot"></span>
      <span class="admin-mode-badge">⚡ ADMIN MODE</span>
      <span class="admin-mode-label">Viewing account: <strong><?php echo e($user['name'] ?? 'User'); ?></strong> (<?php echo e($user['email'] ?? ''); ?>)</span>
    </div>
    <div class="admin-mode-actions">
      <a href="../admin/users" class="admin-mode-panel-link">Admin Panel</a>
      <a href="../admin/exit_user_mode" class="admin-mode-exit-btn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span>Exit Admin Mode</span>
      </a>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Mobile Top Navigation Header -->
<header class="mobile-topbar">
  <div class="mobile-brand">
    <div class="brand-mark-glow">
      <div class="brand-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
      </div>
    </div>
    <span class="brand-name"><?php echo e(SITE_NAME); ?></span>
    <span class="badge-studio">STUDIO</span>
  </div>
  <div class="mobile-actions">
    <a href="../p/<?php echo urlencode($user['public_slug'] ?? ''); ?>" target="_blank" class="mobile-icon-btn" title="View Public Page">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
    </a>
    <button type="button" class="mobile-icon-btn" id="mobileMenuBtn" aria-label="Toggle menu" onclick="document.body.classList.toggle('mobile-menu-open');">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
    </button>
  </div>
</header>

<!-- Mobile Overlay Backdrop -->
<div class="mobile-drawer-backdrop" onclick="document.body.classList.remove('mobile-menu-open');"></div>

<!-- Sidebar Component -->
<aside class="sidebar" id="appSidebar">
  <div class="brand-row">
    <div class="brand-mark-glow">
      <div class="brand-mark">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
      </div>
    </div>
    <div class="brand-text-col">
      <div class="brand-name-wrap">
        <span class="brand-name"><?php echo e(SITE_NAME); ?></span>
        <span class="badge-studio">PRO</span>
      </div>
      <span class="brand-sub">Creator Portfolio</span>
    </div>
  </div>

  <div class="side-nav">
    <div class="nav-group-label">OVERVIEW</div>

    <a href="profile" class="nav-btn <?php echo ($activeNav ?? '') === 'profile' ? 'active' : ''; ?>">
      <span class="nav-icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
      </span>
      <span class="nav-text">Profile</span>
      <?php if (($activeNav ?? '') === 'profile'): ?><span class="active-dot"></span><?php endif; ?>
    </a>

    <a href="portfolio" class="nav-btn <?php echo ($activeNav ?? '') === 'portfolio' ? 'active' : ''; ?>">
      <span class="nav-icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><rect width="7" height="7" x="3" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="14" rx="1.5"/><rect width="7" height="7" x="3" y="14" rx="1.5"/></svg>
      </span>
      <span class="nav-text">Portfolio</span>
      <span class="nav-badge-pill"><?php echo $imageCount; ?></span>
    </a>

    <a href="upgrade" class="nav-btn <?php echo ($activeNav ?? '') === 'upgrade' ? 'active' : ''; ?>">
      <span class="nav-icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
      </span>
      <span class="nav-text">Subscription</span>
      <?php if ($isSubscribed): ?>
        <span class="nav-status-tag pro">PRO</span>
      <?php else: ?>
        <span class="nav-status-tag">TRIAL</span>
      <?php endif; ?>
    </a>

    <div class="nav-group-label" style="margin-top:16px;">CLIENT ACCESS</div>

    <a href="../p/<?php echo urlencode($user['public_slug'] ?? ''); ?>" target="_blank" class="nav-btn live-link">
      <span class="nav-icon-wrap">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
      </span>
      <span class="nav-text">View Public Site</span>
      <svg class="ext-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
    </a>
  </div>

  <div class="side-foot">
    <?php if ($isSubscribed): ?>
      <div class="plan-card pro">
        <div class="plan-header">
          <div class="plan-icon pro">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
          </div>
          <div class="plan-meta">
            <div class="plan-title">Pro Member</div>
            <div class="plan-subtitle">Unlimited Storage &amp; PDF</div>
          </div>
        </div>
        <div class="plan-perks-row">
          <span class="perk-tag">Unlimited Works</span>
          <span class="perk-tag">HD PDF</span>
        </div>
      </div>
    <?php else: ?>
      <div class="plan-card trial">
        <div class="plan-header">
          <div class="plan-icon trial">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
          </div>
          <div class="plan-meta">
            <div class="plan-title">Trial Account</div>
            <div class="plan-subtitle"><?php echo $imageCount; ?> of <?php echo $freeImgLimit; ?> uploads used</div>
          </div>
        </div>

        <div class="meter-bar-wrap">
          <div class="meter-bar-info">
            <span>Portfolio Storage</span>
            <span><?php echo $pct; ?>%</span>
          </div>
          <div class="meter"><div class="meter-fill" style="width:<?php echo $pct; ?>%"></div></div>
        </div>

        <div class="meter-bar-wrap" style="margin-top:6px;">
          <div class="meter-bar-info">
            <span>PDF Exports</span>
            <span><?php echo $pdfUsed; ?> / <?php echo $freePdfLimit; ?></span>
          </div>
          <div class="meter"><div class="meter-fill meter-fill-cyan" style="width:<?php echo $pdfPct; ?>%"></div></div>
        </div>

        <a href="upgrade" class="upgrade-btn-shiny">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
          <span>Upgrade to Pro</span>
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="9 18 15 12 9 6"/></svg>
        </a>
      </div>
    <?php endif; ?>

    <!-- User Profile Strip -->
    <div class="side-user-card">
      <div class="side-user-avatar">
        <?php if (!empty($user['avatar'])): ?>
          <img src="../assets/uploads/avatars/<?php echo e($user['avatar']); ?>" alt="User avatar">
        <?php else: ?>
          <span><?php echo e(initials($user['name'] ?? 'User')); ?></span>
        <?php endif; ?>
      </div>
      <div class="side-user-details">
        <div class="side-user-name" title="<?php echo e($user['name'] ?? ''); ?>"><?php echo e($user['name'] ?? 'Creator'); ?></div>
        <div class="side-user-role"><?php echo e($user['profession'] ?? 'Graphic Designer'); ?></div>
      </div>
      <a href="../auth/logout" class="side-logout-btn" title="Sign out" aria-label="Sign out">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      </a>
    </div>
  </div>
</aside>
