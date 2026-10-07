<?php
/**
 * SAAQIFOLIO - Core Helper Functions
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
function handle_image_upload($fileInput, $destDir, $maxMB = 30) {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($_FILES[$fileInput]['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    if (!is_dir($destDir)) {
        @mkdir($destDir, 0777, true);
    }
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'jfif', 'avif', 'svg', 'bmp', 'ico', 'tiff', 'tif', 'heic', 'heif'];
    $rawName = $_FILES[$fileInput]['name'] ?? '';
    $ext = strtolower(pathinfo($rawName, PATHINFO_EXTENSION));

    if (empty($ext) || !in_array($ext, $allowed, true)) {
        // Fallback: detect from MIME type or file content
        $mime = '';
        if (function_exists('mime_content_type') && !empty($_FILES[$fileInput]['tmp_name']) && file_exists($_FILES[$fileInput]['tmp_name'])) {
            $mime = strtolower(@mime_content_type($_FILES[$fileInput]['tmp_name']) ?: '');
        }
        if (str_starts_with($mime, 'image/')) {
            $sub = explode('/', $mime)[1] ?? 'jpg';
            $ext = ($sub === 'jpeg') ? 'jpg' : (($sub === 'svg+xml') ? 'svg' : $sub);
        } else {
            $ext = 'jpg'; // Safe default for valid uploads
        }
    }

    if (($_FILES[$fileInput]['size'] ?? 0) > $maxMB * 1024 * 1024) {
        return false;
    }

    $newName = 'img_' . str_replace('.', '', uniqid('', true)) . '.' . $ext;
    $destination = rtrim($destDir, '/') . '/' . $newName;

    $tmpPath = $_FILES[$fileInput]['tmp_name'];
    if (@move_uploaded_file($tmpPath, $destination) || @copy($tmpPath, $destination)) {
        if (strpos($destDir, 'portfolio') !== false) {
            generate_portfolio_thumbnail($destination);
        }
        return $newName;
    }
    return false;
}

/**
 * Generate an ultra-compressed, responsive AVIF thumbnail (with WebP/JPEG fallback)
 * Keeps max dimension at 720px to prevent layout freezing and memory bloat.
 */
function generate_portfolio_thumbnail($sourcePath, $thumbDir = null, $maxDim = 720, $quality = 75) {
    if (!file_exists($sourcePath)) return false;

    if ($thumbDir === null) {
        $thumbDir = dirname($sourcePath) . '/thumbs';
    }
    if (!is_dir($thumbDir)) {
        @mkdir($thumbDir, 0777, true);
    }

    $baseName = pathinfo($sourcePath, PATHINFO_FILENAME);
    $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));

    // For SVGs, copy directly to thumbs
    if ($ext === 'svg') {
        @copy($sourcePath, $thumbDir . '/' . $baseName . '.svg');
        return $baseName . '.svg';
    }

    $info = @getimagesize($sourcePath);
    if (!$info) return false;

    $origW = (int) $info[0];
    $origH = (int) $info[1];
    $mime = $info['mime'] ?? '';

    $srcImg = null;
    switch ($mime) {
        case 'image/jpeg':
            $srcImg = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $srcImg = @imagecreatefrompng($sourcePath);
            break;
        case 'image/webp':
            if (function_exists('imagecreatefromwebp')) {
                $srcImg = @imagecreatefromwebp($sourcePath);
            }
            break;
        case 'image/avif':
            if (function_exists('imagecreatefromavif')) {
                $srcImg = @imagecreatefromavif($sourcePath);
            }
            break;
        case 'image/gif':
            $srcImg = @imagecreatefromgif($sourcePath);
            break;
        case 'image/bmp':
            if (function_exists('imagecreatefrombmp')) {
                $srcImg = @imagecreatefrombmp($sourcePath);
            }
            break;
    }

    if (!$srcImg && function_exists('imagecreatefromstring')) {
        $content = @file_get_contents($sourcePath);
        if ($content) $srcImg = @imagecreatefromstring($content);
    }

    if (!$srcImg) return false;

    // Auto-rotate based on EXIF tag (mobile camera photos)
    if (function_exists('exif_read_data') && ($mime === 'image/jpeg' || $mime === 'image/tiff')) {
        try {
            $exif = @exif_read_data($sourcePath);
            if (!empty($exif['Orientation'])) {
                switch ((int)$exif['Orientation']) {
                    case 3:
                        $srcImg = imagerotate($srcImg, 180, 0);
                        break;
                    case 6:
                        $srcImg = imagerotate($srcImg, -90, 0);
                        $t = $origW; $origW = $origH; $origH = $t;
                        break;
                    case 8:
                        $srcImg = imagerotate($srcImg, 90, 0);
                        $t = $origW; $origW = $origH; $origH = $t;
                        break;
                }
            }
        } catch (\Throwable $e) {}
    }

    // Scale dimensions proportionally
    if ($origW > $maxDim || $origH > $maxDim) {
        $ratio = min($maxDim / $origW, $maxDim / $origH);
        $newW = max(1, (int) round($origW * $ratio));
        $newH = max(1, (int) round($origH * $ratio));
    } else {
        $newW = $origW;
        $newH = $origH;
    }

    $dstImg = imagecreatetruecolor($newW, $newH);
    imagealphablending($dstImg, false);
    imagesavealpha($dstImg, true);
    $transparent = imagecolorallocatealpha($dstImg, 255, 255, 255, 127);
    imagefilledrectangle($dstImg, 0, 0, $newW, $newH, $transparent);
    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

    // Save as AVIF (highest compression + modern fidelity)
    $savedName = false;
    if (function_exists('imageavif')) {
        $targetFile = $thumbDir . '/' . $baseName . '.avif';
        if (@imageavif($dstImg, $targetFile, $quality)) {
            $savedName = $baseName . '.avif';
        }
    }
    // WebP fallback
    if (!$savedName && function_exists('imagewebp')) {
        $targetFile = $thumbDir . '/' . $baseName . '.webp';
        if (@imagewebp($dstImg, $targetFile, $quality)) {
            $savedName = $baseName . '.webp';
        }
    }
    // JPEG fallback
    if (!$savedName) {
        $targetFile = $thumbDir . '/' . $baseName . '.jpg';
        if (@imagejpeg($dstImg, $targetFile, max(60, $quality))) {
            $savedName = $baseName . '.jpg';
        }
    }

    @imagedestroy($srcImg);
    @imagedestroy($dstImg);

    return $savedName;
}

/**
 * Returns optimized thumbnail URL for portfolio images (AVIF/WebP), with fallback to original.
 */
function get_portfolio_thumbnail_url($filename, $baseUrl = '..') {
    if (empty($filename)) return '';

    $baseName = pathinfo($filename, PATHINFO_FILENAME);
    $origPath = __DIR__ . '/../assets/uploads/portfolio/' . $filename;
    $thumbDir = __DIR__ . '/../assets/uploads/portfolio/thumbs';

    if (file_exists($thumbDir . '/' . $baseName . '.avif')) {
        return rtrim($baseUrl, '/') . '/assets/uploads/portfolio/thumbs/' . $baseName . '.avif';
    }
    if (file_exists($thumbDir . '/' . $baseName . '.webp')) {
        return rtrim($baseUrl, '/') . '/assets/uploads/portfolio/thumbs/' . $baseName . '.webp';
    }
    if (file_exists($thumbDir . '/' . $baseName . '.jpg')) {
        return rtrim($baseUrl, '/') . '/assets/uploads/portfolio/thumbs/' . $baseName . '.jpg';
    }
    if (file_exists($thumbDir . '/' . $baseName . '.svg')) {
        return rtrim($baseUrl, '/') . '/assets/uploads/portfolio/thumbs/' . $baseName . '.svg';
    }

    // Lazy generation if thumbnail missing but original exists
    if (file_exists($origPath)) {
        $thumbName = generate_portfolio_thumbnail($origPath, $thumbDir);
        if ($thumbName) {
            return rtrim($baseUrl, '/') . '/assets/uploads/portfolio/thumbs/' . $thumbName;
        }
    }

    return rtrim($baseUrl, '/') . '/assets/uploads/portfolio/' . $filename;
}

/**
 * Deletes original image and any corresponding thumbnails (AVIF/WebP/JPG)
 */
function delete_portfolio_image_files($filename) {
    if (empty($filename)) return;
    $baseName = pathinfo($filename, PATHINFO_FILENAME);
    $origPath = __DIR__ . '/../assets/uploads/portfolio/' . $filename;
    $thumbDir = __DIR__ . '/../assets/uploads/portfolio/thumbs';

    if (file_exists($origPath)) @unlink($origPath);
    if (file_exists($thumbDir . '/' . $baseName . '.avif')) @unlink($thumbDir . '/' . $baseName . '.avif');
    if (file_exists($thumbDir . '/' . $baseName . '.webp')) @unlink($thumbDir . '/' . $baseName . '.webp');
    if (file_exists($thumbDir . '/' . $baseName . '.jpg')) @unlink($thumbDir . '/' . $baseName . '.jpg');
    if (file_exists($thumbDir . '/' . $baseName . '.svg')) @unlink($thumbDir . '/' . $baseName . '.svg');
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

// Tutorial video upload handler (mp4/webm/mov/ogg/m4v). Returns [storedName, originalName] or false/null.
// Optional $errorMessage reference to capture exact failure reason.
function handle_video_upload($fileInput, $destDir, $maxMB = 100, &$errorMessage = '') {
    if (!isset($_FILES[$fileInput]) || $_FILES[$fileInput]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $errCode = $_FILES[$fileInput]['error'];
    if ($errCode !== UPLOAD_ERR_OK) {
        if ($errCode === UPLOAD_ERR_INI_SIZE || $errCode === UPLOAD_ERR_FORM_SIZE) {
            $iniMax = ini_get('upload_max_filesize');
            $errorMessage = "Video file exceeds server upload size limit ({$iniMax}). Please upload a smaller video or contact support.";
        } else {
            $errorMessage = "Upload failed with PHP error code {$errCode}.";
        }
        return false;
    }

    $allowed = ['mp4', 'webm', 'mov', 'ogg', 'm4v'];
    $rawName = $_FILES[$fileInput]['name'] ?? '';
    $ext = strtolower(pathinfo($rawName, PATHINFO_EXTENSION));

    if (empty($ext) || !in_array($ext, $allowed, true)) {
        // MIME-based detection fallback
        $mime = '';
        if (function_exists('mime_content_type') && !empty($_FILES[$fileInput]['tmp_name']) && file_exists($_FILES[$fileInput]['tmp_name'])) {
            $mime = strtolower(@mime_content_type($_FILES[$fileInput]['tmp_name']) ?: '');
        }
        if (str_starts_with($mime, 'video/')) {
            $sub = explode('/', $mime)[1] ?? 'mp4';
            $ext = ($sub === 'quicktime') ? 'mov' : (($sub === 'x-matroska') ? 'mkv' : $sub);
        } else {
            $errorMessage = 'Invalid video file format. Supported formats: MP4, WebM, MOV, OGG.';
            return false;
        }
    }

    if (($_FILES[$fileInput]['size'] ?? 0) > $maxMB * 1024 * 1024) {
        $errorMessage = "Video file size exceeds maximum limit of {$maxMB}MB.";
        return false;
    }

    if (!is_dir($destDir)) {
        @mkdir($destDir, 0777, true);
    }

    $newName = 'tut_' . str_replace('.', '', uniqid('', true)) . '.' . $ext;
    $destination = rtrim($destDir, '/') . '/' . $newName;
    $tmpPath = $_FILES[$fileInput]['tmp_name'];

    if (@move_uploaded_file($tmpPath, $destination) || @copy($tmpPath, $destination)) {
        return [$newName, $rawName];
    }

    $errorMessage = 'Server could not save uploaded video file into storage directory.';
    return false;
}

// Very lightweight category "suggestion" based on the original filename —
// NOT real AI/image recognition. Used only as a fallback when the AI
// backend is unreachable, slow, or returns a low-confidence result.
// Manual category selection is always available and is the primary flow.
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

// Collects debug info about each AI backend call so it can be printed to
// the browser console for troubleshooting. Remove/disable once things
// are working reliably — it's for debugging, not meant for production.
function ai_debug_log($entry = null) {
    static $log = [];
    if ($entry !== null) {
        $log[] = $entry;
    }
    return $log;
}

// ============================================================
// AI Image Categorization (external FastAPI backend)
// Endpoints used: POST /api/classify-image
//                 POST /api/classify-images-batch
// Docs: see the AI backend's own README for the full contract.
// ============================================================

// Classify ONE image already saved on disk. $originalFilename is only
// used for the fallback keyword guess if the AI call doesn't work out.
function classify_image_with_ai($storedPath, $originalFilename, $categories) {
    if (!defined('AI_BACKEND_URL') || !defined('AI_BACKEND_API_KEY') || !file_exists($storedPath)) {
        ai_debug_log([
            'file' => $originalFilename,
            'stage' => 'skipped',
            'reason' => !defined('AI_BACKEND_URL') ? 'AI_BACKEND_URL not defined'
                : (!defined('AI_BACKEND_API_KEY') ? 'AI_BACKEND_API_KEY not defined' : 'stored file not found on disk: ' . $storedPath),
        ]);
        return suggest_category_from_filename($originalFilename);
    }

    $apiUrl = rtrim(AI_BACKEND_URL, '/') . '/api/classify-image';
    $response = null;
    $httpCode = 0;
    $errorMsg = '';
    $timeout = defined('AI_BACKEND_TIMEOUT') ? AI_BACKEND_TIMEOUT : 7;

    if (function_exists('curl_init')) {
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . AI_BACKEND_API_KEY],
            CURLOPT_POSTFIELDS => [
                'image' => new CURLFile($storedPath),
                'allowed_categories' => json_encode($categories),
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);
        $response = curl_exec($ch);
        $errorMsg = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        // Fallback: Native PHP HTTP stream multipart POST (zero curl dependency)
        $boundary = '--------------------------' . microtime(true);
        $mime = 'image/jpeg';
        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($storedPath);
            if ($detected) $mime = $detected;
        }
        $fileBytes = @file_get_contents($storedPath);
        if ($fileBytes !== false) {
            $body = "--{$boundary}\r\n"
                  . "Content-Disposition: form-data; name=\"allowed_categories\"\r\n\r\n"
                  . json_encode($categories) . "\r\n"
                  . "--{$boundary}\r\n"
                  . "Content-Disposition: form-data; name=\"image\"; filename=\"" . basename($storedPath) . "\"\r\n"
                  . "Content-Type: {$mime}\r\n\r\n"
                  . $fileBytes . "\r\n"
                  . "--{$boundary}--\r\n";

            $opts = [
                'http' => [
                    'method'  => 'POST',
                    'header'  => "Content-Type: multipart/form-data; boundary={$boundary}\r\n"
                               . "Authorization: Bearer " . AI_BACKEND_API_KEY . "\r\n"
                               . "Content-Length: " . strlen($body) . "\r\n",
                    'content' => $body,
                    'timeout' => $timeout,
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ]
            ];
            $ctx = stream_context_create($opts);
            $response = @file_get_contents($apiUrl, false, $ctx);
            if (isset($http_response_header) && is_array($http_response_header)) {
                foreach ($http_response_header as $hdr) {
                    if (preg_match('/HTTP\/\S+\s+(\d{3})/', $hdr, $m)) {
                        $httpCode = (int)$m[1];
                        break;
                    }
                }
            }
            if ($response === false) {
                $errorMsg = 'Stream request failed';
            }
        } else {
            $errorMsg = 'Failed to read file bytes';
        }
    }

    if (!$response || ($httpCode !== 0 && $httpCode !== 200)) {
        error_log("AI classify-image failed for $originalFilename: $errorMsg (HTTP $httpCode)");
        ai_debug_log([
            'file' => $originalFilename,
            'stage' => 'request_failed',
            'url' => $apiUrl,
            'http_code' => $httpCode,
            'curl_error' => $errorMsg,
            'raw_response' => $response,
        ]);
        return suggest_category_from_filename($originalFilename);
    }

    $data = json_decode($response, true);
    $minConfidence = defined('AI_MIN_CONFIDENCE') ? AI_MIN_CONFIDENCE : 0.5;

    if (
        !empty($data['success']) &&
        !empty($data['category']) &&
        in_array($data['category'], $categories, true) &&
        ($data['confidence'] ?? 0) >= $minConfidence
    ) {
        ai_debug_log([
            'file' => $originalFilename,
            'stage' => 'success',
            'category' => $data['category'],
            'confidence' => $data['confidence'] ?? null,
        ]);
        return $data['category'];
    }

    ai_debug_log([
        'file' => $originalFilename,
        'stage' => 'rejected_response',
        'reason' => 'response did not pass the success/category/confidence checks',
        'raw_response' => $response,
    ]);
    return suggest_category_from_filename($originalFilename);
}

// Classify MULTIPLE images already saved on disk in a single request.
// $files must be an array of ['filename' => storedFilenameOnDisk,
// 'path' => absoluteStoredPath, 'original' => originalUploadedFilename].
// Returns an associative array keyed by stored filename => category,
// so the caller can look up each result by the name it already has.
// NOTE: currently NOT used by portfolio.php — see the comment in the
// upload handler. PHP's curl can't send a truly-repeated "images" field
// (it always adds [0],[1]... brackets), which this API's batch endpoint
// rejects with 422. Left here for reference / future fix (e.g. building
// the multipart body manually, or asking the backend dev to also accept
// "images[]").
function classify_images_batch_with_ai($files, $categories) {
    $results = [];
    if (empty($files)) return $results;

    if (!defined('AI_BACKEND_URL') || !defined('AI_BACKEND_API_KEY')) {
        foreach ($files as $f) {
            $results[$f['filename']] = suggest_category_from_filename($f['original']);
        }
        return $results;
    }

    $postFields = ['allowed_categories' => json_encode($categories)];
    $index = 0;
    foreach ($files as $f) {
        if (file_exists($f['path'])) {
            // Repeated "images" field, one per file — matches the batch API's contract.
            $postFields['images[' . $index . ']'] = new CURLFile($f['path']);
            $index++;
        }
    }

    $ch = curl_init(rtrim(AI_BACKEND_URL, '/') . '/api/classify-images-batch');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => defined('AI_BACKEND_BATCH_TIMEOUT') ? AI_BACKEND_BATCH_TIMEOUT : 60,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . AI_BACKEND_API_KEY],
        CURLOPT_POSTFIELDS => $postFields,
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError !== '' || $httpCode !== 200) {
        error_log("AI classify-images-batch failed: $curlError (HTTP $httpCode)");
        foreach ($files as $f) {
            $results[$f['filename']] = suggest_category_from_filename($f['original']);
        }
        return $results;
    }

    $data = json_decode($response, true);
    $minConfidence = defined('AI_MIN_CONFIDENCE') ? AI_MIN_CONFIDENCE : 0.5;

    // Index the API's per-file results by the filename it reports back.
    $byFilename = [];
    if (!empty($data['results']) && is_array($data['results'])) {
        foreach ($data['results'] as $r) {
            if (!empty($r['filename'])) $byFilename[$r['filename']] = $r;
        }
    }

    foreach ($files as $f) {
        $r = $byFilename[$f['filename']] ?? null;
        if (
            $r && !empty($r['success']) && !empty($r['category']) &&
            in_array($r['category'], $categories, true) &&
            ($r['confidence'] ?? 0) >= $minConfidence
        ) {
            $results[$f['filename']] = $r['category'];
        } else {
            $results[$f['filename']] = suggest_category_from_filename($f['original']);
        }
    }

    return $results;
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
        'pp_BillReference' => 'SaaqiFolioPro',
        'pp_Description' => 'SaaqiFolio Pro Plan Upgrade',
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

// ============================================
// Site Settings Helpers & Video Embed Parser
// ============================================
function get_setting($key, $default = '') {
    global $conn;
    static $settingsCache = [];
    if (isset($settingsCache[$key])) return $settingsCache[$key];
    if (!$conn) return $default;
    $stmt = mysqli_prepare($conn, "SELECT setting_value FROM site_settings WHERE setting_key = ?");
    if (!$stmt) return $default;
    mysqli_stmt_bind_param($stmt, 's', $key);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
        $settingsCache[$key] = $row['setting_value'];
        return $row['setting_value'];
    }
    $settingsCache[$key] = $default;
    return $default;
}

function set_setting($key, $value) {
    global $conn;
    if (!$conn) return false;
    $stmt = mysqli_prepare($conn, "INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'sss', $key, $value, $value);
    return mysqli_stmt_execute($stmt);
}

function format_embed_video_url($url) {
    $url = trim($url ?? '');
    if ($url === '') return '';

    // YouTube: youtu.be/ID or youtube.com/watch?v=ID or /embed/ID or /shorts/ID or /v/ID
    if (preg_match('/(?:youtu\.be\/|youtube\.com\/(?:embed\/|v\/|shorts\/|watch\?v=|watch\?.+&v=))([\w-]{11})/', $url, $m)) {
        return 'https://www.youtube-nocookie.com/embed/' . $m[1] . '?autoplay=1&rel=0&modestbranding=1';
    }

    // Vimeo: vimeo.com/ID or player.vimeo.com/video/ID
    if (preg_match('/vimeo\.com\/(?:video\/)?([0-9]+)/', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1] . '?autoplay=1&title=0&byline=0&portrait=0';
    }

    return $url;
}

// Global cache buster query string generator: style.css?v=1791325...
function asset_v($relativePath) {
    $clean = ltrim($relativePath, '/');
    $full = __DIR__ . '/../' . $clean;
    $v = file_exists($full) ? filemtime($full) : 1;
    return $relativePath . '?v=' . $v;
}

// Returns the web base URL path for the site (e.g. '/folivo' on local XAMPP, or '' on live root domain)
function get_site_root_url() {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $scriptDir = str_replace('\\', '/', dirname($script));
    $base = preg_replace('#/(auth|dashboard|admin|includes|database)$#', '', $scriptDir);
    return rtrim($base, '/');
}

// Resolves local filesystem path for an uploaded asset (avatars, portfolio, banners, cv).
// If running on localhost and the file is missing locally, fetches and caches it from
// production so server-side operations (like PDF generation) work without broken images.
function resolve_upload_path($subfolder, $filename) {
    if (empty($filename)) return null;
    $localPath = __DIR__ . '/../assets/uploads/' . trim($subfolder, '/') . '/' . $filename;
    if (file_exists($localPath)) {
        return $localPath;
    }
    $httpHost = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $isLocal = in_array($httpHost, ['localhost', '127.0.0.1', '::1']) || str_starts_with($httpHost, 'localhost:');
    if ($isLocal) {
        $liveUrl = 'https://saaqifolio.com/assets/uploads/' . trim($subfolder, '/') . '/' . rawurlencode($filename);
        $dir = dirname($localPath);
        if (!is_dir($dir)) @mkdir($dir, 0777, true);
        $content = @file_get_contents($liveUrl);
        if ($content !== false && strlen($content) > 0) {
            @file_put_contents($localPath, $content);
            return $localPath;
        }
    }
    return null;
}


