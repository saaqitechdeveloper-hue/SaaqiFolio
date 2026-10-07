<?php
/**
 * SAAQIFOLIO - Easypaisa Return/Callback Handler
 *
 * Easypaisa posts back to postBackURL after payment. As with JazzCash,
 * always re-verify the signature yourself — never trust the status field
 * blindly. Field names below follow Easypaisa's commonly published Open
 * API integration guide; confirm the exact field names/response-hash
 * order against your current merchant documentation before going live,
 * since these can vary by integration type.
 */
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$response = $_POST ?: $_GET;
$txnRef = $response['orderRefNumber'] ?? ($response['orderRefNum'] ?? '');
$status = strtolower($response['status'] ?? $response['transactionStatus'] ?? '');
$gatewayTxnId = $response['transactionId'] ?? '';
$responseMessage = $response['responseDesc'] ?? $response['msg'] ?? 'Unknown response from Easypaisa.';

// NOTE: Easypaisa's exact "success" status string/field varies by
// integration (e.g. "0000", "SUCCESS", "PAID"). Check your merchant docs
// and adjust this condition to match exactly what your account returns.
$isSuccess = in_array($status, ['0000', 'success', 'paid', 'completed'], true);

$success = false;

if ($txnRef) {
    if ($isSuccess) {
        finalize_payment($conn, $txnRef, 'Completed', $gatewayTxnId, json_encode($response));
        $success = true;
    } else {
        finalize_payment($conn, $txnRef, 'Failed', $gatewayTxnId, json_encode($response));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment Result - SaaqiFolio</title>
<?php
$ogTitle = 'Payment Confirmation — SaaqiFolio';
$ogDescription = 'Payment transaction result and confirmation on SaaqiFolio.';
include __DIR__ . '/../includes/og_meta.php';
?>
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
