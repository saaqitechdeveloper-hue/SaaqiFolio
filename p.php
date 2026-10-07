<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$slug = clean($conn, $_GET['slug'] ?? '');

// Canonical redirect from old p.php?slug=xxx or p?slug=xxx to clean /p/xxx
if (!empty($slug) && (strpos($_SERVER['REQUEST_URI'] ?? '', 'slug=') !== false || strpos($_SERVER['REQUEST_URI'] ?? '', 'p.php') !== false)) {
    $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
    header("Location: " . ($base ? $base : '') . "/p/" . urlencode($slug), true, 301);
    exit;
}

// If slug is empty and user is logged in, redirect to user's portfolio
if (empty($slug) && isset($_SESSION['user_id'])) {
    $uStmt = mysqli_prepare($conn, "SELECT public_slug FROM users WHERE id = ?");
    $uid = (int) $_SESSION['user_id'];
    mysqli_stmt_bind_param($uStmt, 'i', $uid);
    mysqli_stmt_execute($uStmt);
    $uRes = mysqli_stmt_get_result($uStmt)->fetch_assoc();
    if (!empty($uRes['public_slug'])) {
        header("Location: p/" . urlencode($uRes['public_slug']));
        exit;
    }
}

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE public_slug = ?");
mysqli_stmt_bind_param($stmt, 's', $slug);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

$isOwner = isset($_SESSION['user_id']) && $user && (int) $_SESSION['user_id'] === (int) $user['id'];

if ($user) {
    $imgRes = mysqli_query($conn, "SELECT * FROM portfolio_images WHERE user_id=" . (int) $user['id'] . " ORDER BY sort_order ASC, id DESC");
    $allImages = [];
    while ($row = mysqli_fetch_assoc($imgRes)) $allImages[] = $row;

    $bannerImages = array_slice($allImages, 0, 5);
    $skills = skills_to_array($user['skills']);
    $posX = $user['banner_pos_x'] ?? 50;
    $posY = $user['banner_pos_y'] ?? 50;
    $zoom = $user['banner_zoom'] ?? 100;
    $categories = get_categories();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $user ? e($user['name']) . ' — Portfolio' : 'Portfolio Not Found'; ?> | SaaqiFolio</title>
<?php
if ($user) {
    $ogTitle = $user['name'] . ' — ' . ($user['profession'] ?: 'Creative Portfolio') . ' | SaaqiFolio';
    if (!empty($user['bio'])) {
        $cleanBio = trim(strip_tags($user['bio']));
        $ogDescription = mb_substr($cleanBio, 0, 160) . (mb_strlen($cleanBio) > 160 ? '...' : '');
    } else {
        $ogDescription = 'Explore ' . $user['name'] . '\'s creative portfolio, design projects, and curated works on SaaqiFolio.';
    }
}
include __DIR__ . '/includes/og_meta.php';
$assetBase = get_site_root_url();
?>
<link rel="icon" type="image/svg+xml" href="<?php echo $assetBase; ?>/assets/favicon.svg">
<link rel="alternate icon" type="image/png" href="<?php echo $assetBase; ?>/assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo $assetBase; ?>/assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/assets/css/style.css'); ?>">
<script src="<?php echo $assetBase; ?>/assets/js/ui.js?v=<?php echo filemtime(__DIR__ . '/assets/js/ui.js'); ?>" defer></script>
</head>
<body>

<?php if (!$user): ?>

  <div class="public-page" style="display:flex;flex-direction:column;min-height:100vh;justify-content:space-between;">
    <div class="public-topbar">
      <div class="brand-row" style="margin:0;">
        <div class="brand-mark-glow">
          <div class="brand-mark">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
          </div>
        </div>
        <div class="brand-text-col">
          <span class="brand-name">SaaqiFolio</span>
        </div>
      </div>
      <div>
        <a href="<?php echo $assetBase; ?>/auth/signup" class="btn btn-primary" style="width:auto;padding:8px 18px;font-size:13px;text-decoration:none;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
          <span>Get Started</span>
        </a>
      </div>
    </div>

    <div style="text-align:center;padding:60px 24px;max-width:520px;margin:auto;">
      <div style="font-size:72px;margin-bottom:16px;">
        <svg width="72" height="72" viewBox="0 0 24 24" fill="none" stroke="var(--text-faint)" stroke-width="1.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      </div>
      <h2 style="font-family:var(--font-display);margin:0 0 10px;font-size:24px;font-weight:700;">Portfolio Not Found</h2>
      <p class="muted" style="margin:0 0 28px;font-size:15px;line-height:1.6;">This portfolio link does not exist or has been removed.</p>
      <a href="<?php echo $assetBase; ?>/auth/signup" class="btn btn-primary" style="width:auto;display:inline-flex;padding:12px 28px;text-decoration:none;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
        <span>Create Your Portfolio</span>
      </a>
    </div>

    <div class="public-footer">
      Built with <strong>SaaqiFolio</strong> &mdash; <?php echo date('Y'); ?> | Powered by <a href="http://saaqitech.com/" target="_blank" rel="noopener noreferrer">SAAQi Tech</a>
    </div>
  </div>

<?php else: ?>

  <div class="public-page">
    <!-- Top Navigation Bar -->
    <div class="public-topbar">
      <div class="brand-row" style="margin:0;">
        <div class="brand-mark-glow">
          <div class="brand-mark">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
          </div>
        </div>
        <div class="brand-text-col">
          <span class="brand-name">SaaqiFolio</span>
        </div>
      </div>
      <div class="public-topbar-actions">
        <?php if (!empty($allImages)): ?>
          <button type="button" class="btn-pdf" onclick="openPdfExportModal()" title="Export Portfolio as PDF">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/></svg>
            <span>Export Your Portfolio</span>
          </button>
        <?php endif; ?>
        <?php if ($isOwner): ?>
          <a href="<?php echo $assetBase; ?>/dashboard/profile" class="btn btn-ghost" style="width:auto;padding:8px 16px;font-size:13px;text-decoration:none;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            <span>Edit Profile</span>
          </a>
        <?php else: ?>
          <a href="<?php echo $assetBase; ?>/auth/signup" class="btn btn-primary" style="width:auto;padding:8px 18px;font-size:13px;text-decoration:none;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 5v14M5 12h14"/></svg>
            <span>Build Your Portfolio</span>
          </a>
        <?php endif; ?>
      </div>
    </div>

    <div class="public-content">

      <?php if (isset($_GET['limit'])): ?>
        <div class="preview-banner-note" style="border-color:rgba(255,107,74,0.4);background:rgba(255,107,74,0.1);color:#ff9e8a;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          Free PDF export limit reached for this portfolio. Contact the owner or upgrade to Pro to unlock unlimited exports.
        </div>
      <?php endif; ?>

      <?php if ($isOwner): ?>
        <div class="preview-banner-note">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          Preview Mode — This is exactly how your clients will see your portfolio.
        </div>
      <?php endif; ?>

      <!-- Banner -->
      <div class="profile-banner <?php echo ($user['banner'] || $bannerImages) ? 'has-images' : ''; ?>">
        <?php if ($user['banner']): ?>
          <?php 
            $bannerPath = __DIR__ . '/assets/uploads/banners/' . $user['banner'];
            $bSize = @getimagesize($bannerPath);
            $isWide = false;
            if ($bSize && $bSize[0] > 0 && $bSize[1] > 0) {
                $isWide = ($bSize[0] / $bSize[1]) >= 4.4;
            }
            $sizePct = round(115 * ($zoom / 100), 1);
            $bgSize = $isWide ? "auto {$sizePct}%" : "{$sizePct}% auto";
          ?>
          <div class="banner-bg-cover"
               style="background-image:url('<?php echo $assetBase; ?>/assets/uploads/banners/<?php echo e($user['banner']); ?>');background-size:<?php echo $bgSize; ?>;background-position:<?php echo $posX; ?>% <?php echo $posY; ?>%;"></div>
          <div class="banner-overlay"></div>
        <?php elseif ($bannerImages): ?>
          <div class="banner-collage">
            <?php foreach ($bannerImages as $img): ?>
              <img src="<?php echo $assetBase; ?>/assets/uploads/portfolio/<?php echo e($img['filename']); ?>">
            <?php endforeach; ?>
          </div>
          <div class="banner-overlay"></div>
        <?php else: ?>
          <div style="position:absolute;inset:0;background:linear-gradient(135deg,rgba(124,92,252,0.15),rgba(255,107,74,0.08));"></div>
        <?php endif; ?>
      </div>

      <!-- Profile Header -->
      <div class="profile-header-row profile-hero-row">
        <div class="avatar-wrap">
          <div class="profile-avatar-lg" <?php echo $user['avatar'] ? 'style="background:none;"' : ''; ?>>
            <?php if ($user['avatar']): ?>
              <img src="<?php echo $assetBase; ?>/assets/uploads/avatars/<?php echo e($user['avatar']); ?>" class="avatar-img">
            <?php else: ?>
              <span class="avatar-initials"><?php echo e(initials($user['name'])); ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="profile-header-text profile-hero-text">
          <div class="profile-name-lg"><?php echo e($user['name']); ?></div>
          <div class="profile-role-lg"><?php echo e($user['profession']); ?></div>
        </div>
        <div class="profile-actions-row profile-hero-actions">
          <a class="btn btn-primary" style="width:auto;padding:9px 18px;font-size:13px;text-decoration:none;" href="mailto:<?php echo e($user['email']); ?>">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            <span>Contact</span>
          </a>
          <?php if (!empty($allImages)): ?>
            <button type="button" class="btn-pdf" onclick="openPdfExportModal()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/></svg>
              <span>Export Your Portfolio</span>
            </button>
          <?php endif; ?>
        </div>
      </div>

      <!-- Info Grid -->
      <div class="info-grid">
        <div class="info-item">
          <div class="info-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L1 7"/></svg>
          </div>
          <div>
            <div class="info-label">Email</div>
            <div class="info-val"><?php echo e($user['email']); ?></div>
          </div>
        </div>
        <?php if ($user['phone']): ?>
          <div class="info-item">
            <div class="info-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <div>
              <div class="info-label">Phone</div>
              <div class="info-val"><?php echo e($user['phone']); ?></div>
            </div>
          </div>
        <?php endif; ?>
        <?php if ($user['address']): ?>
          <div class="info-item">
            <div class="info-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div>
              <div class="info-label">Location</div>
              <div class="info-val"><?php echo e($user['address']); ?></div>
            </div>
          </div>
        <?php endif; ?>
        <div class="info-item">
          <div class="info-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
          </div>
          <div>
            <div class="info-label">Portfolio Items</div>
            <div class="info-val"><?php echo count($allImages); ?> works</div>
          </div>
        </div>
      </div>

      <!-- Bio -->
      <?php if ($user['bio']): ?>
        <div class="about-block">
          <h4>About</h4>
          <div class="profile-bio"><?php echo e($user['bio']); ?></div>
        </div>
      <?php endif; ?>

      <!-- Education & Experience -->
      <?php if ($user['education'] || $user['experience']): ?>
        <div class="about-block">
          <?php if ($user['education']): ?>
            <h4>Education</h4>
            <div class="profile-bio" style="margin-bottom:18px;"><?php echo e($user['education']); ?></div>
          <?php endif; ?>
          <?php if ($user['experience']): ?>
            <h4>Experience</h4>
            <div class="profile-bio"><?php echo e($user['experience']); ?></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <!-- Skills -->
      <?php if ($skills): ?>
        <div class="about-block">
          <h4>Technical Skills</h4>
          <div class="field-chips">
            <?php foreach ($skills as $s): ?>
              <span class="field-chip"><?php echo e($s); ?></span>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>

      <!-- CV Download -->
      <?php if ($user['cv_file']): ?>
        <div class="about-block">
          <h4>CV / Resume</h4>
          <a href="<?php echo $assetBase; ?>/assets/uploads/cv/<?php echo e($user['cv_file']); ?>"
             download="<?php echo e($user['cv_original_name']); ?>"
             class="btn btn-ghost" style="width:auto;display:inline-flex;padding:10px 18px;text-decoration:none;margin-top:4px;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            <?php echo e($user['cv_original_name']); ?>
          </a>
        </div>
      <?php endif; ?>

      <!-- Portfolio -->
      <div class="about-block" style="background:transparent;border:none;backdrop-filter:none;padding:0;">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:18px;">
          <h4 style="margin:0;font-family:var(--font-display);font-weight:700;font-size:18px;color:var(--text);text-transform:none;letter-spacing:normal;">Portfolio</h4>
          <?php if (!empty($allImages)): ?>
            <button type="button" class="btn-pdf" onclick="openPdfExportModal()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="9" y1="12" x2="15" y2="12"/><line x1="9" y1="16" x2="13" y2="16"/></svg>
              <span>Export Your Portfolio</span>
            </button>
          <?php endif; ?>
        </div>
        <?php
        $hasAny = false;
        foreach ($categories as $cat):
          $imgs = array_filter($allImages, fn($i) => $i['category'] === $cat);
          if (empty($imgs)) continue;
          $hasAny = true;
        ?>
          <div class="cat-section">
            <div class="cat-section-header cat-section-head">
              <span class="cat-section-title"><?php echo e($cat); ?></span>
              <span class="cat-section-count"><?php echo count($imgs); ?></span>
            </div>
            <div class="grid">
              <?php foreach ($imgs as $img): ?>
                <div class="card">
                  <div class="thumb-wrap">
                    <img src="<?php echo $assetBase; ?>/assets/uploads/portfolio/<?php echo e($img['filename']); ?>" class="thumb" loading="lazy">
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$hasAny): ?>
          <div class="empty-note">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin:0 auto 10px;display:block;color:var(--text-faint)"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>
            No portfolio works have been uploaded yet.
          </div>
        <?php endif; ?>
      </div>

    </div>

    <div class="public-footer">
      Built with <strong>SaaqiFolio</strong> &mdash; <?php echo date('Y'); ?> | Powered by <a href="http://saaqitech.com/" target="_blank" rel="noopener noreferrer">SAAQi Tech</a>
    </div>
  </div>

<?php endif; ?>

<?php
if ($user && !empty($allImages)) {
    $pdfModalBaseUrl = $assetBase . '/portfolio_pdf?slug=' . urlencode($slug);
    $pdfTotalImages = count($allImages);
    $pdfCategoriesCount = [];
    foreach ($allImages as $img) {
        $c = $img['category'] ?? 'Other';
        $pdfCategoriesCount[$c] = ($pdfCategoriesCount[$c] ?? 0) + 1;
    }
    include __DIR__ . '/includes/pdf_export_modal.php';
}
?>
<?php include __DIR__ . '/includes/tutorial_video_modal.php'; ?>
</body>
</html>
