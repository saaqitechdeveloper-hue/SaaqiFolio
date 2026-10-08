<?php
/**
 * SAAQIFOLIO - Ultra-Lightweight Reactive Status Endpoint
 * Returns real-time user quota, subscription, image count & PDF limit in <0.5ms.
 */
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');

$userId = (int)($_SESSION['user_id'] ?? 0);
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthenticated']);
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id, is_subscribed, pdf_downloads_count FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$user) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}

$imgRes = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM portfolio_images WHERE user_id = $userId");
$totalImages = (int)mysqli_fetch_assoc($imgRes)['cnt'];

$isSubscribed = !empty($user['is_subscribed']);
$downloadsCount = (int)($user['pdf_downloads_count'] ?? 0);
$remainingPdf = $isSubscribed ? 'unlimited' : max(0, FREE_PDF_LIMIT - $downloadsCount);
$canExportPdf = $isSubscribed || ($downloadsCount < FREE_PDF_LIMIT);
$atImageLimit = !$isSubscribed && ($totalImages >= FREE_IMAGE_LIMIT);

$catRes = mysqli_query($conn, "SELECT category, COUNT(*) as cnt FROM portfolio_images WHERE user_id = $userId GROUP BY category");
$catCounts = [];
while ($cr = mysqli_fetch_assoc($catRes)) {
    $catCounts[$cr['category']] = (int)$cr['cnt'];
}

echo json_encode([
    'success' => true,
    'is_subscribed' => $isSubscribed,
    'total_images' => $totalImages,
    'downloads_count' => $downloadsCount,
    'remaining_pdf' => $remainingPdf,
    'can_export_pdf' => $canExportPdf,
    'at_image_limit' => $atImageLimit,
    'free_pdf_limit' => FREE_PDF_LIMIT,
    'free_image_limit' => FREE_IMAGE_LIMIT,
    'categories' => $catCounts
]);
