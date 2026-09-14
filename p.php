<?php
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$slug = clean($conn, $_GET['slug'] ?? '');

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE public_slug = ?");
mysqli_stmt_bind_param($stmt, 's', $slug);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

$isOwner = isset($_SESSION['user_id']) && $user && (int) $_SESSION['user_id'] === (int) $user['id'];

if ($user) {
    $imgRes = mysqli_query($conn, "SELECT * FROM portfolio_images WHERE user_id=" . (int) $user['id'] . " ORDER BY id DESC");
    $allImages = [];
    while ($row = mysqli_fetch_assoc($imgRes)) $allImages[] = $row;

    $bannerImages = array_slice($allImages, 0, 5);
    $skills = skills_to_array($user['skills']);
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
<title><?php echo $user ? e($user['name']) . ' — Portfolio' : 'Not Found'; ?> - Folivo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php if (!$user): ?>

  <div class="public-page">
    <div class="public-content" style="text-align:center;padding-top:80px;">
      <div class="mark-row" style="justify-content:center;"><div class="brand-mark"></div><div class="brand-name">Folivo</div></div>
      <h2 style="font-family:var(--font-display);margin-top:30px;">Profile not found</h2>
      <p class="muted">Yeh portfolio link exist nahi karta.</p>
      <a href="auth/signup.php" class="btn btn-primary" style="width:auto;display:inline-flex;padding:12px 24px;text-decoration:none;margin-top:20px;">Apna portfolio banayein →</a>
    </div>
  </div>

<?php else: ?>

  <div class="public-page">
    <div class="public-topbar">
      <div class="mark-row"><div class="brand-mark"></div><div class="brand-name">Folivo</div></div>
      <?php if ($isOwner): ?>
        <a href="dashboard/profile.php" class="btn btn-ghost" style="width:auto;padding:9px 16px;font-size:13px;text-decoration:none;">✎ Edit profile par wapas</a>
      <?php else: ?>
        <a href="auth/signup.php" class="btn btn-primary" style="width:auto;padding:9px 16px;font-size:13px;text-decoration:none;">Apna bhi portfolio banayein →</a>
      <?php endif; ?>
    </div>

    <div class="public-content">
      <?php if ($isOwner): ?>
        <div class="preview-banner-note">✨ Yeh preview hai — client bilkul isi tarah aapki profile dekhega.</div>
      <?php endif; ?>

      <div class="profile-banner <?php echo ($user['banner'] || $bannerImages) ? 'has-images' : ''; ?>">
        <?php if ($user['banner']): ?>
          <img src="assets/uploads/banners/<?php echo e($user['banner']); ?>" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center <?php echo $posY; ?>%;transform:scale(<?php echo $zoom/100; ?>);">
          <div class="banner-overlay"></div>
        <?php elseif ($bannerImages): ?>
          <div class="banner-collage">
            <?php foreach ($bannerImages as $img): ?><img src="assets/uploads/portfolio/<?php echo e($img['filename']); ?>"><?php endforeach; ?>
          </div>
          <div class="banner-overlay"></div>
        <?php endif; ?>
      </div>

      <div class="profile-header-row">
        <div class="avatar-wrap">
          <div class="profile-avatar-lg" <?php echo $user['avatar'] ? 'style="background:none;"' : ''; ?>>
            <?php if ($user['avatar']): ?>
              <img src="assets/uploads/avatars/<?php echo e($user['avatar']); ?>" class="avatar-img">
            <?php else: ?>
              <?php echo e(initials($user['name'])); ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="profile-header-text">
          <div class="profile-name-lg"><?php echo e($user['name']); ?></div>
          <div class="profile-role-lg"><?php echo e($user['profession']); ?></div>
        </div>
        <div class="profile-actions-row">
          <a class="btn btn-primary" style="width:auto;padding:10px 18px;text-decoration:none;" href="mailto:<?php echo e($user['email']); ?>">Contact karein</a>
        </div>
      </div>

      <div class="info-grid">
        <div class="info-item"><div class="info-label">Email</div><div class="info-val"><?php echo e($user['email']); ?></div></div>
        <?php if ($user['phone']): ?><div class="info-item"><div class="info-label">Phone</div><div class="info-val"><?php echo e($user['phone']); ?></div></div><?php endif; ?>
        <?php if ($user['address']): ?><div class="info-item"><div class="info-label">Location</div><div class="info-val"><?php echo e($user['address']); ?></div></div><?php endif; ?>
      </div>

      <?php if ($user['bio']): ?>
        <div class="about-block"><h4>About</h4><div class="profile-bio"><?php echo e($user['bio']); ?></div></div>
      <?php endif; ?>

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

      <?php if ($user['cv_file']): ?>
        <div class="about-block">
          <h4>CV / Resume</h4>
          <a href="assets/uploads/cv/<?php echo e($user['cv_file']); ?>" download="<?php echo e($user['cv_original_name']); ?>" class="btn btn-ghost" style="width:auto;display:inline-flex;padding:10px 16px;text-decoration:none;">⬇ <?php echo e($user['cv_original_name']); ?></a>
        </div>
      <?php endif; ?>

      <div class="about-block">
        <h4>Portfolio</h4>
        <?php
        $hasAny = false;
        foreach ($categories as $cat):
          $imgs = array_filter($allImages, fn($i) => $i['category'] === $cat);
          if (empty($imgs)) continue;
          $hasAny = true;
        ?>
          <div class="cat-section">
            <div class="cat-section-head"><span class="cat-section-title"><?php echo e($cat); ?></span><span class="cat-section-count"><?php echo count($imgs); ?></span></div>
            <div class="grid">
              <?php foreach ($imgs as $img): ?>
                <div class="card"><div class="thumb-wrap"><img src="assets/uploads/portfolio/<?php echo e($img['filename']); ?>" class="thumb"></div></div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (!$hasAny): ?><p class="empty-note">Abhi koi portfolio pieces upload nahi hui hain.</p><?php endif; ?>
      </div>

      <div class="public-footer">Made with Folivo · <?php echo date('Y'); ?></div>
    </div>
  </div>

<?php endif; ?>

</body>
</html>
