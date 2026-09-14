<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) $_SESSION['user_id'];
$error = '';
$success = '';

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

$categories = get_categories();

// ---------- POST: Upload images ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_images'])) {
    $countRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM portfolio_images WHERE user_id=$userId");
    $currentCount = mysqli_fetch_assoc($countRes)['cnt'];

    if (!$user['is_subscribed'] && $currentCount >= FREE_IMAGE_LIMIT) {
        $error = "Free plan limit (" . FREE_IMAGE_LIMIT . " images) reached. Upgrade to Pro to add more.";
        $limitHitNow = true;
    } else {
        $uploaded = 0;
        $failed = 0;
        $limitHitNow = false;
        // How many more images this account is allowed to add right now.
        // Pro accounts have no cap (PHP_INT_MAX acts as "unlimited" here).
        $remainingSlots = $user['is_subscribed'] ? PHP_INT_MAX : (FREE_IMAGE_LIMIT - $currentCount);

        if (isset($_FILES['images'])) {
            $fileCount = count($_FILES['images']['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                // Stop processing further files the moment the free limit is hit —
                // this is what makes the limit apply DURING a bulk upload, not just before it.
                if ($remainingSlots <= 0) {
                    $limitHitNow = true;
                    break;
                }

                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;

                $tmpFile = ['name' => $_FILES['images']['name'][$i], 'type' => $_FILES['images']['type'][$i],
                            'tmp_name' => $_FILES['images']['tmp_name'][$i], 'error' => $_FILES['images']['error'][$i],
                            'size' => $_FILES['images']['size'][$i]];
                $_FILES['single_image'] = $tmpFile;

                $filename = handle_image_upload('single_image', __DIR__ . '/../assets/uploads/portfolio');
                if ($filename) {
                    $category = suggest_category_from_filename($_FILES['images']['name'][$i]);
                    $ins = mysqli_prepare($conn, "INSERT INTO portfolio_images (user_id, filename, category) VALUES (?,?,?)");
                    mysqli_stmt_bind_param($ins, 'iss', $userId, $filename, $category);
                    mysqli_stmt_execute($ins);
                    $uploaded++;
                    $remainingSlots--;
                } else {
                    $failed++;
                }
            }
        }
        if ($uploaded) $success = "$uploaded image(s) uploaded successfully.";
        if ($failed) $error = ($error ? $error . ' ' : '') . "$failed file(s) failed (only JPG/PNG/WEBP under 5MB allowed).";
        if ($limitHitNow) {
            $success = ($success ? $success . ' ' : '') . "You've now reached the free plan limit of " . FREE_IMAGE_LIMIT . " images" . ($failed || ($fileCount ?? 0) > $uploaded ? ' — the rest of this batch was not uploaded.' : '.');
        }
    }
}

// ---------- POST: Change single image category ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_category'])) {
    $imgId = (int) $_POST['image_id'];
    $category = clean($conn, $_POST['category']);
    if (in_array($category, $categories)) {
        $stmt = mysqli_prepare($conn, "UPDATE portfolio_images SET category=? WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, 'sii', $category, $imgId, $userId);
        mysqli_stmt_execute($stmt);
    }
}

// ---------- POST: Bulk set category ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_set_category'])) {
    $category = clean($conn, $_POST['bulk_category']);
    $ids = array_map('intval', $_POST['selected_ids'] ?? []);
    if (in_array($category, $categories) && $ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = 's' . str_repeat('i', count($ids)) . 'i';
        $params = array_merge([$category], $ids, [$userId]);
        $stmt = mysqli_prepare($conn, "UPDATE portfolio_images SET category=? WHERE id IN ($placeholders) AND user_id=?");
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $success = count($ids) . " image(s) moved to $category.";
    }
}

// ---------- POST: Delete image ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $imgId = (int) $_POST['image_id'];
    $stmt = mysqli_prepare($conn, "SELECT filename FROM portfolio_images WHERE id=? AND user_id=?");
    mysqli_stmt_bind_param($stmt, 'ii', $imgId, $userId);
    mysqli_stmt_execute($stmt);
    $img = mysqli_stmt_get_result($stmt)->fetch_assoc();
    if ($img) {
        if (file_exists(__DIR__ . '/../assets/uploads/portfolio/' . $img['filename'])) {
            @unlink(__DIR__ . '/../assets/uploads/portfolio/' . $img['filename']);
        }
        $del = mysqli_prepare($conn, "DELETE FROM portfolio_images WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($del, 'ii', $imgId, $userId);
        mysqli_stmt_execute($del);
    }
}

// ---------- Load images ----------
$activeCategory = $_GET['category'] ?? 'All';
$allImagesRes = mysqli_query($conn, "SELECT * FROM portfolio_images WHERE user_id=$userId ORDER BY id DESC");
$allImages = [];
while ($row = mysqli_fetch_assoc($allImagesRes)) $allImages[] = $row;

$imageCount = count($allImages);
$atLimit = !$user['is_subscribed'] && $imageCount >= FREE_IMAGE_LIMIT;

$counts = array_fill_keys($categories, 0);
foreach ($allImages as $img) $counts[$img['category']]++;

$activeNav = 'portfolio';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portfolio - Folivo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="main">
    <?php if ($success): ?><div class="auth-error" style="background:#3DDC9726;color:#3DDC97;"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="auth-error"><?php echo e($error); ?></div><?php endif; ?>
    <?php if (!empty($limitHitNow) && !$user['is_subscribed']): ?>
      <div class="auth-error" style="background:#7C5CFC26;color:#7C5CFC;display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap;">
        <span>👑 You've reached the <?php echo FREE_IMAGE_LIMIT; ?>-image free limit. Upgrade to Pro to keep adding your work.</span>
        <a href="upgrade.php" class="btn btn-primary" style="width:auto;padding:8px 18px;text-decoration:none;flex-shrink:0;">Upgrade Now →</a>
      </div>
    <?php endif; ?>

    <?php if ($imageCount === 0): ?>

      <div class="page-head">
        <div class="page-title">Portfolio</div>
        <div class="page-desc">Apna kaam upload karein aur category select karein.</div>
      </div>
      <div class="dropzone" id="dropzone">
        <div class="dz-icon">🖼️</div>
        <div class="dz-title">Create your portfolio</div>
        <div class="dz-sub">Images select karein — Logo, Banner, UI/UX, Flyer, sab manually category assign kar sakte hain.</div>
        <form method="POST" enctype="multipart/form-data">
          <label class="btn btn-primary" style="width:auto;padding-left:22px;padding-right:22px;display:inline-flex;cursor:pointer;">
            ⬆ Upload images
            <input type="file" name="images[]" multiple accept="image/*" onchange="this.form.submit()" style="display:none;">
          </label>
          <input type="hidden" name="upload_images" value="1">
        </form>
      </div>

    <?php else: ?>

      <div class="portfolio-head">
        <div>
          <div class="page-title">Portfolio</div>
          <div class="page-desc"><?php echo $imageCount; ?> image<?php echo $imageCount !== 1 ? 's' : ''; ?> · category wise organized</div>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
          <form method="POST" enctype="multipart/form-data" style="display:inline;">
            <label class="btn <?php echo $atLimit ? 'btn-ghost' : 'btn-primary'; ?>" style="width:auto;padding-left:18px;padding-right:18px;display:inline-flex;cursor:pointer;">
              + Add images
              <?php if (!$atLimit): ?>
                <input type="file" name="images[]" multiple accept="image/*" onchange="this.form.submit()" style="display:none;">
              <?php endif; ?>
            </label>
            <input type="hidden" name="upload_images" value="1">
          </form>
          <?php if ($atLimit): ?>
            <a href="upgrade.php" class="btn btn-ghost" style="text-decoration:none;">Limit reached — Upgrade</a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Bulk select -->
      <div class="bulk-toggle" style="margin-bottom:14px;">
        <button type="button" class="btn btn-ghost" style="width:auto;padding-left:16px;padding-right:16px;" onclick="toggleBulk()">☑ Bulk select</button>
      </div>
      <div class="bulk-bar" id="bulkBar" style="display:none;">
        <div class="bulk-info">
          <span class="bulk-count"><span id="selCount">0</span> select ki gayi</span>
          <button type="button" class="upgrade-link" style="margin:0;" onclick="selectAllImages()">Sab select karein</button>
          <button type="button" class="upgrade-link" style="margin:0;" onclick="clearSelection()">Clear</button>
        </div>
        <div class="bulk-cats">
          <?php foreach ($categories as $c): ?>
            <button type="button" class="bulk-cat-btn badge-<?php echo slugify($c); ?>" onclick="submitBulk('<?php echo e($c); ?>')"><?php echo e($c); ?></button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Standalone bulk form — populated by JS on submit, kept outside the image grid so it never nests inside per-card forms -->
      <form method="POST" id="bulkForm" style="display:none;">
        <input type="hidden" name="bulk_set_category" value="1">
        <input type="hidden" name="bulk_category" id="bulkCategoryInput" value="">
        <div id="bulkIdsHolder"></div>
      </form>

      <div class="tabs">
        <a href="?category=All" class="tab <?php echo $activeCategory === 'All' ? 'tab-active' : ''; ?>" style="text-decoration:none;">All <span class="tab-count"><?php echo $imageCount; ?></span></a>
        <?php foreach ($categories as $c): if ($counts[$c] === 0) continue; ?>
          <a href="?category=<?php echo urlencode($c); ?>" class="tab <?php echo $activeCategory === $c ? 'tab-active' : ''; ?>" style="text-decoration:none;"><?php echo e($c); ?> <span class="tab-count"><?php echo $counts[$c]; ?></span></a>
        <?php endforeach; ?>
      </div>

      <?php
      $displayImages = $activeCategory === 'All' ? $allImages : array_filter($allImages, fn($i) => $i['category'] === $activeCategory);
      if (empty($displayImages)):
      ?>
        <p class="empty-note">Is category mein abhi koi image nahi hai.</p>
      <?php else: ?>

        <?php if ($activeCategory === 'All'): ?>
          <?php foreach ($categories as $cat):
            $imgs = array_filter($allImages, fn($i) => $i['category'] === $cat);
            if (empty($imgs)) continue;
          ?>
            <div class="cat-section">
              <div class="cat-section-head"><span class="cat-section-title"><?php echo e($cat); ?></span><span class="cat-section-count"><?php echo count($imgs); ?></span></div>
              <div class="grid">
                <?php foreach ($imgs as $img): include __DIR__ . '/_image_card.php'; endforeach; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="grid">
            <?php foreach ($displayImages as $img): include __DIR__ . '/_image_card.php'; endforeach; ?>
          </div>
        <?php endif; ?>

      <?php endif; ?>

    <?php endif; ?>
  </div>
</div>

<script>
let bulkMode = false;
function toggleBulk(){
  bulkMode = !bulkMode;
  document.getElementById('bulkBar').style.display = bulkMode ? 'flex' : 'none';
  document.querySelectorAll('.img-select-box').forEach(el => el.style.display = bulkMode ? 'block' : 'none');
  if(!bulkMode) clearSelection();
}
function selectAllImages(){
  document.querySelectorAll('.img-select-cb').forEach(cb => cb.checked = true);
  updateSelCount();
}
function clearSelection(){
  document.querySelectorAll('.img-select-cb').forEach(cb => cb.checked = false);
  updateSelCount();
}
function updateSelCount(){
  document.getElementById('selCount').textContent = document.querySelectorAll('.img-select-cb:checked').length;
}
function submitBulk(category){
  const checked = document.querySelectorAll('.img-select-cb:checked');
  if(checked.length === 0){ alert('Pehle kam se kam ek image select karein.'); return; }
  const holder = document.getElementById('bulkIdsHolder');
  holder.innerHTML = '';
  checked.forEach(cb => {
    const inp = document.createElement('input');
    inp.type = 'hidden';
    inp.name = 'selected_ids[]';
    inp.value = cb.dataset.id;
    holder.appendChild(inp);
  });
  document.getElementById('bulkCategoryInput').value = category;
  document.getElementById('bulkForm').submit();
}
document.addEventListener('change', (e) => {
  if(e.target.classList.contains('img-select-cb')) updateSelCount();
});
</script>

</body>
</html>
