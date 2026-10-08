<?php
/**
 * Behance Artwork Scraper & Importer Helper for SaaqiFolio
 * Dual-Mode: Full cURL + Native PHP HTTP Streams fallback (Zero cURL dependency)
 */

if (!function_exists('behance_http_request')) {
    function behance_http_request($url, $cookie = '') {
        $ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36";
        $accept = "text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8";

        // MODE 1: cURL (if extension enabled)
        if (function_exists('curl_init')) {
            $ch = curl_init();
            $headers = [
                "User-Agent: $ua",
                "Accept: $accept",
                "Accept-Language: en-US,en;q=0.9",
                "Referer: https://www.google.com/"
            ];
            if ($cookie) {
                $headers[] = "Cookie: js_challenge_value=" . $cookie;
            }

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_HTTPHEADER => $headers
            ]);
            $res = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            // Adobe / Behance anti-bot challenge
            if (($code !== 200 || strpos($res, 'js_challenge_value=') !== false) && empty($cookie)) {
                if (preg_match('/js_challenge_value=([^;\'"\s]+)/', $res, $m)) {
                    curl_close($ch);
                    return behance_http_request($url, trim($m[1]));
                }
            }
            curl_close($ch);
            return ['code' => $code, 'body' => $res];
        }

        // MODE 2: Native PHP HTTP Streams (Zero cURL dependency, 100% resilient)
        $headers = [
            "User-Agent: $ua",
            "Accept: $accept",
            "Accept-Language: en-US,en;q=0.9",
            "Referer: https://www.google.com/"
        ];
        if ($cookie) {
            $headers[] = "Cookie: js_challenge_value=" . $cookie;
        }

        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", $headers) . "\r\n",
                'ignore_errors' => true,
                'follow_location' => 1,
                'timeout' => 25
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $body = @file_get_contents($url, false, $ctx);
        $code = 200;
        if (isset($http_response_header) && is_array($http_response_header)) {
            foreach ($http_response_header as $hdr) {
                if (preg_match('#HTTP/\S+\s+(\d+)#i', $hdr, $m)) {
                    $code = (int) $m[1];
                }
            }
        }

        // Check if anti-bot challenge received
        if (($code !== 200 || strpos($body, 'js_challenge_value=') !== false) && empty($cookie)) {
            if (preg_match('/js_challenge_value=([^;\'"\s]+)/', $body, $cm)) {
                return behance_http_request($url, trim($cm[1]));
            }
        }

        return ['code' => $code, 'body' => $body];
    }
}

/**
 * Downloads binary image data from Behance CDN (Dual cURL + Streams)
 */
if (!function_exists('behance_download_image')) {
    function behance_download_image($imgUrl) {
        $ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36";

        if (function_exists('curl_init')) {
            $ch = curl_init($imgUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 25,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_HTTPHEADER => [
                    "User-Agent: $ua",
                    "Referer: https://www.behance.net/"
                ]
            ]);
            $imgData = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code === 200 && !empty($imgData)) {
                return $imgData;
            }
        }

        // Native streams download
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: $ua\r\nReferer: https://www.behance.net/\r\n",
                'ignore_errors' => true,
                'follow_location' => 1,
                'timeout' => 25
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        return @file_get_contents($imgUrl, false, $ctx);
    }
}

/**
 * Extracts all project artworks or profile projects from a Behance URL
 */
function behance_extract_portfolio($url) {
    $url = trim($url);
    if (!preg_match('~^https?://~i', $url)) {
        $url = 'https://' . $url;
    }

    $resp = behance_http_request($url);
    if ($resp['code'] !== 200 || empty($resp['body'])) {
        return [
            'success' => false,
            'error' => "Unable to connect to Behance (HTTP " . ($resp['code'] ?: 'Network Error') . "). Please check the link."
        ];
    }

    $html = $resp['body'];

    // Search for SSR JSON state in script tags
    preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $html, $scripts);
    $stateData = null;
    foreach ($scripts[1] as $s) {
        $s = trim($s);
        if (strpos($s, '{"gates":') === 0 || strpos($s, '{"activity":') === 0 || strpos($s, '{"config":') === 0) {
            $decoded = json_decode($s, true);
            if ($decoded && (isset($decoded['project']) || isset($decoded['profile']))) {
                $stateData = $decoded;
                break;
            }
        }
    }

    // CASE 1: Single Project / Gallery Page
    if ($stateData && isset($stateData['project']['project'])) {
        $p = $stateData['project']['project'];
        $title = $p['name'] ?? 'Behance Artwork';
        $images = [];

        foreach ($p['modules'] ?? [] as $idx => $m) {
            $imgUrl = '';
            $thumbUrl = '';

            if (!empty($m['imageSizes']['allAvailable']) && is_array($m['imageSizes']['allAvailable'])) {
                $sizes = $m['imageSizes']['allAvailable'];
                usort($sizes, function($a, $b) {
                    return ($b['width'] ?? 0) <=> ($a['width'] ?? 0);
                });
                foreach ($sizes as $sz) {
                    if (!empty($sz['url'])) {
                        $imgUrl = $sz['url'];
                        break;
                    }
                }
                // Pick preview thumbnail
                foreach (array_reverse($sizes) as $sz) {
                    if (!empty($sz['url']) && ($sz['width'] ?? 0) >= 300) {
                        $thumbUrl = $sz['url'];
                        break;
                    }
                }
            }

            if (!$imgUrl && !empty($m['imageSizes'])) {
                $imgUrl = $m['imageSizes']['size_disp']['url'] ?? $m['src'] ?? '';
            }
            if (!$thumbUrl) $thumbUrl = $imgUrl;

            if ($imgUrl) {
                $caption = !empty($m['caption']) ? trim(strip_tags($m['caption'])) : '';
                $images[] = [
                    'url' => $imgUrl,
                    'preview' => $thumbUrl,
                    'title' => $caption ?: ($title . ' #' . (count($images) + 1)),
                    'alt' => $m['altText'] ?? '',
                ];
            }
        }

        if (empty($images)) {
            return ['success' => false, 'error' => 'No image artworks found in this Behance gallery.'];
        }

        return [
            'success' => true,
            'type' => 'gallery',
            'title' => $title,
            'total' => count($images),
            'images' => $images,
        ];
    }

    // CASE 2: User Profile Page
    if ($stateData && isset($stateData['profile']['activeSection']['work']['profileProjects'])) {
        $projects = $stateData['profile']['activeSection']['work']['profileProjects'];
        $creatorName = $stateData['profile']['user']['displayName'] ?? 'Behance Creator';

        $allImages = [];
        // Scan up to 3 projects to keep extraction blazing fast (< 3 seconds)
        $projectsToScan = array_slice($projects, 0, 3);

        foreach ($projectsToScan as $proj) {
            $projUrl = $proj['url'] ?? ('https://www.behance.net/gallery/' . ($proj['id'] ?? '') . '/project');
            $subData = behance_extract_portfolio($projUrl);
            if ($subData['success'] && !empty($subData['images'])) {
                foreach ($subData['images'] as $img) {
                    $allImages[] = $img;
                    // Cap at 25 images per profile import to avoid browser timeout
                    if (count($allImages) >= 25) break 2;
                }
            }
        }

        if (empty($allImages)) {
            // Fallback: use project covers
            foreach ($projects as $proj) {
                $cover = $proj['covers']['allAvailable'][0]['url'] ?? ($proj['covers']['original_webp'] ?? '');
                if ($cover) {
                    $allImages[] = [
                        'url' => $cover,
                        'preview' => $cover,
                        'title' => $proj['name'] ?? 'Project Artwork',
                        'alt' => '',
                    ];
                }
            }
        }

        if (empty($allImages)) {
            return ['success' => false, 'error' => "No public portfolio projects found on this user's Behance profile."];
        }

        return [
            'success' => true,
            'type' => 'profile',
            'title' => "$creatorName's Portfolio",
            'total' => count($allImages),
            'images' => $allImages,
        ];
    }

    // CASE 3: Fallback Regex Extraction if SSR JSON was obscured
    preg_match_all('/https:\/\/mir-s3-cdn-cf\.behance\.net\/project_modules\/(?:fs|source|hd|max_1200)[^"\'\s\\\]+/i', $html, $regImgs);
    $unique = array_values(array_unique(array_map('stripslashes', $regImgs[0] ?? [])));
    if (!empty($unique)) {
        $images = [];
        foreach ($unique as $idx => $u) {
            $images[] = [
                'url' => $u,
                'preview' => $u,
                'title' => 'Behance Piece #' . ($idx + 1),
                'alt' => '',
            ];
        }
        return [
            'success' => true,
            'type' => 'gallery',
            'title' => 'Behance Project',
            'total' => count($images),
            'images' => $images,
        ];
    }

    return ['success' => false, 'error' => "Could not extract images. Please ensure the Behance link is public and accessible."];
}

/**
 * Downloads a single image from Behance CDN, optimizes thumbnail, runs existing AI categorization, and saves into DB.
 */
function behance_import_and_classify_single($userId, $imgItem, $categories, $conn) {
    $imgUrl = $imgItem['url'] ?? '';
    if (empty($imgUrl)) {
        return ['success' => false, 'error' => 'Invalid image URL'];
    }

    // 1. Download image stream (Dual cURL + Streams)
    $imgData = behance_download_image($imgUrl);
    if (empty($imgData)) {
        return ['success' => false, 'error' => "Failed to download artwork from Behance CDN"];
    }

    // 2. Determine file extension
    $ext = 'jpg';
    if (str_starts_with($imgData, "\x89PNG")) {
        $ext = 'png';
    } elseif (str_starts_with($imgData, "RIFF") && strpos(substr($imgData, 8, 4), "WEBP") !== false) {
        $ext = 'webp';
    } elseif (strpos(substr($imgData, 4, 8), "ftypavif") !== false) {
        $ext = 'avif';
    }

    // 3. Save to portfolio directory
    $uploadDir = __DIR__ . '/../assets/uploads/portfolio';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }

    $newFilename = 'img_behance_' . str_replace('.', '', uniqid('', true)) . '.' . $ext;
    $destination = rtrim($uploadDir, '/') . '/' . $newFilename;

    if (@file_put_contents($destination, $imgData) === false) {
        return ['success' => false, 'error' => 'Failed to save downloaded image to server storage'];
    }

    // 4. Generate optimized AVIF/WebP thumbnail for lightning-fast gallery render
    generate_portfolio_thumbnail($destination);

    // 5. Run existing Gemini AI categorization
    $category = 'Other';
    $descriptiveName = trim(($imgItem['title'] ?? '') . ' ' . ($imgItem['alt'] ?? ''));
    if (empty($descriptiveName)) {
        $descriptiveName = $newFilename;
    }

    try {
        if (function_exists('classify_image_with_ai')) {
            $category = classify_image_with_ai($destination, $descriptiveName, $categories);
        }
    } catch (\Throwable $e) {
        error_log("Behance import classify_image_with_ai error: " . $e->getMessage());
        $category = 'Other';
    }

    if (empty($category) || !in_array($category, $categories, true)) {
        $category = 'Other';
    }

    // 6. Insert record into database
    $ins = mysqli_prepare($conn, "INSERT INTO portfolio_images (user_id, filename, category) VALUES (?,?,?)");
    mysqli_stmt_bind_param($ins, 'iss', $userId, $newFilename, $category);
    $inserted = mysqli_stmt_execute($ins);

    if (!$inserted) {
        @unlink($destination);
        return ['success' => false, 'error' => 'Database error saving portfolio image'];
    }

    $imageId = mysqli_insert_id($conn);

    return [
        'success' => true,
        'image' => [
            'id' => $imageId,
            'filename' => $newFilename,
            'category' => $category,
            'title' => $imgItem['title'] ?? 'Artwork',
        ]
    ];
}
