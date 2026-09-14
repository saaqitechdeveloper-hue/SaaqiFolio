<?php
/**
 * FOLIVO - Portfolio PDF Download
 *
 * Uses includes/simple_pdf.php — a small PDF writer built into this app
 * itself, so there is NOTHING to install (no Composer, no dompdf). It
 * only needs the GD extension for embedding images, which is enabled by
 * default on almost every PHP install (including XAMPP).
 */
ob_start(); // guard against any stray output corrupting the binary PDF

require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/simple_pdf.php';

$userId = (int) $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!can_download_pdf($user)) {
    ob_end_clean();
    header('Location: upgrade.php?pdf_limit=1');
    exit;
}

// ---------- Gather portfolio data ----------
$imgRes = mysqli_query($conn, "SELECT * FROM portfolio_images WHERE user_id=$userId ORDER BY id DESC");
$allImages = [];
while ($row = mysqli_fetch_assoc($imgRes)) $allImages[] = $row;

$categories = get_categories();
$skills = skills_to_array($user['skills']);

// ---------- Build the PDF ----------
$pdf = new SimplePdf();

$contactParts = array_filter([$user['email'], $user['phone']]);
$avatarPath = $user['avatar'] ? realpath(__DIR__ . '/../assets/uploads/avatars/' . $user['avatar']) : null;
// Folivo's own brand accent color (matches the app's UI, converted to 0-1 RGB for the PDF)
$brandColor = [0.486, 0.361, 0.988]; // #7C5CFC
$pdf->addCoverBanner($user['name'], $user['profession'], implode('   |   ', $contactParts), $avatarPath ?: null, $brandColor);

if ($user['bio']) {
    $pdf->addSectionTitle('About');
    $pdf->addText($user['bio']);
    $pdf->addSpacer(6);
}

if ($user['education']) {
    $pdf->addSectionTitle('Education');
    $pdf->addText($user['education']);
    $pdf->addSpacer(6);
}

if ($user['experience']) {
    $pdf->addSectionTitle('Experience');
    $pdf->addText($user['experience']);
    $pdf->addSpacer(6);
}

if ($skills) {
    $pdf->addSectionTitle('Software Expertise');
    $pdf->addText(implode('   •   ', $skills), 10.5, false, [0.35, 0.33, 0.4]);
    $pdf->addSpacer(6);
}

// How many images per row, by category — matches how each category looks
// best (logos are small/square so more fit per row; banners are wide so
// fewer fit per row without getting too small to see).
$colsByCategory = [
    'Logo' => 4,
    'UI/UX' => 3,
    'Banner' => 2,
];

if ($allImages) {
    $pdf->addSectionTitle('Portfolio');
    foreach ($categories as $cat) {
        $imgsInCat = array_values(array_filter($allImages, fn($i) => $i['category'] === $cat));
        if (empty($imgsInCat)) continue;

        $pdf->addText(strtoupper($cat) . '  (' . count($imgsInCat) . ')', 10.5, true, [0.3, 0.28, 0.35]);
        $pdf->addSpacer(4);

        $paths = [];
        foreach ($imgsInCat as $img) {
            $p = realpath(__DIR__ . '/../assets/uploads/portfolio/' . $img['filename']);
            if ($p) $paths[] = $p;
        }
        $pdf->addImageGrid($paths, $colsByCategory[$cat] ?? 3);
        $pdf->addSpacer(10);
    }
}

// Count this download against the trial limit (Pro accounts are unaffected by the check above).
if (!$user['is_subscribed']) {
    increment_pdf_downloads($conn, $userId);
}

$filename = slugify($user['name']) . '-portfolio.pdf';
$pdf->output($filename);
