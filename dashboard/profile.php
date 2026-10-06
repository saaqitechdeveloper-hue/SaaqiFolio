<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) $_SESSION['user_id'];
$error = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

function get_user($conn, $id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt)->fetch_assoc();
}

$user = get_user($conn, $userId);
if (!$user) { session_destroy(); header('Location: ../auth/login.php'); exit; }

$editing = isset($_GET['edit']);

// ---------- POST: Save profile fields ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_profile'])) {
    $name = clean($conn, $_POST['name']);
    $profession = clean($conn, $_POST['profession']);
    $phone = clean($conn, $_POST['phone']);
    $address = clean($conn, $_POST['address']);
    $bio = clean($conn, $_POST['bio']);
    $education = clean($conn, $_POST['education']);
    $experience = clean($conn, $_POST['experience']);
    $skills = isset($_POST['skills']) ? implode(',', array_map(fn($s) => clean($conn, $s), $_POST['skills'])) : '';

    $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, profession=?, phone=?, address=?, bio=?, education=?, experience=?, skills=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ssssssssi', $name, $profession, $phone, $address, $bio, $education, $experience, $skills, $userId);
    mysqli_stmt_execute($stmt);

    $_SESSION['flash_success'] = 'Profile updated successfully.';
    header('Location: profile.php');
    exit;
}

// ---------- POST: Upload avatar ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_avatar'])) {
    $filename = handle_image_upload('avatar', __DIR__ . '/../assets/uploads/avatars');
    if ($filename) {
        if ($user['avatar'] && file_exists(__DIR__ . '/../assets/uploads/avatars/' . $user['avatar'])) {
            @unlink(__DIR__ . '/../assets/uploads/avatars/' . $user['avatar']);
        }
        $stmt = mysqli_prepare($conn, "UPDATE users SET avatar=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'si', $filename, $userId);
        mysqli_stmt_execute($stmt);
        $_SESSION['flash_success'] = 'Avatar updated successfully.';
    } elseif ($filename === false) {
        $_SESSION['flash_error'] = 'Avatar upload failed. Use JPG/PNG/WEBP under 5MB.';
    }
    header('Location: profile.php');
    exit;
}

// ---------- POST: Upload banner ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_banner'])) {
    $filename = handle_image_upload('banner', __DIR__ . '/../assets/uploads/banners');
    if ($filename) {
        if ($user['banner'] && file_exists(__DIR__ . '/../assets/uploads/banners/' . $user['banner'])) {
            @unlink(__DIR__ . '/../assets/uploads/banners/' . $user['banner']);
        }
        $stmt = mysqli_prepare($conn, "UPDATE users SET banner=?, banner_pos_x=50, banner_pos_y=50, banner_zoom=100 WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'si', $filename, $userId);
        mysqli_stmt_execute($stmt);
        $_SESSION['flash_success'] = 'Banner updated successfully.';
    } elseif ($filename === false) {
        $_SESSION['flash_error'] = 'Banner upload failed. Use JPG/PNG/WEBP under 5MB.';
    }
    header('Location: profile.php');
    exit;
}

// ---------- POST: Reset banner ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_banner'])) {
    if ($user['banner'] && file_exists(__DIR__ . '/../assets/uploads/banners/' . $user['banner'])) {
        @unlink(__DIR__ . '/../assets/uploads/banners/' . $user['banner']);
    }
    $stmt = mysqli_prepare($conn, "UPDATE users SET banner=NULL, banner_pos_x=50, banner_pos_y=50, banner_zoom=100 WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $_SESSION['flash_success'] = 'Banner reset successfully.';
    header('Location: profile.php');
    exit;
}

// ---------- POST: Save banner position/zoom ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_banner_pos'])) {
    $posX = max(0, min(100, (int) ($_POST['banner_pos_x'] ?? 50)));
    $posY = max(0, min(100, (int) ($_POST['banner_pos_y'] ?? 50)));
    $zoom = max(100, min(180, (int) ($_POST['banner_zoom'] ?? 100)));
    $stmt = mysqli_prepare($conn, "UPDATE users SET banner_pos_x=?, banner_pos_y=?, banner_zoom=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'iiii', $posX, $posY, $zoom, $userId);
    mysqli_stmt_execute($stmt);
    $_SESSION['flash_success'] = 'Banner position saved.';
    header('Location: profile.php');
    exit;
}

// ---------- POST: Upload CV ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_cv'])) {
    $result = handle_cv_upload('cv', __DIR__ . '/../assets/uploads/cv');
    if ($result) {
        [$storedName, $originalName] = $result;
        if ($user['cv_file'] && file_exists(__DIR__ . '/../assets/uploads/cv/' . $user['cv_file'])) {
            @unlink(__DIR__ . '/../assets/uploads/cv/' . $user['cv_file']);
        }
        $stmt = mysqli_prepare($conn, "UPDATE users SET cv_file=?, cv_original_name=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssi', $storedName, $originalName, $userId);
        mysqli_stmt_execute($stmt);
        $_SESSION['flash_success'] = 'CV uploaded successfully.';
    } elseif ($result === false) {
        $_SESSION['flash_error'] = 'CV upload failed. Use PDF/DOC/DOCX under 8MB.';
    }
    header('Location: ' . ($editing ? 'profile.php?edit=1' : 'profile.php'));
    exit;
}

// ---------- POST: Remove CV ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_cv'])) {
    if ($user['cv_file'] && file_exists(__DIR__ . '/../assets/uploads/cv/' . $user['cv_file'])) {
        @unlink(__DIR__ . '/../assets/uploads/cv/' . $user['cv_file']);
    }
    $stmt = mysqli_prepare($conn, "UPDATE users SET cv_file=NULL, cv_original_name=NULL WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $_SESSION['flash_success'] = 'CV removed.';
    header('Location: ' . ($editing ? 'profile.php?edit=1' : 'profile.php'));
    exit;
}

// ---------- Data for view ----------
$imageCountRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM portfolio_images WHERE user_id=$userId");
$imageCount = mysqli_fetch_assoc($imageCountRes)['cnt'];

$catRes = mysqli_query($conn, "SELECT DISTINCT category FROM portfolio_images WHERE user_id=$userId");
$categoriesUsed = [];
while ($r = mysqli_fetch_assoc($catRes)) $categoriesUsed[] = $r['category'];

$featuredRes = mysqli_query($conn, "SELECT * FROM portfolio_images WHERE user_id=$userId ORDER BY sort_order ASC, id DESC LIMIT 6");

$skills = skills_to_array($user['skills']);
$posX = $user['banner_pos_x'] ?? 50;
$posY = $user['banner_pos_y'] ?? 50;
$zoom = $user['banner_zoom'] ?? 100;
$activeNav = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Creator Profile - SaaqiFolio</title>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="alternate icon" type="image/png" href="../assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<script src="../assets/js/ui.js"></script>
</head>
<body>

<div class="shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="main">
    <div class="page-wrap">
      <?php if (isset($_GET['welcome'])): ?>
        <div class="alert-glass success">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          <span>Welcome to SaaqiFolio! Complete your creator profile to attract prospective clients.</span>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert-glass success">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          <span><?php echo e($success); ?></span>
        </div>
      <?php endif; ?>

      <?php if ($error): ?>
        <div class="alert-glass danger">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span><?php echo e($error); ?></span>
        </div>
      <?php endif; ?>

      <?php if ($editing): ?>

        <div class="page-head">
          <div class="page-title">Edit Creator Profile</div>
          <div class="page-desc">This information will be presented publicly on your custom portfolio page.</div>
        </div>

        <form method="POST" class="profile-card glass-panel" style="max-width:720px;">
          <div class="form-row">
            <div class="field">
              <label>Full Name</label>
              <input type="text" name="name" value="<?php echo e($user['name']); ?>" required>
            </div>
            <div class="field">
              <label>Profession / Title</label>
              <div class="form-select">
                <input type="hidden" name="profession" value="<?php echo e($user['profession']); ?>">
                <button type="button" class="form-select-trigger">
                  <span><?php echo e($user['profession'] ?: 'Select profession'); ?></span>
                  <span class="fs-caret">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="14" height="14"><polyline points="6 9 12 15 18 9"/></svg>
                  </span>
                </button>
                <ul class="form-select-list" hidden>
                  <?php foreach (get_professions() as $p): ?>
                    <li class="form-select-item <?php echo $user['profession'] === $p ? 'active' : ''; ?>" data-value="<?php echo e($p); ?>"><?php echo e($p); ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            </div>
          </div>

          <div class="form-row">
            <div class="field">
              <label>Phone Number</label>
              <input type="tel" name="phone" value="<?php echo e($user['phone']); ?>" placeholder="+92 3xx xxxxxxx">
            </div>
            <div class="field">
              <label>City &amp; Country</label>
              <input type="text" name="address" value="<?php echo e($user['address']); ?>" placeholder="Lahore, Pakistan">
            </div>
          </div>

          <div class="field">
            <label>Professional Bio</label>
            <textarea name="bio" rows="4" placeholder="Brief summary of your design ethos, specialties, and experience..."><?php echo e($user['bio']); ?></textarea>
          </div>

          <div class="field">
            <label>Education &amp; Credentials</label>
            <textarea name="education" rows="2" placeholder="e.g. BS Graphic Design — Punjab University (2019–2023)"><?php echo e($user['education']); ?></textarea>
          </div>

          <div class="field">
            <label>Experience &amp; Track Record</label>
            <textarea name="experience" rows="2" placeholder="e.g. 4 years senior freelance designer for international brands in US &amp; UAE"><?php echo e($user['experience']); ?></textarea>
          </div>

          <div class="field">
            <label>Design &amp; Creative Tools Mastery</label>
            <div class="skill-grid">
              <?php foreach (get_software_list() as $s): ?>
                <label class="skill-check">
                  <input type="checkbox" name="skills[]" value="<?php echo e($s); ?>" <?php echo in_array($s, $skills) ? 'checked' : ''; ?>>
                  <span class="skill-pill">
                    <svg class="skill-check-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" width="12" height="12"><polyline points="20 6 9 17 4 12"/></svg>
                    <span><?php echo e($s); ?></span>
                  </span>
                </label>
              <?php endforeach; ?>
            </div>
          </div>

          <div class="profile-actions" style="margin-top:28px;">
            <button class="btn btn-primary" type="submit" name="save_profile" style="width:auto;flex:1;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
              <span>Save Changes</span>
            </button>
            <a href="profile.php" class="btn btn-ghost" style="text-decoration:none;text-align:center;">Cancel</a>
          </div>
        </form>

        <!-- CV Upload Card in Edit Mode -->
        <div class="profile-card glass-panel" style="max-width:720px;margin-top:20px;">
          <div class="field" style="margin-bottom:0;">
            <label>Resume / Curriculum Vitae (PDF/DOC)</label>
            <div class="cv-row">
              <?php if ($user['cv_file']): ?>
                <div class="cv-chip-modern">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                  <span class="cv-name"><?php echo e($user['cv_original_name']); ?></span>
                </div>
                <form method="POST" style="display:inline;">
                  <button type="submit" name="remove_cv" class="btn btn-danger-ghost btn-sm" title="Remove CV">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                    <span>Remove</span>
                  </button>
                </form>
              <?php else: ?>
                <span class="muted" style="font-size:13.5px;">No CV uploaded yet.</span>
              <?php endif; ?>

              <form method="POST" enctype="multipart/form-data" style="display:inline;">
                <label class="btn btn-ghost btn-sm" style="cursor:pointer;">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                  <span>Upload CV</span>
                  <input type="file" name="cv" accept=".pdf,.doc,.docx" onchange="this.form.submit()" style="display:none;">
                </label>
                <input type="hidden" name="upload_cv" value="1">
              </form>
            </div>
          </div>
        </div>

      <?php else: ?>

        <!-- Normal View Mode -->
        <div class="page-head">
          <div class="page-title">Creator Profile</div>
          <div class="page-desc">Preview your public identity, configure credentials, and manage client-facing links.</div>
        </div>

        <!-- Glassmorphic Banner Container -->
        <?php $hasCustomBanner = !empty($user['banner']) && file_exists(__DIR__ . '/../assets/uploads/banners/' . $user['banner']); ?>
        <div class="profile-banner-container">
          <div class="profile-banner <?php echo $hasCustomBanner || $featuredRes->num_rows ? 'has-images' : ''; ?>">
            <?php if ($hasCustomBanner): ?>
              <?php 
                $bannerPath = __DIR__ . '/../assets/uploads/banners/' . $user['banner'];
                $bSize = @getimagesize($bannerPath);
                $isWide = false;
                if ($bSize && $bSize[0] > 0 && $bSize[1] > 0) {
                    $isWide = ($bSize[0] / $bSize[1]) >= 4.4;
                }
                $sizePct = round(115 * ($zoom / 100), 1);
                $bgSize = $isWide ? "auto {$sizePct}%" : "{$sizePct}% auto";
              ?>
              <div id="bannerPreviewDiv" class="banner-bg-cover" data-aspect-mode="<?php echo $isWide ? 'wide' : 'tall'; ?>" style="background-image:url('../assets/uploads/banners/<?php echo e($user['banner']); ?>');background-size:<?php echo $bgSize; ?>;background-position:<?php echo $posX; ?>% <?php echo $posY; ?>%;"></div>
              <div class="banner-overlay"></div>
            <?php elseif ($featuredRes->num_rows): ?>
              <div class="banner-collage">
                <?php mysqli_data_seek($featuredRes, 0); while ($img = mysqli_fetch_assoc($featuredRes)): ?>
                  <img src="../assets/uploads/portfolio/<?php echo e($img['filename']); ?>" alt="Featured preview">
                <?php endwhile; ?>
              </div>
              <div class="banner-overlay"></div>
            <?php else: ?>
              <div class="banner-fallback-pattern"></div>
            <?php endif; ?>

            <div class="banner-actions-floating">
              <form method="POST" enctype="multipart/form-data" style="display:inline;">
                <label class="banner-glass-btn" title="Change header banner" style="cursor:pointer;">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                  <span>Cover Banner</span>
                  <input type="file" name="banner" accept="image/*" onchange="this.form.submit()" style="display:none;">
                </label>
                <input type="hidden" name="upload_banner" value="1">
              </form>
              
              <?php if ($hasCustomBanner): ?>
                <button class="banner-glass-btn" type="button" onclick="const p = document.getElementById('adjustPanel'); p.style.display = p.style.display === 'block' ? 'none' : 'block';">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                  <span>Adjust</span>
                </button>
                <form method="POST" style="display:inline;">
                  <button class="banner-glass-btn" type="submit" name="reset_banner" title="Reset banner">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    <span>Reset</span>
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($hasCustomBanner): ?>
            <!-- Floating Adjust Panel - Placed outside banner to avoid overflow:hidden clipping -->
            <div class="banner-adjust-panel" id="adjustPanel" style="display:none;">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;">
                <div style="font-size:12px;color:var(--accent-purple-light);display:flex;align-items:center;gap:6px;font-weight:600;">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M5 9l-3 3 3 3M9 5l3-3 3 3M15 19l-3 3-3-3M19 9l3 3-3 3"/></svg>
                  <span>Adjust Banner View</span>
                </div>
                <button type="button" style="background:none;border:none;color:var(--text-muted);cursor:pointer;padding:2px;display:flex;" onclick="document.getElementById('adjustPanel').style.display='none';" title="Close">
                  <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
              </div>
              <p style="font-size:11px;color:var(--text-muted);margin:0 0 10px;line-height:1.4;">Drag the banner directly with mouse or adjust sliders:</p>
              <form method="POST" id="bannerPosForm">
                <div class="adjust-row">
                  <label>Horizontal Position (X: <span id="posXVal"><?php echo $posX; ?>%</span>)</label>
                  <input type="range" name="banner_pos_x" id="bannerPosXInput" min="0" max="100" value="<?php echo $posX; ?>" oninput="livePreviewBanner();">
                </div>
                <div class="adjust-row">
                  <label>Vertical Position (Y: <span id="posYVal"><?php echo $posY; ?>%</span>)</label>
                  <input type="range" name="banner_pos_y" id="bannerPosYInput" min="0" max="100" value="<?php echo $posY; ?>" oninput="livePreviewBanner();">
                </div>
                <div class="adjust-row">
                  <label>Zoom Level (<span id="zoomVal"><?php echo $zoom; ?>%</span>)</label>
                  <input type="range" name="banner_zoom" id="bannerZoomInput" min="100" max="180" value="<?php echo $zoom; ?>" oninput="livePreviewBanner();">
                </div>
                <input type="hidden" name="save_banner_pos" value="1">
                <button class="btn btn-primary btn-sm" type="submit" style="margin-top:12px;width:100%;">Apply Positioning</button>
              </form>
            </div>
          <?php endif; ?>

          <!-- Profile Info Bar -->
          <div class="profile-hero-row">
            <div class="avatar-wrap">
              <div class="profile-avatar-lg">
                <?php if ($user['avatar']): ?>
                  <img src="../assets/uploads/avatars/<?php echo e($user['avatar']); ?>" class="avatar-img" alt="<?php echo e($user['name']); ?>">
                <?php else: ?>
                  <span class="avatar-initials"><?php echo e(initials($user['name'])); ?></span>
                <?php endif; ?>
              </div>
              <form method="POST" enctype="multipart/form-data">
                <label class="avatar-edit-badge" title="Change profile picture" style="cursor:pointer;">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/></svg>
                  <input type="file" name="avatar" accept="image/*" onchange="this.form.submit()" style="display:none;">
                </label>
                <input type="hidden" name="upload_avatar" value="1">
              </form>
            </div>

            <div class="profile-hero-text">
              <div class="profile-name-row">
                <h1 class="profile-name-lg"><?php echo e($user['name']); ?></h1>
                <?php if ($user['is_subscribed']): ?>
                  <span class="plan-pill-glowing">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="13" height="13"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
                    <span>PRO</span>
                  </span>
                <?php else: ?>
                  <span class="plan-pill-trial">TRIAL</span>
                <?php endif; ?>
              </div>
              <div class="profile-role-lg"><?php echo e($user['profession'] ?: 'Creative Professional'); ?></div>
            </div>

            <div class="profile-hero-actions">
              <a href="profile.php?edit=1" class="btn btn-ghost btn-sm" style="text-decoration:none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/><path d="m15 5 4 4"/></svg>
                <span>Edit Profile</span>
              </a>

              <a href="../p.php?slug=<?php echo urlencode($user['public_slug']); ?>" target="_blank" class="btn btn-ghost btn-sm" style="text-decoration:none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <span>Live Preview</span>
              </a>

              <button class="btn btn-ghost btn-sm" type="button" onclick="copyShareLink()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" x2="15.42" y1="13.51" y2="17.49"/><line x1="15.41" x2="8.59" y1="6.51" y2="10.49"/></svg>
                <span>Share Link</span>
              </button>

              <?php
              $remainingPdf = $user['is_subscribed'] ? 'Unlimited' : max(0, FREE_PDF_LIMIT - (int)($user['pdf_downloads_count'] ?? 0));
              $canDownloadPdf = $user['is_subscribed'] || ($user['pdf_downloads_count'] ?? 0) < FREE_PDF_LIMIT;
              ?>
              <?php if ($canDownloadPdf): ?>
                <button type="button" class="btn btn-ghost btn-sm" onclick="openPdfExportModal()">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                  <span>Export Your Portfolio<?php echo $user['is_subscribed'] ? '' : ' (' . $remainingPdf . ' left)'; ?></span>
                </button>
              <?php else: ?>
                <a href="upgrade.php" class="btn btn-ghost btn-sm text-accent" style="text-decoration:none;">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
                  <span>Export Your Portfolio (Upgrade)</span>
                </a>
              <?php endif; ?>

              <a href="portfolio.php" class="btn btn-primary btn-sm" style="width:auto;text-decoration:none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><rect width="7" height="7" x="3" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="14" rx="1.5"/><rect width="7" height="7" x="3" y="14" rx="1.5"/></svg>
                <span>Manage Works</span>
              </a>
            </div>
          </div>
        </div>

        <!-- Glass Stat Cards Grid -->
        <div class="stats-grid">
          <div class="stat-card stat-accent">
            <div class="stat-icon-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
            </div>
            <div class="stat-value"><?php echo $imageCount; ?></div>
            <div class="stat-label">Portfolio Pieces</div>
          </div>

          <div class="stat-card stat-cool">
            <div class="stat-icon-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
            </div>
            <div class="stat-value"><?php echo count($categoriesUsed); ?></div>
            <div class="stat-label">Active Categories</div>
          </div>

          <div class="stat-card stat-warm">
            <div class="stat-icon-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
            </div>
            <div class="stat-value"><?php echo $user['is_subscribed'] ? 'PRO' : 'TRIAL'; ?></div>
            <div class="stat-label">Membership Tier</div>
          </div>

          <div class="stat-card stat-green">
            <div class="stat-icon-wrap">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
            </div>
            <div class="stat-value"><?php echo $user['is_subscribed'] ? 'Unlimited' : (max(0, FREE_PDF_LIMIT - (int) $user['pdf_downloads_count']) . ' Left'); ?></div>
            <div class="stat-label">PDF Downloads Remaining</div>
          </div>
        </div>

        <!-- Contact & Location Glass Cards -->
        <div class="info-grid">
          <div class="info-item">
            <div class="info-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L1 7"/></svg>
            </div>
            <div>
              <div class="info-label">Direct Email</div>
              <div class="info-val"><?php echo e($user['email']); ?></div>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
            </div>
            <div>
              <div class="info-label">Phone Contact</div>
              <div class="info-val"><?php echo $user['phone'] ? e($user['phone']) : '<span class="muted">—</span>'; ?></div>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>
            </div>
            <div>
              <div class="info-label">Location</div>
              <div class="info-val"><?php echo $user['address'] ? e($user['address']) : '<span class="muted">—</span>'; ?></div>
            </div>
          </div>
        </div>

        <!-- About Section -->
        <div class="about-block">
          <div class="block-title-row">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <h4>About the Creator</h4>
          </div>
          <div class="profile-bio"><?php echo $user['bio'] ? e($user['bio']) : '<span class="muted">No biography added yet. Click "Edit Profile" above to describe your creative background.</span>'; ?></div>
        </div>

        <?php if ($user['education'] || $user['experience']): ?>
          <div class="about-block">
            <?php if ($user['education']): ?>
              <div class="block-title-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M22 10v6M2 10l10-5 10 5-10 5z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>
                <h4>Education &amp; Qualifications</h4>
              </div>
              <div class="profile-bio" style="margin-bottom:18px;"><?php echo e($user['education']); ?></div>
            <?php endif; ?>

            <?php if ($user['experience']): ?>
              <div class="block-title-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><rect width="20" height="14" x="2" y="7" rx="2" ry="2"/><path d="M16 21V5a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/></svg>
                <h4>Work Experience</h4>
              </div>
              <div class="profile-bio"><?php echo e($user['experience']); ?></div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($skills): ?>
          <div class="about-block">
            <div class="block-title-row">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
              <h4>Software &amp; Tool Expertise</h4>
            </div>
            <div class="field-chips">
              <?php foreach ($skills as $s): ?>
                <span class="field-chip"><?php echo e($s); ?></span>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>

        <?php if ($user['cv_file']): ?>
          <div class="about-block">
            <div class="block-title-row">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
              <h4>Resume / Curriculum Vitae</h4>
            </div>
            <a href="../assets/uploads/cv/<?php echo e($user['cv_file']); ?>" download="<?php echo e($user['cv_original_name']); ?>" class="btn btn-ghost btn-sm" style="display:inline-flex;text-decoration:none;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
              <span>Download <?php echo e($user['cv_original_name']); ?></span>
            </a>
          </div>
        <?php endif; ?>

        <!-- Featured Gallery Strip -->
        <?php if ($featuredRes->num_rows): ?>
          <div class="featured-block">
            <div class="featured-head">
              <div class="block-title-row" style="margin-bottom:0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><rect width="7" height="7" x="3" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="3" rx="1.5"/><rect width="7" height="7" x="14" y="14" rx="1.5"/><rect width="7" height="7" x="3" y="14" rx="1.5"/></svg>
                <h4>Featured Highlights</h4>
              </div>
              <a href="portfolio.php" class="view-all-link">
                <span>View All Works</span>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="9 18 15 12 9 6"/></svg>
              </a>
            </div>
            <div class="featured-grid">
              <?php mysqli_data_seek($featuredRes, 0); while ($img = mysqli_fetch_assoc($featuredRes)): ?>
                <div class="featured-thumb">
                  <img src="../assets/uploads/portfolio/<?php echo e($img['filename']); ?>" alt="Featured preview" loading="lazy">
                </div>
              <?php endwhile; ?>
            </div>
          </div>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </main>
</div>

<script>
function livePreviewBanner(){
  const bg = document.getElementById('bannerPreviewDiv');
  const form = document.getElementById('bannerPosForm');
  if(!bg || !form) return;
  const rawX = parseFloat(form.banner_pos_x.value);
  const rawY = parseFloat(form.banner_pos_y.value);
  const rawZ = parseFloat(form.banner_zoom.value);
  const posX = isNaN(rawX) ? 50 : Math.max(0, Math.min(100, Math.round(rawX)));
  const posY = isNaN(rawY) ? 50 : Math.max(0, Math.min(100, Math.round(rawY)));
  const zoom = isNaN(rawZ) ? 100 : Math.max(100, Math.min(180, Math.round(rawZ)));
  if(document.getElementById('posXVal')) document.getElementById('posXVal').textContent = posX + '%';
  if(document.getElementById('posYVal')) document.getElementById('posYVal').textContent = posY + '%';
  if(document.getElementById('zoomVal')) document.getElementById('zoomVal').textContent = zoom + '%';
  const isWide = bg.dataset.aspectMode === 'wide';
  const sizePct = (115 * (zoom / 100)).toFixed(1);
  bg.style.backgroundSize = isWide ? `auto ${sizePct}%` : `${sizePct}% auto`;
  bg.style.backgroundPosition = posX + '% ' + posY + '%';
  bg.style.transform = 'none';
}

document.addEventListener('DOMContentLoaded', function() {
  const bg = document.getElementById('bannerPreviewDiv');
  const banner = document.querySelector('.profile-banner');
  if (bg && banner) {
    const bgImg = window.getComputedStyle(bg).backgroundImage;
    const match = bgImg && bgImg.match(/url\(["']?([^"']+)["']?\)/);
    if (match && match[1]) {
      const probe = new Image();
      probe.onload = function() {
        const bW = banner.clientWidth || 1108;
        const bH = banner.clientHeight || 250;
        const bRatio = bW / bH;
        const imgRatio = probe.naturalWidth / probe.naturalHeight;
        bg.dataset.aspectMode = (imgRatio >= bRatio) ? 'wide' : 'tall';
        livePreviewBanner();
      };
      probe.src = match[1];
    }
  }
});
function copyShareLink(){
  const url = window.location.origin + window.location.pathname.replace(/dashboard\/.*/, '') + 'p.php?slug=<?php echo urlencode($user['public_slug']); ?>';
  navigator.clipboard.writeText(url).then(() => {
    alert('Public portfolio link copied to clipboard:\n' + url);
  });
}

// Interactive Direct Mouse Drag on Cover Banner
(function() {
  const banner = document.querySelector('.profile-banner');
  const bg = document.getElementById('bannerPreviewDiv');
  const form = document.getElementById('bannerPosForm');
  if (!banner || !bg || !form) return;

  let isDraggingBanner = false;
  let startX = 0, startY = 0;
  let startPosX = 50, startPosY = 50;

  banner.addEventListener('mousedown', (e) => {
    const adjustPanel = document.getElementById('adjustPanel');
    if (!adjustPanel || adjustPanel.style.display === 'none') return;
    if (e.target.closest('button, input, form, label, .banner-actions-floating')) return;

    isDraggingBanner = true;
    startX = e.clientX;
    startY = e.clientY;
    const rawX = parseFloat(form.banner_pos_x.value);
    const rawY = parseFloat(form.banner_pos_y.value);
    startPosX = isNaN(rawX) ? 50 : rawX;
    startPosY = isNaN(rawY) ? 50 : rawY;
    banner.style.cursor = 'grabbing';
    e.preventDefault();
  });

  window.addEventListener('mousemove', (e) => {
    if (!isDraggingBanner) return;
    const dx = e.clientX - startX;
    const dy = e.clientY - startY;
    const bannerRect = banner.getBoundingClientRect();

    const deltaPercentX = (dx / bannerRect.width) * 100;
    const deltaPercentY = (dy / bannerRect.height) * 100;

    let newX = Math.round(startPosX - deltaPercentX);
    let newY = Math.round(startPosY - deltaPercentY);

    newX = Math.max(0, Math.min(100, newX));
    newY = Math.max(0, Math.min(100, newY));

    form.banner_pos_x.value = newX;
    form.banner_pos_y.value = newY;
    livePreviewBanner();
  });

  window.addEventListener('mouseup', () => {
    if (isDraggingBanner) {
      isDraggingBanner = false;
      const adjustPanel = document.getElementById('adjustPanel');
      if (adjustPanel && adjustPanel.style.display !== 'none') {
        banner.style.cursor = 'grab';
      } else {
        banner.style.cursor = '';
      }
    }
  });
})();
</script>
<?php
if ($canDownloadPdf && $imageCount > 0) {
    $pdfModalBaseUrl = 'download_pdf.php';
    $pdfTotalImages = $imageCount;
    $pdfCategoriesCount = [];
    $countsRes = mysqli_query($conn, "SELECT category, COUNT(*) as cnt FROM portfolio_images WHERE user_id=$userId GROUP BY category");
    while ($r = mysqli_fetch_assoc($countsRes)) {
        $pdfCategoriesCount[$r['category']] = (int)$r['cnt'];
    }
    include __DIR__ . '/../includes/pdf_export_modal.php';
}
?>
</body>
</html>
