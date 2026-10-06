<?php
// Generates favicon.svg, assets/favicon.svg, assets/favicon.png, and favicon.ico

$svgContent = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="64" height="64">
  <defs>
    <linearGradient id="bgGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#18142a"/>
      <stop offset="50%" stop-color="#0e0c18"/>
      <stop offset="100%" stop-color="#07060b"/>
    </linearGradient>
    <linearGradient id="brandGrad" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#a78bfa"/>
      <stop offset="45%" stop-color="#8b5cf6"/>
      <stop offset="100%" stop-color="#ff6b4a"/>
    </linearGradient>
    <linearGradient id="accentGlow" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#c4b5fd"/>
      <stop offset="100%" stop-color="#fda4af"/>
    </linearGradient>
    <filter id="dropGlow" x="-20%" y="-20%" width="140%" height="140%">
      <feDropShadow dx="0" dy="4" stdDeviation="4" flood-color="#8b5cf6" flood-opacity="0.55"/>
    </filter>
  </defs>

  <!-- Dark Obsidian Luxe Squircle Background -->
  <rect x="2" y="2" width="60" height="60" rx="16" fill="url(#bgGrad)" stroke="rgba(255, 255, 255, 0.14)" stroke-width="1.5"/>

  <!-- Subtle Inner Border Highlight -->
  <rect x="3.5" y="3.5" width="57" height="57" rx="14.5" fill="none" stroke="rgba(139, 92, 246, 0.2)" stroke-width="1"/>

  <!-- Signature SaaqiFolio Layers Icon -->
  <g filter="url(#dropGlow)">
    <!-- Bottom Layer -->
    <path d="M 12 43 L 32 53 L 52 43" fill="none" stroke="url(#brandGrad)" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" opacity="0.8"/>

    <!-- Middle Layer -->
    <path d="M 12 33 L 32 43 L 52 33" fill="none" stroke="url(#brandGrad)" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round"/>

    <!-- Top Rhombus -->
    <polygon points="32 11 52 21 32 31 12 21" fill="url(#brandGrad)" stroke="#ffffff" stroke-width="1.4" stroke-linejoin="round"/>

    <!-- Top Rhombus Sheen Highlight -->
    <polygon points="32 12.5 50 21 32 23 14 21" fill="url(#accentGlow)" opacity="0.45"/>
  </g>
</svg>
SVG;

// Save SVG to root and assets
file_put_contents(__DIR__ . '/../favicon.svg', $svgContent);
if (!is_dir(__DIR__ . '/../assets')) {
    mkdir(__DIR__ . '/../assets', 0777, true);
}
file_put_contents(__DIR__ . '/../assets/favicon.svg', $svgContent);

// Generate 64x64 and 32x32 PNGs using GD
$size = 64;
$im = imagecreatetruecolor($size, $size);
imagealphablending($im, false);
imagesavealpha($im, true);

$transparent = imagecolorallocatealpha($im, 0, 0, 0, 127);
imagefilledrectangle($im, 0, 0, $size, $size, $transparent);

// Enable alpha blending for drawing
imagealphablending($im, true);

// Draw squircle (rounded rect) with dark obsidian color
$bgDark = imagecolorallocate($im, 14, 12, 24);
$borderCol = imagecolorallocatealpha($im, 255, 255, 255, 100);

// Draw rounded rectangle
function drawRoundedRect($img, $x1, $y1, $x2, $y2, $radius, $color) {
    imagefilledrectangle($img, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
    imagefilledrectangle($img, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);
    imagefilledellipse($img, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($img, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($img, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    imagefilledellipse($img, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
}

drawRoundedRect($im, 2, 2, 61, 61, 15, $bgDark);

// Draw layered SaaqiFolio logo
$purple = imagecolorallocate($im, 139, 92, 246);
$coral = imagecolorallocate($im, 255, 107, 74);
$white = imagecolorallocate($im, 255, 255, 255);
$glow = imagecolorallocatealpha($im, 167, 139, 250, 40);

// Draw bottom layer line
imagesetthickness($im, 3);
imageline($im, 13, 44, 32, 53, $coral);
imageline($im, 32, 53, 51, 44, $coral);

// Draw middle layer line
imageline($im, 13, 34, 32, 43, $purple);
imageline($im, 32, 43, 51, 34, $purple);

// Draw top polygon
$polyPoints = [
    32, 12,
    51, 21,
    32, 31,
    13, 21
];
imagefilledpolygon($im, $polyPoints, 4, $purple);

// Polygon border
imagesetthickness($im, 2);
imagepolygon($im, $polyPoints, 4, $white);

// Inner sheen
$sheen = imagecolorallocatealpha($im, 255, 255, 255, 80);
imageline($im, 18, 21, 32, 15, $sheen);
imageline($im, 32, 15, 46, 21, $sheen);

// Save PNG
$pngPath = __DIR__ . '/../assets/favicon.png';
imagepng($im, $pngPath, 9);

// Create 32x32 version for ICO
$im32 = imagecreatetruecolor(32, 32);
imagealphablending($im32, false);
imagesavealpha($im32, true);
imagecopyresampled($im32, $im, 0, 0, 0, 0, 32, 32, $size, $size);

// Save 32x32 png
$png32Path = __DIR__ . '/../assets/favicon-32.png';
imagepng($im32, $png32Path, 9);
$png32Data = file_get_contents($png32Path);

// Build valid ICO file embedding the 32x32 PNG data
$icoHeader = pack('vvv', 0, 1, 1); // reserved, type=1 (ICO), count=1
$icoDir = pack(
    'CCCCvvVV',
    32, // width
    32, // height
    0,  // color count
    0,  // reserved
    1,  // color planes
    32, // bpp
    strlen($png32Data), // bytes in image
    22  // offset (6 byte header + 16 byte dir = 22)
);

$icoData = $icoHeader . $icoDir . $png32Data;
file_put_contents(__DIR__ . '/../favicon.ico', $icoData);
file_put_contents(__DIR__ . '/../assets/favicon.ico', $icoData);

imagedestroy($im);
imagedestroy($im32);
if (file_exists($png32Path)) {
    unlink($png32Path);
}

echo "FAVICONS_GENERATED_SUCCESSFULLY\n";
