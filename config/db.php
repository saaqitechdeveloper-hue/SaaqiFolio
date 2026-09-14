<?php
/**
 * FOLIVO - Database Connection
 * Update these according to your MySQL/XAMPP/hosting setup.
 */
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'folivo');

define('SITE_NAME', 'Folivo');
define('FREE_IMAGE_LIMIT', 20);
define('FREE_PDF_LIMIT', 3);

// Full base URL of this app (no trailing slash) — used to build the
// return/callback URLs that JazzCash/Easypaisa redirect back to after
// payment. MUST be correct and publicly reachable (not localhost) once
// you go live, or the payment callback won't work.
define('APP_URL', 'http://localhost/folivo');

// Pro plan price shown on the upgrade page and sent to the gateways.
define('PRO_PRICE', 999.00); // PKR

// ============================================
// JazzCash Mobile Account / Card (Hosted Checkout Page)
// Get these from your JazzCash merchant dashboard (Integration -> Postman/API).
// ============================================
define('JAZZCASH_MERCHANT_ID', 'REPLACE_WITH_YOUR_MERCHANT_ID');
define('JAZZCASH_PASSWORD', 'REPLACE_WITH_YOUR_PASSWORD');
define('JAZZCASH_INTEGRITY_SALT', 'REPLACE_WITH_YOUR_INTEGRITY_SALT');
// Sandbox URL while testing; switch to the live URL JazzCash gives you at go-live.
define('JAZZCASH_API_URL', 'https://sandbox.jazzcash.com.pk/CustomerPortal/transactionmanagement/merchantform/');

// ============================================
// Easypaisa (Hosted Checkout Page)
// Get these from your Easypaisa merchant onboarding.
// ============================================
define('EASYPAISA_STORE_ID', 'REPLACE_WITH_YOUR_STORE_ID');
define('EASYPAISA_HASH_KEY', 'REPLACE_WITH_YOUR_HASH_KEY');
// Sandbox URL while testing; switch to the live URL Easypaisa gives you at go-live.
define('EASYPAISA_API_URL', 'https://easypay.easypaisa.com.pk/tpg/navigation/checkout.html');

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if (!$conn) {
    die('Database connection failed: ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');
