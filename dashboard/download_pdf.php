<?php
/**
 * SAAQIFOLIO - Portfolio PDF Download
 *
 * Supports category scoping and 3 luxury design templates:
 * - obsidian: Obsidian Noir (Signature Luxury Dark)
 * - atelier: Minimalist Atelier (Editorial Light Gallery)
 * - creative: Creative Studio (Vibrant Modern Duo)
 */
ob_start(); // guard against any stray output corrupting the binary PDF
@ini_set('memory_limit', '512M');
@set_time_limit(180);

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/simple_pdf.php';

$userId = (int) $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

$isPreview = isset($_GET['preview']) && $_GET['preview'] === '1';

if (!$isPreview && !can_download_pdf($user)) {
    ob_end_clean();
    header('Location: upgrade.php?pdf_limit=1');
    exit;
}

$categories = get_categories();
$rawCats = $_GET['categories'] ?? $_GET['category'] ?? 'all';
$selectedCats = [];

if (is_array($rawCats)) {
    $selectedCats = array_values(array_intersect(array_map('trim', $rawCats), $categories));
} elseif ($rawCats !== 'all' && !empty($rawCats)) {
    $split = array_map('trim', explode(',', $rawCats));
    $selectedCats = array_values(array_intersect($split, $categories));
}

$requestedTpl = clean($conn, $_GET['template'] ?? 'obsidian');
if (!in_array($requestedTpl, ['obsidian', 'cyber', 'swiss', 'atelier', 'creative'])) {
    $requestedTpl = 'obsidian';
}

$themeMode = clean($conn, $_GET['theme_mode'] ?? $_GET['mode'] ?? 'dark');
if (!in_array($themeMode, ['dark', 'light'])) {
    $themeMode = 'dark';
}

$accentColor = clean($conn, $_GET['accent'] ?? '');

// ---------- Gather portfolio data ----------
if (!empty($selectedCats)) {
    $placeholders = implode(',', array_fill(0, count($selectedCats), '?'));
    $types = 'i' . str_repeat('s', count($selectedCats));
    $params = array_merge([$userId], $selectedCats);
    $imgStmt = mysqli_prepare($conn, "SELECT * FROM portfolio_images WHERE user_id=? AND category IN ($placeholders) ORDER BY sort_order ASC, id DESC");
    mysqli_stmt_bind_param($imgStmt, $types, ...$params);
    mysqli_stmt_execute($imgStmt);
    $imgRes = mysqli_stmt_get_result($imgStmt);
    $catsToRender = $selectedCats;
} else {
    $imgRes = mysqli_query($conn, "SELECT * FROM portfolio_images WHERE user_id=$userId ORDER BY sort_order ASC, id DESC");
    $catsToRender = $categories;
}

$allImages = [];
while ($row = mysqli_fetch_assoc($imgRes)) $allImages[] = $row;

if (empty($allImages)) {
    ob_end_clean();
    die('This selection has no images yet. PDF cannot be generated.');
}

$skills = skills_to_array($user['skills']);

// ---------- Build the PDF with Selected Template ----------
$pdf = new SimplePdf($requestedTpl, $themeMode, $accentColor, $isPreview);

$contactParts = array_filter([$user['email'], $user['phone'], $user['address']]);
$avatarPath = !empty($user['avatar']) ? resolve_upload_path('avatars', $user['avatar']) : null;

$catsWithImages = array_filter($catsToRender, function($cat) use ($allImages) {
    return !empty(array_filter($allImages, fn($i) => $i['category'] === $cat));
});

if (!empty($selectedCats)) {
    $colName = count($selectedCats) <= 2 ? implode(' & ', $selectedCats) : 'CURATED';
    $curatedDate = 'VERIFIED SAAQIFOLIO DESIGNER | ' . strtoupper($colName) . ' COLLECTION';
} else {
    $curatedDate = 'VERIFIED SAAQIFOLIO DESIGNER | CURATED ' . strtoupper(date('j M Y'));
}

// Master Render: dispatches to Swiss Editorial, Cyber Minimalist, or Obsidian Noir
$pdf->renderPortfolioDocument(
    $user,
    $allImages,
    $catsWithImages,
    $skills,
    $contactParts,
    $avatarPath,
    $curatedDate,
    $isPreview
);

// Count this download against the trial limit (Pro accounts are unaffected by the check above).
if (!$isPreview && !$user['is_subscribed']) {
    increment_pdf_downloads($conn, $userId);
}

$scopeSuffix = !empty($selectedCats) ? '-' . slugify(implode('-', $selectedCats)) : '';
$filename = slugify($user['name']) . $scopeSuffix . '-portfolio.pdf';
$pdf->output($filename, $isPreview ? 'inline' : 'attachment');
