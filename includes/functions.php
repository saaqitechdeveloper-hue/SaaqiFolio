<?php
/**
 * FOLIVO - Core Helper Functions
 */

function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function clean($conn, $value) {
    return mysqli_real_escape_string($conn, trim($value ?? ''));
}

function slugify($text) {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-');
}

// Generate a unique public slug for a user, e.g. "ayesha-khan", "ayesha-khan-2"
function generate_unique_slug($conn, $name) {
    $base = slugify($name);
    if ($base === '') $base = 'designer';
    $slug = $base;
    $i = 2;
    while (true) {
        $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE public_slug = ?");
        mysqli_stmt_bind_param($stmt, 's', $slug);
        mysqli_stmt_execute($stmt);
        if (mysqli_stmt_get_result($stmt)->num_rows === 0) {
            return $slug;
        }
        $slug = $base . '-' . $i;
        $i++;
    }
}

function initials($name) {
    $parts = preg_split('/\s+/', trim($name));
    $init = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        if ($p !== '') $init .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $init ?: '?';
}

// Generic image upload handler. Returns filename on success, false on failure, null if no file given.
function handle_image_upload($fileInput, $destDir, $maxMB = 5) {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $ext = strtolower(pathinfo($_FILES[$fileInput]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return false;
    }
    if ($_FILES[$fileInput]['size'] > $maxMB * 1024 * 1024) {
        return false;
    }
    $newName = 'img_' . str_replace('.', '', uniqid('', true)) . '.' . $ext;
    $destination = rtrim($destDir, '/') . '/' . $newName;
    if (move_uploaded_file($_FILES[$fileInput]['tmp_name'], $destination)) {
        return $newName;
    }
    return false;
}

// CV upload handler (pdf/doc/docx). Returns [storedName, originalName] or false/null.
function handle_cv_upload($fileInput, $destDir, $maxMB = 8) {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    $allowed = ['pdf', 'doc', 'docx'];
    $ext = strtolower(pathinfo($_FILES[$fileInput]['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return false;
    }
    if ($_FILES[$fileInput]['size'] > $maxMB * 1024 * 1024) {
        return false;
    }
    $newName = 'cv_' . str_replace('.', '', uniqid('', true)) . '.' . $ext;
    $destination = rtrim($destDir, '/') . '/' . $newName;
    if (move_uploaded_file($_FILES[$fileInput]['tmp_name'], $destination)) {
        return [$newName, $_FILES[$fileInput]['name']];
    }
    return false;
}

// Very lightweight category "suggestion" based on the original filename —
// NOT real AI/image recognition (that needs a paid vision API). Manual
// category selection is always available and is the primary flow.
function suggest_category_from_filename($filename) {
    $name = strtolower($filename);
    $map = [
        'logo' => 'Logo',
        'banner' => 'Banner',
        'ui' => 'UI/UX',
        'ux' => 'UI/UX',
        'web' => 'Color Separation',
        'flyer' => 'Flyer',
        'poster' => 'Poster',
        'social' => 'Social Media',
        'post' => 'Social Media',
    ];
    foreach ($map as $keyword => $category) {
        if (strpos($name, $keyword) !== false) return $category;
    }
    return 'Other';
}

function get_categories() {
    return ["Logo","Banner","UI/UX","Color Separation","Flyer","Poster","Social Media","Other"];
}

function get_software_list() {
    return ["Adobe Photoshop","Adobe Illustrator","Adobe XD","Figma","Canva","CorelDRAW","Sketch","Adobe InDesign","After Effects","Premiere Pro","Procreate","Blender","Adobe Lightroom","Webflow"];
}

function get_professions() {
    return ["Graphic Designer","UI/UX Designer","Web Designer","Illustrator","Brand Designer","Other"];
}

function skills_to_array($skillsStr) {
    if (!$skillsStr) return [];
    return array_filter(array_map('trim', explode(',', $skillsStr)));
}

// ============================================
// PDF download limit helpers
// ============================================
function can_download_pdf($user) {
    if ($user['is_subscribed']) return true;
    return (int) $user['pdf_downloads_count'] < FREE_PDF_LIMIT;
}

function increment_pdf_downloads($conn, $userId) {
    $stmt = mysqli_prepare($conn, "UPDATE users SET pdf_downloads_count = pdf_downloads_count + 1 WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    mysqli_stmt_execute($stmt);
}

// ============================================
// Payment helpers
// ============================================

// Generate a unique transaction reference, e.g. T20260908103245ab12
function generate_txn_ref() {
    return 'T' . date('YmdHis') . substr(bin2hex(random_bytes(3)), 0, 4);
}

// Record a new pending payment row before redirecting the user to the gateway.
function create_pending_payment($conn, $userId, $gateway, $txnRef, $amount) {
    $stmt = mysqli_prepare($conn, "INSERT INTO payments (user_id, gateway, txn_ref, amount, status) VALUES (?,?,?,?, 'Pending')");
    mysqli_stmt_bind_param($stmt, 'issd', $userId, $gateway, $txnRef, $amount);
    mysqli_stmt_execute($stmt);
}

// Mark a payment Completed/Failed after the gateway calls back, and upgrade
// the account to Pro if it succeeded.
function finalize_payment($conn, $txnRef, $status, $gatewayTxnId, $rawResponse) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM payments WHERE txn_ref = ?");
    mysqli_stmt_bind_param($stmt, 's', $txnRef);
    mysqli_stmt_execute($stmt);
    $payment = mysqli_stmt_get_result($stmt)->fetch_assoc();
    if (!$payment) return false;

    $upd = mysqli_prepare($conn, "UPDATE payments SET status=?, gateway_txn_id=?, raw_response=? WHERE txn_ref=?");
    mysqli_stmt_bind_param($upd, 'ssss', $status, $gatewayTxnId, $rawResponse, $txnRef);
    mysqli_stmt_execute($upd);

    if ($status === 'Completed') {
        $up = mysqli_prepare($conn, "UPDATE users SET is_subscribed = 1 WHERE id = ?");
        mysqli_stmt_bind_param($up, 'i', $payment['user_id']);
        mysqli_stmt_execute($up);
    }

    return $payment['user_id'];
}

// ---------- JazzCash Mobile Account / Card (Hosted Checkout Page) ----------
// Builds the full set of POST fields for JazzCash's hosted form, including
// the required pp_SecureHash. Reference: JazzCash Hosted Checkout Page (HCP)
// integration guide — field names/order must match exactly what your
// merchant dashboard docs specify; verify against your latest JazzCash
// integration PDF, since gateway APIs do change over time.
function jazzcash_build_fields($txnRef, $amountPkr) {
    $amountPaisa = (string) round($amountPkr * 100); // JazzCash expects amount in paisa, no decimal
    $now = new DateTime();
    $expiry = (clone $now)->modify('+1 hour');

    $fields = [
        'pp_Version' => '1.1',
        'pp_TxnType' => 'MWALLET',
        'pp_Language' => 'EN',
        'pp_MerchantID' => JAZZCASH_MERCHANT_ID,
        'pp_Password' => JAZZCASH_PASSWORD,
        'pp_TxnRefNo' => $txnRef,
        'pp_Amount' => $amountPaisa,
        'pp_TxnCurrency' => 'PKR',
        'pp_TxnDateTime' => $now->format('YmdHis'),
        'pp_TxnExpiryDateTime' => $expiry->format('YmdHis'),
        'pp_BillReference' => 'FolivoPro',
        'pp_Description' => 'Folivo Pro Plan Upgrade',
        'pp_ReturnURL' => APP_URL . '/dashboard/jazzcash_callback.php',
    ];

    // Hash = HMAC-SHA256(IntegritySalt, IntegritySalt&value1&value2&...) using
    // fields sorted alphabetically by key, per JazzCash's spec.
    ksort($fields);
    $hashString = JAZZCASH_INTEGRITY_SALT;
    foreach ($fields as $value) {
        $hashString .= '&' . $value;
    }
    $fields['pp_SecureHash'] = hash_hmac('sha256', $hashString, JAZZCASH_INTEGRITY_SALT);

    return $fields;
}

// ---------- Easypaisa (Hosted Checkout Page) ----------
// Reference: Easypaisa Open API / HPP integration guide — again, verify
// field names against your current merchant documentation before going live.
function easypaisa_build_fields($txnRef, $amountPkr) {
    $expiry = (new DateTime())->modify('+1 day')->format('YmdHis');

    $fields = [
        'storeId' => EASYPAISA_STORE_ID,
        'amount' => number_format($amountPkr, 2, '.', ''),
        'postBackURL' => APP_URL . '/dashboard/easypaisa_callback.php',
        'orderRefNum' => $txnRef,
        'expiryDate' => $expiry,
        'merchantHashedReq' => '', // filled below
        'autoRedirect' => '1',
        'paymentMethod' => 'InitialRequest',
        'emailAddr' => '',
    ];

    // Hash = HMAC-SHA256(HashKey, "amount=..&expiryDate=..&orderRefNum=..&storeId=..")
    // over the fields sorted alphabetically by key (excluding the hash itself).
    $forHash = $fields;
    unset($forHash['merchantHashedReq']);
    ksort($forHash);
    $parts = [];
    foreach ($forHash as $key => $value) {
        $parts[] = "$key=$value";
    }
    $hashString = implode('&', $parts);
    $fields['merchantHashedReq'] = base64_encode(hash_hmac('sha256', $hashString, EASYPAISA_HASH_KEY, true));

    return $fields;
}
