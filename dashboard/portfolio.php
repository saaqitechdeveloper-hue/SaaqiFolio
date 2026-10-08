<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) $_SESSION['user_id'];
$error = $_SESSION['flash_error'] ?? '';
$success = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

$redirectTarget = "portfolio" . (!empty($_GET['category']) && $_GET['category'] !== 'All' ? "?category=" . urlencode($_GET['category']) : "");

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

$canExportPdf = !empty($user['is_subscribed']) || ((int)($user['pdf_downloads_count'] ?? 0) < FREE_PDF_LIMIT);
$remPdf = !empty($user['is_subscribed']) ? '' : ' (' . max(0, FREE_PDF_LIMIT - (int)($user['pdf_downloads_count'] ?? 0)) . ' left)';

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
        $remainingSlots = $user['is_subscribed'] ? PHP_INT_MAX : (FREE_IMAGE_LIMIT - $currentCount);

        // Universal file extraction: safely handles single file, array of files, and multiple keys
        $incomingFiles = [];
        $possibleKeys = ['images', 'image', 'files', 'file', 'single_image'];
        foreach ($possibleKeys as $key) {
            if (isset($_FILES[$key]) && !empty($_FILES[$key]['name'])) {
                if (is_array($_FILES[$key]['name'])) {
                    $cnt = count($_FILES[$key]['name']);
                    for ($i = 0; $i < $cnt; $i++) {
                        if (empty($_FILES[$key]['name'][$i])) continue;
                        $incomingFiles[] = [
                            'name'     => $_FILES[$key]['name'][$i],
                            'type'     => $_FILES[$key]['type'][$i] ?? '',
                            'tmp_name' => $_FILES[$key]['tmp_name'][$i] ?? '',
                            'error'    => $_FILES[$key]['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                            'size'     => $_FILES[$key]['size'][$i] ?? 0,
                        ];
                    }
                } else {
                    $incomingFiles[] = [
                        'name'     => $_FILES[$key]['name'],
                        'type'     => $_FILES[$key]['type'] ?? '',
                        'tmp_name' => $_FILES[$key]['tmp_name'] ?? '',
                        'error'    => $_FILES[$key]['error'] ?? UPLOAD_ERR_NO_FILE,
                        'size'     => $_FILES[$key]['size'] ?? 0,
                    ];
                }
            }
        }

        $totalIncoming = count($incomingFiles);
        $savedFiles = [];

        foreach ($incomingFiles as $fileItem) {
            if ($remainingSlots <= 0) {
                $limitHitNow = true;
                break;
            }

            if ($fileItem['error'] !== UPLOAD_ERR_OK) {
                $failed++;
                continue;
            }

            $_FILES['single_upload_item'] = $fileItem;
            $filename = handle_image_upload('single_upload_item', __DIR__ . '/../assets/uploads/portfolio');
            if ($filename) {
                $savedFiles[] = [
                    'filename' => $filename,
                    'path'     => __DIR__ . '/../assets/uploads/portfolio/' . $filename,
                    'original' => $fileItem['name'],
                ];
                $uploaded++;
                $remainingSlots--;
            } else {
                $failed++;
            }
        }

        foreach ($savedFiles as $f) {
            $category = 'Other';
            try {
                $category = classify_image_with_ai($f['path'], $f['original'], $categories);
            } catch (Throwable $e) {
                error_log("classify_image_with_ai error: " . $e->getMessage());
                $category = 'Other';
            }
            if (empty($category) || !in_array($category, $categories, true)) {
                $category = 'Other';
            }
            $ins = mysqli_prepare($conn, "INSERT INTO portfolio_images (user_id, filename, category) VALUES (?,?,?)");
            mysqli_stmt_bind_param($ins, 'iss', $userId, $f['filename'], $category);
            mysqli_stmt_execute($ins);
        }

        if ($uploaded) $success = "$uploaded image(s) uploaded successfully.";
        if ($failed) $error = ($error ? $error . ' ' : '') . "$failed file(s) failed (files must be valid images under 30MB).";
        if ($limitHitNow) {
            $success = ($success ? $success . ' ' : '') . "You've now reached the free plan limit of " . FREE_IMAGE_LIMIT . " images" . ($failed || $totalIncoming > $uploaded ? ' — the rest of this batch was not uploaded.' : '.');
        }
    }

    if (($_POST['ajax'] ?? '') === '1') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => (bool) ($uploaded ?? 0),
            'uploaded' => $uploaded ?? 0,
            'failed' => $failed ?? 0,
            'message' => $success,
            'error' => $error,
            'debug' => ai_debug_log(),
        ]);
        exit;
    }

    // Standard POST redirect (PRG)
    if ($success) $_SESSION['flash_success'] = $success;
    if ($error) $_SESSION['flash_error'] = $error;
    header("Location: " . $redirectTarget);
    exit;
}

// ---------- POST: Change single image category ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_category'])) {
    $imgId = (int) $_POST['image_id'];
    $category = clean($conn, $_POST['category']);
    if (in_array($category, $categories)) {
        $stmt = mysqli_prepare($conn, "UPDATE portfolio_images SET category=? WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($stmt, 'sii', $category, $imgId, $userId);
        mysqli_stmt_execute($stmt);
        $_SESSION['flash_success'] = "Category updated to $category.";
    }
    header("Location: " . $redirectTarget);
    exit;
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
        $_SESSION['flash_success'] = count($ids) . " image(s) moved to $category.";
    }
    header("Location: " . $redirectTarget);
    exit;
}

// ---------- POST: Bulk delete images ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete_images'])) {
    $ids = array_map('intval', $_POST['selected_ids'] ?? []);
    if (!empty($ids)) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids)) . 'i';
        $params = array_merge($ids, [$userId]);

        // Get filenames to delete files from filesystem
        $stmt = mysqli_prepare($conn, "SELECT filename FROM portfolio_images WHERE id IN ($placeholders) AND user_id=?");
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($res)) {
            delete_portfolio_image_files($row['filename']);
        }

        // Delete from database
        $delStmt = mysqli_prepare($conn, "DELETE FROM portfolio_images WHERE id IN ($placeholders) AND user_id=?");
        mysqli_stmt_bind_param($delStmt, $types, ...$params);
        mysqli_stmt_execute($delStmt);

        $_SESSION['flash_success'] = count($ids) . " image(s) deleted permanently.";
    }
    header("Location: " . $redirectTarget);
    exit;
}

// ---------- POST: Delete single image ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_image'])) {
    $imgId = (int) $_POST['image_id'];
    $stmt = mysqli_prepare($conn, "SELECT filename FROM portfolio_images WHERE id=? AND user_id=?");
    mysqli_stmt_bind_param($stmt, 'ii', $imgId, $userId);
    mysqli_stmt_execute($stmt);
    $img = mysqli_stmt_get_result($stmt)->fetch_assoc();
    if ($img) {
        delete_portfolio_image_files($img['filename']);
        $del = mysqli_prepare($conn, "DELETE FROM portfolio_images WHERE id=? AND user_id=?");
        mysqli_stmt_bind_param($del, 'ii', $imgId, $userId);
        mysqli_stmt_execute($del);
        $_SESSION['flash_success'] = "Image deleted successfully.";
    }
    header("Location: " . $redirectTarget);
    exit;
}

// ---------- POST: AJAX Reorder images within category ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (($_POST['action'] ?? '') === 'reorder_images' || isset($_POST['reorder_images']))) {
    header('Content-Type: application/json');
    $orderRaw = $_POST['order'] ?? [];
    if (!is_array($orderRaw)) {
        $orderRaw = json_decode($_POST['order'] ?? '[]', true) ?: [];
    }
    $ids = array_map('intval', $orderRaw);
    if (!empty($ids)) {
        $stmt = mysqli_prepare($conn, "UPDATE portfolio_images SET sort_order = ? WHERE id = ? AND user_id = ?");
        foreach ($ids as $idx => $id) {
            $sortPos = $idx + 1;
            mysqli_stmt_bind_param($stmt, 'iii', $sortPos, $id, $userId);
            mysqli_stmt_execute($stmt);
        }
        echo json_encode(['success' => true, 'count' => count($ids)]);
        exit;
    }
    echo json_encode(['success' => false, 'error' => 'No order provided']);
    exit;
}

// ---------- POST: AJAX Behance Extraction & Import ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'behance_extract') {
    header('Content-Type: application/json');
    require_once __DIR__ . '/../includes/behance_helper.php';

    $url = trim($_POST['url'] ?? '');
    if (empty($url)) {
        echo json_encode(['success' => false, 'error' => 'Please enter a Behance URL']);
        exit;
    }

    $countRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM portfolio_images WHERE user_id=$userId");
    $currentCount = (int) mysqli_fetch_assoc($countRes)['cnt'];
    $remainingSlots = $user['is_subscribed'] ? PHP_INT_MAX : (FREE_IMAGE_LIMIT - $currentCount);

    if (!$user['is_subscribed'] && $remainingSlots <= 0) {
        echo json_encode([
            'success' => false,
            'error' => "Free plan limit (" . FREE_IMAGE_LIMIT . " images) reached. Upgrade to Pro to import more artworks."
        ]);
        exit;
    }

    $data = behance_extract_portfolio($url);
    if (!$data['success']) {
        echo json_encode(['success' => false, 'error' => $data['error'] ?? 'Extraction failed']);
        exit;
    }

    $extracted = $data['images'] ?? [];
    if (!$user['is_subscribed'] && count($extracted) > $remainingSlots) {
        $extracted = array_slice($extracted, 0, $remainingSlots);
    }

    echo json_encode([
        'success' => true,
        'type' => $data['type'] ?? 'gallery',
        'title' => $data['title'] ?? 'Behance Artworks',
        'total' => count($extracted),
        'images' => $extracted,
        'remaining_slots' => $remainingSlots,
        'limit_capped' => (!$user['is_subscribed'] && count($data['images']) > $remainingSlots)
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'behance_import_item') {
    header('Content-Type: application/json');
    require_once __DIR__ . '/../includes/behance_helper.php';

    $countRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM portfolio_images WHERE user_id=$userId");
    $currentCount = (int) mysqli_fetch_assoc($countRes)['cnt'];
    if (!$user['is_subscribed'] && $currentCount >= FREE_IMAGE_LIMIT) {
        echo json_encode(['success' => false, 'error' => 'Free plan limit reached. Upgrade to Pro.']);
        exit;
    }

    $imgItem = [
        'url' => trim($_POST['url'] ?? ''),
        'title' => trim($_POST['title'] ?? ''),
        'alt' => trim($_POST['alt'] ?? ''),
    ];

    if (empty($imgItem['url'])) {
        echo json_encode(['success' => false, 'error' => 'Invalid image item URL']);
        exit;
    }

    $res = behance_import_and_classify_single($userId, $imgItem, $categories, $conn);
    if (!$res['success']) {
        echo json_encode(['success' => false, 'error' => $res['error'] ?? 'Failed to import artwork']);
        exit;
    }

    // Render image card HTML for immediate dynamic DOM insertion
    $img = [
        'id' => $res['image']['id'],
        'filename' => $res['image']['filename'],
        'category' => $res['image']['category']
    ];
    ob_start();
    include __DIR__ . '/_image_card.php';
    $cardHtml = ob_get_clean();

    echo json_encode([
        'success' => true,
        'image' => $img,
        'card_html' => $cardHtml,
        'category' => $img['category'],
        'id' => $img['id']
    ]);
    exit;
}


// ---------- Load images ----------
$activeCategory = $_GET['category'] ?? 'All';
$allImagesRes = mysqli_query($conn, "SELECT * FROM portfolio_images WHERE user_id=$userId ORDER BY sort_order ASC, id DESC");
$allImages = [];
while ($row = mysqli_fetch_assoc($allImagesRes)) $allImages[] = $row;

$imageCount = count($allImages);
$atLimit = !$user['is_subscribed'] && $imageCount >= FREE_IMAGE_LIMIT;
$canExportPdf = !empty($user['is_subscribed']) || ((int)($user['pdf_downloads_count'] ?? 0) < FREE_PDF_LIMIT);
$remPdf = !empty($user['is_subscribed']) ? '' : ' (' . max(0, FREE_PDF_LIMIT - (int)($user['pdf_downloads_count'] ?? 0)) . ' left)';

$counts = array_fill_keys($categories, 0);
foreach ($allImages as $img) {
    if (isset($counts[$img['category']])) {
        $counts[$img['category']]++;
    }
}

$activeNav = 'portfolio';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portfolio Works - SaaqiFolio</title>
<?php
$ogTitle = 'Portfolio Works — SaaqiFolio';
$ogDescription = 'Upload and organize your design works into smart categories with AI assistance on SaaqiFolio.';
include __DIR__ . '/../includes/og_meta.php';
?>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="alternate icon" type="image/png" href="../assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
<?php include __DIR__ . '/../includes/theme_head.php'; ?>
<script src="../assets/js/ui.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/ui.js'); ?>"></script>
</head>
<body>

<div class="shell">
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <main class="main">
    <div class="page-wrap">
      <!-- Top Dashboard Header / Switcher Bar -->
      <div class="dash-top-bar">
        <div class="dash-top-left">
          <span class="dash-top-greeting">Welcome back, <strong><?php echo e($user['name'] ?? 'Creator'); ?></strong></span>
          <span class="dash-top-badge">STUDIO</span>
        </div>
        <div class="dash-top-right">
          <?php include __DIR__ . '/../includes/theme_switcher.php'; ?>
        </div>
      </div>
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

      <?php if (!empty($limitHitNow) && !$user['is_subscribed']): ?>
        <div class="alert-glass pro-banner">
          <div class="pro-banner-left">
            <div class="pro-crown-badge">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
            </div>
            <div>
              <div class="pro-banner-title">Free Plan Limit Reached</div>
              <div class="pro-banner-desc">You've reached the free tier limit of <?php echo FREE_IMAGE_LIMIT; ?> images. Upgrade to Pro for unlimited uploads &amp; high-res PDF generation.</div>
            </div>
          </div>
          <a href="upgrade" class="btn btn-primary" style="width:auto;flex-shrink:0;">
            <span>Upgrade to Pro</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><polyline points="9 18 15 12 9 6"/></svg>
          </a>
        </div>
      <?php endif; ?>

      <?php if ($imageCount === 0): ?>

        <div class="page-head">
          <div class="page-title">Portfolio Works</div>
          <div class="page-desc">Upload your design pieces — SaaqiFolio AI automatically categorizes and organizes them into an elegant showcase.</div>
        </div>

        <div class="dropzone" id="dropzone">
          <div class="dz-glow-orb"></div>
          <div class="dz-icon">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="32" height="32"><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M12 12v9"/><polyline points="8 16 12 12 16 16"/></svg>
          </div>
          <div class="dz-title">Build your creative showcase</div>
          <div class="dz-sub">Drag &amp; drop design files here, or browse from your computer. Our smart engine will organize them into Logo, UI/UX, Banner, Flyer, and more.</div>
          
          <form method="POST" action="portfolio" enctype="multipart/form-data" id="uploadFormMain">
            <input type="hidden" name="upload_images" value="1">
            <input type="hidden" name="ajax" value="1">
            <input type="file" id="fileInputMain" name="images[]" multiple accept="image/*,.jpg,.jpeg,.png,.webp,.gif,.jfif,.avif,.svg,.bmp,.ico" hidden>
            <div class="dz-browse-row">
              <button type="button" class="btn btn-primary" data-browse-btn onclick="event.preventDefault(); document.getElementById('fileInputMain').click();" style="width:auto;padding:12px 24px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span>Browse Files</span>
              </button>
              <button type="button" class="btn-behance-featured" data-no-dropzone-click="1" onclick="event.stopPropagation(); event.preventDefault(); openBehanceImportModal();" style="padding:11px 22px;font-size:14px;">
                <svg viewBox="0 0 24 24" fill="currentColor" width="17" height="17"><path d="M22 7h-7v-2h7v2zm1.726 10c-.442 1.297-2.029 3-5.101 3-4.356 0-5.746-3.081-5.746-5.918 0-3.327 1.884-6.082 5.753-6.082 4.093 0 5.282 3.013 4.966 6.136h-7.669c.074 1.705.952 2.824 2.83 2.824 1.488 0 2.228-.696 2.617-1.488l2.35.528zm-4.992-4.832c-.067-1.121-.692-2.128-2.316-2.128-1.503 0-2.296 1.007-2.42 2.128h4.736zm-11.734-7.168h4.59c1.944 0 3.41.486 3.41 2.378 0 1.258-.707 1.954-1.636 2.254 1.343.434 2.052 1.439 2.052 2.766 0 2.146-1.748 2.602-3.824 2.602h-4.592v-10zm2.748 3.972h1.611c.783 0 1.505-.125 1.505-1.07 0-.82-.577-.962-1.396-.962h-1.72v2.032zm0 4.068h1.838c.969 0 1.758-.154 1.758-1.229 0-.962-.738-1.122-1.654-1.122h-1.942v2.351z"/></svg>
                <span>Import from Behance</span>
                <span class="behance-sparkle-pill">AI</span>
              </button>
              <span class="dz-or">or drag &amp; drop anywhere here</span>
            </div>
            <div class="dz-file-types">Supports JPG, PNG, WEBP, GIF, SVG, JFIF, AVIF (Max 30MB each)</div>
          </form>
        </div>

      <?php else: ?>

        <div class="portfolio-head">
          <div class="portfolio-title-col">
            <div class="page-title">Portfolio Works</div>
            <div class="portfolio-meta-tags">
              <span class="meta-tag-pill"><?php echo $imageCount; ?> piece<?php echo $imageCount !== 1 ? 's' : ''; ?></span>
              <span class="meta-tag-divider">•</span>
              <span class="meta-tag-sub">AI Categorized</span>
            </div>
          </div>
          
          <div class="portfolio-head-actions">
            <?php if (!$atLimit): ?>
              <form method="POST" action="portfolio" enctype="multipart/form-data" id="uploadFormAdd" style="display:inline;">
                <input type="hidden" name="upload_images" value="1">
                <input type="hidden" name="ajax" value="1">
                <input type="file" id="fileInputAdd" name="images[]" multiple accept="image/*,.jpg,.jpeg,.png,.webp,.gif,.jfif,.avif,.svg,.bmp,.ico" hidden>
                <div class="mini-dropzone" id="dropzoneAdd">
                  <button type="button" data-browse-btn onclick="event.preventDefault(); document.getElementById('fileInputAdd').click();" class="btn btn-primary btn-sm">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    <span>Add Images</span>
                  </button>
                  <span class="mini-dropzone-text">or drop here</span>
                </div>
              </form>
              <button type="button" class="btn-behance-featured" data-no-dropzone-click="1" onclick="event.stopPropagation(); event.preventDefault(); openBehanceImportModal();" style="padding:6px 14px;font-size:12.5px;">
                <svg viewBox="0 0 24 24" fill="currentColor" width="14" height="14"><path d="M22 7h-7v-2h7v2zm1.726 10c-.442 1.297-2.029 3-5.101 3-4.356 0-5.746-3.081-5.746-5.918 0-3.327 1.884-6.082 5.753-6.082 4.093 0 5.282 3.013 4.966 6.136h-7.669c.074 1.705.952 2.824 2.83 2.824 1.488 0 2.228-.696 2.617-1.488l2.35.528zm-4.992-4.832c-.067-1.121-.692-2.128-2.316-2.128-1.503 0-2.296 1.007-2.42 2.128h4.736zm-11.734-7.168h4.59c1.944 0 3.41.486 3.41 2.378 0 1.258-.707 1.954-1.636 2.254 1.343.434 2.052 1.439 2.052 2.766 0 2.146-1.748 2.602-3.824 2.602h-4.592v-10zm2.748 3.972h1.611c.783 0 1.505-.125 1.505-1.07 0-.82-.577-.962-1.396-.962h-1.72v2.032zm0 4.068h1.838c.969 0 1.758-.154 1.758-1.229 0-.962-.738-1.122-1.654-1.122h-1.942v2.351z"/></svg>
                <span>Import from Behance</span>
                <span class="behance-sparkle-pill">AI</span>
              </button>
            <?php else: ?>
              <a href="upgrade" class="btn btn-primary btn-sm" style="width:auto;text-decoration:none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
                <span>Upgrade for Unlimited</span>
              </a>
            <?php endif; ?>
            <?php if ($canExportPdf): ?>
              <button type="button" class="btn btn-ghost btn-sm" onclick="openPdfExportModal()" style="width:auto;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <span>Export Your Portfolio<?php echo $remPdf; ?></span>
              </button>
            <?php else: ?>
              <a href="upgrade" class="btn btn-ghost btn-sm text-accent" style="width:auto;text-decoration:none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
                <span>Export Your Portfolio (Upgrade)</span>
              </a>
            <?php endif; ?>

            <button type="button" class="btn btn-ghost btn-sm" onclick="toggleBulk()" id="bulkToggleBtn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="m9 11 3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
              <span>Bulk Select</span>
            </button>
          </div>
        </div>

        <!-- Bulk Floating Control Bar -->
        <div class="bulk-bar" id="bulkBar" style="display:none;">
          <div class="bulk-info">
            <span class="bulk-badge-num"><span id="selCount">0</span> selected</span>
            <div class="bulk-quick-links">
              <button type="button" class="bulk-link" onclick="selectAllImages()">Select all</button>
              <span class="bulk-link-sep">•</span>
              <button type="button" class="bulk-link" onclick="clearSelection()">Clear</button>
            </div>
          </div>

          <div class="bulk-actions-cluster">
            <div class="bulk-cats-wrap">
              <span class="bulk-cats-label">Move to:</span>
              <div class="bulk-cats">
                <?php foreach ($categories as $c): ?>
                  <button type="button" class="bulk-cat-btn badge-<?php echo slugify($c); ?>" onclick="submitBulk('<?php echo e($c); ?>')">
                    <span class="cat-dot-sm"></span>
                    <span><?php echo e($c); ?></span>
                  </button>
                <?php endforeach; ?>
              </div>
            </div>

            <button type="button" class="btn btn-danger-ghost btn-sm bulk-delete-btn" onclick="submitBulkDelete()">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
              <span>Delete Selected</span>
            </button>
          </div>
        </div>

        <!-- Standalone bulk forms -->
        <form method="POST" id="bulkForm" style="display:none;">
          <input type="hidden" name="bulk_set_category" value="1">
          <input type="hidden" name="bulk_category" id="bulkCategoryInput" value="">
          <div id="bulkIdsHolder"></div>
        </form>

        <form method="POST" id="bulkDeleteForm" style="display:none;">
          <input type="hidden" name="bulk_delete_images" value="1">
          <div id="bulkDeleteIdsHolder"></div>
        </form>

        <!-- Segmented Category Filter Tabs -->
        <div class="tabs-segmented">
          <a href="?category=All" class="tab-item <?php echo $activeCategory === 'All' ? 'active' : ''; ?>">
            <span class="tab-label">All</span>
            <span class="tab-count"><?php echo $imageCount; ?></span>
          </a>
          <?php foreach ($categories as $c): if ($counts[$c] === 0) continue; ?>
            <a href="?category=<?php echo urlencode($c); ?>" class="tab-item <?php echo $activeCategory === $c ? 'active' : ''; ?>">
              <span class="tab-label"><?php echo e($c); ?></span>
              <span class="tab-count"><?php echo $counts[$c]; ?></span>
            </a>
          <?php endforeach; ?>
        </div>

        <?php
        $displayImages = $activeCategory === 'All' ? $allImages : array_filter($allImages, fn($i) => $i['category'] === $activeCategory);
        if (empty($displayImages)):
        ?>
          <div class="empty-glass-box">
            <div class="empty-icon">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>
            </div>
            <div class="empty-title">No images in this category</div>
            <div class="empty-desc">Upload new designs or move existing pieces into this section.</div>
          </div>
        <?php else: ?>

          <?php if ($activeCategory === 'All'): ?>
            <?php foreach ($categories as $cat):
              $imgs = array_values(array_filter($allImages, fn($i) => $i['category'] === $cat));
              if (empty($imgs)) continue;
            ?>
              <section class="cat-section" data-cat="<?php echo e($cat); ?>">
                <div class="cat-section-header">
                  <div class="cat-title-wrap">
                    <span class="cat-bullet cat-bullet-<?php echo slugify($cat); ?>"></span>
                    <h3 class="cat-section-title"><?php echo e($cat); ?></h3>
                    <span class="cat-section-count"><?php echo count($imgs); ?></span>
                  </div>
                  <span class="cat-reorder-hint" title="Drag and drop cards to change arrangement in this category">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="m3 9 4-4 4 4M7 5v14M21 15l-4 4-4-4M17 19V5"/></svg>
                    Drag to reorder
                  </span>
                </div>
                <div class="grid sortable-grid" data-category="<?php echo e($cat); ?>">
                  <?php foreach ($imgs as $img): include __DIR__ . '/_image_card.php'; endforeach; ?>
                </div>
              </section>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="grid sortable-grid" data-category="<?php echo e($activeCategory); ?>">
              <?php foreach ($displayImages as $img): include __DIR__ . '/_image_card.php'; endforeach; ?>
            </div>
          <?php endif; ?>

        <?php endif; ?>

      <?php endif; ?>
    </div>
  </main>
</div>

<!-- Full-Page Drag & Drop Floating Overlay -->
<div class="page-drop-overlay" id="pageDropOverlay" hidden>
  <div class="page-drop-box">
    <div class="page-drop-glow"></div>
    <div class="page-drop-icon">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="38" height="38"><path d="M4 14.899A7 7 0 1 1 15.71 8h1.79a4.5 4.5 0 0 1 2.5 8.242"/><path d="M12 12v9"/><polyline points="8 16 12 12 16 16"/></svg>
    </div>
    <div class="page-drop-title">Drop images anywhere to upload</div>
    <div class="page-drop-sub">Release your files here. Our smart AI engine will automatically analyze and organize them into your portfolio categories.</div>
  </div>
</div>

<!-- Upload Progress Loader Modal -->
<div class="upload-loader-overlay" id="uploadLoader" hidden>
  <div class="upload-loader-box">
    <div class="upload-spinner-wrap">
      <svg class="upload-ring" viewBox="0 0 80 80">
        <defs>
          <linearGradient id="ringGrad" x1="0%" y1="0%" x2="100%" y2="100%">
            <stop offset="0%" stop-color="#8b5cf6"/>
            <stop offset="50%" stop-color="#ec4899"/>
            <stop offset="100%" stop-color="#f43f5e"/>
          </linearGradient>
        </defs>
        <circle class="upload-ring-bg" cx="40" cy="40" r="34"></circle>
        <circle class="upload-ring-fg" cx="40" cy="40" r="34"></circle>
      </svg>
      <div class="upload-spinner-center">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/></svg>
      </div>
    </div>
    <div class="upload-loader-title" id="uploadLoaderTitle">Processing &amp; AI Classification…</div>
    <div class="upload-loader-sub">Analyzing artwork style and sorting into categories. Please keep this tab open.</div>
    <div class="upload-loader-timer" id="uploadTimer">0s</div>
  </div>
</div>

<script>
let bulkMode = false;
function toggleBulk(){
  bulkMode = !bulkMode;
  const bar = document.getElementById('bulkBar');
  const btn = document.getElementById('bulkToggleBtn');
  if (bar) bar.style.display = bulkMode ? 'flex' : 'none';
  if (btn) btn.classList.toggle('active', bulkMode);
  document.querySelectorAll('.img-select-box').forEach(el => el.style.display = bulkMode ? 'block' : 'none');
  if(!bulkMode) clearSelection();
}
function selectAllImages(){
  document.querySelectorAll('.img-select-cb').forEach(cb => {
    cb.checked = true;
    const card = cb.closest('.card');
    if (card) card.classList.add('selected');
  });
  updateSelCount();
}
function clearSelection(){
  document.querySelectorAll('.img-select-cb').forEach(cb => {
    cb.checked = false;
    const card = cb.closest('.card');
    if (card) card.classList.remove('selected');
  });
  updateSelCount();
}
function updateSelCount(){
  const checked = document.querySelectorAll('.img-select-cb:checked');
  const cnt = document.getElementById('selCount');
  if (cnt) cnt.textContent = checked.length;
}
function submitBulk(category){
  const checked = document.querySelectorAll('.img-select-cb:checked');
  if(checked.length === 0){ alert('Please select at least one image first.'); return; }
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
function submitBulkDelete(){
  const checked = document.querySelectorAll('.img-select-cb:checked');
  if(checked.length === 0){ alert('Please select at least one image to delete.'); return; }
  if(!confirm(`Are you sure you want to permanently delete ${checked.length} selected image(s)? This action cannot be undone.`)) {
    return;
  }
  const holder = document.getElementById('bulkDeleteIdsHolder');
  holder.innerHTML = '';
  checked.forEach(cb => {
    const inp = document.createElement('input');
    inp.type = 'hidden';
    inp.name = 'selected_ids[]';
    inp.value = cb.dataset.id;
    holder.appendChild(inp);
  });
  document.getElementById('bulkDeleteForm').submit();
}
document.addEventListener('change', (e) => {
  if(e.target.classList.contains('img-select-cb')) {
    const card = e.target.closest('.card');
    if (card) card.classList.toggle('selected', e.target.checked);
    updateSelCount();
  }
});

// Dropzone wiring
function initPortfolioDropzones() {
  const loaderEls = {
    overlay: document.getElementById('uploadLoader'),
    timerEl: document.getElementById('uploadTimer'),
    titleEl: document.getElementById('uploadLoaderTitle'),
  };

  const mainZone = document.getElementById('dropzone');
  const mainInput = document.getElementById('fileInputMain');
  const mainForm = document.getElementById('uploadFormMain');
  if (mainZone && mainInput && mainForm) {
    if (typeof wireDropzone === 'function') {
      wireDropzone(mainZone, mainInput, files => ajaxUploadFiles(mainForm, mainInput, files, loaderEls));
    }
  }

  const addZone = document.getElementById('dropzoneAdd');
  const addInput = document.getElementById('fileInputAdd');
  const addForm = document.getElementById('uploadFormAdd');
  if (addZone && addInput && addForm) {
    if (typeof wireDropzone === 'function') {
      wireDropzone(addZone, addInput, files => ajaxUploadFiles(addForm, addInput, files, loaderEls));
    }
  }

  // Global Page-Wide Drag & Drop
  const pageDropOverlay = document.getElementById('pageDropOverlay');
  const activeForm = addForm || mainForm;
  const activeInput = addInput || mainInput;
  if (pageDropOverlay && activeForm && activeInput) {
    if (typeof initGlobalPageDrop === 'function') {
      initGlobalPageDrop(pageDropOverlay, activeForm, activeInput, loaderEls);
    }
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initPortfolioDropzones);
} else {
  initPortfolioDropzones();
}

// Sortable Category Grids
function initSortableGrids() {
  const grids = document.querySelectorAll('.sortable-grid:not([data-sortable-ready])');
  grids.forEach(grid => {
    grid.setAttribute('data-sortable-ready', '1');
    let draggedCard = null;
    let initialIndex = -1;

    grid.addEventListener('dragstart', (e) => {
      const card = e.target.closest('.card');
      if (!card || bulkMode) return;
      // Don't drag if clicking category dropdown or checkbox
      if (e.target.closest('.cat-dropdown-wrap') || e.target.closest('.img-select-box') || e.target.closest('button, input, a')) {
        e.preventDefault();
        return;
      }
      draggedCard = card;
      initialIndex = Array.from(grid.querySelectorAll('.card')).indexOf(card);
      grid.classList.add('is-sorting');

      setTimeout(() => {
        if (draggedCard) draggedCard.classList.add('is-dragging');
      }, 0);

      if (e.dataTransfer) {
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', card.dataset.imgId || '');
      }
    });

    grid.addEventListener('dragend', () => {
      if (!draggedCard) return;
      grid.classList.remove('is-sorting');
      draggedCard.classList.remove('is-dragging');
      const cardsNow = Array.from(grid.querySelectorAll('.card'));
      const finalIndex = cardsNow.indexOf(draggedCard);
      if (finalIndex !== -1 && finalIndex !== initialIndex) {
        saveCategoryOrder(grid);
      }
      draggedCard = null;
      initialIndex = -1;
    });

    grid.addEventListener('dragover', (e) => {
      e.preventDefault();
      if (!draggedCard || bulkMode) return;

      const targetCard = e.target.closest('.card');
      if (!targetCard || targetCard === draggedCard || targetCard.parentElement !== grid) return;

      const rect = targetCard.getBoundingClientRect();
      const allCards = Array.from(grid.querySelectorAll('.card'));
      const draggedIdx = allCards.indexOf(draggedCard);
      const targetIdx = allCards.indexOf(targetCard);
      if (draggedIdx === -1 || targetIdx === -1) return;

      // Stable directional hysteresis prevents jitter/oscillation:
      if (draggedIdx < targetIdx) {
        // Dragging right/down: only swap when mouse crosses into target's right/bottom zone
        const isPastThreshold = (e.clientX > rect.left + rect.width * 0.4) || (e.clientY > rect.top + rect.height * 0.5);
        if (isPastThreshold) {
          grid.insertBefore(draggedCard, targetCard.nextSibling);
        }
      } else if (draggedIdx > targetIdx) {
        // Dragging left/up: only swap when mouse is before target's left/top zone
        const isBeforeThreshold = (e.clientX < rect.left + rect.width * 0.6) || (e.clientY < rect.top + rect.height * 0.5);
        if (isBeforeThreshold) {
          grid.insertBefore(draggedCard, targetCard);
        }
      }
    });
  });
}

function saveCategoryOrder(grid) {
  const category = grid.dataset.category || '';
  const cards = grid.querySelectorAll('.card[data-img-id]');
  const order = Array.from(cards).map(c => parseInt(c.dataset.imgId, 10)).filter(id => !isNaN(id));
  if (!order.length) return;

  const formData = new FormData();
  formData.append('action', 'reorder_images');
  formData.append('category', category);
  order.forEach(id => formData.append('order[]', id));

  fetch('portfolio', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      if (typeof showToast === 'function') {
        showToast('Arrangement saved in ' + (category || 'category') + '!', 'success', 2200);
      }
    } else {
      if (typeof showToast === 'function') {
        showToast(data.error || 'Could not save order', 'danger', 3000);
      }
    }
  })
  .catch(err => {
    console.error(err);
    if (typeof showToast === 'function') {
      showToast('Error saving arrangement', 'danger', 3000);
    }
  });
}

document.addEventListener('DOMContentLoaded', initSortableGrids);
initSortableGrids();
</script>
<?php
$pdfModalBaseUrl = 'download_pdf';
$pdfTotalImages = count($allImages ?? []);
$pdfCategoriesCount = [];
foreach (($allImages ?? []) as $img) {
    $c = $img['category'] ?? 'Other';
    $pdfCategoriesCount[$c] = ($pdfCategoriesCount[$c] ?? 0) + 1;
}
include __DIR__ . '/../includes/pdf_export_modal.php';
?>
<?php include __DIR__ . '/../includes/image_lightbox.php'; ?>
<?php include __DIR__ . '/../includes/tutorial_video_modal.php'; ?>
<?php include __DIR__ . '/../includes/behance_import_modal.php'; ?>
<?php include __DIR__ . '/../includes/whatsapp_btn.php'; ?>
</body>
</html>
