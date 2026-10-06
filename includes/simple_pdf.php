<?php
/**
 * SAAQIFOLIO - Luxury PDF Engine (SimplePdf v5.0)
 *
 * Generates executive, luxury editorial styled PDF portfolios
 * with 3 distinct luxury design templates (Obsidian Noir, Minimalist Atelier, Creative Studio)
 * with zero external dependencies (no Composer, no dompdf).
 * Uses Adobe Type 1 core fonts (Helvetica, Helvetica-Bold, Times).
 */

class SimplePdf {
    private array $objects = [];
    private array $pageObjNums = [];
    private array $pagesContent = [];
    private array $pagesResources = [];
    private int $curPage = -1;
    private int $imgCounter = 0;
    private array $imageCache = [];
    private array $fontMetrics = [];
    private static array $cachedFontMetrics = [];

    public float $pw = 595.28; // A4 width in points
    public float $ph = 841.89; // A4 height in points

    private int $fontHelvetica;
    private int $fontHelveticaBold;
    private int $fontTimesBold;
    private int $fontTimesItalic;
    private int $fontTimesRoman;

    // Design System Color Tokens (RGB 0-255)
    public const BG           = [7, 7, 11];       // #07070b - Obsidian Noir
    public const SURFACE      = [13, 11, 23];     // #0d0b17 - Deep surface
    public const ELEV         = [20, 17, 36];     // #141124 - Elevated card
    public const ELEV2        = [27, 23, 48];     // #1b1730 - High elevation
    public const BORDER       = [35, 31, 56];     // #231f38 - Glass border
    public const TEXT         = [245, 243, 239];  // #f5f3ef - Crisp white/cream
    public const TEXT_SOFT    = [196, 194, 212];  // #c4c2d4 - Soft body text
    public const TEXT_2       = [156, 160, 184];  // #9ca0b8 - Secondary captions
    public const TEXT_MUTED   = [100, 103, 125];  // #64677d - Faint / folio
    public const PURPLE       = [139, 92, 246];   // #8b5cf6 - SaaqiFolio Vivid Purple
    public const PURPLE_LIGHT = [167, 139, 250];  // #a78bfa - Soft Violet Glow
    public const CORAL        = [255, 107, 74];   // #ff6b4a - Neon Coral Accent

    // Dynamic Theme Variables
    public string $theme = 'obsidian';
    public array $cBg = self::BG;
    public array $cSurface = self::SURFACE;
    public array $cElev = self::ELEV;
    public array $cElev2 = self::ELEV2;
    public array $cBorder = self::BORDER;
    public array $cText = self::TEXT;
    public array $cTextSoft = self::TEXT_SOFT;
    public array $cText2 = self::TEXT_2;
    public array $cTextMuted = self::TEXT_MUTED;
    public array $cAccent1 = self::PURPLE;
    public array $cAccent2 = self::CORAL;
    public array $cAccentLight = self::PURPLE_LIGHT;

    public function __construct(string $theme = 'obsidian') {
        $this->fontHelvetica     = $this->addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>');
        $this->fontHelveticaBold = $this->addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>');
        $this->fontTimesBold     = $this->addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Times-Bold /Encoding /WinAnsiEncoding >>');
        $this->fontTimesItalic   = $this->addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Times-Italic /Encoding /WinAnsiEncoding >>');
        $this->fontTimesRoman    = $this->addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Times-Roman /Encoding /WinAnsiEncoding >>');

        if (empty(self::$cachedFontMetrics)) {
            $metricsFile = __DIR__ . '/pdf_font_metrics.php';
            if (file_exists($metricsFile)) {
                self::$cachedFontMetrics = require $metricsFile;
            }
        }
        $this->fontMetrics = self::$cachedFontMetrics;

        $this->setTheme($theme);
    }

    public function setTheme(string $theme = 'obsidian'): void {
        $this->theme = strtolower($theme);
        if ($this->theme === 'atelier') {
            // Minimalist Atelier (Editorial Light Gallery)
            $this->cBg          = [250, 249, 246]; // #faf9f6 warm ivory
            $this->cSurface     = [242, 240, 235]; // #f2f0eb soft paper
            $this->cElev        = [234, 231, 224]; // #eae7e0 card surface
            $this->cElev2       = [226, 222, 214]; // #e2ded6 card high elevation
            $this->cBorder      = [214, 210, 200]; // #d6d2c8 crisp border
            $this->cText        = [18, 18, 20];    // #121214 deep onyx
            $this->cTextSoft    = [55, 55, 62];    // #37373e charcoal
            $this->cText2       = [95, 95, 106];   // #5f5f6a slate
            $this->cTextMuted   = [150, 148, 158]; // #96949e muted
            $this->cAccent1     = [175, 130, 20];  // #af8214 champagne gold
            $this->cAccent2     = [60, 65, 85];    // #3c4155 dark slate
            $this->cAccentLight = [175, 130, 20];
        } elseif ($this->theme === 'creative') {
            // Creative Studio (Vibrant Modern Duo)
            $this->cBg          = [10, 15, 29];    // #0a0f1d deep midnight navy
            $this->cSurface     = [17, 24, 39];    // #111827
            $this->cElev        = [31, 41, 55];    // #1f2937
            $this->cElev2       = [45, 55, 72];    // #2d3748
            $this->cBorder      = [55, 65, 81];    // #374151
            $this->cText        = [249, 250, 251]; // #f9fafb pure white
            $this->cTextSoft    = [209, 213, 219]; // #d1d5db
            $this->cText2       = [156, 163, 175]; // #9ca3af
            $this->cTextMuted   = [107, 114, 128]; // #6b7280
            $this->cAccent1     = [6, 182, 212];   // #06b6d4 electric cyan
            $this->cAccent2     = [236, 72, 153];  // #ec4899 neon magenta
            $this->cAccentLight = [6, 182, 212];
        } else {
            // Obsidian Noir (Signature Luxury Dark)
            $this->theme        = 'obsidian';
            $this->cBg          = self::BG;
            $this->cSurface     = self::SURFACE;
            $this->cElev        = self::ELEV;
            $this->cElev2       = self::ELEV2;
            $this->cBorder      = self::BORDER;
            $this->cText        = self::TEXT;
            $this->cTextSoft    = self::TEXT_SOFT;
            $this->cText2       = self::TEXT_2;
            $this->cTextMuted   = self::TEXT_MUTED;
            $this->cAccent1     = self::PURPLE;
            $this->cAccent2     = self::CORAL;
            $this->cAccentLight = self::PURPLE_LIGHT;
        }
    }

    private function addObject($body): int {
        $this->objects[] = $body;
        return count($this->objects);
    }

    public function addBlankPage(): void {
        $num = $this->addObject('');
        $this->pageObjNums[] = $num;
        $this->pagesContent[] = '';
        $this->pagesResources[] = [];
        $this->curPage++;

        // Fill full page with theme background
        $this->fillRect(0, 0, $this->pw, $this->ph, $this->cBg);
    }

    public function currentPageNum(): int {
        return $this->curPage + 1;
    }

    // ---------- Text Helpers & Typography ----------

    public function sanitizeText(string $text): string {
        $converted = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $text);
        if ($converted === false) {
            $converted = preg_replace('/[^\x20-\x7E]/', '', $text);
        }
        return $converted ?: '';
    }

    private function escapePdfString(string $text): string {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    public function textWidth(string $text, float $size = 12, string $fontKey = 'FR'): float {
        $clean = $this->sanitizeText($text);
        $table = $this->fontMetrics[$fontKey] ?? ($this->fontMetrics['FR'] ?? null);
        $len = strlen($clean);
        $units = 0;
        if ($table) {
            for ($i = 0; $i < $len; $i++) {
                $ord = ord($clean[$i]);
                $units += $table[$ord] ?? 556;
            }
        } else {
            $factor = ($fontKey === 'FB' || $fontKey === 'FSB') ? 560 : 500;
            $units = $len * $factor;
        }
        return ($units / 1000.0) * $size;
    }

    public function clip(string $text, float $maxWidth, float $size = 12, string $fontKey = 'FR'): string {
        $clean = $this->sanitizeText($text);
        if ($this->textWidth($clean, $size, $fontKey) <= $maxWidth) {
            return $clean;
        }
        $ellipsis = '...';
        $wEll = $this->textWidth($ellipsis, $size, $fontKey);
        $target = $maxWidth - $wEll;
        if ($target <= 0) return $ellipsis;

        $len = strlen($clean);
        while ($len > 0 && $this->textWidth(substr($clean, 0, $len), $size, $fontKey) > $target) {
            $len--;
        }
        return rtrim(substr($clean, 0, $len)) . $ellipsis;
    }

    public static function usableText(?string $str): bool {
        if ($str === null) return false;
        $trim = trim($str);
        if ($trim === '') return false;
        // Require at least one group of 3 letters (filters out junk like "564" or "-")
        return (bool)preg_match('/\p{L}{3,}/u', $trim);
    }

    public function drawText(string $text, float $x, float $yTop, float $size, string $fontKey = 'FR', ?array $color = null, $align = 'L'): void {
        $clean = $this->sanitizeText($text);
        if ($clean === '') return;

        $color = $color ?? $this->cText;

        $pdfFont = match ($fontKey) {
            'FB'  => '/FB',
            'FSB' => '/FSB',
            'FSI' => '/FSI',
            'FS'  => '/FS',
            default => '/FR',
        };

        if ($align === true || $align === 'C') {
            $w = $this->textWidth($clean, $size, $fontKey);
            $x = $x - ($w / 2.0);
        } elseif ($align === 'R') {
            $w = $this->textWidth($clean, $size, $fontKey);
            $x = $x - $w;
        }

        $pdfY = $this->ph - $yTop;
        $r = $color[0] / 255.0;
        $g = $color[1] / 255.0;
        $b = $color[2] / 255.0;
        $escaped = $this->escapePdfString($clean);

        $this->pagesContent[$this->curPage] .= sprintf(
            "BT %s %.2f Tf %.3f %.3f %.3f rg 1 0 0 1 %.2f %.2f Tm (%s) Tj ET\n",
            $pdfFont, $size, $r, $g, $b, $x, $pdfY, $escaped
        );
    }

    public function drawWrappedText(string $text, float $x, float $yTop, float $maxWidth, float $size, string $fontKey, ?array $color = null, float $lineHeight = 15.0, $align = 'L'): float {
        $clean = $this->sanitizeText($text);
        if ($clean === '') return $yTop;

        $color = $color ?? $this->cTextSoft;

        $paragraphs = preg_split('/\r\n|\r|\n/', $clean);
        $curY = $yTop;

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para === '') {
                $curY += $lineHeight * 0.5;
                continue;
            }
            $words = preg_split('/\s+/', $para);
            $line = '';
            foreach ($words as $w) {
                $test = ($line === '') ? $w : $line . ' ' . $w;
                if ($this->textWidth($test, $size, $fontKey) <= $maxWidth) {
                    $line = $test;
                } else {
                    if ($line !== '') {
                        $this->drawText($line, $x, $curY, $size, $fontKey, $color, $align);
                        $curY += $lineHeight;
                    }
                    $line = $w;
                }
            }
            if ($line !== '') {
                $this->drawText($line, $x, $curY, $size, $fontKey, $color, $align);
                $curY += $lineHeight;
            }
        }
        return $curY;
    }

    // ---------- Path & Shape Drawing ----------

    public function paint(?array $fillColor = null, ?array $strokeColor = null, float $lineWidth = 1.0): string {
        $out = "";
        if ($strokeColor !== null) {
            $out .= sprintf("%.2f w\n", $lineWidth);
            $out .= sprintf("%.3f %.3f %.3f RG\n", $strokeColor[0]/255.0, $strokeColor[1]/255.0, $strokeColor[2]/255.0);
        }
        if ($fillColor !== null) {
            $out .= sprintf("%.3f %.3f %.3f rg\n", $fillColor[0]/255.0, $fillColor[1]/255.0, $fillColor[2]/255.0);
        }
        if ($fillColor !== null && $strokeColor !== null) {
            $out .= "B\n";
        } elseif ($fillColor !== null) {
            $out .= "f\n";
        } elseif ($strokeColor !== null) {
            $out .= "S\n";
        } else {
            $out .= "n\n";
        }
        return $out;
    }

    public function rrPath(float $x, float $yTop, float $w, float $h, float $r): string {
        $pdfY = $this->ph - $yTop - $h;
        $r = max(0, min($r, min($w, $h) / 2.0));
        if ($r <= 0.001) {
            return sprintf("%.2f %.2f %.2f %.2f re\n", $x, $pdfY, $w, $h);
        }
        $k = 0.552284749831 * $r;
        $x0 = $x; $x1 = $x + $r; $x2 = $x + $w - $r; $x3 = $x + $w;
        $y0 = $pdfY; $y1 = $pdfY + $r; $y2 = $pdfY + $h - $r; $y3 = $pdfY + $h;

        return sprintf(
            "%.2f %.2f m\n" .
            "%.2f %.2f l\n" .
            "%.2f %.2f %.2f %.2f %.2f %.2f c\n" .
            "%.2f %.2f l\n" .
            "%.2f %.2f %.2f %.2f %.2f %.2f c\n" .
            "%.2f %.2f l\n" .
            "%.2f %.2f %.2f %.2f %.2f %.2f c\n" .
            "%.2f %.2f l\n" .
            "%.2f %.2f %.2f %.2f %.2f %.2f c\n",
            $x1, $y0,
            $x2, $y0,
            $x2 + $k, $y0, $x3, $y1 - $k, $x3, $y1,
            $x3, $y2,
            $x3, $y2 + $k, $x2 + $k, $y3, $x2, $y3,
            $x1, $y3,
            $x1 - $k, $y3, $x0, $y2 + $k, $x0, $y2,
            $x0, $y1,
            $x0, $y1 - $k, $x1 - $k, $y0, $x1, $y0
        );
    }

    public function circlePath(float $cx, float $yTopCenter, float $r): string {
        $cy = $this->ph - $yTopCenter;
        $k = 0.552284749831 * $r;
        return sprintf(
            "%.2f %.2f m\n" .
            "%.2f %.2f %.2f %.2f %.2f %.2f c\n" .
            "%.2f %.2f %.2f %.2f %.2f %.2f c\n" .
            "%.2f %.2f %.2f %.2f %.2f %.2f c\n" .
            "%.2f %.2f %.2f %.2f %.2f %.2f c\n",
            $cx, $cy + $r,
            $cx + $k, $cy + $r, $cx + $r, $cy + $k, $cx + $r, $cy,
            $cx + $r, $cy - $k, $cx + $k, $cy - $r, $cx, $cy - $r,
            $cx - $k, $cy - $r, $cx - $r, $cy - $k, $cx - $r, $cy,
            $cx - $r, $cy + $k, $cx - $k, $cy + $r, $cx, $cy + $r
        );
    }

    public function fillRect(float $x, float $yTop, float $w, float $h, array $color): void {
        $pdfY = $this->ph - $yTop - $h;
        $r = $color[0] / 255.0;
        $g = $color[1] / 255.0;
        $b = $color[2] / 255.0;
        $this->pagesContent[$this->curPage] .= sprintf(
            "q %.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f Q\n",
            $r, $g, $b, $x, $pdfY, $w, $h
        );
    }

    public function drawLine(float $x1, float $y1Top, float $x2, float $y2Top, ?array $color = null, float $width = 0.8): void {
        $color = $color ?? $this->cBorder;
        $pdfY1 = $this->ph - $y1Top;
        $pdfY2 = $this->ph - $y2Top;
        $r = $color[0] / 255.0;
        $g = $color[1] / 255.0;
        $b = $color[2] / 255.0;
        $this->pagesContent[$this->curPage] .= sprintf(
            "q %.2f w %.3f %.3f %.3f RG %.2f %.2f m %.2f %.2f l S Q\n",
            $width, $r, $g, $b, $x1, $pdfY1, $x2, $pdfY2
        );
    }

    public function drawRoundedRect(float $x, float $yTop, float $w, float $h, float $r = 0, ?array $fillColor = null, ?array $strokeColor = null, float $lineWidth = 1.0): void {
        $this->pagesContent[$this->curPage] .= "q\n" . $this->rrPath($x, $yTop, $w, $h, $r) . $this->paint($fillColor, $strokeColor, $lineWidth) . "Q\n";
    }

    public function drawCircle(float $cx, float $yTopCenter, float $r, ?array $fillColor = null, ?array $strokeColor = null, float $lineWidth = 1.0): void {
        $this->pagesContent[$this->curPage] .= "q\n" . $this->circlePath($cx, $yTopCenter, $r) . $this->paint($fillColor, $strokeColor, $lineWidth) . "Q\n";
    }

    public function gradientBar(float $x, float $yTop, float $w, float $h): void {
        $steps = 30;
        $stepW = $w / (float)$steps;
        $c1 = $this->cAccent1;
        $c2 = $this->cAccent2;
        $pdfY = $this->ph - $yTop - $h;

        $ops = "q\n";
        for ($i = 0; $i < $steps; $i++) {
            $t = $i / (float)($steps - 1);
            $r = ($c1[0] + $t * ($c2[0] - $c1[0])) / 255.0;
            $g = ($c1[1] + $t * ($c2[1] - $c1[1])) / 255.0;
            $b = ($c1[2] + $t * ($c2[2] - $c1[2])) / 255.0;
            $sx = $x + ($i * $stepW);
            $ops .= sprintf("%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f\n", $r, $g, $b, $sx, $pdfY, $stepW + 0.15, $h);
        }
        $ops .= "Q\n";
        $this->pagesContent[$this->curPage] .= $ops;
    }

    public function glow(float $cx, float $yTopCenter, float $radius, ?array $color = null, int $steps = 8): void {
        $color = $color ?? $this->cAccent1;
        $bg = $this->cBg;
        $maxAlpha = ($this->theme === 'atelier') ? 0.08 : 0.28;
        for ($i = $steps; $i >= 1; $i--) {
            $t = $i / (float)$steps;
            $alpha = (1.0 - $t) * $maxAlpha;
            $r = (int)round($bg[0] + $alpha * ($color[0] - $bg[0]));
            $g = (int)round($bg[1] + $alpha * ($color[1] - $bg[1]));
            $b = (int)round($bg[2] + $alpha * ($color[2] - $bg[2]));
            $curR = $radius * (0.35 + 0.65 * $t);
            $this->drawCircle($cx, $yTopCenter, $curR, [$r, $g, $b], null);
        }
    }

    // ---------- Image Embedding, Caching & Boxing ----------

    public function embedImage(string $filePath): ?array {
        $real = realpath($filePath);
        if (!$real || !file_exists($real)) {
            return null;
        }
        if (isset($this->imageCache[$real])) {
            return $this->imageCache[$real];
        }

        $info = @getimagesize($real);
        if (!$info) return null;

        $origW = $info[0];
        $origH = $info[1];
        $mime = $info['mime'] ?? '';

        $maxDim = 1600;
        $scale = 1.0;
        if ($origW > $maxDim || $origH > $maxDim) {
            $scale = min($maxDim / (float)$origW, $maxDim / (float)$origH);
        }
        $targetW = (int)round($origW * $scale);
        $targetH = (int)round($origH * $scale);

        if ($mime === 'image/jpeg' && $scale >= 0.999) {
            $data = file_get_contents($real);
            $filter = '/DCTDecode';
            $w = $origW;
            $h = $origH;
        } else {
            $src = match ($mime) {
                'image/jpeg' => @imagecreatefromjpeg($real),
                'image/png'  => @imagecreatefrompng($real),
                'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($real) : null,
                default      => null,
            };
            if (!$src) return null;

            $dst = imagecreatetruecolor($targetW, $targetH);
            // Solid background under transparent images matching theme background
            $bgCol = imagecolorallocate($dst, $this->cBg[0], $this->cBg[1], $this->cBg[2]);
            imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $bgCol);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);
            imagedestroy($src);

            ob_start();
            imagejpeg($dst, null, 88);
            $data = ob_get_clean();
            imagedestroy($dst);
            $filter = '/DCTDecode';
            $w = $targetW;
            $h = $targetH;
        }

        $objNum = $this->addObject([
            'dict' => "<< /Type /XObject /Subtype /Image /Width $w /Height $h /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter $filter /Length " . strlen($data) . " >>",
            'stream' => $data,
        ]);

        $this->imgCounter++;
        $name = 'Im' . $this->imgCounter;
        $res = [
            'name' => $name,
            'objNum' => $objNum,
            'w' => $w,
            'h' => $h,
            'ar' => (float)$w / (float)$h,
        ];
        $this->imageCache[$real] = $res;
        return $res;
    }

    public function drawImageBox(string $filePath, float $x, float $yTop, float $w, float $h, float $radius = 0, string $shape = 'rounded', string $fit = 'contain'): void {
        $img = $this->embedImage($filePath);
        if (!$img) return;

        $this->pagesResources[$this->curPage][$img['name']] = $img['objNum'];

        $ar = $img['ar'];
        $boxAR = $w / $h;

        if ($fit === 'cover') {
            if ($ar > $boxAR) {
                $ih = $h;
                $iw = $h * $ar;
                $ix = $x - ($iw - $w) / 2.0;
                $iyTop = $yTop;
            } else {
                $iw = $w;
                $ih = $w / $ar;
                $ix = $x;
                $iyTop = $yTop - ($ih - $h) / 2.0;
            }
        } else {
            // contain: preserve full design, zero distortion
            if ($ar > $boxAR) {
                $iw = $w;
                $ih = $w / $ar;
                $ix = $x;
                $iyTop = $yTop + ($h - $ih) / 2.0;
            } else {
                $ih = $h;
                $iw = $h * $ar;
                $ix = $x + ($w - $iw) / 2.0;
                $iyTop = $yTop;
            }
        }

        if ($shape === 'circle') {
            $cx = $x + $w / 2.0;
            $cy = $yTop + $h / 2.0;
            $r = min($w, $h) / 2.0;
            $clipPath = $this->circlePath($cx, $cy, $r);
        } elseif ($shape === 'rounded' && $radius > 0) {
            $clipBoxX = ($fit === 'contain') ? $ix : $x;
            $clipBoxY = ($fit === 'contain') ? $iyTop : $yTop;
            $clipBoxW = ($fit === 'contain') ? $iw : $w;
            $clipBoxH = ($fit === 'contain') ? $ih : $h;
            $clipPath = $this->rrPath($clipBoxX, $clipBoxY, $clipBoxW, $clipBoxH, $radius);
        } else {
            $clipBoxX = ($fit === 'contain') ? $ix : $x;
            $clipBoxY = ($fit === 'contain') ? $this->ph - $iyTop - $ih : $this->ph - $yTop - $h;
            $clipBoxW = ($fit === 'contain') ? $iw : $w;
            $clipBoxH = ($fit === 'contain') ? $ih : $h;
            $clipPath = sprintf("%.2f %.2f %.2f %.2f re\n", $clipBoxX, $clipBoxY, $clipBoxW, $clipBoxH);
        }

        $pdfImgY = $this->ph - $iyTop - $ih;
        $this->pagesContent[$this->curPage] .=
            "q\n" .
            $clipPath .
            "W n\n" .
            sprintf("%.2f 0 0 %.2f %.2f %.2f cm\n/%s Do\n", $iw, $ih, $ix, $pdfImgY, $img['name']) .
            "Q\n";
    }

    // ---------- High-Level Page Rendering Templates ----------

    /**
     * Page 1: Balanced Luxury Cover Page
     */
    public function renderCoverPage(string $name, string $role, string $tagline, array $contact, string $curatedDate = '', ?string $avatarPath = null): void {
        $this->addBlankPage();

        // Top Left Kicker
        $kickerText = "SAAQIFOLIO | PORTFOLIO " . date('Y');
        $this->drawText($kickerText, 45, 54, 8.5, 'FR', $this->cAccentLight);

        $cx = $this->pw / 2.0;
        $cyTop = 280.0;
        $radius = 82.0;

        // Background Ambient Glow
        $this->glow($cx, $cyTop, 140, $this->cAccent1, 9);

        // Circular Avatar or Initials Fallback
        if ($avatarPath && file_exists($avatarPath)) {
            $this->drawImageBox($avatarPath, $cx - $radius, $cyTop - $radius, $radius * 2, $radius * 2, $radius, 'circle');
            $this->drawCircle($cx, $cyTop, $radius + 3, null, $this->cAccent1, 2.4);
        } else {
            $this->drawCircle($cx, $cyTop, $radius, $this->cElev2, $this->cAccent1, 2.4);
            $initial = strtoupper(substr(trim($name ?: 'U'), 0, 1));
            $this->drawText($initial, $cx, $cyTop + 20, 56, 'FB', $this->cText, 'C');
        }

        // Creator Identity
        $this->drawText($name, $cx, 415, 36, 'FB', $this->cText, 'C');
        $this->drawText($role, $cx, 448, 14, 'FR', $this->cAccentLight, 'C');
        $this->gradientBar($cx - 25, 466, 50, 3.5);

        // Credential Subhead
        $this->drawText("CURATED DESIGN SHOWCASE & WORKS", $cx, 500, 9, 'FR', $this->cText2, 'C');

        // Contact Information Group
        $contactY = 560.0;
        foreach (array_slice($contact, 0, 3) as $c) {
            $this->drawText($c, $cx, $contactY, 10, 'FR', $this->cText2, 'C');
            $contactY += 20.0;
        }

        // Verified Creator Badge Pill
        $badgeW = 270.0;
        $this->drawRoundedRect($cx - $badgeW/2.0, 670, $badgeW, 30, 15, $this->cElev, $this->cBorder);
        $badgeTitle = ($this->theme === 'atelier') ? "CURATED ATELIER EDITION // " . date('Y') : (($this->theme === 'creative') ? "STUDIO PRO SHOWCASE // " . date('Y') : "VERIFIED SAAQIFOLIO CREATOR // " . date('Y'));
        $this->drawText($badgeTitle, $cx, 689, 8.5, 'FB', $this->cAccentLight, 'C');

        // Bottom Curated Date
        if ($curatedDate === '') {
            $curatedDate = 'VERIFIED SAAQIFOLIO DESIGNER | CURATED ' . strtoupper(date('j M Y'));
        }
        $this->drawText($curatedDate, $cx, 770, 8, 'FR', $this->cTextMuted, 'C');
    }

    /**
     * Page 2: Balanced Profile Page (Stats, About, Experience, Tools, Contact)
     */
    public function renderProfilePage(
        string $name,
        string $role,
        string $bio,
        int $projectCount,
        int $catCount,
        array $tools,
        array $contact,
        ?string $education = null,
        ?string $experience = null
    ): void {
        $this->addBlankPage();

        // Headline & Accent Bar
        $this->drawText("Profile", 45, 75, 26, 'FB', $this->cText);
        $this->gradientBar(45, 87, 40, 3);

        // 3 Modern Stat Cards
        $statY = 120.0;
        $cardW = 158.0;
        $cardH = 76.0;
        $gap = 15.5;

        // Card 1: Projects
        $this->drawRoundedRect(45, $statY, $cardW, $cardH, 14, $this->cSurface, $this->cBorder);
        $this->drawText((string)$projectCount, 45 + $cardW/2.0, $statY + 34, 22, 'FB', $this->cAccentLight, 'C');
        $this->drawText("Projects", 45 + $cardW/2.0, $statY + 56, 9.0, 'FR', $this->cText2, 'C');

        // Card 2: Categories
        $this->drawRoundedRect(45 + $cardW + $gap, $statY, $cardW, $cardH, 14, $this->cSurface, $this->cBorder);
        $this->drawText((string)$catCount, 45 + $cardW + $gap + $cardW/2.0, $statY + 34, 22, 'FB', $this->cAccentLight, 'C');
        $this->drawText("Categories", 45 + $cardW + $gap + $cardW/2.0, $statY + 56, 9.0, 'FR', $this->cText2, 'C');

        // Card 3: Tools
        $toolCount = count($tools);
        $this->drawRoundedRect(45 + ($cardW + $gap)*2, $statY, $cardW, $cardH, 14, $this->cSurface, $this->cBorder);
        $this->drawText((string)$toolCount, 45 + ($cardW + $gap)*2 + $cardW/2.0, $statY + 34, 22, 'FB', $this->cAccentLight, 'C');
        $this->drawText("Tools", 45 + ($cardW + $gap)*2 + $cardW/2.0, $statY + 56, 9.0, 'FR', $this->cText2, 'C');

        $curY = 230.0;

        // About / Professional Statement
        $hasBio = self::usableText($bio);
        $displayBio = $hasBio ? trim($bio) : "Creative and detail-oriented " . ($role ?: 'Designer') . " dedicated to crafting compelling visual identities, brand systems, and engaging digital media for forward-thinking clients worldwide.";

        $this->drawText("About", 45, $curY, 12, 'FB', $this->cAccentLight);
        $curY += 20;
        $curY = $this->drawWrappedText($displayBio, 45, $curY, 505, 10.0, 'FR', $this->cTextSoft, 15);
        $curY += 30;

        // Experience Section (if real text exists)
        if (self::usableText($experience)) {
            $this->drawText("Experience", 45, $curY, 12, 'FB', $this->cAccentLight);
            $curY += 20;
            $curY = $this->drawWrappedText($experience, 45, $curY, 505, 10.0, 'FR', $this->cTextSoft, 15);
            $curY += 30;
        }

        // Education Section (if real text exists)
        if (self::usableText($education)) {
            $this->drawText("Education", 45, $curY, 12, 'FB', $this->cAccentLight);
            $curY += 20;
            $curY = $this->drawWrappedText($education, 45, $curY, 505, 10.0, 'FR', $this->cTextSoft, 15);
            $curY += 30;
        }

        // Software & Technical Expertise (Tools Chips)
        if (!empty($tools)) {
            $this->drawText("Software & technical expertise", 45, $curY, 12, 'FB', $this->cAccentLight);
            $curY += 22;

            $chipX = 45.0;
            $chipH = 26.0;
            foreach ($tools as $t) {
                $tname = trim($t);
                if ($tname === '') continue;
                $tw = $this->textWidth($tname, 9.0, 'FR');
                $chipW = $tw + 26.0;
                if ($chipX + $chipW > 550.0) {
                    $chipX = 45.0;
                    $curY += 34.0;
                }
                $this->drawRoundedRect($chipX, $curY, $chipW, $chipH, 13, $this->cElev, $this->cBorder);
                $this->drawText($tname, $chipX + $chipW/2.0, $curY + 17.0, 9.0, 'FR', $this->cText, 'C');
                $chipX += $chipW + 9.0;
            }
            $curY += 46.0;
        }

        // Direct Inquiries & Collaboration Card
        if (!empty($contact)) {
            $contactCardH = 105.0;
            $this->drawRoundedRect(45, $curY, 505, $contactCardH, 16, $this->cSurface, $this->cBorder);

            // Left content
            $this->drawText("Direct Inquiries & Bookings", 65, $curY + 28, 11.5, 'FB', $this->cAccentLight);
            $this->drawText("Available for freelance commissions, brand design, and full-time creative work.", 65, $curY + 48, 9.5, 'FR', $this->cTextSoft);

            // Contact items row
            $infoY = $curY + 78;
            $infoX = 65.0;
            foreach (array_slice($contact, 0, 3) as $idx => $c) {
                $this->drawText($c, $infoX, $infoY, 9.5, 'FB', $this->cText);
                $infoX += $this->textWidth($c, 9.5, 'FB') + 20.0;
                if ($idx < count($contact) - 1 && $infoX < 500) {
                    $this->drawText("•", $infoX - 12, $infoY, 10, 'FR', $this->cTextMuted);
                }
            }
        }

        // Running Footer
        $this->renderFooter($name, $role);
    }

    /**
     * Pages 3+: Category Showcase with Page-Filling Justified Layouts
     */
    public function renderCategoryShowcasePage(string $catName, array $images, string $creatorName, string $creatorRole, $ignored = null): void {
        if (empty($images)) return;

        $W = 505.0;   // Available content width
        $gap = 14.0;  // Gap between images
        $startY = 145.0;
        $bottomLimit = 780.0;
        $availH = $bottomLimit - $startY; // 635 points

        // Inspect aspect ratios for each image
        $items = [];
        foreach ($images as $p) {
            $img = $this->embedImage($p);
            if (!$img) continue;
            $items[] = [
                'path' => $p,
                'name' => $img['name'],
                'ar' => $img['ar'],
            ];
        }
        if (empty($items)) return;

        // Group into rows of 1 or 2 images
        $rows = [];
        $i = 0;
        $N = count($items);
        while ($i < $N) {
            $cur = $items[$i];
            if ($cur['ar'] >= 1.4) {
                // Wide banner/landscape: single row
                $rows[] = ['images' => [$cur], 'isLandscape' => true];
                $i++;
            } elseif ($i + 1 < $N) {
                // Pair two images together
                $rows[] = ['images' => [$cur, $items[$i + 1]], 'isLandscape' => false];
                $i += 2;
            } else {
                // Single remaining image
                $rows[] = ['images' => [$cur], 'isLandscape' => false];
                $i++;
            }
        }

        // Paginate across pages
        $pages = [];
        $curPageRows = [];
        $curPageEstH = 0.0;
        foreach ($rows as $row) {
            $estH = $row['isLandscape'] ? 180.0 : 250.0;
            $addH = (empty($curPageRows) ? 0 : 16.0) + $estH;
            if (!empty($curPageRows) && ($curPageEstH + $addH > $availH || count($curPageRows) >= 3)) {
                $pages[] = $curPageRows;
                $curPageRows = [$row];
                $curPageEstH = $estH;
            } else {
                $curPageRows[] = $row;
                $curPageEstH += $addH;
            }
        }
        if (!empty($curPageRows)) {
            $pages[] = $curPageRows;
        }

        $totalPagesInCat = count($pages);
        foreach ($pages as $pIdx => $pageRows) {
            $this->addBlankPage();

            // Header Kicker
            $this->drawText($creatorName . ' // ' . $creatorRole, 45, 54, 8.5, 'FR', $this->cAccentLight);

            // Category Title with Sub-Collection indicator if multi-page
            $title = strtoupper($catName);
            if ($totalPagesInCat > 1) {
                $title .= " (" . ($pIdx + 1) . "/" . $totalPagesInCat . ")";
            }
            $this->drawText($title, 45, 76, 20, 'FB', $this->cText);
            $this->gradientBar(45, 85, 34, 2.5);

            // Compute proportional row heights
            $nRows = count($pageRows);
            $totalGaps = ($nRows - 1) * 16.0;
            $availForRows = $availH - $totalGaps;

            $weights = [];
            foreach ($pageRows as $rIdx => $row) {
                $weights[$rIdx] = $row['isLandscape'] ? 1.0 : 1.45;
            }
            $sumWeights = array_sum($weights);

            $curY = $startY;
            foreach ($pageRows as $rIdx => $row) {
                $rowH = ($weights[$rIdx] / $sumWeights) * $availForRows;
                $maxAllowed = $row['isLandscape'] ? 220.0 : 310.0;
                $rowH = min($rowH, $maxAllowed);

                $rowImages = $row['images'];
                $count = count($rowImages);

                if ($count === 1) {
                    $img = $rowImages[0];
                    $imgW = ($img['ar'] >= 1.4) ? $W : min($W, $rowH * $img['ar']);
                    $imgX = 45.0 + ($W - $imgW) / 2.0;
                    $this->drawRoundedRect($imgX, $curY, $imgW, $rowH, 12, $this->cElev, $this->cBorder);
                    $this->drawImageBox($img['path'], $imgX + 2, $curY + 2, $imgW - 4, $rowH - 4, 10, 'rounded', 'contain');
                } else {
                    $sumAR = 0;
                    foreach ($rowImages as $img) $sumAR += $img['ar'];
                    $availWidth = $W - ($count - 1) * $gap;
                    $curX = 45.0;
                    foreach ($rowImages as $img) {
                        $imgW = ($img['ar'] / $sumAR) * $availWidth;
                        $this->drawRoundedRect($curX, $curY, $imgW, $rowH, 12, $this->cElev, $this->cBorder);
                        $this->drawImageBox($img['path'], $curX + 2, $curY + 2, $imgW - 4, $rowH - 4, 10, 'rounded', 'contain');
                        $curX += $imgW + $gap;
                    }
                }

                $curY += $rowH + 16.0;
            }

            $this->renderFooter($creatorName, $creatorRole);
        }
    }

    /**
     * Final Page: "Let's work together"
     */
    public function renderClosingPage(string $name, string $role, array $contact): void {
        $this->addBlankPage();

        $cx = $this->pw / 2.0;

        // Centered Ambient Glow
        $this->glow($cx, 400.0, 200, $this->cAccent1, 10);

        // Centered Headline & Accent
        $this->drawText("Let's work together", $cx, 310, 36, 'FB', $this->cText, 'C');
        $this->gradientBar($cx - 26, 335, 52, 3.5);
        $this->drawText("Available for selected freelance projects, brand design, and art direction.", $cx, 365, 11, 'FR', $this->cText2, 'C');

        // Contact Box Card
        $cardW = 380.0;
        $cardH = 115.0;
        $cardY = 415.0;
        $this->drawRoundedRect($cx - $cardW/2.0, $cardY, $cardW, $cardH, 16, $this->cSurface, $this->cBorder);

        $cy = $cardY + 36.0;
        foreach (array_slice($contact, 0, 3) as $c) {
            $this->drawText($c, $cx, $cy, 10.5, 'FB', $this->cText, 'C');
            $cy += 22.0;
        }

        // Bottom Signature
        $this->drawText($name, $cx, 590, 22, 'FB', $this->cText, 'C');
        $this->drawText($role . ' | Verified on SaaqiFolio', $cx, 614, 10, 'FR', $this->cTextMuted, 'C');

        // Full-Width Bottom Edge Gradient Strip
        $this->gradientBar(0, $this->ph - 4, $this->pw, 4);
    }

    /**
     * Running Folio Footer with Dynamic Page Numbering
     */
    public function renderFooter(string $name, string $role): void {
        $pageNum = $this->currentPageNum();
        $this->drawLine(45, 805, 550, 805, $this->cBorder, 0.6);
        $left = $name . ' | ' . $role;
        $this->drawText($left, 45, 818, 8, 'FR', $this->cTextMuted);
        $this->drawText((string)$pageNum, 550, 818, 8, 'FR', $this->cTextMuted, 'R');
    }

    // ---------- PDF Document Assembly & Serialization ----------

    public function buildPdfString(): string {
        $pagesObjNum = $this->addObject('');
        $catalogObjNum = $this->addObject('');

        $contentObjNums = [];
        foreach ($this->pagesContent as $content) {
            $contentObjNums[] = $this->addObject([
                'stream_raw' => $content
            ]);
        }

        foreach ($this->pageObjNums as $idx => $pageNum) {
            $resDict = "/Font << /FR {$this->fontHelvetica} 0 R /FB {$this->fontHelveticaBold} 0 R /FSB {$this->fontTimesBold} 0 R /FSI {$this->fontTimesItalic} 0 R /FS {$this->fontTimesRoman} 0 R >>";
            $imgs = $this->pagesResources[$idx] ?? [];
            if (!empty($imgs)) {
                $entries = [];
                foreach ($imgs as $name => $objNum) {
                    $entries[] = "/$name $objNum 0 R";
                }
                $resDict .= " /XObject << " . implode(' ', $entries) . " >>";
            }
            $contentNum = $contentObjNums[$idx];
            $this->objects[$pageNum - 1] =
                "<< /Type /Page /Parent $pagesObjNum 0 R /MediaBox [0 0 {$this->pw} {$this->ph}] /Resources << $resDict >> /Contents $contentNum 0 R >>";
        }

        $kids = implode(' ', array_map(fn($n) => "$n 0 R", $this->pageObjNums));
        $count = count($this->pageObjNums);
        $this->objects[$pagesObjNum - 1] = "<< /Type /Pages /Kids [ $kids ] /Count $count >>";
        $this->objects[$catalogObjNum - 1] = "<< /Type /Catalog /Pages $pagesObjNum 0 R >>";

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0 => 0];

        foreach ($this->objects as $i => $obj) {
            $objNum = $i + 1;
            $offsets[$objNum] = strlen($pdf);

            if (is_array($obj) && isset($obj['dict'])) {
                $pdf .= "$objNum 0 obj\n{$obj['dict']}\nstream\n" . $obj['stream'] . "\nendstream\nendobj\n";
            } elseif (is_array($obj) && isset($obj['stream_raw'])) {
                $streamData = $obj['stream_raw'];
                $len = strlen($streamData);
                $pdf .= "$objNum 0 obj\n<< /Length $len >>\nstream\n" . $streamData . "\nendstream\nendobj\n";
            } else {
                $pdf .= "$objNum 0 obj\n$obj\nendobj\n";
            }
        }

        $xrefStart = strlen($pdf);
        $totalObjs = count($this->objects) + 1;
        $xref = "xref\n0 $totalObjs\n0000000000 65535 f \n";
        for ($n = 1; $n < $totalObjs; $n++) {
            $xref .= sprintf("%010d 00000 n \n", $offsets[$n]);
        }
        $pdf .= $xref;
        $pdf .= "trailer\n<< /Size $totalObjs /Root $catalogObjNum 0 R >>\nstartxref\n$xrefStart\n%%EOF";

        return $pdf;
    }

    public function output(string $filename): void {
        $pdf = $this->buildPdfString();
        if (ob_get_length()) { @ob_end_clean(); }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $pdf;
        exit;
    }

    public function save(string $filePath): bool {
        $pdf = $this->buildPdfString();
        return file_put_contents($filePath, $pdf) !== false;
    }
}
