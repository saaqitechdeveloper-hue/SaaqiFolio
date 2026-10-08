<?php
require_once __DIR__ . '/../includes/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = (int) $_SESSION['user_id'];
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

$error = '';
$gatewayFields = null;
$gatewayUrl = null;

// ---------- POST: Start a payment ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay_with'])) {
    $gateway = $_POST['pay_with'] === 'easypaisa' ? 'Easypaisa' : 'JazzCash';
    $txnRef = generate_txn_ref();
    create_pending_payment($conn, $userId, $gateway, $txnRef, PRO_PRICE);

    if ($gateway === 'JazzCash') {
        $gatewayFields = jazzcash_build_fields($txnRef, PRO_PRICE);
        $gatewayUrl = JAZZCASH_API_URL;
    } else {
        $gatewayFields = easypaisa_build_fields($txnRef, PRO_PRICE);
        $gatewayUrl = EASYPAISA_API_URL;
    }
}

$activeNav = 'upgrade';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upgrade to Pro - SaaqiFolio</title>
<?php
$ogTitle = 'Upgrade to SaaqiFolio Pro';
$ogDescription = 'Unlock unlimited portfolio uploads, custom branding, and instant PDF client exports.';
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
  <?php
    $imgCountRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM portfolio_images WHERE user_id=$userId");
    $imageCount = mysqli_fetch_assoc($imgCountRes)['cnt'];
    include __DIR__ . '/../includes/sidebar.php';
  ?>

  <main class="main">
    <div class="page-wrap">
      <!-- Top Dashboard Header / Switcher Bar -->
      <div class="dash-top-bar">
        <div class="dash-top-left">
          <span class="dash-top-greeting">Welcome back, <strong><?php echo e($user['name'] ?? 'Creator'); ?></strong></span>
          <span class="dash-top-badge">MEMBERSHIP</span>
        </div>
        <div class="dash-top-right">
          <?php include __DIR__ . '/../includes/theme_switcher.php'; ?>
        </div>
      </div>
      <div class="page-head">
        <div class="page-title">Elevate to <span class="grad-text">SaaqiFolio Pro</span></div>
        <div class="page-desc">Supercharge your design career with unlimited portfolio storage, custom branding, and instant PDF client proposals.</div>
      </div>

      <?php if ($error): ?>
        <div class="alert-glass danger">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span><?php echo e($error); ?></span>
        </div>
      <?php endif; ?>

      <?php if ($gatewayFields): ?>
        <div class="glass-panel" style="max-width:520px;margin:30px auto;text-align:center;padding:48px 32px;">
          <div class="upload-spinner-wrap" style="margin:0 auto 20px;">
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
          </div>
          <h3 style="font-family:var(--font-display);font-size:18px;margin-bottom:8px;">Connecting to Secure Gateway…</h3>
          <p class="muted" style="font-size:13.5px;line-height:1.6;">Redirecting you to <?php echo e($_POST['pay_with'] === 'easypaisa' ? 'Easypaisa' : 'JazzCash'); ?> to complete your transaction securely.</p>
          <form id="gatewayForm" method="POST" action="<?php echo e($gatewayUrl); ?>">
            <?php foreach ($gatewayFields as $key => $value): ?>
              <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
            <?php endforeach; ?>
          </form>
        </div>
        <script>document.getElementById('gatewayForm').submit();</script>

      <?php else: ?>

        <!-- Pricing Cards -->
        <div class="pricing-grid-modern">
          
          <!-- Free / Trial Tier -->
          <div class="pricing-glass-card">
            <div class="tier-badge-row">
              <span class="tier-pill">STARTER TIER</span>
            </div>
            <h3 class="tier-title">Trial Creator</h3>
            <p class="tier-desc">Essential tools to showcase your initial portfolio pieces to clients.</p>
            
            <div class="tier-price-row">
              <span class="price-currency">Rs.</span>
              <span class="price-val">0</span>
              <span class="price-period">/ forever</span>
            </div>

            <ul class="tier-perks">
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Up to <strong><?php echo FREE_IMAGE_LIMIT; ?></strong> portfolio images</span>
              </li>
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Up to <strong><?php echo FREE_PDF_LIMIT; ?></strong> client PDF downloads</span>
              </li>
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Shareable public profile link</span>
              </li>
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Curriculum Vitae / resume attachment</span>
              </li>
            </ul>

            <div class="tier-action-wrap">
              <button type="button" class="btn btn-ghost" style="width:100%;cursor:default;opacity:0.75;" disabled>Current Free Tier</button>
            </div>
          </div>

          <!-- Pro Plan Card -->
          <div class="pricing-glass-card featured-pro">
            <div class="featured-ribbon-tag">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
              <span>MOST POPULAR</span>
            </div>

            <div class="tier-badge-row">
              <span class="tier-pill-pro">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="13" height="13"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
                <span>PRO CREATOR</span>
              </span>
            </div>
            
            <h3 class="tier-title text-gradient">Pro Unlimited</h3>
            <p class="tier-desc">Designed for active freelance designers and agencies demanding top performance.</p>
            
            <div class="tier-price-row">
              <span class="price-currency">Rs.</span>
              <span class="price-val"><?php echo number_format(PRO_PRICE, 0); ?></span>
              <span class="price-period">/ month</span>
            </div>

            <ul class="tier-perks">
              <li class="perk-highlight">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
                <span><strong>Unlimited</strong> portfolio image uploads</span>
              </li>
              <li class="perk-highlight">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
                <span><strong>Unlimited</strong> client-ready PDF downloads</span>
              </li>
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
                <span>AI auto-categorization &amp; tags</span>
              </li>
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Verified Pro profile badge on public site</span>
              </li>
              <li>
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Priority WhatsApp &amp; email support</span>
              </li>
            </ul>

            <div class="tier-action-wrap">
              <?php if ($user['is_subscribed']): ?>
                <div class="pro-active-badge">
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
                  <span>Active Pro Subscription</span>
                </div>
              <?php else: ?>
                <a href="#paymentSection" class="btn btn-primary" style="width:100%;">
                  <span>Get Started with Pro</span>
                  <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="9 18 15 12 9 6"/></svg>
                </a>
              <?php endif; ?>
            </div>
          </div>

        </div>

        <?php if ($user['is_subscribed']): ?>
          <div class="alert-glass success" style="max-width:580px;margin:32px 0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
            <div>
              <strong>You are an active Pro Member.</strong>
              <div style="font-size:12.5px;opacity:0.85;margin-top:2px;">Your account enjoys unlimited portfolio uploads, infinite PDF exports, and priority support.</div>
            </div>
          </div>
        <?php else: ?>
          <!-- Payment Options Container -->
          <div class="payment-section-box" id="paymentSection">
            <div class="section-label-glow">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
              <span>Instant Payment Methods</span>
            </div>

            <div class="gateway-buttons-grid">
              <!-- JazzCash -->
              <form method="POST" class="gateway-form-card">
                <button type="submit" name="pay_with" value="jazzcash" class="gateway-btn jazzcash">
                  <div class="gw-btn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><rect width="14" height="20" x="5" y="2" rx="2" ry="2"/><line x1="12" x2="12.01" y1="18" y2="18"/></svg>
                  </div>
                  <div class="gw-btn-text">
                    <span class="gw-brand">JazzCash</span>
                    <span class="gw-sub">Mobile Account, Debit or Credit Card</span>
                  </div>
                  <div class="gw-arrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="9 18 15 12 9 6"/></svg>
                  </div>
                </button>
              </form>

              <!-- Easypaisa -->
              <form method="POST" class="gateway-form-card">
                <button type="submit" name="pay_with" value="easypaisa" class="gateway-btn easypaisa">
                  <div class="gw-btn-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/></svg>
                  </div>
                  <div class="gw-btn-text">
                    <span class="gw-brand">Easypaisa</span>
                    <span class="gw-sub">Mobile Wallet or Bank Debit/Credit Card</span>
                  </div>
                  <div class="gw-arrow">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><polyline points="9 18 15 12 9 6"/></svg>
                  </div>
                </button>
              </form>
            </div>

            <div class="payment-trust-bar">
              <div class="trust-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <span>256-bit Encrypted SSL Gateway</span>
              </div>
              <div class="trust-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><polyline points="20 6 9 17 4 12"/></svg>
                <span>Instant Account Activation</span>
              </div>
              <div class="trust-item">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10"/></svg>
                <span>Supports All Pakistani Bank Cards</span>
              </div>
            </div>
          </div>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/tutorial_video_modal.php'; ?>
<?php include __DIR__ . '/../includes/whatsapp_btn.php'; ?>
</body>
</html>
