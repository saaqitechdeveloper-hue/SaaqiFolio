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
    public string $mode = 'dark';
    public ?string $customAccent = null;
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
    public array $cAccentText = self::PURPLE_LIGHT;
    public bool $isPreview = false;

    public function __construct(string $theme = 'obsidian', string $mode = 'dark', ?string $customAccent = null, bool $isPreview = false) {
        $this->isPreview = $isPreview;
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

        $this->mode = strtolower($mode) === 'light' ? 'light' : 'dark';
        $this->customAccent = $customAccent;
        $this->setTheme($theme, $this->mode, $this->customAccent);
    }

    public static function hexToRgb(string $hex): ?array {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) !== 6) return null;
        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2))
        ];
    }

    /**
     * Calculates WCAG 2.1 relative luminance of an RGB array [r, g, b] (0-255).
     */
    public static function getRelativeLuminance(array $rgb): float {
        $r = ($rgb[0] ?? 0) / 255.0;
        $g = ($rgb[1] ?? 0) / 255.0;
        $b = ($rgb[2] ?? 0) / 255.0;
        $rLin = ($r <= 0.03928) ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $gLin = ($g <= 0.03928) ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $bLin = ($b <= 0.03928) ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);
        return 0.2126 * $rLin + 0.7152 * $gLin + 0.0722 * $bLin;
    }

    /**
     * Calculates WCAG 2.1 contrast ratio between two RGB colors (1.0 to 21.0).
     */
    public static function getContrastRatio(array $rgb1, array $rgb2): float {
        $l1 = self::getRelativeLuminance($rgb1);
        $l2 = self::getRelativeLuminance($rgb2);
        $high = max($l1, $l2);
        $low  = min($l1, $l2);
        return ($high + 0.05) / ($low + 0.05);
    }

    /**
     * Given a background color, returns high-contrast text color (pure white or deep black).
     */
    public static function getContrastingTextOnBg(array $bgRgb, ?array $preferredColor = null): array {
        if ($preferredColor !== null && self::getContrastRatio($preferredColor, $bgRgb) >= 4.5) {
            return $preferredColor;
        }
        $bgLum = self::getRelativeLuminance($bgRgb);
        return ($bgLum < 0.35) ? [255, 255, 255] : [10, 10, 10];
    }

    /**
     * WCAG Contrast Adaptation:
     * If $color does not meet $minRatio against $bg, intelligently shifts/lightens/darkens
     * the color while preserving its hue and identity so that it is guaranteed to be visible.
     */
    public static function ensureAccessibleColor(array $color, array $bg, float $minRatio = 4.5): array {
        $ratio = self::getContrastRatio($color, $bg);
        if ($ratio >= $minRatio) {
            return $color;
        }

        $bgLum = self::getRelativeLuminance($bg);
        $isDarkBg = ($bgLum < 0.35);
        $target = $isDarkBg ? [255, 255, 255] : [12, 12, 14];

        // If color is near-neutral monochrome (black, onyx, gray, white)
        $isNeutral = (abs($color[0] - $color[1]) < 22 && abs($color[1] - $color[2]) < 22 && abs($color[0] - $color[2]) < 22);
        if ($isNeutral) {
            return $isDarkBg ? [228, 230, 236] : [24, 24, 28];
        }

        // Iteratively blend towards target until required contrast ratio is reached
        for ($step = 1; $step <= 25; $step++) {
            $t = $step / 25.0;
            $candidate = [
                (int)round($color[0] + ($target[0] - $color[0]) * $t),
                (int)round($color[1] + ($target[1] - $color[1]) * $t),
                (int)round($color[2] + ($target[2] - $color[2]) * $t)
            ];
            if (self::getContrastRatio($candidate, $bg) >= $minRatio) {
                return $candidate;
            }
        }

        return $target;
    }

    public function setTheme(string $theme = 'obsidian', string $mode = 'dark', ?string $customAccent = null): void {
        $this->theme = strtolower($theme);
        $this->mode = strtolower($mode) === 'light' ? 'light' : 'dark';
        $userRgb = (!empty($customAccent)) ? self::hexToRgb($customAccent) : null;

        if ($this->theme === 'swiss') {
            // Design 3: Swiss Editorial (Vivid Yellow #FFD21A, Solid Black #0A0A0A, Pure White)
            $defaultYellow      = [255, 210, 26]; // #ffd21a
            $this->cAccent1     = $userRgb ?: $defaultYellow;
            $this->cAccentLight = $this->cAccent1;
            if ($this->mode === 'dark') {
                $this->cAccent2     = [255, 255, 255];
                $this->cBg          = [14, 14, 18];
                $this->cSurface     = [22, 22, 28];
                $this->cElev        = [32, 32, 40];
                $this->cElev2       = [42, 42, 52];
                $this->cBorder      = [55, 55, 68];
                $this->cText        = [245, 245, 245];
                $this->cTextSoft    = [200, 200, 210];
                $this->cText2       = [150, 150, 165];
                $this->cTextMuted   = [100, 100, 115];
            } else {
                $this->cAccent2     = [10, 10, 10];   // Solid black
                $this->cBg          = [255, 255, 255];
                $this->cSurface     = [245, 245, 245];
                $this->cElev        = [235, 235, 235];
                $this->cElev2       = [10, 10, 10];
                $this->cBorder      = [204, 204, 204];
                $this->cText        = [10, 10, 10];
                $this->cTextSoft    = [50, 50, 50];
                $this->cText2       = [115, 115, 115];
                $this->cTextMuted   = [160, 160, 160];
            }
        } elseif ($this->theme === 'cyber') {
            // Design 2: Cyber Minimalist (High-Density Dark / Electric Cyan)
            $defaultCyan = ($this->mode === 'light') ? [2, 132, 199] : [0, 229, 255];
            $this->cAccent1     = $userRgb ?: $defaultCyan;
            $this->cAccentLight = $this->cAccent1;
            $this->cAccent2     = $this->cAccent1;

            if ($this->mode === 'light') {
                $this->cBg          = [248, 250, 252]; // #f8fafc
                $this->cSurface     = [255, 255, 255]; // #ffffff
                $this->cElev        = [241, 245, 249]; // #f1f5f9
                $this->cElev2       = [226, 232, 240]; // #e2e8f0
                $this->cBorder      = [226, 232, 240]; // #e2e8f0
                $this->cText        = [15, 23, 42];    // #0f172a
                $this->cTextSoft    = [51, 65, 85];    // #334155
                $this->cText2       = [71, 85, 105];   // #475569
                $this->cTextMuted   = [148, 163, 184]; // #94a3b8
            } else {
                $this->cBg          = [11, 15, 25];    // #0b0f19 midnight slate
                $this->cSurface     = [17, 24, 39];    // #111827
                $this->cElev        = [24, 32, 50];    // #182032
                $this->cElev2       = [31, 41, 55];    // #1f2937
                $this->cBorder      = [38, 48, 70];    // #263046
                $this->cText        = [245, 248, 252]; // crisp white
                $this->cTextSoft    = [203, 213, 225]; // soft slate
                $this->cText2       = [148, 163, 184]; // secondary
                $this->cTextMuted   = [100, 116, 139]; // muted
            }
        } elseif ($this->theme === 'atelier' || ($this->theme === 'obsidian' && $this->mode === 'light')) {
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
            $this->cAccent1     = $userRgb ?: [124, 58, 237];  // #7c3aed Royal Violet
            $this->cAccent2     = [234, 88, 12];   // #ea580c coral
            $this->cAccentLight = $this->cAccent1;
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
            $this->cAccent1     = $userRgb ?: [6, 182, 212];   // #06b6d4 electric cyan
            $this->cAccent2     = [236, 72, 153];  // #ec4899 neon magenta
            $this->cAccentLight = $this->cAccent1;
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
            $this->cAccent1     = $userRgb ?: self::PURPLE;
            $this->cAccent2     = self::CORAL;
            $this->cAccentLight = $userRgb ?: self::PURPLE_LIGHT;
        }

        // Guarantee WCAG contrast accessibility for accent text across all modes & custom colors
        $this->cAccentText  = self::ensureAccessibleColor($this->cAccent1, $this->cBg, 4.5);
        $this->cAccentLight = self::ensureAccessibleColor($this->cAccentLight, $this->cBg, 4.5);
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

    public function drawText(?string $text, float $x, float $yTop, float $size, string $fontKey = 'FR', ?array $color = null, $align = 'L'): void {
        if ($text === null) return;
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

    public function drawPolygon(array $points, ?array $fillColor = null, ?array $strokeColor = null, float $lineWidth = 1.0): void {
        if (empty($points)) return;
        $ops = "q\n";
        $p0 = $points[0];
        $ops .= sprintf("%.2f %.2f m\n", $p0[0], $this->ph - $p0[1]);
        for ($i = 1; $i < count($points); $i++) {
            $pt = $points[$i];
            $ops .= sprintf("%.2f %.2f l\n", $pt[0], $this->ph - $pt[1]);
        }
        $ops .= "h\n";
        $ops .= $this->paint($fillColor, $strokeColor, $lineWidth);
        $ops .= "Q\n";
        $this->pagesContent[$this->curPage] .= $ops;
    }

    public function drawVerticalText(string $text, float $x, float $yTop, float $size = 6, ?array $color = null, float $rot = 90): void {
        $clean = $this->sanitizeText($text);
        if ($clean === '') return;
        $color = $color ?? $this->cText;
        $r = $color[0] / 255.0;
        $g = $color[1] / 255.0;
        $b = $color[2] / 255.0;
        $pdfY = $this->ph - $yTop;
        $rad = deg2rad($rot);
        $cos = cos($rad);
        $sin = sin($rad);
        $escaped = $this->escapePdfString($clean);
        $this->pagesContent[$this->curPage] .= sprintf(
            "q BT /FB %.2f Tf %.3f %.3f %.3f rg %.4f %.4f %.4f %.4f %.2f %.2f Tm (%s) Tj ET Q\n",
            $size, $r, $g, $b, $cos, $sin, -$sin, $cos, $x, $pdfY, $escaped
        );
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
        $real = null;
        $baseName = pathinfo($filePath, PATHINFO_FILENAME);
        $dir = dirname($filePath);
        $thumbDir = $dir . DIRECTORY_SEPARATOR . 'thumbs';

        if ($this->isPreview) {
            // For live instant canvas preview, check if pre-cached lightweight JPEG exists
            $fastPrev = $thumbDir . DIRECTORY_SEPARATOR . 'prev_' . $baseName . '.jpg';
            if (file_exists($fastPrev)) {
                $real = realpath($fastPrev);
            } elseif (is_dir($thumbDir)) {
                foreach (['.jpg', '.jpeg', '.webp', '.png', '.avif'] as $tExt) {
                    $tPath = $thumbDir . DIRECTORY_SEPARATOR . $baseName . $tExt;
                    if (file_exists($tPath)) {
                        $real = realpath($tPath);
                        break;
                    }
                }
            }
        }
        if (!$real) {
            $real = realpath($filePath);
        }
        if (!$real || !file_exists($real)) {
            // Fallback to thumbs/ only if the original file is missing
            if (is_dir($thumbDir)) {
                foreach (['.jpg', '.jpeg', '.webp', '.png', '.avif'] as $tExt) {
                    $tPath = $thumbDir . DIRECTORY_SEPARATOR . $baseName . $tExt;
                    if (file_exists($tPath)) {
                        $real = realpath($tPath);
                        break;
                    }
                }
            }
        }
        if (!$real || !file_exists($real)) {
            return null;
        }

        $origReal = $real;
        $optExportPath = $thumbDir . DIRECTORY_SEPARATOR . 'opt_' . $baseName . '.jpg';

        if (isset($this->imageCache[$filePath])) {
            return $this->imageCache[$filePath];
        }
        if (isset($this->imageCache[$real])) {
            return $this->imageCache[$real];
        }
        if (isset($this->imageCache[$optExportPath])) {
            return $this->imageCache[$optExportPath];
        }

        $info = @getimagesize($real);
        if (!$info) return null;

        $origW = $info[0];
        $origH = $info[1];
        $mime = $info['mime'] ?? '';

        // Check if JPEG has EXIF orientation rotation
        $needsRotation = false;
        $exifOrientation = 1;
        if (function_exists('exif_read_data') && ($mime === 'image/jpeg' || $mime === 'image/tiff')) {
            try {
                $exif = @exif_read_data($real);
                if (!empty($exif['Orientation']) && in_array((int)$exif['Orientation'], [3, 6, 8])) {
                    $needsRotation = true;
                    $exifOrientation = (int)$exif['Orientation'];
                }
            } catch (\Throwable $e) {}
        }

        // Check if pre-optimized high-res export JPEG exists (for lightning-fast downloads)
        $optExportPath = $thumbDir . DIRECTORY_SEPARATOR . 'opt_' . $baseName . '.jpg';
        if (!$this->isPreview && file_exists($optExportPath) && @filemtime($optExportPath) >= @filemtime($real)) {
            $optInfo = @getimagesize($optExportPath);
            if ($optInfo && !empty($optInfo[0]) && !empty($optInfo[1])) {
                $data = file_get_contents($optExportPath);
                $filter = '/DCTDecode';
                $w = $optInfo[0];
                $h = $optInfo[1];
                $real = $optExportPath;
            }
        }

        if (!isset($data)) {
            // Check if source JPEG is already compact and within optimal print/screen bounds
            $isAlreadyCompactJpeg = (
                $mime === 'image/jpeg' &&
                !$needsRotation &&
                max($origW, $origH) <= 1920 &&
                @filesize($real) <= 450 * 1024
            );

            if ($isAlreadyCompactJpeg && !$this->isPreview) {
                // Direct stream embedding for files that are already lightweight
                $data = file_get_contents($real);
                $filter = '/DCTDecode';
                $w = $origW;
                $h = $origH;
            } else {
                // For oversized images, PNG, WebP, AVIF, rotated JPEGs, or previews:
                $src = match ($mime) {
                    'image/jpeg' => @imagecreatefromjpeg($real),
                    'image/png'  => @imagecreatefrompng($real),
                    'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($real) : null,
                    'image/avif' => function_exists('imagecreatefromavif') ? @imagecreatefromavif($real) : null,
                    'image/bmp'  => function_exists('imagecreatefrombmp') ? @imagecreatefrombmp($real) : null,
                    'image/gif'  => @imagecreatefromgif($real),
                    default      => null,
                };
                if (!$src && function_exists('imagecreatefromstring')) {
                    $rawContent = @file_get_contents($real);
                    if ($rawContent) $src = @imagecreatefromstring($rawContent);
                }
                if (!$src) return null;

                if ($needsRotation) {
                    switch ($exifOrientation) {
                        case 3:
                            $src = imagerotate($src, 180, 0);
                            break;
                        case 6:
                            $src = imagerotate($src, -90, 0);
                            $t = $origW; $origW = $origH; $origH = $t;
                            break;
                        case 8:
                            $src = imagerotate($src, 90, 0);
                            $t = $origW; $origW = $origH; $origH = $t;
                            break;
                    }
                }

                // Intelligent aspect-ratio aware resolution calculation:
                // 1. Tall / Scroll images (UI mockups, landing page scrolls, mobile app designs):
                //    Crucial: NEVER crush the width! The width carries all UI buttons, headings, text, and cards.
                //    We preserve up to 1920px width (or 100% original if <= 1920px), so text & details are 100% sharp.
                // 2. Ultra-wide panoramic banners:
                //    Crucial: NEVER crush the height! Preserve up to 1600px height.
                // 3. Standard aspect ratios (photos, squares, cards, logos):
                //    Cap max dimension to 2048px (250-300 DPI print quality).
                $aspectRatio = (float)$origH / (float)max(1, $origW);
                $scale = 1.0;

                if ($this->isPreview) {
                    $maxDim = 850;
                    if ($origW > $maxDim || $origH > $maxDim) {
                        $scale = min($maxDim / (float)$origW, $maxDim / (float)$origH);
                    }
                } elseif ($aspectRatio > 1.35) {
                    // Tall / Scroll mockup: scale strictly by WIDTH to keep text and UI crystal-clear
                    $maxW = 1920;
                    $scale = ($origW > $maxW) ? ($maxW / (float)$origW) : 1.0;
                    if ($origH * $scale > 12000) {
                        $scale = 12000 / (float)$origH;
                    }
                } elseif ($aspectRatio < 0.5) {
                    // Ultra-wide panoramic banner: scale by HEIGHT
                    $maxH = 1600;
                    $scale = ($origH > $maxH) ? ($maxH / (float)$origH) : 1.0;
                    if ($origW * $scale > 2500) {
                        $scale = 2500 / (float)$origW;
                    }
                } else {
                    // Standard aspect ratio: cap maximum dimension to 2048px
                    $maxDim = 2048;
                    if ($origW > $maxDim || $origH > $maxDim) {
                        $scale = min($maxDim / (float)$origW, $maxDim / (float)$origH);
                    }
                }

                $targetW = max(1, (int)round($origW * $scale));
                $targetH = max(1, (int)round($origH * $scale));

                if ($targetW === $origW && $targetH === $origH) {
                    $dst = $src;
                } else {
                    $dst = imagecreatetruecolor($targetW, $targetH);
                    $bgCol = imagecolorallocate($dst, $this->cBg[0], $this->cBg[1], $this->cBg[2]);
                    imagefilledrectangle($dst, 0, 0, $targetW, $targetH, $bgCol);
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);
                    @imagedestroy($src);
                }

                $jpegQuality = $this->isPreview ? 78 : 85;
                ob_start();
                imagejpeg($dst, null, $jpegQuality);
                $data = ob_get_clean();
                @imagedestroy($dst);

                // Cache optimized image on disk to eliminate re-compression on next download/preview
                if (!empty($baseName) && is_dir($thumbDir)) {
                    if ($this->isPreview) {
                        $fastPrev = $thumbDir . DIRECTORY_SEPARATOR . 'prev_' . $baseName . '.jpg';
                        @file_put_contents($fastPrev, $data);
                    } else {
                        @file_put_contents($optExportPath, $data);
                    }
                }

                if (function_exists('gc_collect_cycles')) { @gc_collect_cycles(); }
                $filter = '/DCTDecode';
                $w = $targetW;
                $h = $targetH;
            }
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
        $this->imageCache[$filePath] = $res;
        $this->imageCache[$origReal] = $res;
        $this->imageCache[$real] = $res;
        if (!empty($optExportPath)) {
            $this->imageCache[$optExportPath] = $res;
        }
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

    /**
     * Helper to detect tall / panoramic vertical images (e.g. mobile UI, long web designs, posters)
     */
    public function isTallImage(?string $filePath): bool {
        if (!$filePath || !file_exists($filePath)) return false;
        $info = @getimagesize($filePath);
        if (!$info || empty($info[0]) || empty($info[1])) return false;
        return ((float)$info[1] / (float)$info[0]) >= 1.45;
    }

    // ---------- High-Level Page Rendering Templates ----------

    /**
     * Page 1: Balanced Luxury Cover Page
     */
    public function renderCoverPage(string $name, string $role, string $tagline, array $contact, string $curatedDate = '', ?string $avatarPath = null): void {
        $this->addBlankPage();

        // Top Left Kicker
        $kickerText = "SAAQIFOLIO | PORTFOLIO " . date('Y');
        $this->drawText($kickerText, 45, 54, 8.5, 'FR', $this->cAccentText);

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
        $this->drawText($role, $cx, 448, 14, 'FR', $this->cAccentText, 'C');
        $this->gradientBar($cx - 25, 466, 50, 3.5);

        // Credential Subhead
        $this->drawText("CURATED DESIGN SHOWCASE & WORKS", $cx, 500, 9, 'FR', $this->cText2, 'C');

        // Contact Information Group
        $contactY = 560.0;
        foreach (array_filter(array_slice($contact, 0, 3)) as $c) {
            $this->drawText((string)$c, $cx, $contactY, 10, 'FR', $this->cText2, 'C');
            $contactY += 20.0;
        }

        // Verified Creator Badge Pill
        $badgeW = 270.0;
        $this->drawRoundedRect($cx - $badgeW/2.0, 670, $badgeW, 30, 15, $this->cElev, $this->cBorder);
        $badgeTitle = ($this->theme === 'atelier') ? "CURATED ATELIER EDITION // " . date('Y') : (($this->theme === 'creative') ? "STUDIO PRO SHOWCASE // " . date('Y') : "VERIFIED SAAQIFOLIO CREATOR // " . date('Y'));
        $badgeTitleCol = self::ensureAccessibleColor($this->cAccent1, $this->cElev, 4.5);
        $this->drawText($badgeTitle, $cx, 689, 8.5, 'FB', $badgeTitleCol, 'C');

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
        $statNumCol = self::ensureAccessibleColor($this->cAccent1, $this->cSurface, 4.5);

        // Card 1: Projects
        $this->drawRoundedRect(45, $statY, $cardW, $cardH, 14, $this->cSurface, $this->cBorder);
        $this->drawText((string)$projectCount, 45 + $cardW/2.0, $statY + 34, 22, 'FB', $statNumCol, 'C');
        $this->drawText("Projects", 45 + $cardW/2.0, $statY + 56, 9.0, 'FR', $this->cText2, 'C');

        // Card 2: Categories
        $this->drawRoundedRect(45 + $cardW + $gap, $statY, $cardW, $cardH, 14, $this->cSurface, $this->cBorder);
        $this->drawText((string)$catCount, 45 + $cardW + $gap + $cardW/2.0, $statY + 34, 22, 'FB', $statNumCol, 'C');
        $this->drawText("Categories", 45 + $cardW + $gap + $cardW/2.0, $statY + 56, 9.0, 'FR', $this->cText2, 'C');

        // Card 3: Tools
        $toolCount = count($tools);
        $this->drawRoundedRect(45 + ($cardW + $gap)*2, $statY, $cardW, $cardH, 14, $this->cSurface, $this->cBorder);
        $this->drawText((string)$toolCount, 45 + ($cardW + $gap)*2 + $cardW/2.0, $statY + 34, 22, 'FB', $statNumCol, 'C');
        $this->drawText("Tools", 45 + ($cardW + $gap)*2 + $cardW/2.0, $statY + 56, 9.0, 'FR', $this->cText2, 'C');

        $curY = 230.0;

        // About / Professional Statement
        $hasBio = self::usableText($bio);
        $displayBio = $hasBio ? trim($bio) : "Creative and detail-oriented " . ($role ?: 'Designer') . " dedicated to crafting compelling visual identities, brand systems, and engaging digital media for forward-thinking clients worldwide.";

        $this->drawText("About", 45, $curY, 12, 'FB', $this->cAccentText);
        $curY += 20;
        $curY = $this->drawWrappedText($displayBio, 45, $curY, 505, 10.0, 'FR', $this->cTextSoft, 15);
        $curY += 30;

        // Experience Section (if real text exists)
        if (self::usableText($experience)) {
            $this->drawText("Experience", 45, $curY, 12, 'FB', $this->cAccentText);
            $curY += 20;
            $curY = $this->drawWrappedText($experience, 45, $curY, 505, 10.0, 'FR', $this->cTextSoft, 15);
            $curY += 30;
        }

        // Education Section (if real text exists)
        if (self::usableText($education)) {
            $this->drawText("Education", 45, $curY, 12, 'FB', $this->cAccentText);
            $curY += 20;
            $curY = $this->drawWrappedText($education, 45, $curY, 505, 10.0, 'FR', $this->cTextSoft, 15);
            $curY += 30;
        }

        // Software & Technical Expertise (Tools Chips)
        if (!empty($tools)) {
            $this->drawText("Software & technical expertise", 45, $curY, 12, 'FB', $this->cAccentText);
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
            $contactHeadCol = self::ensureAccessibleColor($this->cAccent1, $this->cSurface, 4.5);
            $this->drawText("Direct Inquiries & Bookings", 65, $curY + 28, 11.5, 'FB', $contactHeadCol);
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
        $startY = 105.0;
        $bottomLimit = 790.0;
        $availH = $bottomLimit - $startY; // 685 points

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
            if ($cur['ar'] <= 0.82) {
                // Tall vertical image / portrait: dedicated single row with full available height
                $rows[] = ['images' => [$cur], 'isLandscape' => false, 'isTall' => true];
                $i++;
            } elseif ($cur['ar'] >= 1.4) {
                // Wide banner/landscape: single row
                $rows[] = ['images' => [$cur], 'isLandscape' => true, 'isTall' => false];
                $i++;
            } elseif ($i + 1 < $N && $items[$i + 1]['ar'] > 0.82) {
                // Pair two images together
                $rows[] = ['images' => [$cur, $items[$i + 1]], 'isLandscape' => false, 'isTall' => false];
                $i += 2;
            } else {
                // Single remaining image
                $rows[] = ['images' => [$cur], 'isLandscape' => false, 'isTall' => false];
                $i++;
            }
        }

        // Paginate across pages
        $pages = [];
        $curPageRows = [];
        $curPageEstH = 0.0;
        foreach ($rows as $row) {
            if (!empty($row['isTall'])) {
                // Tall image gets its own exclusive page with maximum height
                if (!empty($curPageRows)) {
                    $pages[] = $curPageRows;
                    $curPageRows = [];
                    $curPageEstH = 0.0;
                }
                $pages[] = [$row];
                continue;
            }

            $estH = $row['isLandscape'] ? 200.0 : 280.0;
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
            $this->drawText($creatorName . ' // ' . $creatorRole, 45, 54, 8.5, 'FR', $this->cAccentText);

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
                if ($nRows === 1) {
                    $rowH = min($availForRows, 680.0);
                } else {
                    $rowH = ($weights[$rIdx] / $sumWeights) * $availForRows;
                    $maxAllowed = $row['isLandscape'] ? 240.0 : 340.0;
                    $rowH = min($rowH, $maxAllowed);
                }

                $rowImages = $row['images'];
                $count = count($rowImages);

                if ($count === 1) {
                    $img = $rowImages[0];
                    // Full-width card container spanning from x=45 to x=550
                    $imgW = $W;
                    $imgX = 45.0;
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

    /**
     * Master Multi-Template Document Dispatcher
     */
    public function renderPortfolioDocument(
        array $user,
        array $allImages,
        array $catsToRender,
        array $skills,
        array $contactParts,
        ?string $avatarPath = null,
        string $curatedDate = '',
        bool $coverOnly = false
    ): void {
        $this->isPreview = $coverOnly;
        if ($this->theme === 'swiss') {
            $this->renderSwissLayout($user, $allImages, $catsToRender, $skills, $contactParts, $avatarPath, $curatedDate, $coverOnly);
        } elseif ($this->theme === 'cyber') {
            $this->renderCyberLayout($user, $allImages, $catsToRender, $skills, $contactParts, $avatarPath, $curatedDate, $coverOnly);
        } else {
            // Obsidian Noir (or Atelier / Creative)
            $this->renderCoverPage($user['name'] ?? '', $user['profession'] ?? '', $user['bio'] ?? '', $contactParts, $curatedDate, $avatarPath);
            if ($coverOnly) {
                return;
            }
            $this->renderProfilePage(
                $user['name'] ?? '',
                $user['profession'] ?? '',
                $user['bio'] ?? '',
                count($allImages),
                count($catsToRender),
                $skills,
                $contactParts,
                $user['education'] ?? '',
                $user['experience'] ?? ''
            );
            foreach ($catsToRender as $cat) {
                $imgsInCat = array_values(array_filter($allImages, fn($i) => ($i['category'] ?? '') === $cat));
                if (empty($imgsInCat)) continue;
                $paths = [];
                foreach ($imgsInCat as $img) {
                    $p = resolve_upload_path('portfolio', $img['filename']);
                    if ($p) $paths[] = $p;
                }
                if (!empty($paths)) {
                    $this->renderCategoryShowcasePage($cat, $paths, $user['name'] ?? '', $user['profession'] ?? '');
                }
            }
            $this->renderClosingPage($user['name'] ?? '', $user['profession'] ?? '', $contactParts);
        }
    }

    /**
     * Design 2: Cyber Minimalist (High-Density Grid as in User Reference)
     */
    public function renderCyberLayout(
        array $user,
        array $allImages,
        array $catsToRender,
        array $skills,
        array $contactParts,
        ?string $avatarPath = null,
        string $curatedDate = '',
        bool $coverOnly = false
    ): void {
        $name = trim($user['name'] ?? 'Creator');
        $role = trim($user['profession'] ?? 'Graphic Designer');
        $bio  = trim($user['bio'] ?? '');
        $edu  = trim($user['education'] ?? '');
        $exp  = trim($user['experience'] ?? '');

        $totalImages = count($allImages);
        $totalCats = count($catsToRender);
        $totalTools = count($skills);

        // PAGE 1: Overview + First 9 Artworks (3 columns x 3 rows)
        $this->addBlankPage();

        // Top Header
        $year = date('Y');
        $this->drawText("SAAQIFOLIO | PORTFOLIO " . $year, 45, 46, 7.5, 'FB', $this->cAccentText);
        $this->drawText($name, 45, 72, 22, 'FB', $this->cText);
        $this->drawText($role, 45, 88, 11, 'FB', $this->cAccentText);

        // Top Right: Contact Group
        $contactY = 46.0;
        foreach (array_slice($contactParts, 0, 3) as $c) {
            $this->drawText($c, 550, $contactY, 7.5, 'FR', $this->cText2, 'R');
            $contactY += 12.0;
        }

        // Top Right: Stats Row
        $statX = 550.0;
        $statY = 88.0;
        $statItems = [
            ['num' => (string)$totalTools, 'label' => 'Tools'],
            ['num' => (string)$totalCats,  'label' => 'Categories'],
            ['num' => (string)$totalImages,'label' => 'Projects']
        ];
        foreach ($statItems as $st) {
            $this->drawText($st['num'], $statX, $statY, 18, 'FB', $this->cText, 'R');
            $this->drawText($st['label'], $statX, $statY + 12, 6.5, 'FR', $this->cTextMuted, 'R');
            $statX -= 56.0;
        }

        // Two-column Info Block: About (left) & Details (right)
        $infoBoxY = 110.0;
        $infoBoxH = 100.0;

        // Left About Card
        $aboutW = 325.0;
        $this->drawRoundedRect(45, $infoBoxY, $aboutW, $infoBoxH, 6, $this->cSurface, $this->cBorder);
        $this->drawText("ABOUT", 57, $infoBoxY + 18, 7.5, 'FB', $this->cAccentText);
        $displayBio = $bio ?: "I'm a passionate {$role} dedicated to turning ideas into visually compelling designs. I specialize in creating modern, creative and professional graphics that communicate clearly and leave a lasting impression.";
        $this->drawWrappedText($displayBio, 57, $infoBoxY + 32, $aboutW - 24, 7.5, 'FR', $this->cTextSoft, 11);

        // Right Meta Card
        $metaX = 45 + $aboutW + 10;
        $metaW = 505 - $aboutW - 10; // 170pt
        $this->drawRoundedRect($metaX, $infoBoxY, $metaW, $infoBoxH, 6, $this->cSurface, $this->cBorder);

        $my = $infoBoxY + 16;
        if ($exp) {
            $this->drawText("EXPERIENCE", $metaX + 12, $my, 6.5, 'FB', $this->cAccentText);
            $this->drawText($this->clip($exp, $metaW - 24, 7, 'FR'), $metaX + 12, $my + 10, 7.0, 'FR', $this->cTextSoft);
            $my += 24;
        }
        if ($edu) {
            $this->drawText("EDUCATION", $metaX + 12, $my, 6.5, 'FB', $this->cAccentText);
            $this->drawText($this->clip($edu, $metaW - 24, 7, 'FR'), $metaX + 12, $my + 10, 7.0, 'FR', $this->cTextSoft);
            $my += 24;
        }
        if (!empty($skills)) {
            $this->drawText("TOOLS", $metaX + 12, $my, 6.5, 'FB', $this->cAccentText);
            $toolsStr = implode(' • ', array_slice($skills, 0, 3));
            $this->drawText($this->clip($toolsStr, $metaW - 24, 7, 'FR'), $metaX + 12, $my + 10, 7.0, 'FR', $this->cTextSoft);
            $my += 24;
        }
        $this->drawText("AVAILABLE FOR", $metaX + 12, $my, 6.5, 'FB', $this->cAccentText);
        $this->drawText("Freelance, brand design & full-time", $metaX + 12, $my + 10, 7.0, 'FR', $this->cTextSoft);

        // Separate tall vertical images from regular grid items
        $regularImages = [];
        $tallImages = [];
        foreach ($allImages as $img) {
            $realPath = resolve_upload_path('portfolio', $img['filename'] ?? '');
            if ($this->isTallImage($realPath)) {
                $tallImages[] = $img;
            } else {
                $regularImages[] = $img;
            }
        }

        // Page 1 Artwork Grid: 3 columns x 3 rows (up to 9 items)
        $gridStartY = $infoBoxY + $infoBoxH + 12; // 222pt
        $cardW = (505.0 - 2 * 10.0) / 3.0; // ~161.6pt
        $cardH = 175.0;
        $gridGap = 10.0;

        $page1Images = array_slice($regularImages, 0, 9);
        $remImages   = array_slice($regularImages, 9);

        foreach ($page1Images as $idx => $img) {
            $r = (int)floor($idx / 3);
            $c = $idx % 3;
            $cx = 45.0 + $c * ($cardW + $gridGap);
            $cy = $gridStartY + $r * ($cardH + $gridGap);

            $this->renderCyberImageCard($img, $cx, $cy, $cardW, $cardH);
        }

        $this->renderCyberFooter($name, $role, $contactParts);

        if ($coverOnly) {
            return;
        }

        // PAGE 2+: Remaining Regular Images (3 columns x 4 rows = 12 per page)
        $perPage = 12;
        $chunks = array_chunk($remImages, $perPage);

        foreach ($chunks as $chunk) {
            $this->addBlankPage();
            $p2StartY = 45.0;
            $p2CardH = 174.0;

            foreach ($chunk as $idx => $img) {
                $r = (int)floor($idx / 3);
                $c = $idx % 3;
                $cx = 45.0 + $c * ($cardW + $gridGap);
                $cy = $p2StartY + $r * ($p2CardH + $gridGap);

                $this->renderCyberImageCard($img, $cx, $cy, $cardW, $p2CardH);
            }

            $this->renderCyberFooter($name, $role, $contactParts);
        }

        // DEDICATED FULL-PAGE TALL IMAGE SHOWCASES
        foreach ($tallImages as $tImg) {
            $this->addBlankPage();
            $this->drawText(strtoupper($name), 45, 26, 7, 'FB', $this->cAccentText);
            $this->drawText(sprintf('%s // EXTENDED FULL SHOWCASE', strtoupper($tImg['category'] ?? 'WORK')), 550, 26, 6.5, 'FR', $this->cTextMuted, 'R');
            $this->drawLine(45, 34, 550, 34, $this->cBorder, 0.6);

            $capText = strtoupper(trim($tImg['title'] ?? 'Full Artwork Project'));
            $this->drawText($capText, 45, 52, 14, 'FB', $this->cText);

            $boxX = 45.0;
            $boxY = 62.0;
            $boxW = 505.0;
            $boxH = 720.0;
            $this->drawRoundedRect($boxX, $boxY, $boxW, $boxH, 6, $this->cSurface, $this->cBorder);
            $gp = resolve_upload_path('portfolio', $tImg['filename'] ?? '');
            if ($gp && file_exists($gp)) {
                $this->drawImageBox($gp, $boxX, $boxY, $boxW, $boxH, 6, 'rounded', 'contain');
            }
            $this->renderCyberFooter($name, $role, $contactParts);
        }
    }

    private function renderCyberImageCard(array $img, float $x, float $y, float $w, float $h): void {
        $realPath = resolve_upload_path('portfolio', $img['filename'] ?? '');
        $cat = strtoupper(trim($img['category'] ?? 'WORK'));

        $this->drawRoundedRect($x, $y, $w, $h, 4, $this->cSurface, $this->cBorder);

        if ($realPath && file_exists($realPath)) {
            $this->drawImageBox($realPath, $x, $y, $w, $h, 4, 'rounded', 'contain');
        }

        $badgeTxt = $cat;
        $badgeTw  = $this->textWidth($badgeTxt, 6.2, 'FB');
        $badgeW   = $badgeTw + 12.0;
        $badgeH   = 15.0;

        $badgeBg = ($this->mode === 'light') ? [255, 255, 255] : [11, 15, 25];
        $badgeBorderCol = self::ensureAccessibleColor($this->cAccent1, $this->cSurface, 2.5);
        $badgeTextCol   = self::ensureAccessibleColor($this->cAccent1, $badgeBg, 4.5);
        $this->drawRoundedRect($x + 6, $y + 6, $badgeW, $badgeH, 3, $badgeBg, $badgeBorderCol, 0.6);
        $this->drawText($badgeTxt, $x + 6 + $badgeW/2.0, $y + 6 + 10.5, 6.2, 'FB', $badgeTextCol, 'C');
    }

    private function renderCyberFooter(string $name, string $role, array $contactParts): void {
        $pg = $this->currentPageNum();
        $this->drawLine(45, 802, 550, 802, $this->cBorder, 0.6);
        $left = $name . ' | ' . $role;
        $right = "Let's work together: " . implode(' • ', array_slice($contactParts, 0, 3)) . "   " . $pg;
        $this->drawText($left, 45, 816, 7.5, 'FR', $this->cTextMuted);
        $this->drawText($right, 550, 816, 7.5, 'FR', $this->cTextMuted, 'R');
    }

    /**
     * Intelligently selects the best-proportioned artwork for hero showcases (Cover trio, Thank-you hero)
     * Filters out ultra-tall mockups and ultra-panoramic strips, ranking well-proportioned squares/landscapes first.
     */
    public function getBestHeroImages(array $allImages, int $limit = 3): array {
        if (empty($allImages)) return [];
        $scored = [];
        foreach ($allImages as $idx => $img) {
            $p = resolve_upload_path('portfolio', $img['filename'] ?? '');
            if (!$p || !file_exists($p)) continue;
            $sz = @getimagesize($p);
            if (!$sz || empty($sz[0]) || empty($sz[1])) continue;
            $ar = (float)$sz[0] / (float)$sz[1];
            // Ideal hero aspect ratio is around 1.0 - 1.4. Penalize extreme vertical or wide ratios.
            $isExtreme = ($ar < 0.65 || $ar > 2.0);
            $distFromIdeal = abs($ar - 1.15);
            $score = ($isExtreme ? 100 : 0) + $distFromIdeal;
            $scored[] = ['img' => $img, 'ar' => $ar, 'score' => $score, 'path' => $p];
        }
        if (empty($scored)) {
            return array_slice($allImages, 0, $limit);
        }
        usort($scored, fn($a, $b) => $a['score'] <=> $b['score']);
        return array_map(fn($item) => $item['img'], array_slice($scored, 0, $limit));
    }

    /**
     * Design 3: Swiss Editorial / Yellow-Black Poster Layout (Exact as Reference & Skill)
     */
    public function renderSwissLayout(
        array $user,
        array $allImages,
        array $catsToRender,
        array $skills,
        array $contactParts,
        ?string $avatarPath = null,
        string $curatedDate = '',
        bool $coverOnly = false
    ): void {
        $name = trim($user['name'] ?? 'Creator');
        $role = trim($user['profession'] ?? 'Graphic Designer');
        $bio  = trim($user['bio'] ?? '');
        $edu  = trim($user['education'] ?? '');
        $exp  = trim($user['experience'] ?? '');
        $totalImages = count($allImages);
        $totalCats = count($catsToRender);
        $totalTools = count($skills);

        $isDarkSwiss = ($this->mode === 'dark');
        $accentCol = $this->cAccent1; // Yellow [255, 210, 26] or user custom hex
        $pageBgCol = $isDarkSwiss ? [14, 14, 18] : [255, 255, 255];
        $textMainCol = $isDarkSwiss ? [245, 245, 245] : [12, 12, 12];
        $textSubCol = $isDarkSwiss ? [160, 160, 170] : [115, 115, 115];
        $cardBgCol = $isDarkSwiss ? [22, 22, 28] : [245, 245, 248];
        $cardBorderCol = $isDarkSwiss ? [40, 40, 50] : [225, 225, 230];

        $bandBgCol = [12, 12, 12]; // Swiss diagonal signature band is always rich obsidian black
        $bandTextCol = [255, 255, 255]; // Always pure crisp white inside the black band
        $bandSubCol = [220, 220, 220];

        // PAGE 1: SWISS COVER
        $this->addBlankPage();
        $this->fillRect(0, 0, $this->pw, $this->ph, $pageBgCol);
        $this->fillRect(0, 0, $this->pw, 330, $accentCol);

        $accentHeaderCol = self::getContrastingTextOnBg($accentCol);
        $this->drawText("SAAQIFOLIO | PORTFOLIO " . date('Y'), 22, 28, 6.5, 'FB', $accentHeaderCol);

        // Intelligent Hero Trio on Yellow Header (aspect-ratio aware)
        $heroImgs = $this->getBestHeroImages($allImages, 3);
        if (!empty($heroImgs)) {
            $hCount = count($heroImgs);
            $cardH = 145.0;
            $cards = [];
            foreach ($heroImgs as $hImg) {
                $hp = resolve_upload_path('portfolio', $hImg['filename'] ?? '');
                $ar = 1.0;
                if ($hp && file_exists($hp)) {
                    $sz = @getimagesize($hp);
                    if ($sz && $sz[0] > 0 && $sz[1] > 0) $ar = (float)$sz[0] / (float)$sz[1];
                }
                $cardW = min(175.0, max(85.0, $cardH * $ar));
                $cards[] = ['path' => $hp, 'w' => $cardW, 'h' => $cardH, 'ar' => $ar];
            }
            $totalCardsW = array_sum(array_column($cards, 'w')) + ($hCount - 1) * 14.0;
            $curCardX = ($this->pw - $totalCardsW) / 2.0;
            $cardY = 145.0;
            foreach ($cards as $c) {
                $this->drawRoundedRect($curCardX, $cardY, $c['w'], $c['h'], 8, [20, 20, 24], null);
                if (!empty($c['path']) && file_exists($c['path'])) {
                    $this->drawImageBox($c['path'], $curCardX + 2, $cardY + 2, $c['w'] - 4, $cardH - 4, 6, 'rounded', 'contain');
                }
                $curCardX += $c['w'] + 14.0;
            }
        } elseif ($avatarPath && file_exists($avatarPath)) {
            $this->drawImageBox($avatarPath, ($this->pw - 170)/2.0, 135, 170, 170, 85, 'circle', 'cover');
        }

        // Signature Straight Horizontal Black Band for Name & Role (0 deg, no rotation)
        $this->fillRect(0, 330, 420, 85, $bandBgCol);

        // Text INSIDE straight signature band
        $this->drawText(strtoupper($name), 24, 368, 17, 'FB', $bandTextCol);
        $this->drawText($role . " Portfolio", 24, 392, 9.5, 'FR', $bandSubCol);

        // Stacked Giant Typography BELOW straight signature band (no overlap, clean clearance)
        $words = ['GRAPHIC', 'DESIGN', 'PORT', 'FOLIO'];
        $sy = 475.0;
        foreach ($words as $w) {
            $this->drawText($w, 24, $sy, 56, 'FB', $textMainCol);
            $sy += 52.0;
        }

        // Right column metadata
        $rx = 430.0;
        $ry = 435.0;
        foreach (array_slice($catsToRender, 0, 4) as $cat) {
            $this->drawText(strtoupper($cat), $rx, $ry, 7.5, 'FB', $textMainCol);
            $ry += 13.0;
        }
        $this->fillRect($rx, $ry + 10, 52, 7, $textMainCol);
        $this->drawText("SELECTED", $rx, $ry + 32, 7.5, 'FB', $textMainCol);
        $this->drawText("WORK", $rx, $ry + 45, 7.5, 'FB', $textMainCol);
        $this->drawText(date('Y'), $rx, $ry + 58, 7.5, 'FB', $textMainCol);

        $cy = 780.0;
        foreach (array_slice($contactParts, 0, 3) as $c) {
            $this->drawText($c, $rx, $cy, 7.0, 'FR', $textMainCol);
            $cy += 12.0;
        }

        $this->fillRect(22, 804, 30, 3, $accentCol);
        $this->drawVerticalText("GRAPHIC-DESIGN", 12, 330, 6, $textMainCol, 90);
        $this->drawVerticalText("GRAPHIC-DESIGN", $this->pw - 12, 330, 6, $textMainCol, -90);

        if ($coverOnly) {
            return;
        }

        // PAGE 2: ABOUT / INTRO
        $this->addBlankPage();
        $this->fillRect(0, 0, $this->pw, $this->ph, $pageBgCol);

        $this->drawText(strtoupper($name), 22, 26, 6.5, 'FB', $textMainCol);
        $this->drawText("GRAPHIC DESIGN PORTFOLIO", $this->pw - 22, 26, 6.5, 'FR', $textMainCol, 'R');
        $this->fillRect(22, 33, 22, 2.2, $accentCol);
        $this->drawText("02", $this->pw - 22, 825, 8, 'FB', $textMainCol, 'R');
        $this->fillRect($this->pw - 52, 815, 12, 2.2, $accentCol);

        $firstName = strtoupper(explode(' ', $name)[0] ?? 'DESIGNER');
        $this->drawText("HELLO,", 22, 95, 46, 'FB', $textMainCol);
        $this->drawText("I'M " . $firstName . ".", 22, 145, 46, 'FB', $textMainCol);

        $avBoxW = 196.0;
        $avBoxH = 150.0;
        $avBoxX = $this->pw - 22 - $avBoxW;
        $this->fillRect($avBoxX, 55, $avBoxW, $avBoxH, $accentCol);
        if ($avatarPath && file_exists($avatarPath)) {
            $this->drawImageBox($avatarPath, $avBoxX + 8, 65, $avBoxW - 16, $avBoxH - 20, 6, 'rounded', 'contain');
        }
        $this->drawPolygon([[$avBoxX, 55 + $avBoxH], [$avBoxX + 50, 55 + $avBoxH], [$avBoxX, 55 + $avBoxH - 40]], [12, 12, 12]);

        $this->drawText("ABOUT", 22, 215, 6.5, 'FB', $textMainCol);
        $this->fillRect(22, 221, 22, 2.2, $accentCol);
        $displayBio2 = $bio ?: "I'm a passionate Graphic Designer dedicated to turning ideas into visually compelling designs. I specialize in creating modern, creative and professional graphics that communicate clearly and leave a lasting impression. From branding and social media designs to promotional materials and custom artwork, I focus on delivering high-quality work with creativity, precision and attention to detail.";
        $this->drawWrappedText($displayBio2, 22, 235, 290, 8.5, 'FR', $textMainCol, 12.8);

        $dy = 220.0;
        $details = [
            ['EXPERIENCE', $exp ?: 'Kaitech'],
            ['EDUCATION', $edu ?: 'KaiTech & Saycreation'],
            ['TOOLS', implode(' • ', array_slice($skills, 0, 3)) ?: 'Adobe Photoshop • Adobe Illustrator'],
            ['AVAILABLE FOR', 'Freelance, brand design & full-time']
        ];
        foreach ($details as $det) {
            $this->drawText($det[0], $avBoxX, $dy, 6.3, 'FB', $textMainCol);
            $this->fillRect($avBoxX, $dy + 4, $avBoxW, 0.6, $textSubCol);
            $this->drawText($det[1], $avBoxX, $dy + 15, 8.4, 'FR', $textMainCol);
            $dy += 36.0;
        }

        // Stats Band (solid black background with high contrast yellow & white)
        $this->fillRect(0, 395, $this->pw, 66, [12, 12, 12]);
        $statsW = ($this->pw - 44) / 3.0;
        $swissStats = [
            [(string)sprintf('%02d', $totalImages), 'PROJECTS'],
            [(string)sprintf('%02d', $totalCats),   'CATEGORIES'],
            [(string)sprintf('%02d', $totalTools),  'TOOLS']
        ];
        $swissStatCol = self::ensureAccessibleColor($accentCol, [12, 12, 12], 4.5);
        foreach ($swissStats as $si => $st) {
            $sx = 22 + $si * $statsW;
            $this->drawText($st[0], $sx, 442, 40, 'FB', $swissStatCol);
            $this->drawText($st[1], $sx + 52, 432, 7, 'FB', [255, 255, 255]);
        }

        $this->drawText("SELECTED HIGHLIGHTS", 22, 485, 6.5, 'FB', $textMainCol);
        $this->fillRect(22, 491, 22, 2.2, $accentCol);

        $hlImgs = array_slice($allImages, 0, 8);
        $tw = ($this->pw - 44 - 18) / 4.0;
        foreach ($hlImgs as $hi => $hImg) {
            $r = (int)floor($hi / 4);
            $c = $hi % 4;
            $hx = 22 + $c * ($tw + 6);
            $hy = 505 + $r * ($tw + 6);
            $hp = resolve_upload_path('portfolio', $hImg['filename'] ?? '');
            if ($hp && file_exists($hp)) {
                $this->drawImageBox($hp, $hx, $hy, $tw, $tw, 0, 'none', 'cover');
            }
        }

        // PAGE 3: TABLE OF CONTENTS (SIGNATURE EDITORIAL BLACK)
        $this->addBlankPage();
        $this->fillRect(0, 0, $this->pw, $this->ph, [12, 12, 12]);
        $tocAccentCol = self::ensureAccessibleColor($accentCol, [12, 12, 12], 4.5);

        $this->drawText(strtoupper($name), 22, 26, 6.5, 'FB', [255, 255, 255]);
        $this->drawText("TABLE OF CONTENTS", $this->pw - 22, 26, 6.5, 'FR', [220, 220, 220], 'R');
        $this->fillRect(22, 33, 22, 2.2, $tocAccentCol);
        $this->drawText("03", $this->pw - 22, 825, 8, 'FB', [255, 255, 255], 'R');
        $this->fillRect($this->pw - 52, 815, 12, 2.2, $tocAccentCol);

        $this->drawText("THE ART OF", 22, 100, 50, 'FB', [255, 255, 255]);
        $this->drawText("SELECTION.", 22, 150, 50, 'FB', [255, 255, 255]);
        $this->drawText("BY " . strtoupper($name), 22, 175, 6.5, 'FB', $tocAccentCol);

        $tocY = 225.0;
        foreach (array_slice($catsToRender, 0, 4) as $ci => $cat) {
            $this->fillRect(22, $tocY - 14, $this->pw - 44, 0.6, [70, 70, 75]);
            $num = sprintf('%02d', $ci + 1);
            $this->drawText($num, 22, $tocY + 22, 28, 'FB', $tocAccentCol);
            $this->drawText(strtoupper($cat), 65, $tocY + 12, 16, 'FB', [255, 255, 255]);
            $this->drawText("Curated collection & works", 65, $tocY + 25, 7.5, 'FR', [170, 170, 175]);
            $pgRange = sprintf('0%d', $ci + 4);
            $this->drawText($pgRange, $this->pw - 22, $tocY + 14, 10, 'FB', [255, 255, 255], 'R');
            $tocY += 56.0;
        }
        $this->fillRect(22, $tocY - 14, $this->pw - 44, 0.6, [70, 70, 75]);

        $this->drawText("TOOLS", 22, $tocY + 12, 6.5, 'FB', $tocAccentCol);
        $toolX = 65.0;
        foreach (array_slice($skills, 0, 3) as $sk) {
            $skTxt = strtoupper(trim($sk));
            $skTw  = $this->textWidth($skTxt, 6.8, 'FB');
            $skW   = $skTw + 20.0;
            $this->drawRoundedRect($toolX, $tocY + 2, $skW, 16, 8, null, $tocAccentCol, 0.8);
            $this->drawText($skTxt, $toolX + 10, $tocY + 13, 6.8, 'FB', [255, 255, 255]);
            $toolX += $skW + 8;
        }

        $tocArt = $this->getBestHeroImages($allImages, 2);
        $aw = 244.0;
        $ah = 250.0;
        foreach ($tocArt as $ti => $tImg) {
            $tp = resolve_upload_path('portfolio', $tImg['filename'] ?? '');
            $tx = ($ti === 0) ? 22 : ($this->pw - 22 - $aw);
            if ($tp && file_exists($tp)) {
                $this->drawImageBox($tp, $tx, 520, $aw, $ah, 0, 'none', 'contain');
            }
        }

        // PAGES 4+: SECTION PAGES (SMART ASPECT-RATIO PACKING: TALL, WIDE BANNER, REGULAR)
        $sectionPg = 4;
        foreach ($catsToRender as $ci => $cat) {
            $catImgs = array_values(array_filter($allImages, fn($i) => ($i['category'] ?? '') === $cat));
            if (empty($catImgs)) continue;

            $tallImgs = [];
            $bannerImgs = [];
            $regularImgs = [];
            foreach ($catImgs as $cImg) {
                $gp = resolve_upload_path('portfolio', $cImg['filename'] ?? '');
                $sz = ($gp && file_exists($gp)) ? @getimagesize($gp) : null;
                $ar = ($sz && $sz[0] > 0 && $sz[1] > 0) ? (float)$sz[0] / (float)$sz[1] : 1.0;
                if ($ar <= 0.68) {
                    $tallImgs[] = ['data' => $cImg, 'path' => $gp, 'ar' => $ar];
                } elseif ($ar >= 1.75) {
                    $bannerImgs[] = ['data' => $cImg, 'path' => $gp, 'ar' => $ar];
                } else {
                    $regularImgs[] = ['data' => $cImg, 'path' => $gp, 'ar' => $ar];
                }
            }

            // Combine banners and regulars in natural order
            $mixedItems = [];
            foreach ($catImgs as $cImg) {
                $gp = resolve_upload_path('portfolio', $cImg['filename'] ?? '');
                $sz = ($gp && file_exists($gp)) ? @getimagesize($gp) : null;
                $ar = ($sz && $sz[0] > 0 && $sz[1] > 0) ? (float)$sz[0] / (float)$sz[1] : 1.0;
                if ($ar <= 0.68) continue; // Talls handled separately
                if ($ar >= 1.75) {
                    $mixedItems[] = ['type' => 'banner', 'data' => $cImg, 'path' => $gp, 'ar' => $ar];
                } else {
                    $mixedItems[] = ['type' => 'regular', 'data' => $cImg, 'path' => $gp, 'ar' => $ar];
                }
            }

            // Group into rows
            $gridRows = [];
            $mi = 0;
            $M = count($mixedItems);
            while ($mi < $M) {
                $item = $mixedItems[$mi];
                if ($item['type'] === 'banner') {
                    $gridRows[] = ['type' => 'banner', 'items' => [$item]];
                    $mi++;
                } else {
                    if ($mi + 1 < $M && $mixedItems[$mi + 1]['type'] === 'regular') {
                        $gridRows[] = ['type' => 'pair', 'items' => [$item, $mixedItems[$mi + 1]]];
                        $mi += 2;
                    } else {
                        $gridRows[] = ['type' => 'single_regular', 'items' => [$item]];
                        $mi++;
                    }
                }
            }

            // Paginate rows
            $pagesList = [];
            $activeRows = [];
            $activeH = 0.0;
            foreach ($gridRows as $row) {
                $rowEstH = ($row['type'] === 'banner') ? 160.0 : 270.0;
                if (!empty($activeRows) && ($activeH + $rowEstH > 610.0 || count($activeRows) >= 3)) {
                    $pagesList[] = $activeRows;
                    $activeRows = [$row];
                    $activeH = $rowEstH;
                } else {
                    $activeRows[] = $row;
                    $activeH += $rowEstH + 18.0;
                }
            }
            if (!empty($activeRows)) {
                $pagesList[] = $activeRows;
            }

            foreach ($pagesList as $chIndex => $pRows) {
                $this->addBlankPage();
                $isDarkPage = ($chIndex % 2 === 1);
                $pBg = $isDarkPage ? [12, 12, 12] : $pageBgCol;
                $pTxt = $isDarkPage ? [255, 255, 255] : $textMainCol;
                $secAccentCol = self::ensureAccessibleColor($accentCol, $pBg, 4.5);
                $this->fillRect(0, 0, $this->pw, $this->ph, $pBg);

                $this->drawText(strtoupper($name), 22, 26, 6.5, 'FB', $pTxt);
                $this->drawText(sprintf('%02d / %s', $ci + 1, strtoupper($cat)), $this->pw - 22, 26, 6.5, 'FR', $pTxt, 'R');
                $this->fillRect(22, 33, 22, 2.2, $secAccentCol);
                $this->drawText(sprintf('%02d', $sectionPg), $this->pw - 22, 825, 8, 'FB', $pTxt, 'R');
                $this->fillRect($this->pw - 52, 815, 12, 2.2, $secAccentCol);

                $catTitle = strtoupper($cat);
                $this->drawText($catTitle, 22, 95, 48, 'FB', $pTxt);
                $this->drawText(sprintf('%02d', $ci + 1), $this->pw - 22, 95, 26, 'FB', $secAccentCol, 'R');

                $curRowY = 125.0;
                $fullW = $this->pw - 44.0;
                $colW = ($fullW - 14.0) / 2.0;

                foreach ($pRows as $rIdx => $row) {
                    if ($row['type'] === 'banner') {
                        $bItem = $row['items'][0];
                        $barAR = max(1.75, $bItem['ar']);
                        $bH = min(220.0, max(120.0, $fullW / $barAR));
                        $this->fillRect(22, $curRowY, $fullW, $bH, $isDarkPage ? [20, 20, 24] : [242, 242, 246]);
                        if (!empty($bItem['path']) && file_exists($bItem['path'])) {
                            $this->drawImageBox($bItem['path'], 22, $curRowY, $fullW, $bH, 0, 'none', 'contain');
                        }
                        $capText = strtoupper(trim($bItem['data']['title'] ?? ($cat . ' Banner')));
                        $this->drawText('FEATURED BANNER', 22, $curRowY + $bH + 12, 6.5, 'FB', $secAccentCol);
                        $this->drawText($capText, 105, $curRowY + $bH + 12, 6.5, 'FB', $pTxt);
                        $curRowY += $bH + 26.0;
                    } elseif ($row['type'] === 'pair') {
                        $rH = 265.0;
                        foreach ($row['items'] as $colIdx => $pItem) {
                            $gx = 22.0 + $colIdx * ($colW + 14.0);
                            $this->fillRect($gx, $curRowY, $colW, $rH, $isDarkPage ? [20, 20, 24] : [242, 242, 246]);
                            if (!empty($pItem['path']) && file_exists($pItem['path'])) {
                                $this->drawImageBox($pItem['path'], $gx, $curRowY, $colW, $rH, 0, 'none', 'contain');
                            }
                            $capText = strtoupper(trim($pItem['data']['title'] ?? ($cat . ' ' . sprintf('%02d', $colIdx + 1))));
                            $this->drawText(sprintf('%02d', $colIdx + 1), $gx, $curRowY + $rH + 12, 6.5, 'FB', $secAccentCol);
                            $this->drawText($capText, $gx + 16, $curRowY + $rH + 12, 6.5, 'FB', $pTxt);
                        }
                        $curRowY += $rH + 26.0;
                    } elseif ($row['type'] === 'single_regular') {
                        $pItem = $row['items'][0];
                        $rH = 280.0;
                        $sw = min($fullW, $rH * $pItem['ar']);
                        $sx = 22.0 + ($fullW - $sw) / 2.0;
                        $this->fillRect($sx, $curRowY, $sw, $rH, $isDarkPage ? [20, 20, 24] : [242, 242, 246]);
                        if (!empty($pItem['path']) && file_exists($pItem['path'])) {
                            $this->drawImageBox($pItem['path'], $sx, $curRowY, $sw, $rH, 0, 'none', 'contain');
                        }
                        $capText = strtoupper(trim($pItem['data']['title'] ?? ($cat . ' Project')));
                        $this->drawText('01', $sx, $curRowY + $rH + 12, 6.5, 'FB', $secAccentCol);
                        $this->drawText($capText, $sx + 16, $curRowY + $rH + 12, 6.5, 'FB', $pTxt);
                        $curRowY += $rH + 26.0;
                    }
                }

                if ($isDarkPage) {
                    $this->drawPolygon([[0, 841.89], [70, 841.89], [0, 771.89]], $secAccentCol);
                } else {
                    $this->fillRect(22, 804, $this->pw - 44, 4, $secAccentCol);
                }

                $sectionPg++;
            }

            // 2. Render each tall image as an exclusive SINGLE FULL-PAGE SHOWCASE with maximum height
            foreach ($tallImgs as $tiIdx => $tItem) {
                $this->addBlankPage();
                $isDarkPage = ($tiIdx % 2 === 1);
                $pBg = $isDarkPage ? [12, 12, 12] : $pageBgCol;
                $pTxt = $isDarkPage ? [255, 255, 255] : $textMainCol;
                $secAccentCol = self::ensureAccessibleColor($accentCol, $pBg, 4.5);
                $this->fillRect(0, 0, $this->pw, $this->ph, $pBg);

                // Top Header
                $this->drawText(strtoupper($name), 22, 26, 6.5, 'FB', $pTxt);
                $this->drawText(sprintf('%02d / %s &bull; FULL SHOWCASE', $ci + 1, strtoupper($cat)), $this->pw - 22, 26, 6.5, 'FR', $pTxt, 'R');
                $this->fillRect(22, 33, 22, 2.2, $secAccentCol);
                $this->drawText(sprintf('%02d', $sectionPg), $this->pw - 22, 825, 8, 'FB', $pTxt, 'R');
                $this->fillRect($this->pw - 52, 815, 12, 2.2, $secAccentCol);

                // Title & Category Ribbon
                $capText = strtoupper(trim($tItem['data']['title'] ?? ($cat . ' Showcase')));
                $this->drawText($capText, 22, 62, 16, 'FB', $pTxt);
                $this->drawText('EXTENDED VERTICAL PRESENTATION', 22, 73, 6.5, 'FB', $secAccentCol);

                // Full-width, Maximum Height Showcase Container (A4 full height)
                $boxW = $this->pw - 44; // 551.28 pt
                $boxH = 705.0;          // Max height from 82 to 787
                $boxX = 22.0;
                $boxY = 82.0;

                $this->fillRect($boxX, $boxY, $boxW, $boxH, $isDarkPage ? [18, 18, 22] : [245, 245, 248]);
                if (!empty($tItem['path']) && file_exists($tItem['path'])) {
                    $this->drawImageBox($tItem['path'], $boxX, $boxY, $boxW, $boxH, 0, 'none', 'contain');
                }

                if ($isDarkPage) {
                    $this->drawPolygon([[0, 841.89], [55, 841.89], [0, 786.89]], $secAccentCol);
                } else {
                    $this->fillRect(22, 804, $this->pw - 44, 3, $secAccentCol);
                }

                $sectionPg++;
            }
        }

        // FINAL PAGE: THANK YOU
        $this->addBlankPage();
        $this->fillRect(0, 0, $this->pw, $this->ph, $pageBgCol);
        $this->fillRect(0, 0, $this->pw, 330, $accentCol);

        // Thank you hero image on yellow header
        $bestHero = $this->getBestHeroImages($allImages, 1);
        if (!empty($bestHero[0])) {
            $hp = resolve_upload_path('portfolio', $bestHero[0]['filename'] ?? '');
            if ($hp && file_exists($hp)) {
                $sz = @getimagesize($hp);
                $ar = ($sz && $sz[0] > 0 && $sz[1] > 0) ? (float)$sz[0] / (float)$sz[1] : 1.0;
                $maxW = 260.0;
                $maxH = 150.0;
                if ($ar >= 1.0) {
                    $boxW = min($maxW, $maxH * $ar);
                    $boxH = $boxW / $ar;
                } else {
                    $boxH = min($maxH, $maxW / $ar);
                    $boxW = $boxH * $ar;
                }
                $boxX = ($this->pw - $boxW) / 2.0;
                $boxY = 145.0 + ($maxH - $boxH) / 2.0;
                $this->drawRoundedRect($boxX, $boxY, $boxW, $boxH, 8, [20, 20, 24], null);
                $this->drawImageBox($hp, $boxX + 2, $boxY + 2, $boxW - 4, $boxH - 4, 6, 'rounded', 'contain');
            }
        }

        // Signature Straight Horizontal Black Band (0 deg, no rotation)
        $this->fillRect(0, 330, 420, 85, $bandBgCol);

        // Text INSIDE straight signature band
        $this->drawText("LET'S WORK TOGETHER", 24, 368, 16, 'FB', $bandTextCol);
        $this->drawText("Available for freelance, brand design & full-time", 24, 392, 9.5, 'FR', $bandSubCol);

        // Big Typography Below Band (clean clearance, no overlap)
        $this->drawText("THANK", 24, 475, 72, 'FB', $textMainCol);
        $this->drawText("YOU.", 24, 545, 72, 'FB', $textMainCol);

        $this->drawText("EMAIL", 24, 600, 6.5, 'FB', $textMainCol);
        $this->drawText($user['email'] ?? '', 24, 614, 11, 'FB', $textMainCol);

        $this->drawText("PHONE", 24, 638, 6.5, 'FB', $textMainCol);
        $this->drawText($user['phone'] ?? '', 24, 652, 11, 'FB', $textMainCol);

        $this->drawText("LOCATION", 24, 676, 6.5, 'FB', $textMainCol);
        $this->drawText($user['address'] ?? 'Worldwide', 24, 690, 11, 'FB', $textMainCol);

        $this->fillRect(24, 715, 52, 6, $textMainCol);
        $this->drawText(strtoupper($name), 24, 736, 8, 'FB', $textMainCol);
        $this->drawText(strtoupper($role) . " | VERIFIED ON SAAQIFOLIO", 24, 748, 6.5, 'FR', $textMainCol);
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
                if (function_exists('gzcompress')) {
                    $compressed = gzcompress($streamData, 6);
                    $len = strlen($compressed);
                    $pdf .= "$objNum 0 obj\n<< /Filter /FlateDecode /Length $len >>\nstream\n" . $compressed . "\nendstream\nendobj\n";
                } else {
                    $len = strlen($streamData);
                    $pdf .= "$objNum 0 obj\n<< /Length $len >>\nstream\n" . $streamData . "\nendstream\nendobj\n";
                }
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

    public function output(string $filename, string $dest = 'attachment'): void {
        $pdf = $this->buildPdfString();
        if (ob_get_length()) { @ob_end_clean(); }
        $disp = ($dest === 'inline') ? 'inline' : 'attachment';
        header('Content-Type: application/pdf');
        header('Content-Disposition: ' . $disp . '; filename="' . $filename . '"');
        header('Access-Control-Expose-Headers: Content-Disposition');
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
