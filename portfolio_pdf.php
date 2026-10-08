<?php
/**
 * SAAQIFOLIO - Download Full Portfolio as PDF (Public & Client View)
 * Generates an executive, luxury obsidian-glass styled PDF proposal.
 */
ob_start();
@ini_set('memory_limit', '512M');
@set_time_limit(180);

session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/simple_pdf.php';

$slug = clean($conn, $_GET['slug'] ?? '');

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE public_slug = ?");
mysqli_stmt_bind_param($stmt, 's', $slug);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$user) {
    ob_end_clean();
    http_response_code(404);
    die('Portfolio not found.');
}

$isPreview = isset($_GET['preview']) && $_GET['preview'] === '1';

if (!$isPreview && !can_download_pdf($user)) {
    ob_end_clean();
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
           || (isset($_GET['ajax']) && $_GET['ajax'] === '1')
           || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));
    if ($isAjax) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'limit_reached' => true,
            'remaining' => 0,
            'redirect' => 'p.php?slug=' . urlencode($slug) . '&limit=1',
            'error' => 'This creator has reached the free PDF download limit.'
        ]);
        exit;
    }
    header('Location: p.php?slug=' . urlencode($slug) . '&limit=1');
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

$userId = (int) $user['id'];
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
    $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
           || (isset($_GET['ajax']) && $_GET['ajax'] === '1')
           || (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'));
    if ($isAjax) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'This selection has no images yet. Please upload artwork before generating a PDF.']);
        exit;
    }
    die('This selection has no images yet. PDF cannot be generated.');
}

$skills = skills_to_array($user['skills']);

// Build the Luxury Editorial PDF with chosen template
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

// Count download towards trial limit if not subscribed and not a preview request
$newRemaining = 'unlimited';
if (!$isPreview && !$user['is_subscribed']) {
    increment_pdf_downloads($conn, $userId);
    $newCount = (int)($user['pdf_downloads_count'] ?? 0) + 1;
    $newRemaining = (string)max(0, FREE_PDF_LIMIT - $newCount);
}

header('Access-Control-Expose-Headers: Content-Disposition, X-Pdf-Remaining, X-Pdf-Limit-Reached, X-Pdf-Subscribed');
header('X-Pdf-Remaining: ' . $newRemaining);
header('X-Pdf-Limit-Reached: ' . (($newRemaining !== 'unlimited' && (int)$newRemaining <= 0) ? '1' : '0'));
header('X-Pdf-Subscribed: ' . ($user['is_subscribed'] ? '1' : '0'));

$catSuffix = !empty($selectedCats) ? '-' . slugify(implode('-', $selectedCats)) : '';
$fileSlug = slugify($user['name'] ?: $slug ?: 'portfolio');
$pdf->output($fileSlug . $catSuffix . '-portfolio.pdf', $isPreview ? 'inline' : 'attachment');
