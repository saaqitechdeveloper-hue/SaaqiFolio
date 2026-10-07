<?php
/**
 * SaaqiFolio - Global Open Graph (OG) & Twitter Card Meta Tags
 * Provides modern rich social sharing previews across Facebook, WhatsApp, LinkedIn, Twitter/X, Discord, Slack.
 */

if (!isset($ogTitle)) {
    $ogTitle = 'SaaqiFolio — Build Your Creative Portfolio in Minutes';
}
if (!isset($ogDescription)) {
    $ogDescription = 'Upload your work, organize with smart categories, and share a sleek, professional link with clients worldwide.';
}

$ogAppUrl = defined('APP_URL') ? rtrim(APP_URL, '/') : 'https://saaqifolio.com';

if (!isset($ogImage) || empty($ogImage)) {
    $ogImage = $ogAppUrl . '/assets/og-image.jpg';
} elseif (!str_starts_with($ogImage, 'http://') && !str_starts_with($ogImage, 'https://')) {
    $ogImage = $ogAppUrl . '/' . ltrim($ogImage, '/');
}

if (!isset($ogUrl) || empty($ogUrl)) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $reqUri = $_SERVER['REQUEST_URI'] ?? '/';
    if (!empty($host)) {
        $ogUrl = $scheme . '://' . $host . $reqUri;
    } else {
        $ogUrl = $ogAppUrl . $reqUri;
    }
}
?>
<!-- Primary Meta & Search Engine Tags -->
<meta name="description" content="<?php echo htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8'); ?>">

<!-- Open Graph / Facebook / WhatsApp / LinkedIn -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="SaaqiFolio">
<meta property="og:url" content="<?php echo htmlspecialchars($ogUrl, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:title" content="<?php echo htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:description" content="<?php echo htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:image:secure_url" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="SaaqiFolio — Build your creative portfolio in minutes">

<!-- Twitter / X Cards -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:url" content="<?php echo htmlspecialchars($ogUrl, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:title" content="<?php echo htmlspecialchars($ogTitle, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:description" content="<?php echo htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:image" content="<?php echo htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8'); ?>">
<meta name="twitter:image:alt" content="SaaqiFolio — Build your creative portfolio in minutes">
