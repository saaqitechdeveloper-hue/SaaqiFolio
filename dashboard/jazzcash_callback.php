<?php
/**
 * SAAQIFOLIO - JazzCash Return/Callback Handler
 *
 * JazzCash redirects the customer's browser back here (via pp_ReturnURL)
 * after they complete or cancel payment on JazzCash's hosted page.
 *
 * IMPORTANT: Always re-verify the pp_SecureHash on this response yourself
 * before trusting pp_ResponseCode — never mark an order Completed just
 * because the browser was redirected here, since return URLs can be
 * hit directly. Verify against your JazzCash integration guide for the
 * exact response-hash field order, as it can differ slightly from the
 * request-hash order used in functions.php.
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$response = $_POST ?: $_GET;
$txnRef = $response['pp_TxnRefNo'] ?? '';
$responseCode = $response['pp_ResponseCode'] ?? '';
$responseMessage = $response['pp_ResponseMessage'] ?? 'Unknown response from JazzCash.';
$gatewayTxnId = $response['pp_RetreivalReferenceNo'] ?? ($response['pp_TxnRefNo'] ?? '');

// Verify the secure hash JazzCash sent back, using the same salt.
$receivedHash = $response['pp_SecureHash'] ?? '';
$toVerify = $response;
unset($toVerify['pp_SecureHash']);
ksort($toVerify);
$hashString = JAZZCASH_INTEGRITY_SALT;
foreach ($toVerify as $value) {
    $hashString .= '&' . $value;
}
$calculatedHash = hash_hmac('sha256', $hashString, JAZZCASH_INTEGRITY_SALT);
$hashValid = $txnRef && hash_equals($calculatedHash, $receivedHash);

$success = false;
$userId = null;

if ($txnRef) {
    if ($hashValid && $responseCode === '000') {
        $userId = finalize_payment($conn, $txnRef, 'Completed', $gatewayTxnId, json_encode($response));
        $success = true;
    } else {
        $userId = finalize_payment($conn, $txnRef, 'Failed', $gatewayTxnId, json_encode($response));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Result - SaaqiFolio</title>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="alternate icon" type="image/png" href="../assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-form-side" style="min-height:100vh;">
  <div class="auth-card" style="text-align:center;">
    <?php if ($success): ?>
      <div style="width:64px;height:64px;border-radius:50%;background:rgba(16,185,129,0.15);border:1px solid rgba(16,185,129,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:#10b981;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="32" height="32"><polyline points="20 6 9 17 4 12"/></svg>
      </div>
      <h2 style="font-family:var(--font-display);margin:12px 0 8px;">Payment Successful!</h2>
      <p class="muted" style="margin-bottom:20px;">Your account has been upgraded to Pro. Enjoy unlimited images and PDF downloads.</p>
    <?php else: ?>
      <div style="width:64px;height:64px;border-radius:50%;background:rgba(244,63,94,0.15);border:1px solid rgba(244,63,94,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;color:#f43f5e;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="32" height="32"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
      </div>
      <h2 style="font-family:var(--font-display);margin:12px 0 8px;">Payment Not Completed</h2>
      <p class="muted" style="margin-bottom:20px;"><?php echo e($responseMessage); ?></p>
    <?php endif; ?>
    <a href="<?php echo $success ? 'profile.php' : 'upgrade.php'; ?>" class="btn btn-primary" style="text-decoration:none;">
      <?php echo $success ? 'Go to my profile' : 'Try again'; ?>
    </a>
  </div>
</div>
</body>
</html>
