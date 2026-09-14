<?php
/**
 * FOLIVO - Download Full Portfolio as PDF
 * Builds a client-ready, category-wise PDF of a designer's portfolio using Dompdf.
 */
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$slug = clean($conn, $_GET['slug'] ?? '');

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE public_slug = ?");
mysqli_stmt_bind_param($stmt, 's', $slug);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$user) {
    http_response_code(404);
    die('Profile not found.');
}

$imgRes = mysqli_query($conn, "SELECT * FROM portfolio_images WHERE user_id=" . (int) $user['id'] . " ORDER BY id DESC");
$allImages = [];
while ($row = mysqli_fetch_assoc($imgRes)) $allImages[] = $row;

if (empty($allImages)) {
    die('Is portfolio mein abhi koi images nahi hain, PDF generate nahi ho sakti.');
}

$categories = get_categories();
$skills = skills_to_array($user['skills']);
$portfolioDir = __DIR__ . '/assets/uploads/portfolio/';
$avatarPath = $user['avatar'] ? __DIR__ . '/assets/uploads/avatars/' . $user['avatar'] : null;

// Convert a local image file to a base64 data URI so Dompdf embeds it reliably.
function img_data_uri($path) {
    if (!$path || !is_file($path)) return null;
    $mime = @mime_content_type($path) ?: 'image/png';
    $data = @file_get_contents($path);
    if ($data === false) return null;
    return 'data:' . $mime . ';base64,' . base64_encode($data);
}

$avatarUri = $avatarPath ? img_data_uri($avatarPath) : null;

ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  @page { margin: 0; }
  * { box-sizing: border-box; }
  body {
    font-family: 'DejaVu Sans', sans-serif;
    color: #241E38;
    margin: 0;
  }

  /* ---------- Cover ---------- */
  .cover {
    padding: 42px 46px 34px;
    background: linear-gradient(135deg, #7C5CFC, #FF6B4A);
    color: #ffffff;
  }
  .cover-brand { font-size: 11px; letter-spacing: 3px; text-transform: uppercase; opacity: 0.85; margin-bottom: 26px; }
  .cover-row { }
  .avatar {
    width: 68px; height: 68px; border-radius: 50%;
    background: rgba(255,255,255,0.22);
    color: #fff; font-size: 24px; font-weight: bold; text-align: center;
    line-height: 68px;
    float: left; margin-right: 18px;
    border: 2px solid rgba(255,255,255,0.6);
  }
  .avatar img { width: 68px; height: 68px; border-radius: 50%; object-fit: cover; }
  .cover-name { font-size: 30px; font-weight: bold; margin: 4px 0 2px; }
  .cover-role { font-size: 14px; opacity: 0.92; margin-bottom: 10px; }
  .cover-contact { font-size: 10.5px; opacity: 0.92; line-height: 1.7; clear: both; padding-top: 14px; }
  .cover-contact span { margin-right: 22px; }
  .cover-bio {
    margin-top: 22px; font-size: 11px; line-height: 1.7; opacity: 0.95;
    border-top: 1px solid rgba(255,255,255,0.35); padding-top: 16px;
  }

  .summary-bar {
    background: #F4F1FF; padding: 14px 46px; font-size: 10.5px; color: #5B4FA0;
    border-bottom: 1px solid #E6E0FA;
  }
  .summary-bar b { color: #241E38; }

  .skills-wrap { padding: 16px 46px 4px; }
  .skills-title { font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: #8B84A8; margin-bottom: 8px; }
  .chip {
    display: inline-block; background: #F4F1FF; color: #6a4cf0; border: 1px solid #E6E0FA;
    padding: 4px 10px; border-radius: 20px; font-size: 9.5px; margin: 0 6px 6px 0;
  }

  /* ---------- Category sections ---------- */
  .content { padding: 4px 46px 30px; }
  .cat-section { margin-top: 26px; }
  .cat-head {
    background: linear-gradient(90deg, #7C5CFC, #FF6B4A);
    color: #fff;
    padding: 8px 14px;
    border-radius: 6px;
    font-size: 13px;
    font-weight: bold;
  }
  .cat-count { float: right; font-weight: normal; opacity: 0.9; font-size: 10.5px; }

  .grid-cell {
    display: inline-block;
    width: 48%;
    margin: 12px 1% 0 0;
    vertical-align: top;
  }
  .grid-cell img {
    width: 100%;
    height: 190px;
    object-fit: cover;
    border-radius: 6px;
    border: 1px solid #E7E3F5;
  }

  .footer {
    padding: 16px 46px; font-size: 9px; color: #A79FC7; text-align: center;
    border-top: 1px solid #EFEBFB; margin-top: 30px;
  }
</style>
</head>
<body>

  <div class="cover">
    <div class="cover-brand">Folivo &middot; Portfolio</div>
    <div class="cover-row">
      <div class="avatar">
        <?php if ($avatarUri): ?>
          <img src="<?php echo $avatarUri; ?>">
        <?php else: ?>
          <?php echo e(initials($user['name'])); ?>
        <?php endif; ?>
      </div>
      <div>
        <div class="cover-name"><?php echo e($user['name']); ?></div>
        <div class="cover-role"><?php echo e($user['profession']); ?></div>
      </div>
    </div>
    <div class="cover-contact">
      <span>&#9993; <?php echo e($user['email']); ?></span>
      <?php if ($user['phone']): ?><span>&#9742; <?php echo e($user['phone']); ?></span><?php endif; ?>
      <?php if ($user['address']): ?><span>&#128205; <?php echo e($user['address']); ?></span><?php endif; ?>
    </div>
    <?php if ($user['bio']): ?>
      <div class="cover-bio"><?php echo nl2br(e($user['bio'])); ?></div>
    <?php endif; ?>
  </div>

  <div class="summary-bar">
    <b><?php echo count($allImages); ?></b> pieces of work across
    <b><?php echo count(array_unique(array_column($allImages, 'category'))); ?></b> categories &middot;
    Generated on <?php echo date('d M Y'); ?>
  </div>

  <?php if ($skills): ?>
    <div class="skills-wrap">
      <div class="skills-title">Software Expertise</div>
      <?php foreach ($skills as $s): ?><span class="chip"><?php echo e($s); ?></span><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="content">
    <?php foreach ($categories as $cat):
      $imgs = array_values(array_filter($allImages, fn($i) => $i['category'] === $cat));
      if (empty($imgs)) continue;
    ?>
      <div class="cat-section">
        <div class="cat-head"><?php echo e($cat); ?><span class="cat-count"><?php echo count($imgs); ?> item<?php echo count($imgs) !== 1 ? 's' : ''; ?></span></div>
        <?php foreach ($imgs as $img):
          $uri = img_data_uri($portfolioDir . $img['filename']);
          if (!$uri) continue;
        ?>
          <div class="grid-cell"><img src="<?php echo $uri; ?>"></div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="footer">
    <?php echo e($user['name']); ?> &middot; <?php echo e($user['email']); ?> &middot; Made with Folivo
  </div>

</body>
</html>
<?php
$html = ob_get_clean();

$options = new Options();
$options->setChroot(__DIR__);
$options->setIsRemoteEnabled(false);
$options->setIsHtml5ParserEnabled(true);
$options->setDefaultFont('DejaVu Sans');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$fileSlug = slugify($user['name']) ?: 'portfolio';
$dompdf->stream($fileSlug . '-portfolio.pdf', ['Attachment' => true]);
exit;
