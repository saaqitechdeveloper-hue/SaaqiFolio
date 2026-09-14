<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) $_SESSION['user_id'];
$error = '';
$success = '';

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

    $success = 'Profile updated successfully.';
    $user = get_user($conn, $userId);
    $editing = false;
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
        $user = get_user($conn, $userId);
    } elseif ($filename === false) {
        $error = 'Avatar upload failed. Use JPG/PNG/WEBP under 5MB.';
    }
}

// ---------- POST: Upload banner ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_banner'])) {
    $filename = handle_image_upload('banner', __DIR__ . '/../assets/uploads/banners');
    if ($filename) {
        if ($user['banner'] && file_exists(__DIR__ . '/../assets/uploads/banners/' . $user['banner'])) {
            @unlink(__DIR__ . '/../assets/uploads/banners/' . $user['banner']);
        }
        $stmt = mysqli_prepare($conn, "UPDATE users SET banner=?, banner_pos_y=50, banner_zoom=100 WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'si', $filename, $userId);
        mysqli_stmt_execute($stmt);
        $user = get_user($conn, $userId);
    } elseif ($filename === false) {
        $error = 'Banner upload failed. Use JPG/PNG/WEBP under 5MB.';
    }
}

// ---------- POST: Reset banner (remove custom banner image) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_banner'])) {
    if ($user['banner'] && file_exists(__DIR__ . '/../assets/uploads/banners/' . $user['banner'])) {
        @unlink(__DIR__ . '/../assets/uploads/banners/' . $user['banner']);
    }
    $stmt = mysqli_prepare($conn, "UPDATE users SET banner=NULL, banner_pos_y=50, banner_zoom=100 WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $user = get_user($conn, $userId);
}

// ---------- POST: Save banner position/zoom ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_banner_pos'])) {
    $posY = max(0, min(100, (int) $_POST['banner_pos_y']));
    $zoom = max(100, min(180, (int) $_POST['banner_zoom']));
    $stmt = mysqli_prepare($conn, "UPDATE users SET banner_pos_y=?, banner_zoom=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'iii', $posY, $zoom, $userId);
    mysqli_stmt_execute($stmt);
    $user = get_user($conn, $userId);
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
        $user = get_user($conn, $userId);
    } elseif ($result === false) {
        $error = 'CV upload failed. Use PDF/DOC/DOCX under 8MB.';
    }
}

// ---------- POST: Remove CV ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_cv'])) {
    if ($user['cv_file'] && file_exists(__DIR__ . '/../assets/uploads/cv/' . $user['cv_file'])) {
        @unlink(__DIR__ . '/../assets/uploads/cv/' . $user['cv_file']);
    }
    $stmt = mysqli_prepare($conn, "UPDATE users SET cv_file=NULL, cv_original_name=NULL WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
    $user = get_user($conn, $userId);
}

// ---------- Data for view ----------
$imageCountRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM portfolio_images WHERE user_id=$userId");
$imageCount = mysqli_fetch_assoc($imageCountRes)['cnt'];

$catRes = mysqli_query($conn, "SELECT DISTINCT category FROM portfolio_images WHERE user_id=$userId");
$categoriesUsed = [];
while ($r = mysqli_fetch_assoc($catRes)) $categoriesUsed[] = $r['category'];

$featuredRes = mysqli_query($conn, "SELECT * FROM portfolio_images WHERE user_id=$userId ORDER BY id DESC LIMIT 6");

$skills = skills_to_array($user['skills']);
$posY = $user['banner_pos_y'] ?? 50;
$zoom = $user['banner_zoom'] ?? 100;
$activeNav = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile - Folivo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main">
    <?php if (isset($_GET['welcome'])): ?>
      <div class="auth-error" style="background:#3DDC9726;color:#3DDC97;">Welcome to Folivo! Apni profile complete karein.</div>
    <?php endif; ?>
    <?php if ($success): ?><div class="auth-error" style="background:#3DDC9726;color:#3DDC97;"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="auth-error"><?php echo e($error); ?></div><?php endif; ?>

    <?php if ($editing): ?>

      <div class="page-head">
        <div class="page-title">Edit profile</div>
        <div class="page-desc">Yeh information aapke portfolio page par show hogi.</div>
      </div>
      <form method="POST" class="profile-card" style="max-width:640px;">
        <div class="field">
          <label>Full name</label>
          <input type="text" name="name" value="<?php echo e($user['name']); ?>" required>
        </div>
        <div class="field">
          <label>Profession</label>
          <select name="profession">
            <?php foreach (get_professions() as $p): ?>
              <option value="<?php echo e($p); ?>" <?php echo $user['profession'] === $p ? 'selected' : ''; ?>><?php echo e($p); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="field">
          <label>Phone number</label>
          <input type="tel" name="phone" value="<?php echo e($user['phone']); ?>" placeholder="+92 3xx xxxxxxx">
        </div>
        <div class="field">
          <label>Address</label>
          <input type="text" name="address" value="<?php echo e($user['address']); ?>" placeholder="Shehar, mulk">
        </div>
        <div class="field">
          <label>Bio</label>
          <textarea name="bio" placeholder="Apne kaam ke baare mein kuch likhein..."><?php echo e($user['bio']); ?></textarea>
        </div>
        <div class="field">
          <label>Education</label>
          <textarea name="education" placeholder="e.g. BS Graphic Design — Punjab University (2019–2023)"><?php echo e($user['education']); ?></textarea>
        </div>
        <div class="field">
          <label>Experience</label>
          <textarea name="experience" placeholder="e.g. 3 years freelance design, worked with clients in UAE and UK"><?php echo e($user['experience']); ?></textarea>
        </div>
        <div class="field">
          <label>Software / design tools jo expert hain</label>
          <div class="skill-grid">
            <?php foreach (get_software_list() as $s): ?>
              <label class="skill-check">
                <input type="checkbox" name="skills[]" value="<?php echo e($s); ?>" <?php echo in_array($s, $skills) ? 'checked' : ''; ?>>
                <span><?php echo e($s); ?></span>
              </label>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="profile-actions">
          <button class="btn btn-primary" type="submit" name="save_profile" style="width:auto;flex:1;">✓ Save changes</button>
          <a href="profile.php" class="btn btn-ghost" style="text-decoration:none;text-align:center;">Cancel</a>
        </div>
      </form>

      <!-- CV upload lives here too -->
      <div class="profile-card" style="max-width:640px;margin-top:18px;">
        <div class="field">
          <label>CV / Resume</label>
          <div class="cv-row">
            <?php if ($user['cv_file']): ?>
              <span class="cv-chip">📄 <?php echo e($user['cv_original_name']); ?></span>
              <form method="POST" style="display:inline;">
                <button type="submit" name="remove_cv" class="btn btn-ghost" style="width:auto;padding:8px 14px;font-size:12.5px;">✕ Remove</button>
              </form>
            <?php else: ?>
              <span class="muted" style="font-size:13px;">Koi CV upload nahi hui.</span>
            <?php endif; ?>
            <form method="POST" enctype="multipart/form-data" style="display:inline;">
              <label class="btn btn-ghost" style="width:auto;padding:8px 14px;font-size:12.5px;cursor:pointer;">
                ⬆ Upload CV
                <input type="file" name="cv" accept=".pdf,.doc,.docx" onchange="this.form.submit()" style="display:none;">
              </label>
              <input type="hidden" name="upload_cv" value="1">
            </form>
          </div>
        </div>
      </div>

    <?php else: ?>

      <div class="page-head">
        <div class="page-title">My profile</div>
        <div class="page-desc">Aapki public portfolio profile.</div>
      </div>

      <div class="profile-banner <?php echo $user['banner'] || $featuredRes->num_rows ? 'has-images' : ''; ?>">
        <?php if ($user['banner']): ?>
          <img id="bannerPreviewImg" src="../assets/uploads/banners/<?php echo e($user['banner']); ?>" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center <?php echo $posY; ?>%;transform:scale(<?php echo $zoom/100; ?>);">
          <div class="banner-overlay"></div>
        <?php elseif ($featuredRes->num_rows): ?>
          <div class="banner-collage">
            <?php mysqli_data_seek($featuredRes, 0); while ($img = mysqli_fetch_assoc($featuredRes)): ?>
              <img src="../assets/uploads/portfolio/<?php echo e($img['filename']); ?>">
            <?php endwhile; ?>
          </div>
          <div class="banner-overlay"></div>
        <?php endif; ?>

        <div class="banner-actions">
          <form method="POST" enctype="multipart/form-data" style="display:inline;">
            <label class="banner-btn" style="cursor:pointer;">
              📷 Change banner
              <input type="file" name="banner" accept="image/*" onchange="this.form.submit()" style="display:none;">
            </label>
            <input type="hidden" name="upload_banner" value="1">
          </form>
          <?php if ($user['banner']): ?>
            <button class="banner-btn" type="button" onclick="document.getElementById('adjustPanel').style.display = document.getElementById('adjustPanel').style.display === 'block' ? 'none' : 'block';">↕ Adjust</button>
            <form method="POST" style="display:inline;">
              <button class="banner-btn" type="submit" name="reset_banner">✕ Reset</button>
            </form>
          <?php endif; ?>
        </div>

        <?php if ($user['banner']): ?>
        <div class="banner-adjust-panel" id="adjustPanel" style="display:none;">
          <form method="POST" id="bannerPosForm">
            <div class="adjust-row">
              <label>Position</label>
              <input type="range" name="banner_pos_y" min="0" max="100" value="<?php echo $posY; ?>" oninput="livePreviewBanner();">
            </div>
            <div class="adjust-row">
              <label>Zoom</label>
              <input type="range" name="banner_zoom" min="100" max="180" value="<?php echo $zoom; ?>" oninput="livePreviewBanner();">
            </div>
            <input type="hidden" name="save_banner_pos" value="1">
            <button class="btn btn-primary" type="submit" style="margin-top:10px;">Save Position</button>
          </form>
        </div>
        <?php endif; ?>
      </div>

      <div class="profile-header-row">
        <div class="avatar-wrap">
          <div class="profile-avatar-lg" <?php echo $user['avatar'] ? 'style="background:none;"' : ''; ?>>
            <?php if ($user['avatar']): ?>
              <img src="../assets/uploads/avatars/<?php echo e($user['avatar']); ?>" class="avatar-img">
            <?php else: ?>
              <?php echo e(initials($user['name'])); ?>
            <?php endif; ?>
          </div>
          <form method="POST" enctype="multipart/form-data">
            <label class="avatar-edit-btn" title="Profile picture change karein" style="cursor:pointer;">
              📷
              <input type="file" name="avatar" accept="image/*" onchange="this.form.submit()" style="display:none;">
            </label>
            <input type="hidden" name="upload_avatar" value="1">
          </form>
        </div>
        <div class="profile-header-text">
          <div class="profile-name-lg"><?php echo e($user['name']); ?></div>
          <div class="profile-role-lg"><?php echo e($user['profession']); ?></div>
        </div>
        <div class="profile-actions-row">
          <a href="profile.php?edit=1" class="btn btn-ghost" style="text-decoration:none;">✎ Edit profile</a>
          <a href="../p.php?slug=<?php echo urlencode($user['public_slug']); ?>" target="_blank" class="btn btn-ghost" style="text-decoration:none;">👤 Client jaisa preview</a>
          <button class="btn btn-ghost" type="button" onclick="copyShareLink()">🔗 Share link</button>
          <?php if ($user['is_subscribed'] || $user['pdf_downloads_count'] < FREE_PDF_LIMIT): ?>
            <a href="download_pdf.php" class="btn btn-ghost" style="text-decoration:none;">
              ⬇ Download PDF<?php echo $user['is_subscribed'] ? '' : ' (' . (FREE_PDF_LIMIT - $user['pdf_downloads_count']) . ' left)'; ?>
            </a>
          <?php else: ?>
            <a href="upgrade.php" class="btn btn-ghost" style="text-decoration:none;color:var(--accent);">⬇ Download PDF (limit reached)</a>
          <?php endif; ?>
          <a href="portfolio.php" class="btn btn-primary" style="width:auto;padding-left:16px;padding-right:16px;text-decoration:none;">⊞ View portfolio</a>
        </div>
      </div>

      <div class="stat-bar">
        <div class="stat-cell"><div class="stat-num"><?php echo $imageCount; ?></div><div class="stat-label">Portfolio pieces</div></div>
        <div class="stat-cell"><div class="stat-num"><?php echo count($categoriesUsed); ?></div><div class="stat-label">Categories</div></div>
        <div class="stat-cell"><div class="stat-num"><?php echo $user['is_subscribed'] ? 'Pro' : 'Trial'; ?></div><div class="stat-label">Plan</div></div>
        <div class="stat-cell"><div class="stat-num"><?php echo e(explode('@', $user['email'])[0]); ?></div><div class="stat-label">Contact</div></div>
      </div>

      <div class="info-grid">
        <div class="info-item"><div class="info-label">Email</div><div class="info-val"><?php echo e($user['email']); ?></div></div>
        <div class="info-item"><div class="info-label">Phone</div><div class="info-val"><?php echo $user['phone'] ? e($user['phone']) : '<span class="muted">—</span>'; ?></div></div>
        <div class="info-item"><div class="info-label">Address</div><div class="info-val"><?php echo $user['address'] ? e($user['address']) : '<span class="muted">—</span>'; ?></div></div>
      </div>

      <div class="about-block">
        <h4>About</h4>
        <div class="profile-bio"><?php echo $user['bio'] ? e($user['bio']) : '<span class="muted">Abhi tak koi bio nahi likha gaya.</span>'; ?></div>
      </div>

      <?php if ($user['education'] || $user['experience']): ?>
      <div class="about-block">
        <?php if ($user['education']): ?><h4>Education</h4><div class="profile-bio" style="margin-bottom:16px;"><?php echo e($user['education']); ?></div><?php endif; ?>
        <?php if ($user['experience']): ?><h4>Experience</h4><div class="profile-bio"><?php echo e($user['experience']); ?></div><?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if ($skills): ?>
      <div class="about-block">
        <h4>Software expertise</h4>
        <div class="field-chips"><?php foreach ($skills as $s): ?><span class="field-chip"><?php echo e($s); ?></span><?php endforeach; ?></div>
      </div>
      <?php endif; ?>

      <?php if ($categoriesUsed): ?>
      <div class="about-block">
        <h4>Portfolio categories</h4>
        <div class="field-chips"><?php foreach ($categoriesUsed as $c): ?><span class="field-chip"><?php echo e($c); ?></span><?php endforeach; ?></div>
      </div>
      <?php endif; ?>

      <?php if ($user['cv_file']): ?>
      <div class="about-block">
        <h4>CV / Resume</h4>
        <a href="../assets/uploads/cv/<?php echo e($user['cv_file']); ?>" download="<?php echo e($user['cv_original_name']); ?>" class="btn btn-ghost" style="width:auto;display:inline-flex;padding:10px 16px;text-decoration:none;">⬇ <?php echo e($user['cv_original_name']); ?></a>
      </div>
      <?php endif; ?>

      <?php if ($featuredRes->num_rows): ?>
      <div class="featured-block">
        <div class="featured-head">
          <h4>Featured work</h4>
          <a href="portfolio.php" class="upgrade-link" style="margin:0;text-decoration:none;">View all →</a>
        </div>
        <div class="featured-grid">
          <?php mysqli_data_seek($featuredRes, 0); while ($img = mysqli_fetch_assoc($featuredRes)): ?>
            <div class="featured-thumb"><img src="../assets/uploads/portfolio/<?php echo e($img['filename']); ?>"></div>
          <?php endwhile; ?>
        </div>
      </div>
      <?php else: ?>
      <div class="featured-block">
        <div class="dropzone" style="padding:40px 24px;">
          <div class="dz-title" style="font-size:16px;">Abhi koi featured work nahi hai</div>
          <div class="dz-sub" style="margin-bottom:14px;">Portfolio mein images add karein, yahan khud-ba-khud dikhna shuru ho jayengi.</div>
          <a href="portfolio.php" class="btn btn-ghost" style="width:auto;padding-left:18px;padding-right:18px;text-decoration:none;">⊞ Portfolio par jayein</a>
        </div>
      </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</div>

<script>
function livePreviewBanner(){
  const img = document.getElementById('bannerPreviewImg');
  if(!img) return;
  const form = document.getElementById('bannerPosForm');
  const posY = form.banner_pos_y.value;
  const zoom = form.banner_zoom.value;
  img.style.objectPosition = 'center ' + posY + '%';
  img.style.transform = 'scale(' + (zoom/100) + ')';
}
function copyShareLink(){
  const url = window.location.origin + window.location.pathname.replace(/dashboard\/.*/, '') + 'p.php?slug=<?php echo urlencode($user['public_slug']); ?>';
  navigator.clipboard.writeText(url).then(() => alert('Link copied: ' + url));
}
</script>

</body>
</html>
