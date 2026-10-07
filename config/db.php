<?php
/**
 * SAAQIFOLIO - Database Connection & Configuration
 */

// Disable default fatal exception throwing on mysqli for clean error handling
mysqli_report(MYSQLI_REPORT_OFF);

// Detect environment (Localhost vs Live Server)
$httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocal = in_array($httpHost, ['localhost', '127.0.0.1', '::1']) || str_starts_with($httpHost, 'localhost:');

if ($isLocal) {
    define('DB_HOST', 'localhost');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'folivo');
    define('APP_URL', 'http://localhost/folivo');
} else {
    // ============================================
    // Live Server (Hostinger) settings:
    // Update these with your Hostinger MySQL database details:
    // ============================================
    define('DB_HOST', 'localhost');
    define('DB_USER', 'u195418993_SaaqiFolio');
    define('DB_PASS', 'Saydev@1234');
    define('DB_NAME', 'u195418993_SaaqiFolio');
    $liveScheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    define('APP_URL', $liveScheme . '://' . ($httpHost ?: 'saaqifolio.com'));
}

define('SITE_NAME', 'SaaqiFolio');
define('FREE_IMAGE_LIMIT', 20);
define('FREE_PDF_LIMIT', 3);

// Pro plan price shown on the upgrade page and sent to the gateways.
define('PRO_PRICE', 999.00); // PKR

// ============================================
// AI Image Categorization Backend (FastAPI service)
// ============================================
define('AI_BACKEND_URL', 'https://saaqitech.pythonanywhere.com');
define('AI_BACKEND_API_KEY', 'ac1ea6fde51f9d94f544930585dc5c4e0457196728cc01db0a00f8d965bc764f');
define('AI_BACKEND_TIMEOUT', 7); // seconds, per single image (prevents hanging)
define('AI_BACKEND_BATCH_TIMEOUT', 45); // seconds, for the whole batch call
define('AI_MIN_CONFIDENCE', 0.2); // below this, fall back to filename guess

// ============================================
// JazzCash Mobile Account / Card (Hosted Checkout Page)
// ============================================
define('JAZZCASH_MERCHANT_ID', 'REPLACE_WITH_YOUR_MERCHANT_ID');
define('JAZZCASH_PASSWORD', 'REPLACE_WITH_YOUR_PASSWORD');
define('JAZZCASH_INTEGRITY_SALT', 'REPLACE_WITH_YOUR_INTEGRITY_SALT');
define('JAZZCASH_API_URL', 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/');

// ============================================
// Easypaisa (Hosted Checkout Page)
// ============================================
define('EASYPAISA_STORE_ID', 'REPLACE_WITH_YOUR_STORE_ID');
define('EASYPAISA_HASH_KEY', 'REPLACE_WITH_YOUR_HASH_KEY');
define('EASYPAISA_API_URL', 'https://easypay.easypaisa.com.pk/tpg/navigation/checkout.html');

$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    $errorMsg = mysqli_connect_error();
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title>Database Setup Required — SaaqiFolio</title>
      <style>
        body { background: #0D0B14; color: #FAF8FF; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; }
        .card { background: rgba(22, 19, 36, 0.9); border: 1px solid rgba(139, 92, 246, 0.3); border-radius: 16px; padding: 36px; max-width: 560px; box-shadow: 0 24px 60px rgba(0,0,0,0.6); }
        .brand { font-size: 20px; font-weight: 700; color: #fff; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
        .brand-dot { width: 10px; height: 10px; border-radius: 50%; background: linear-gradient(135deg, #8B5CF6, #FF6B4A); }
        h2 { margin: 0 0 12px; color: #FAF8FF; font-size: 18px; }
        p { color: #A1A1AA; font-size: 13.5px; line-height: 1.6; margin: 8px 0; }
        code { background: rgba(0,0,0,0.5); padding: 3px 7px; border-radius: 5px; color: #C084FC; font-family: monospace; font-size: 12.5px; }
        .err { background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3); color: #FCA5A5; padding: 12px 14px; border-radius: 8px; font-size: 13px; margin: 16px 0; word-break: break-all; }
        ol { color: #D4D4D8; font-size: 13.5px; line-height: 1.8; padding-left: 20px; margin: 14px 0; }
        li strong { color: #fff; }
      </style>
    </head>
    <body>
      <div class="card">
        <div class="brand"><div class="brand-dot"></div> SaaqiFolio Setup</div>
        <h2>Database Connection Required</h2>
        <p>SaaqiFolio is running, but cannot connect to your MySQL database with current credentials.</p>
        <div class="err"><strong>MySQL Error:</strong> <?php echo htmlspecialchars($errorMsg); ?></div>
        <p><strong>Steps to connect on Hostinger:</strong></p>
        <ol>
          <li>Open <strong>Hostinger hPanel &rarr; Databases &rarr; MySQL Databases</strong>.</li>
          <li>Create a database (e.g. <code>u..._folivo</code>) and note down the <strong>Database Name</strong>, <strong>Username</strong>, and <strong>Password</strong>.</li>
          <li>Click <strong>Enter phpMyAdmin</strong> and import <code>database/schema.sql</code>.</li>
          <li>Open <code>config/db.php</code> on your server and enter your database details in the Live Server section.</li>
        </ol>
      </div>
    </body>
    </html>
    <?php
    exit;
}

mysqli_set_charset($conn, 'utf8mb4');
