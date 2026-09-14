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

// ---------- POST: Start a payment (redirects to JazzCash or Easypaisa) ----------
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

$activeNav = '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Upgrade to Pro - Folivo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="shell">
  <?php
    $imgCountRes = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM portfolio_images WHERE user_id=$userId");
    $imageCount = mysqli_fetch_assoc($imgCountRes)['cnt'];
    include __DIR__ . '/../includes/sidebar.php';
  ?>

  <div class="main">
    <div class="page-head">
      <div class="page-title">Upgrade to Pro</div>
      <div class="page-desc">Unlimited images, unlimited PDF downloads.</div>
    </div>

    <?php if ($error): ?><div class="auth-error"><?php echo e($error); ?></div><?php endif; ?>

    <?php if ($gatewayFields): ?>
      <!-- Auto-submitting form that redirects the user to the gateway's hosted payment page -->
      <div class="profile-card" style="max-width:480px;text-align:center;">
        <p style="margin-bottom:16px;">Redirecting you to <?php echo e($_POST['pay_with'] === 'easypaisa' ? 'Easypaisa' : 'JazzCash'); ?> to complete your payment...</p>
        <form id="gatewayForm" method="POST" action="<?php echo e($gatewayUrl); ?>">
          <?php foreach ($gatewayFields as $key => $value): ?>
            <input type="hidden" name="<?php echo e($key); ?>" value="<?php echo e($value); ?>">
          <?php endforeach; ?>
        </form>
      </div>
      <script>document.getElementById('gatewayForm').submit();</script>

    <?php else: ?>

      <div class="stats-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:20px;max-width:720px;">
        <div class="profile-card">
          <h3 style="font-family:var(--font-display);margin-bottom:14px;">Trial</h3>
          <ul style="list-style:none;padding:0;line-height:2.1;font-size:14px;">
            <li>✓ Up to <?php echo FREE_IMAGE_LIMIT; ?> portfolio images</li>
            <li>✓ Up to <?php echo FREE_PDF_LIMIT; ?> PDF downloads</li>
            <li>✓ Public shareable link</li>
            <li>✓ CV upload</li>
          </ul>
          <div class="muted" style="margin-top:10px;">Rs. 0</div>
        </div>
        <div class="profile-card" style="border:2px solid var(--accent);">
          <h3 style="font-family:var(--font-display);margin-bottom:14px;color:var(--accent);">Pro 👑</h3>
          <ul style="list-style:none;padding:0;line-height:2.1;font-size:14px;">
            <li>✓ <strong>Unlimited</strong> portfolio images</li>
            <li>✓ <strong>Unlimited</strong> PDF downloads</li>
            <li>✓ Public shareable link</li>
            <li>✓ CV upload</li>
            <li>✓ Priority support</li>
          </ul>
          <div style="font-size:22px;font-weight:800;margin-top:10px;">Rs. <?php echo number_format(PRO_PRICE, 0); ?> <span style="font-size:13px;font-weight:400;color:var(--text-muted);">/ month</span></div>
        </div>
      </div>

      <?php if ($user['is_subscribed']): ?>
        <div class="auth-error" style="background:#3DDC9726;color:#3DDC97;max-width:480px;margin-top:24px;">
          👑 You're already on the Pro plan. Thank you!
        </div>
      <?php else: ?>
        <div style="max-width:480px;margin-top:24px;">
          <h4 style="margin-bottom:12px;">Pay with</h4>
          <form method="POST" style="margin-bottom:12px;">
            <button type="submit" name="pay_with" value="jazzcash" class="btn btn-primary" style="width:100%;">
              📱 JazzCash (Mobile Account / Card)
            </button>
          </form>
          <form method="POST" style="margin-bottom:12px;">
            <button type="submit" name="pay_with" value="easypaisa" class="btn btn-primary" style="width:100%;background:#28a745;">
              📱 Easypaisa (Mobile Account / Card)
            </button>
          </form>
          <p class="muted" style="font-size:12px;line-height:1.6;">
            Pakistani bank debit/credit cards are accepted directly on the
            JazzCash and Easypaisa payment pages — you don't need a
            separate "card" button, just choose the Card tab once you're
            redirected.
          </p>
        </div>
      <?php endif; ?>

    <?php endif; ?>
  </div>
</div>

</body>
</html>
