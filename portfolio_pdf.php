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

if (!can_download_pdf($user)) {
    ob_end_clean();
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
if (!in_array($requestedTpl, ['obsidian', 'atelier', 'creative'])) {
    $requestedTpl = 'obsidian';
}

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
    die('This selection has no images yet. PDF cannot be generated.');
}

$skills = skills_to_array($user['skills']);

// Build the Luxury Editorial PDF with chosen template
$pdf = new SimplePdf($requestedTpl);

$contactParts = array_filter([$user['email'], $user['phone'], $user['address']]);
$avatarPath = !empty($user['avatar']) ? resolve_upload_path('avatars', $user['avatar']) : null;

$catsWithImages = array_filter($catsToRender, function($cat) use ($allImages) {
    return !empty(array_filter($allImages, fn($i) => $i['category'] === $cat));
});

// Page 1: Cover Page
if (!empty($selectedCats)) {
    $colName = count($selectedCats) <= 2 ? implode(' & ', $selectedCats) : 'CURATED';
    $curatedDate = 'VERIFIED SAAQIFOLIO DESIGNER | ' . strtoupper($colName) . ' COLLECTION';
} else {
    $curatedDate = 'VERIFIED SAAQIFOLIO DESIGNER | CURATED ' . strtoupper(date('j M Y'));
}

$pdf->renderCoverPage(
    $user['name'],
    $user['profession'],
    $user['bio'] ?? '',
    $contactParts,
    $curatedDate,
    $avatarPath
);

// Page 2: Profile & Stats & Tools Page
$pdf->renderProfilePage(
    $user['name'],
    $user['profession'],
    $user['bio'] ?? '',
    count($allImages),
    count($catsWithImages),
    $skills,
    $contactParts,
    $user['education'] ?? '',
    $user['experience'] ?? ''
);

// Pages 3+: Category Showcase Pages
foreach ($catsToRender as $cat) {
    $imgsInCat = array_values(array_filter($allImages, fn($i) => $i['category'] === $cat));
    if (empty($imgsInCat)) continue;

    $paths = [];
    foreach ($imgsInCat as $img) {
        $p = resolve_upload_path('portfolio', $img['filename']);
        if ($p) $paths[] = $p;
    }

    if (!empty($paths)) {
        $pdf->renderCategoryShowcasePage($cat, $paths, $user['name'], $user['profession']);
    }
}

// Final Closing Page: "Let's work together"
$pdf->renderClosingPage($user['name'], $user['profession'], $contactParts);

// Count download towards trial limit if not subscribed
if (!$user['is_subscribed']) {
    increment_pdf_downloads($conn, $userId);
}

$catSuffix = !empty($selectedCats) ? '-' . slugify(implode('-', $selectedCats)) : '';
$fileSlug = slugify($user['name'] ?: $slug ?: 'portfolio');
$pdf->output($fileSlug . $catSuffix . '-portfolio.pdf');
