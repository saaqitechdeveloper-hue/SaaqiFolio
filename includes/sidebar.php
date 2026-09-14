<?php
// Expects $user (array) and $activeNav ('profile' | 'portfolio') to be set by the including page.
$imageCount = (int) ($imageCount ?? 0);
$pct = min(100, round(($imageCount / FREE_IMAGE_LIMIT) * 100));
?>
<div class="sidebar">
  <div class="brand-row">
    <div class="brand-mark"></div>
    <div class="brand-name"><?php echo e(SITE_NAME); ?></div>
  </div>
  <div class="side-nav">
    <a href="profile.php" class="nav-btn <?php echo $activeNav === 'profile' ? 'active' : ''; ?>" style="text-decoration:none;display:flex;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4 4-6 8-6s8 2 8 6"/></svg>
      Profile
    </a>
    <a href="portfolio.php" class="nav-btn <?php echo $activeNav === 'portfolio' ? 'active' : ''; ?>" style="text-decoration:none;display:flex;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/></svg>
      Portfolio
    </a>
  </div>
  <div class="side-foot">
    <?php if ($user['is_subscribed']): ?>
      <div class="plan-badge pro">👑 Pro Member</div>
    <?php else: ?>
      <div class="plan-badge">
        <div class="trial-label">TRIAL · <?php echo $imageCount; ?>/<?php echo FREE_IMAGE_LIMIT; ?> IMAGES</div>
        <div class="meter"><div class="meter-fill" style="width:<?php echo $pct; ?>%"></div></div>
        <div class="trial-label" style="margin-top:8px;">PDF · <?php echo (int) $user['pdf_downloads_count']; ?>/<?php echo FREE_PDF_LIMIT; ?> USED</div>
        <a href="upgrade.php" class="upgrade-link" style="text-decoration:none;">Upgrade to Pro →</a>
      </div>
    <?php endif; ?>
    <a href="../p.php?slug=<?php echo urlencode($user['public_slug']); ?>" target="_blank" class="nav-btn" style="text-decoration:none;display:flex;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="M8.6 10.6l6.8-3.8M8.6 13.4l6.8 3.8"/></svg>
      View Public Page
    </a>
    <a href="../auth/logout.php" class="nav-btn" style="text-decoration:none;display:flex;">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
      Logout
    </a>
  </div>
</div>
