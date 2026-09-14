<?php
/**
 * FOLIVO - SimplePdf
 *
 * A minimal, dependency-free PDF writer. No Composer, no external library —
 * just plain PHP writing raw PDF syntax directly. This exists specifically
 * so the "Download Portfolio PDF" feature works on any server with zero
 * setup (only needs the GD extension, which almost every PHP install has
 * enabled by default, for embedding images).
 *
 * It only supports what this app needs: wrapped paragraph text, section
 * titles, a simple image grid, and basic page-break handling. It is NOT a
 * general-purpose PDF library.
 *
 * Text limitation: uses the PDF standard Helvetica font, which only
 * reliably renders basic Latin (English/Roman) characters. Non-Latin
 * script (e.g. Arabic-script Urdu) in a bio will not render correctly —
 * this is a real constraint of avoiding an external font-embedding
 * library, not a bug.
 */
class SimplePdf {
    private array $objects = [];        // 1-based: $objects[$i-1] = object body (string) OR array for special (image/stream) objects
    private array $pageObjNums = [];    // page object numbers, in order
    private array $pagesContent = [];   // accumulated content-stream text, per page (0-based index)
    private array $pagesResources = []; // per page: ['ImN' => imageObjNum, ...]
    private int $curPage = -1;
    private int $imgCounter = 0;

    private float $pw = 595.28; // A4 width in points
    private float $ph = 841.89; // A4 height in points
    private float $margin = 42;
    private float $y = 0;       // cursor, top-down design coordinate (converted to PDF's bottom-up system when drawing)

    private int $fontRegular;
    private int $fontBold;

    public function __construct() {
        $this->fontRegular = $this->addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>');
        $this->fontBold = $this->addObject('<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>');
        $this->addPage();
    }

    // ---------- low-level object bookkeeping ----------

    private function addObject($body) {
        $this->objects[] = $body;
        return count($this->objects);
    }

    public function addPage() {
        $num = $this->addObject(''); // filled in later, once Pages/Catalog object numbers are known
        $this->pageObjNums[] = $num;
        $this->pagesContent[] = '';
        $this->pagesResources[] = [];
        $this->curPage++;
        $this->y = $this->margin;
    }

    private function ensureSpace(float $height) {
        if ($this->y + $height > $this->ph - $this->margin) {
            $this->addPage();
        }
    }

    // ---------- text helpers ----------

    private function sanitizeText(string $text): string {
        // Best-effort transliteration to a Latin-1-ish subset the standard
        // Helvetica font can render; anything left over is dropped rather
        // than shown as garbled boxes.
        $converted = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $text);
        if ($converted === false) {
            $converted = preg_replace('/[^\x20-\x7E]/', '', $text);
        }
        return $converted;
    }

    private function escapePdfString(string $text): string {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function textWidth(string $text, float $size): float {
        // Rough average glyph-width estimate for Helvetica (no real font
        // metrics without embedding the font) — good enough for wrapping.
        return strlen($text) * $size * 0.5;
    }

    private function wrapText(string $text, float $size, float $maxWidth): array {
        $text = $this->sanitizeText($text);
        $paragraphs = preg_split('/\r\n|\r|\n/', $text);
        $lines = [];
        foreach ($paragraphs as $para) {
            if (trim($para) === '') { $lines[] = ''; continue; }
            $words = preg_split('/\s+/', trim($para));
            $line = '';
            foreach ($words as $w) {
                $test = $line === '' ? $w : $line . ' ' . $w;
                if ($this->textWidth($test, $size) > $maxWidth && $line !== '') {
                    $lines[] = $line;
                    $line = $w;
                } else {
                    $line = $test;
                }
            }
            if ($line !== '') $lines[] = $line;
        }
        return $lines;
    }

    public function addText(string $text, float $size = 11, bool $bold = false, array $color = [0.13, 0.12, 0.16]) {
        if (trim($text) === '') return;
        $lineHeight = $size * 1.45;
        $maxWidth = $this->pw - 2 * $this->margin;
        $lines = $this->wrapText($text, $size, $maxWidth);

        foreach ($lines as $line) {
            $this->ensureSpace($lineHeight);
            $pdfY = $this->ph - $this->y - $size;
            $this->drawTextLine($line, $this->margin, $pdfY, $size, $bold, $color);
            $this->y += $lineHeight;
        }
    }

    // Low-level: draw one line of (already unwrapped) text at an exact
    // position. Used internally by addText(), and directly by the cover
    // banner where text needs to sit next to the avatar, not at the margin.
    private function drawTextLine(string $line, float $x, float $pdfY, float $size, bool $bold, array $color) {
        $font = $bold ? '/FB' : '/FR';
        $rgb = sprintf('%.3F %.3F %.3F rg', $color[0], $color[1], $color[2]);
        $escaped = $this->escapePdfString($this->sanitizeText($line));
        $this->pagesContent[$this->curPage] .=
            "BT $font $size Tf $rgb 1 0 0 1 " . sprintf('%.2F %.2F', $x, $pdfY) . " Tm ($escaped) Tj ET\n";
    }

    // Filled rectangle. $yTop is measured from the top of the page (design
    // coordinates, matching the rest of this class), not PDF's bottom-up space.
    public function fillRect(float $x, float $yTop, float $w, float $h, array $color) {
        $pdfY = $this->ph - $yTop - $h;
        $rgb = sprintf('%.3F %.3F %.3F rg', $color[0], $color[1], $color[2]);
        $this->pagesContent[$this->curPage] .= sprintf("q %s %.2F %.2F %.2F %.2F re f Q\n", $rgb, $x, $pdfY, $w, $h);
    }

    public function addSpacer(float $height) {
        $this->y += $height;
    }

    public function addSectionTitle(string $title) {
        $this->ensureSpace(30);
        $this->y += 8;
        $this->addText(strtoupper($this->sanitizeText($title)), 12, true, [0.08, 0.08, 0.1]);
        $pdfY = $this->ph - $this->y - 3;
        $x1 = $this->margin;
        $x2 = $this->pw - $this->margin;
        $this->pagesContent[$this->curPage] .=
            sprintf("q 0.6 w 0.65 0.62 0.7 RG %.2F %.2F m %.2F %.2F l S Q\n", $x1, $pdfY, $x2, $pdfY);
        $this->y += 10;
    }

    public function addHeaderBlock(string $name, string $role, string $contactLine) {
        $this->addText($name, 22, true, [0.08, 0.06, 0.1]);
        $this->addText($role, 13, false, [0.4, 0.38, 0.45]);
        if ($contactLine !== '') {
            $this->addText($contactLine, 10, false, [0.5, 0.48, 0.55]);
        }
        $this->addSpacer(6);
    }

    // A full-width colored banner at the very top of page 1 — the PDF's
    // "introduction" section, styled to match the app's brand colors —
    // with a circular avatar photo, name, role and contact line in white.
    public function addCoverBanner(string $name, string $role, string $contactLine, ?string $avatarPath, array $bgColor) {
        $bannerHeight = 130;
        $this->fillRect(0, 0, $this->pw, $bannerHeight, $bgColor);

        $avatarSize = 74;
        $avatarX = $this->margin;
        $avatarY = ($bannerHeight - $avatarSize) / 2;
        $textX = $this->margin;

        if ($avatarPath) {
            $this->addCircularImage($avatarPath, $avatarX, $avatarY, $avatarSize);
            $textX = $avatarX + $avatarSize + 22;
        }

        $textBlockHeight = $contactLine !== '' ? (22 + 16 + 12) : (22 + 16);
        $textY = ($bannerHeight - $textBlockHeight) / 2;

        $this->drawTextLine($this->sanitizeText($name), $textX, $this->ph - $textY - 20, 21, true, [1, 1, 1]);
        $this->drawTextLine($this->sanitizeText($role), $textX, $this->ph - $textY - 40, 12.5, false, [0.92, 0.9, 0.98]);
        if ($contactLine !== '') {
            $this->drawTextLine($this->sanitizeText($contactLine), $textX, $this->ph - $textY - 58, 10, false, [0.85, 0.82, 0.96]);
        }

        $this->y = $bannerHeight + 26;
    }

    // ---------- image helpers (requires the GD extension) ----------

    private function toJpegBinary(string $path): ?string {
        if (!function_exists('imagecreatefromstring')) return null;
        $data = @file_get_contents($path);
        if (!$data) return null;
        $img = @imagecreatefromstring($data);
        if (!$img) return null;

        $w = imagesx($img);
        $h = imagesy($img);
        // Flatten onto a white background in case of PNG transparency.
        $flat = imagecreatetruecolor($w, $h);
        $white = imagecolorallocate($flat, 255, 255, 255);
        imagefill($flat, 0, 0, $white);
        imagecopy($flat, $img, 0, 0, 0, 0, $w, $h);

        ob_start();
        imagejpeg($flat, null, 82);
        $jpeg = ob_get_clean();

        imagedestroy($img);
        imagedestroy($flat);
        return $jpeg ?: null;
    }

    // Registers a JPEG binary as a PDF image XObject on the current page
    // and returns [imgName, width, height], or null if it couldn't be read.
    private function registerImageXObject(string $jpeg): ?array {
        $info = @getimagesizefromstring($jpeg);
        if (!$info) return null;
        [$iw, $ih] = $info;

        $dict = "<< /Type /XObject /Subtype /Image /Width $iw /Height $ih /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length " . strlen($jpeg) . " >>";
        $objNum = $this->addObject(['dict' => $dict, 'stream' => $jpeg]);

        $imgName = 'Im' . (++$this->imgCounter);
        $this->pagesResources[$this->curPage][$imgName] = $objNum;

        return [$imgName, $iw, $ih];
    }

    private function placeImage(string $path, float $x, float $pdfY, float $w, float $h) {
        $jpeg = $this->toJpegBinary($path);
        if ($jpeg === null) return;
        $reg = $this->registerImageXObject($jpeg);
        if ($reg === null) return;
        [$imgName, $iw, $ih] = $reg;

        // Fit the image inside the (w,h) box, preserving aspect ratio, centered.
        $scale = min($w / $iw, $h / $ih);
        $drawW = $iw * $scale;
        $drawH = $ih * $scale;
        $offsetX = ($w - $drawW) / 2;
        $offsetY = ($h - $drawH) / 2;

        $this->pagesContent[$this->curPage] .= sprintf(
            "q %.3F 0 0 %.3F %.3F %.3F cm /%s Do Q\n",
            $drawW, $drawH, $x + $offsetX, $pdfY + $offsetY, $imgName
        );
    }

    // Draws a photo clipped to a circle — used for the avatar in the cover
    // banner. $x/$yTop/$diameter are all in design (top-down) coordinates.
    private function addCircularImage(string $path, float $x, float $yTop, float $diameter) {
        $jpeg = $this->toJpegBinary($path);
        if ($jpeg === null) return;
        $reg = $this->registerImageXObject($jpeg);
        if ($reg === null) return;
        [$imgName, $iw, $ih] = $reg;

        $pdfY = $this->ph - $yTop - $diameter;
        $r = $diameter / 2;
        $cx = $x + $r;
        $cy = $pdfY + $r;
        $k = 0.5522847498 * $r; // standard bezier-circle control-point offset

        $circlePath = sprintf(
            "%.2F %.2F m %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c %.2F %.2F %.2F %.2F %.2F %.2F c W n\n",
            $cx - $r, $cy,
            $cx - $r, $cy + $k, $cx - $k, $cy + $r, $cx, $cy + $r,
            $cx + $k, $cy + $r, $cx + $r, $cy + $k, $cx + $r, $cy,
            $cx + $r, $cy - $k, $cx + $k, $cy - $r, $cx, $cy - $r,
            $cx - $k, $cy - $r, $cx - $r, $cy - $k, $cx - $r, $cy
        );

        // Cover-fit (scale to fill the circle's bounding box, centered crop)
        $scale = max($diameter / $iw, $diameter / $ih);
        $drawW = $iw * $scale;
        $drawH = $ih * $scale;
        $offX = $x - ($drawW - $diameter) / 2;
        $offY = $pdfY - ($drawH - $diameter) / 2;

        $this->pagesContent[$this->curPage] .= "q\n" . $circlePath .
            sprintf("%.3F 0 0 %.3F %.3F %.3F cm /%s Do\nQ\n", $drawW, $drawH, $offX, $offY, $imgName);
    }

    public function addImageGrid(array $imagePaths, int $cols = 3) {
        if (empty($imagePaths)) return;
        $gap = 8;
        $availWidth = $this->pw - 2 * $this->margin;
        $cell = ($availWidth - ($cols - 1) * $gap) / $cols;

        $i = 0;
        $total = count($imagePaths);
        foreach ($imagePaths as $path) {
            $col = $i % $cols;
            if ($col === 0) {
                $this->ensureSpace($cell + $gap);
            }
            $x = $this->margin + $col * ($cell + $gap);
            $pdfY = $this->ph - $this->y - $cell;
            $this->placeImage($path, $x, $pdfY, $cell, $cell);

            if ($col === $cols - 1 || $i === $total - 1) {
                $this->y += $cell + $gap;
            }
            $i++;
        }
    }

    // ---------- final assembly ----------

    public function output(string $filename) {
        // Reserve object numbers for each page's content stream.
        $contentObjNums = [];
        foreach ($this->pagesContent as $idx => $content) {
            $num = $this->addObject(['stream_raw' => $content]);
            $contentObjNums[$idx] = $num;
        }

        $pagesObjNum = $this->addObject('');
        $catalogObjNum = $this->addObject('');

        // Now fill in each Page object's dictionary.
        foreach ($this->pageObjNums as $idx => $pageNum) {
            $resDict = "/Font << /FR {$this->fontRegular} 0 R /FB {$this->fontBold} 0 R >>";
            $imgs = $this->pagesResources[$idx];
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

        // Serialize every object, tracking byte offsets as we go (this is
        // what keeps the xref table correct without any manual math).
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

        if (ob_get_length()) { @ob_end_clean(); }
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $pdf;
        exit;
    }
}
