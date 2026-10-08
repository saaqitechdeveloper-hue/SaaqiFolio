<?php
/**
 * SAAQIFOLIO - 3-Step Interactive PDF Export Modal with Live Customizer & Dynamic Preview
 *
 * Variables expected:
 * - $pdfModalBaseUrl: URL prefix (e.g. 'download_pdf.php' or 'portfolio_pdf.php?slug=xyz')
 * - $pdfTotalImages: int total portfolio images
 * - $pdfCategoriesCount: array [category_name => count]
 */
$pdfModalBaseUrl = $pdfModalBaseUrl ?? 'download_pdf.php';
$pdfTotalImages = (int)($pdfTotalImages ?? 0);
$pdfCategoriesCount = $pdfCategoriesCount ?? [];

// Prepare User Data for Live Preview Engine
$previewUser = [
    'name' => !empty($user['name']) ? $user['name'] : 'Creative Designer',
    'role' => !empty($user['profession']) ? $user['profession'] : (!empty($user['role']) ? $user['role'] : 'Graphic & UI/UX Designer'),
    'email' => !empty($user['email']) ? $user['email'] : 'contact@designer.com',
    'phone' => !empty($user['phone']) ? $user['phone'] : '+1 (555) 234-5678',
    'location' => !empty($user['address']) ? $user['address'] : 'New York, USA',
    'bio' => !empty($user['bio']) ? $user['bio'] : 'Passionate creator dedicated to crafting visually compelling brand identities, digital interfaces, and modern visual experiences.',
    'avatar' => !empty($user['avatar']) ? (function_exists('resolve_upload_path') ? resolve_upload_path('avatars', $user['avatar']) : 'uploads/avatars/' . $user['avatar']) : '',
    'experience' => !empty($user['experience']) ? $user['experience'] : '5+ Years in Digital & Product Design',
    'education' => !empty($user['education']) ? $user['education'] : 'B.Des in Communication & Graphic Design',
    'skills' => !empty($user['skills']) ? (function_exists('skills_to_array') ? skills_to_array($user['skills']) : explode(',', $user['skills'])) : ['Figma', 'Photoshop', 'Illustrator', 'Blender'],
    'total_projects' => $pdfTotalImages,
    'categories' => array_keys($pdfCategoriesCount)
];

// Prepare sample artwork images for live preview (Real User Images)
$previewImagesData = [];
$candidateImages = !empty($allImages) ? $allImages : [];
if (empty($candidateImages) && isset($conn, $user['id'])) {
    $uid = (int)$user['id'];
    $imgRes = mysqli_query($conn, "SELECT filename, category, title FROM portfolio_images WHERE user_id=$uid ORDER BY sort_order ASC, id DESC LIMIT 16");
    if ($imgRes) {
        while ($imgRow = mysqli_fetch_assoc($imgRes)) {
            $candidateImages[] = $imgRow;
        }
    }
}

$isDashboard = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/dashboard') !== false);
$siteRoot = function_exists('get_site_root_url') ? get_site_root_url() : '';
$baseUrlForImg = !empty($siteRoot) ? $siteRoot : ($isDashboard ? '..' : '.');

foreach (array_slice($candidateImages, 0, 16) as $img) {
    $fName = $img['filename'] ?? ($img['image_file'] ?? '');
    $imgUrl = '';
    $isTall = false;
    if (!empty($fName)) {
        if (function_exists('get_portfolio_thumbnail_url')) {
            $imgUrl = get_portfolio_thumbnail_url($fName, $baseUrlForImg);
        }
        if (!$imgUrl) {
            $imgUrl = $baseUrlForImg . '/assets/uploads/portfolio/' . rawurlencode($fName);
        }
        $fullPath = __DIR__ . '/../assets/uploads/portfolio/' . $fName;
        if (file_exists($fullPath)) {
            $dim = @getimagesize($fullPath);
            if ($dim && !empty($dim[0]) && !empty($dim[1])) {
                $isTall = (($dim[1] / $dim[0]) >= 1.45);
            }
        }
    }
    $previewImagesData[] = [
        'title' => $img['title'] ?? 'Artwork Project',
        'category' => $img['category'] ?? 'Design',
        'src' => $imgUrl,
        'is_tall' => $isTall
    ];
}
?>

<!-- ==========================================================================
     STEP 1 MODAL: Select Scope (All vs Specific Category)
     ========================================================================== -->
<div id="pdfScopeModal" class="pdf-modal-backdrop" aria-hidden="true">
  <div class="pdf-modal-card">
    <button type="button" class="pdf-modal-close" onclick="closeAllPdfModals()" title="Close">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <div class="pdf-step-indicator">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      <span>Step 1 of 3 &bull; Export Scope</span>
    </div>

    <h3 class="pdf-modal-title">Export Your Portfolio</h3>
    <p class="pdf-modal-desc">
      Choose whether you would like to export your entire showcase or compile an exclusive portfolio PDF for a specific category.
    </p>

    <div class="pdf-scope-options">
      <!-- Option 1: Complete Portfolio -->
      <div class="pdf-scope-option selected" id="pdfScopeOptionAll" onclick="selectPdfScope('all')">
        <div class="pdf-scope-radio"></div>
        <div class="pdf-scope-text">
          <div class="pdf-scope-title">
            <span>Complete Portfolio</span>
            <span class="badge" style="font-size:11px;padding:2px 8px;background:rgba(139,92,246,0.2);color:var(--accent-purple-light);border:1px solid rgba(139,92,246,0.3);">All Categories</span>
          </div>
          <div class="pdf-scope-sub">Includes all <?php echo $pdfTotalImages; ?> works curated into clean categorized chapters.</div>
        </div>
      </div>

      <!-- Option 2: Specific Category -->
      <div class="pdf-scope-option" id="pdfScopeOptionSpecific" onclick="selectPdfScope('specific')">
        <div class="pdf-scope-radio"></div>
        <div class="pdf-scope-text">
          <div class="pdf-scope-title">
            <span>Specific Category Collection</span>
            <span class="badge" style="font-size:11px;padding:2px 8px;background:rgba(255,255,255,0.06);color:var(--text-secondary);">Targeted Proposal</span>
          </div>
          <div class="pdf-scope-sub">Export works solely from one specialized niche or design discipline.</div>
        </div>
      </div>

      <!-- Specific Category Selection Checkbox Container -->
      <div id="pdfCategorySelectBox" class="pdf-cat-select-box" style="display:none;">
        <div class="pdf-cat-select-header">
          <span style="font-size:12px;font-weight:600;color:var(--text-secondary);">Select One or More Categories:</span>
          <div style="display:flex;gap:10px;align-items:center;">
            <button type="button" class="btn-link-xs" onclick="toggleAllPdfCategories(true)">Select All</button>
            <span style="color:var(--text-muted);font-size:10px;">&bull;</span>
            <button type="button" class="btn-link-xs" onclick="toggleAllPdfCategories(false)">Clear</button>
          </div>
        </div>

        <div class="pdf-cat-checkbox-grid">
          <?php 
          $renderedCount = 0;
          foreach ($pdfCategoriesCount as $catName => $count): 
            if ($count <= 0) continue; 
            $renderedCount++;
          ?>
            <div class="pdf-cat-checkbox-item checked" data-cat="<?php echo htmlspecialchars($catName); ?>" onclick="togglePdfCatItem(this, event)">
              <div class="pdf-cat-checkbox-box">
                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5"><polyline points="20 6 9 17 4 12"/></svg>
              </div>
              <span class="pdf-cat-label"><?php echo htmlspecialchars($catName); ?></span>
              <span class="pdf-cat-badge">(<?php echo $count; ?>)</span>
            </div>
          <?php endforeach; ?>
          <?php if ($renderedCount === 0): ?>
            <div style="font-size:12px;color:var(--text-muted);padding:10px 0;">No categorized images found in this portfolio yet.</div>
          <?php endif; ?>
        </div>

        <div id="pdfCatSelectionNotice" style="margin-top:10px;font-size:11.5px;color:var(--accent-purple-light);display:flex;align-items:center;gap:6px;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          <span id="pdfCatSelectedText">All categories selected</span>
        </div>
      </div>
    </div>

    <div class="pdf-modal-foot">
      <button type="button" class="btn btn-ghost" onclick="closeAllPdfModals()" style="width:auto;padding:9px 18px;font-size:13.5px;">Cancel</button>
      <button type="button" class="btn btn-primary" onclick="goToPdfStep2()" style="width:auto;padding:9px 22px;font-size:13.5px;">
        <span>Next: Choose Design</span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>
  </div>
</div>

<!-- ==========================================================================
     STEP 2 MODAL: Choose PDF Design Template
     ========================================================================== -->
<div id="pdfTemplateModal" class="pdf-modal-backdrop" aria-hidden="true">
  <div class="pdf-modal-card" style="max-width: 680px;">
    <button type="button" class="pdf-modal-close" onclick="closeAllPdfModals()" title="Close">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <div class="pdf-step-indicator">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      <span>Step 2 of 3 &bull; Presentation Design</span>
    </div>

    <h3 class="pdf-modal-title">Select Presentation Style</h3>
    <p class="pdf-modal-desc">
      Choose a distinct aesthetic template. You will be able to customize colors, dark/light mode, and preview pages in the next step.
    </p>

    <!-- 3 Distinct Templates Grid -->
    <div class="pdf-templates-grid">
      <!-- 1. Obsidian Noir -->
      <div class="pdf-template-card selected" data-template="obsidian" onclick="selectPdfTemplate('obsidian')">
        <div class="pdf-tpl-preview obsidian">
          <div class="pdf-tpl-palette">
            <span class="pdf-tpl-dot" style="background:#8b5cf6;"></span>
            <span class="pdf-tpl-dot" style="background:#ff6b4a;"></span>
            <span class="pdf-tpl-dot" style="background:#1e1a34;"></span>
          </div>
          <div class="pdf-tpl-lines">
            <span class="pdf-tpl-line" style="background:#8b5cf6;width:55%;"></span>
            <span class="pdf-tpl-line" style="background:rgba(255,255,255,0.7);width:85%;"></span>
            <span class="pdf-tpl-line" style="background:rgba(255,255,255,0.3);width:40%;"></span>
          </div>
        </div>
        <div class="pdf-tpl-name">Obsidian Noir</div>
        <div class="pdf-tpl-desc">Executive luxury dark layout. Deep obsidian glass cards with glowing violet &amp; coral accents. Perfect for high-end digital design.</div>
      </div>

      <!-- 2. Cyber Minimalist -->
      <div class="pdf-template-card" data-template="cyber" onclick="selectPdfTemplate('cyber')">
        <div class="pdf-tpl-preview cyber">
          <div class="pdf-tpl-palette">
            <span class="pdf-tpl-dot" style="background:#00e5ff;"></span>
            <span class="pdf-tpl-dot" style="background:#38bdf8;"></span>
            <span class="pdf-tpl-dot" style="background:#0f172a;"></span>
          </div>
          <div class="pdf-tpl-lines">
            <span class="pdf-tpl-line" style="background:#00e5ff;width:65%;"></span>
            <span class="pdf-tpl-line" style="background:rgba(255,255,255,0.75);width:80%;"></span>
            <span class="pdf-tpl-line" style="background:rgba(255,255,255,0.35);width:45%;"></span>
          </div>
        </div>
        <div class="pdf-tpl-name">Cyber Minimalist</div>
        <div class="pdf-tpl-desc">High-density 3-column artwork grid with category pill badge overlays, tech stats, and side-by-side details block.</div>
      </div>

      <!-- 3. Swiss Editorial -->
      <div class="pdf-template-card" data-template="swiss" onclick="selectPdfTemplate('swiss')">
        <div class="pdf-tpl-preview swiss">
          <div class="pdf-tpl-palette">
            <span class="pdf-tpl-dot" style="background:#ffd21a;"></span>
            <span class="pdf-tpl-dot" style="background:#0a0a0a;"></span>
            <span class="pdf-tpl-dot" style="background:#ffffff;border:1px solid #ccc;"></span>
          </div>
          <div class="pdf-tpl-lines">
            <span class="pdf-tpl-line" style="background:#ffd21a;width:70%;"></span>
            <span class="pdf-tpl-line" style="background:#0a0a0a;width:90%;"></span>
            <span class="pdf-tpl-line" style="background:#737373;width:50%;"></span>
          </div>
        </div>
        <div class="pdf-tpl-name">Swiss Editorial</div>
        <div class="pdf-tpl-desc">Bold poster aesthetic. Vivid yellow &amp; black typography, angled diagonal band, large stacked headline, and structured 2x2 grids.</div>
      </div>
    </div>

    <div class="pdf-modal-foot">
      <button type="button" class="btn btn-ghost" onclick="backToPdfStep1()" style="width:auto;padding:9px 18px;font-size:13.5px;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        <span>Back to Scope</span>
      </button>
      <button type="button" class="btn btn-primary" onclick="goToPdfStep3()" style="width:auto;padding:9px 24px;font-size:13.5px;">
        <span>Next: Customize &amp; Preview</span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>
  </div>
</div>

<!-- ==========================================================================
     STEP 3 MODAL: Live Interactive Customizer & Dynamic Preview
     ========================================================================== -->
<div id="pdfPreviewModal" class="pdf-modal-backdrop" aria-hidden="true">
  <div class="pdf-modal-card pdf-modal-card-lg">
    <button type="button" class="pdf-modal-close" onclick="closeAllPdfModals()" title="Close">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <div class="pdf-step-indicator">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      <span>Step 3 of 3 &bull; Live Preview &amp; Customizer</span>
    </div>

    <h3 class="pdf-modal-title" style="margin-bottom: 2px;">Customize &amp; Live Preview</h3>
    <p class="pdf-modal-desc" style="margin-bottom: 12px;">
      Adjust theme mode, accent colors, and navigate pages with instant real-time live preview before exporting.
    </p>

    <!-- Customizer Body: Controls on Left, Live Sheet on Right -->
    <div class="pdf-customizer-body">
      <!-- Left Controls Column -->
      <div class="pdf-customizer-sidebar">
        <!-- 1. Theme Mode Switcher -->
        <div class="pdf-ctrl-group">
          <div class="pdf-ctrl-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
            <span>Theme Mode</span>
          </div>
          <div class="pdf-mode-toggle">
            <button type="button" class="pdf-mode-btn active" id="btnModeDark" onclick="setPreviewThemeMode('dark')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
              <span>Dark Mode</span>
            </button>
            <button type="button" class="pdf-mode-btn" id="btnModeLight" onclick="setPreviewThemeMode('light')">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
              <span>Light Mode</span>
            </button>
          </div>
        </div>

        <!-- 2. Accent Color Palette -->
        <div class="pdf-ctrl-group">
          <div class="pdf-ctrl-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="13.5" cy="6.5" r=".5"/><circle cx="17.5" cy="10.5" r=".5"/><circle cx="8.5" cy="7.5" r=".5"/><circle cx="6.5" cy="12.5" r=".5"/><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10c.926 0 1.648-.746 1.648-1.688 0-.437-.18-.835-.437-1.125-.29-.289-.438-.652-.438-1.125a1.64 1.64 0 0 1 1.668-1.668h1.996c3.051 0 5.563-2.512 5.563-5.563C22 6.5 17.5 2 12 2z"/></svg>
            <span>Accent Color</span>
          </div>
          <div class="pdf-swatches-grid">
            <div class="pdf-swatch" style="background:#ffd21a;color:#ffd21a;" data-color="#ffd21a" title="Swiss Vivid Yellow" onclick="setPreviewAccent('#ffd21a', this)"></div>
            <div class="pdf-swatch" style="background:#00e5ff;color:#00e5ff;" data-color="#00e5ff" title="Electric Cyan" onclick="setPreviewAccent('#00e5ff', this)"></div>
            <div class="pdf-swatch active" style="background:#8b5cf6;color:#8b5cf6;" data-color="#8b5cf6" title="Vivid Violet" onclick="setPreviewAccent('#8b5cf6', this)"></div>
            <div class="pdf-swatch" style="background:#ff6b4a;color:#ff6b4a;" data-color="#ff6b4a" title="Sunset Coral" onclick="setPreviewAccent('#ff6b4a', this)"></div>
            <div class="pdf-swatch" style="background:#10b981;color:#10b981;" data-color="#10b981" title="Emerald Green" onclick="setPreviewAccent('#10b981', this)"></div>
            <div class="pdf-swatch" style="background:#27272a;color:#27272a;" data-color="#27272a" title="Onyx Monochrome" onclick="setPreviewAccent('#27272a', this)"></div>
          </div>

          <!-- Custom Color Picker & Hex Input Field -->
          <div class="pdf-custom-color-row" style="display:flex;align-items:center;gap:8px;margin-top:10px;padding:6px 10px;background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);border-radius:8px;">
            <div style="position:relative;width:24px;height:24px;flex-shrink:0;">
              <input type="color" id="pdfColorPicker" value="#8b5cf6" style="position:absolute;inset:0;width:100%;height:100%;opacity:0;cursor:pointer;z-index:2;" oninput="onCustomColorPicked(this.value)">
              <div id="pdfColorPickerSwatch" style="width:100%;height:100%;border-radius:5px;background:#8b5cf6;border:2px solid rgba(255,255,255,0.25);pointer-events:none;"></div>
            </div>
            <div style="position:relative;flex:1;">
              <input type="text" id="pdfCustomHex" maxlength="7" placeholder="#8B5CF6" value="#8B5CF6" style="width:100%;padding:4px 8px;font-size:12px;font-family:var(--font-mono);font-weight:700;background:transparent;border:1px solid rgba(255,255,255,0.12);border-radius:6px;color:var(--text-main);text-transform:uppercase;" oninput="onCustomHexTyped(this.value)">
            </div>
            <span style="font-size:11px;color:var(--text-muted);font-weight:600;white-space:nowrap;">Custom Hex</span>
          </div>
        </div>

        <!-- 3. Quick Template Switcher -->
        <div class="pdf-ctrl-group">
          <div class="pdf-ctrl-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <span>Template Style</span>
          </div>
          <div class="pdf-tpl-pills">
            <button type="button" class="pdf-tpl-pill-btn" id="btnTplObsidian" onclick="switchPreviewTemplate('obsidian')">Obsidian</button>
            <button type="button" class="pdf-tpl-pill-btn" id="btnTplCyber" onclick="switchPreviewTemplate('cyber')">Cyber</button>
            <button type="button" class="pdf-tpl-pill-btn" id="btnTplSwiss" onclick="switchPreviewTemplate('swiss')">Swiss</button>
          </div>
        </div>

        <!-- 4. Front Page Live Preview Info -->
        <div class="pdf-ctrl-group">
          <div class="pdf-ctrl-label">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span>Preview Mode</span>
          </div>
          <div style="font-size:12px;font-weight:600;color:var(--accent-purple-light);background:rgba(139,92,246,0.1);border:1px solid rgba(139,92,246,0.22);padding:8px 12px;border-radius:8px;display:flex;align-items:center;gap:8px;">
            <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#3ddc97;box-shadow:0 0 8px #3ddc97;"></span>
            <span>Front Page (Instant Live Preview)</span>
          </div>
        </div>

        <!-- 5. Scope Info Badge -->
        <div style="font-size:12px;color:var(--text-muted);display:flex;align-items:center;gap:6px;padding:4px 6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
          <span id="pdfPreviewScopeSummary">Exporting Complete Portfolio</span>
        </div>
      </div>

      <!-- Right Column: Live A4 Preview Frame -->
      <div class="pdf-preview-stage">
        <div class="pdf-preview-badge-floating" id="pdfPreviewLiveBadge">
          <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#3ddc97;box-shadow:0 0 8px #3ddc97;"></span>
          <span id="pdfPreviewBadgeText">Obsidian Noir &bull; Dark &bull; Front Page</span>
        </div>

        <!-- Real-Time PDF Canvas Viewport -->
        <div class="pdf-canvas-container" id="pdfCanvasContainer">
          <canvas id="pdfPreviewCanvas" class="pdf-preview-canvas"></canvas>
          <div class="pdf-canvas-loading" id="pdfCanvasLoading">
            <div class="pdf-canvas-spinner"></div>
            <span id="pdfCanvasLoadingText" style="margin-top:4px;">Rendering Front Page...</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Footer Actions -->
    <div class="pdf-modal-foot">
      <button type="button" class="btn btn-ghost" onclick="backToPdfStep2()" style="width:auto;padding:9px 18px;font-size:13.5px;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        <span>Back to Design</span>
      </button>
      <button type="button" class="btn btn-primary" onclick="startPdfGenerationDownload()" id="btnStartPdfGen" style="width:auto;padding:9px 26px;font-size:13.5px;">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
        <span>Generate &amp; Download PDF</span>
      </button>
    </div>
  </div>
</div>

<!-- ==========================================================================
     PDF GENERATION AJAX LOADING OVERLAY
     ========================================================================== -->
<div id="pdfLoadingModal" class="pdf-modal-backdrop" aria-hidden="true" style="z-index:999998;">
  <div class="pdf-modal-card" style="max-width:440px;text-align:center;padding:42px 32px;">
    <!-- Glowing Animated Spinner -->
    <div class="pdf-loading-spinner-wrap">
      <div class="pdf-spinner-glow"></div>
      <div class="pdf-spinner-ring"></div>
      <div class="pdf-spinner-icon">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="32" height="32">
          <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
          <polyline points="14 2 14 8 20 8"></polyline>
          <line x1="12" y1="18" x2="12" y2="12"></line>
          <line x1="9" y1="15" x2="15" y2="15"></line>
        </svg>
      </div>
    </div>

    <h3 style="font-family:var(--font-display);font-size:20px;font-weight:700;color:#ffffff;margin:22px 0 8px 0;">
      Compiling Your PDF...
    </h3>
    <p style="font-size:13px;line-height:1.6;color:var(--text-muted);margin:0 0 22px 0;">
      Please wait while we render your artwork, chapters, and high-resolution layout.
    </p>

    <!-- Animated Progress Bar -->
    <div class="pdf-gen-progress-track">
      <div class="pdf-gen-progress-bar" id="pdfProgressBar" style="width:15%;"></div>
    </div>
    <div style="font-size:12px;color:#a78bfa;margin-top:10px;font-weight:500;" id="pdfProgressText">
      Preparing pages...
    </div>
  </div>
</div>

<!-- ==========================================================================
     PDF DOWNLOAD SUCCESS POPUP MODAL (ENGLISH)
     ========================================================================== -->
<div id="pdfSuccessModal" class="pdf-modal-backdrop" aria-hidden="true" style="z-index:999999;" onclick="closePdfSuccessModal(event)">
  <div class="pdf-modal-card" style="max-width:460px;text-align:center;padding:38px 32px;" onclick="event.stopPropagation()">
    <!-- Close Button -->
    <button type="button" class="pdf-modal-close" onclick="closePdfSuccessModal()" title="Close">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <!-- Glowing Success Badge -->
    <div class="pdf-success-badge-orb">
      <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#3ddc97" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round">
        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
        <polyline points="22 4 12 14.01 9 11.01"></polyline>
      </svg>
    </div>

    <h3 class="pdf-success-modal-title">
      PDF Downloaded Successfully!
    </h3>

    <p class="pdf-success-modal-desc">
      Your portfolio PDF has been generated and downloaded to your device. <br><strong>Please check your Downloads folder.</strong>
    </p>

    <!-- Downloaded File Summary Card -->
    <div class="pdf-download-file-card">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#a78bfa" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
      <span class="pdf-download-file-name" id="pdfSuccessFileName">portfolio.pdf</span>
      <span class="pdf-download-file-tag">Downloaded</span>
    </div>

    <!-- Done Action Button -->
    <div style="margin-top:24px;">
      <button type="button" class="btn btn-primary" onclick="closePdfSuccessModal()" style="width:100%;padding:12px 24px;font-size:14px;justify-content:center;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        <span>Got It, Thanks!</span>
      </button>
    </div>
  </div>
</div>

<script src="<?php echo $baseUrlForImg; ?>/assets/js/pdf.min.js"></script>
<script>
(function() {
  if (window.pdfjsLib) {
    window.pdfjsLib.GlobalWorkerOptions.workerSrc = '<?php echo $baseUrlForImg; ?>/assets/js/pdf.worker.min.js';
  }

  const PDF_BASE_URL = <?php echo json_encode($pdfModalBaseUrl); ?>;
  const PREVIEW_USER = <?php echo json_encode($previewUser); ?>;
  const PREVIEW_IMAGES = <?php echo json_encode($previewImagesData); ?>;

  // State Management
  let currentScope = 'all'; // 'all' or 'specific'
  let selectedCategories = [];
  let currentTemplate = 'obsidian'; // 'obsidian', 'cyber', 'swiss'
  let currentThemeMode = 'dark'; // 'dark' or 'light'
  let currentAccent = '#8b5cf6'; // Default Obsidian violet
  let currentPage = 1;
  let totalPages = 4;
  let currentPdfDoc = null;
  let currentRenderTask = null;
  let previewDebounceTimer = null;

  // Open & Close Handlers
  window.openPdfExportModal = function() {
    const scopeModal = document.getElementById('pdfScopeModal');
    if (scopeModal) {
      scopeModal.classList.add('active');
      scopeModal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }
    // Background preload front page for instant Step 3 display
    if (typeof preloadFrontPagePreview === 'function') {
      preloadFrontPagePreview();
    }
  };

  window.closeAllPdfModals = function() {
    const modals = [
      document.getElementById('pdfScopeModal'),
      document.getElementById('pdfTemplateModal'),
      document.getElementById('pdfPreviewModal'),
      document.getElementById('pdfLoadingModal'),
      document.getElementById('pdfSuccessModal')
    ];
    modals.forEach(m => {
      if (m) {
        m.classList.remove('active');
        m.setAttribute('aria-hidden', 'true');
      }
    });
    const loader = document.getElementById('pdfCanvasLoading');
    if (loader) {
      loader.classList.remove('active');
    }
    document.body.style.overflow = '';
  };

  window.closePdfSuccessModal = function(e) {
    if (e && e.target && e.target !== e.currentTarget && !e.target.closest('.pdf-modal-close')) {
      return;
    }
    const modal = document.getElementById('pdfSuccessModal');
    if (modal) {
      modal.classList.remove('active');
      modal.setAttribute('aria-hidden', 'true');
    }
    document.body.style.overflow = '';
  };

  // -------------------------------------------------------------
  // STEP 1: SCOPE LOGIC
  // -------------------------------------------------------------
  function initCategoriesList() {
    const allItems = document.querySelectorAll('.pdf-cat-checkbox-item');
    if (selectedCategories.length === 0) {
      allItems.forEach(i => i.classList.add('checked'));
    }
    const checkedItems = document.querySelectorAll('.pdf-cat-checkbox-item.checked');
    selectedCategories = Array.from(checkedItems).map(i => i.getAttribute('data-cat')).filter(Boolean);
    updateCatSelectionNotice();
  }

  function updateCatSelectionNotice() {
    const notice = document.getElementById('pdfCatSelectedText');
    const allItems = document.querySelectorAll('.pdf-cat-checkbox-item');
    if (!notice) return;
    if (selectedCategories.length === 0) {
      notice.textContent = 'None selected (select at least 1)';
      notice.style.color = 'var(--text-danger, #f43f5e)';
    } else if (selectedCategories.length === allItems.length) {
      notice.textContent = 'All categories selected (' + selectedCategories.length + ')';
      notice.style.color = 'var(--accent-purple-light)';
    } else {
      notice.textContent = selectedCategories.length + ' category' + (selectedCategories.length > 1 ? 'ies' : '') + ' selected';
      notice.style.color = 'var(--accent-purple-light)';
    }
  }

  window.selectPdfScope = function(scope) {
    currentScope = scope;
    const optAll = document.getElementById('pdfScopeOptionAll');
    const optSpec = document.getElementById('pdfScopeOptionSpecific');
    const catBox = document.getElementById('pdfCategorySelectBox');

    if (scope === 'all') {
      optAll.classList.add('selected');
      optSpec.classList.remove('selected');
      if (catBox) catBox.style.display = 'none';
    } else {
      optAll.classList.remove('selected');
      optSpec.classList.add('selected');
      if (catBox) catBox.style.display = 'block';
      initCategoriesList();
    }
  };

  window.togglePdfCatItem = function(el, evt) {
    if (evt) evt.stopPropagation();
    const cat = el.getAttribute('data-cat');
    if (el.classList.contains('checked')) {
      el.classList.remove('checked');
      selectedCategories = selectedCategories.filter(c => c !== cat);
    } else {
      el.classList.add('checked');
      if (!selectedCategories.includes(cat)) selectedCategories.push(cat);
    }
    updateCatSelectionNotice();
  };

  window.toggleAllPdfCategories = function(selectAll) {
    const items = document.querySelectorAll('.pdf-cat-checkbox-item');
    selectedCategories = [];
    items.forEach(item => {
      const cat = item.getAttribute('data-cat');
      if (selectAll) {
        item.classList.add('checked');
        if (cat) selectedCategories.push(cat);
      } else {
        item.classList.remove('checked');
      }
    });
    updateCatSelectionNotice();
  };

  window.goToPdfStep2 = function() {
    if (currentScope === 'specific' && selectedCategories.length === 0) {
      if (typeof showToast === 'function') {
        showToast('Please select at least one category to export.', 'danger', 3000);
      } else {
        alert('Please select at least one category to export.');
      }
      return;
    }

    const step1 = document.getElementById('pdfScopeModal');
    const step2 = document.getElementById('pdfTemplateModal');
    if (step1) step1.classList.remove('active');
    if (step2) {
      step2.classList.add('active');
      step2.setAttribute('aria-hidden', 'false');
    }
  };

  window.backToPdfStep1 = function() {
    const step1 = document.getElementById('pdfScopeModal');
    const step2 = document.getElementById('pdfTemplateModal');
    if (step2) step2.classList.remove('active');
    if (step1) step1.classList.add('active');
  };

  // -------------------------------------------------------------
  // STEP 2: TEMPLATE SELECTION
  // -------------------------------------------------------------
  window.selectPdfTemplate = function(tpl) {
    currentTemplate = tpl;
    const cards = document.querySelectorAll('.pdf-template-card');
    cards.forEach(c => {
      if (c.getAttribute('data-template') === tpl) {
        c.classList.add('selected');
      } else {
        c.classList.remove('selected');
      }
    });

    // Sync default accent color according to template
    if (tpl === 'swiss') {
      currentAccent = '#ffd21a';
    } else if (tpl === 'cyber') {
      currentAccent = '#00e5ff';
    } else {
      currentAccent = '#8b5cf6';
    }
  };

  window.goToPdfStep3 = function() {
    const step2 = document.getElementById('pdfTemplateModal');
    const step3 = document.getElementById('pdfPreviewModal');
    if (step2) step2.classList.remove('active');
    if (step3) {
      step3.classList.add('active');
      step3.setAttribute('aria-hidden', 'false');
    }

    // Always read currently selected card from Step 2 DOM to ensure 100% sync
    const selCard = document.querySelector('.pdf-template-card.selected');
    if (selCard) {
      const chosenTpl = selCard.getAttribute('data-template');
      if (chosenTpl) {
        currentTemplate = chosenTpl;
      }
    }

    // Ensure default accent is harmonized if not explicitly customized
    if (currentTemplate === 'swiss' && (!currentAccent || currentAccent === '#8b5cf6' || currentAccent === '#00e5ff')) {
      currentAccent = '#ffd21a';
    } else if (currentTemplate === 'cyber' && (!currentAccent || currentAccent === '#8b5cf6' || currentAccent === '#ffd21a')) {
      currentAccent = '#00e5ff';
    } else if (currentTemplate === 'obsidian' && (!currentAccent || currentAccent === '#ffd21a' || currentAccent === '#00e5ff')) {
      currentAccent = '#8b5cf6';
    }

    // Sync UI controls with current settings
    syncCustomizerControlsUI();
    triggerLivePreviewUpdate(true);
  };

  window.backToPdfStep2 = function() {
    const step2 = document.getElementById('pdfTemplateModal');
    const step3 = document.getElementById('pdfPreviewModal');
    if (step3) step3.classList.remove('active');
    if (step2) step2.classList.add('active');
  };

  // -------------------------------------------------------------
  // STEP 3: LIVE CUSTOMIZER & DYNAMIC PREVIEW ENGINE
  // -------------------------------------------------------------
  function syncCustomizerControlsUI() {
    // Mode buttons
    const btnDark = document.getElementById('btnModeDark');
    const btnLight = document.getElementById('btnModeLight');
    if (btnDark && btnLight) {
      btnDark.classList.toggle('active', currentThemeMode === 'dark');
      btnLight.classList.toggle('active', currentThemeMode === 'light');
    }

    // Swatches
    const swatches = document.querySelectorAll('.pdf-swatch');
    swatches.forEach(s => {
      s.classList.toggle('active', s.getAttribute('data-color').toLowerCase() === currentAccent.toLowerCase());
    });

    // Template pills
    ['obsidian', 'cyber', 'swiss'].forEach(t => {
      const pill = document.getElementById('btnTpl' + t.charAt(0).toUpperCase() + t.slice(1));
      if (pill) pill.classList.toggle('active', currentTemplate === t);
    });

    // Scope summary
    const scopeSummary = document.getElementById('pdfPreviewScopeSummary');
    if (scopeSummary) {
      if (currentScope === 'specific' && selectedCategories.length > 0) {
        scopeSummary.textContent = `Scope: ${selectedCategories.length} Categories Selected`;
      } else {
        scopeSummary.textContent = `Scope: Complete Portfolio (${PREVIEW_USER.total_projects} Projects)`;
      }
    }

    // Live badge
    const badgeText = document.getElementById('pdfPreviewBadgeText');
    if (badgeText) {
      const tplName = currentTemplate === 'swiss' ? 'Swiss Editorial' : (currentTemplate === 'cyber' ? 'Cyber Minimalist' : 'Obsidian Noir');
      const modeName = currentThemeMode === 'dark' ? 'Dark' : 'Light';
      badgeText.textContent = `${tplName} • ${modeName}`;
    }

    // Custom color picker & hex input sync
    const picker = document.getElementById('pdfColorPicker');
    const hexInput = document.getElementById('pdfCustomHex');
    const swatch = document.getElementById('pdfColorPickerSwatch');
    if (picker && /^#([0-9A-Fa-f]{3}){1,2}$/.test(currentAccent)) picker.value = currentAccent;
    if (hexInput) hexInput.value = currentAccent.toUpperCase();
    if (swatch) swatch.style.background = currentAccent;

  }

  window.onCustomColorPicked = function(hex) {
    if (!hex) return;
    currentAccent = hex;
    syncCustomizerControlsUI();
    triggerLivePreviewUpdate(false);
  };

  window.onCustomHexTyped = function(val) {
    if (!val) return;
    let hex = val.trim();
    if (!hex.startsWith('#')) hex = '#' + hex;
    if (/^#([0-9A-Fa-f]{3}){1,2}$/.test(hex)) {
      currentAccent = hex;
      syncCustomizerControlsUI();
      triggerLivePreviewUpdate(false);
    }
  };

  window.setPreviewThemeMode = function(mode) {
    currentThemeMode = mode;
    syncCustomizerControlsUI();
    triggerLivePreviewUpdate(true);
  };

  window.setPreviewAccent = function(color, el) {
    currentAccent = color;
    syncCustomizerControlsUI();
    triggerLivePreviewUpdate(true);
  };

  window.switchPreviewTemplate = function(tpl) {
    currentTemplate = tpl;
    selectPdfTemplate(tpl);
    syncCustomizerControlsUI();
    triggerLivePreviewUpdate(true);
  };

  // -------------------------------------------------------------
  // HIGH-SPEED FRONT PAGE PDF.JS PREVIEW & IN-MEMORY CACHE
  // -------------------------------------------------------------
  const previewCanvasCache = new Map();

  function buildLivePdfUrl() {
    let targetUrl = PDF_BASE_URL;
    const separator = targetUrl.includes('?') ? '&' : '?';
    const tplParam = encodeURIComponent(currentTemplate || 'obsidian');
    const modeParam = encodeURIComponent(currentThemeMode || 'dark');
    const accentParam = encodeURIComponent(currentAccent || '#8b5cf6');

    let queryParams = `preview=1&template=${tplParam}&theme_mode=${modeParam}&accent=${accentParam}`;

    if (currentScope === 'specific' && selectedCategories.length > 0) {
      queryParams += `&categories=${encodeURIComponent(selectedCategories.join(','))}`;
    } else {
      queryParams += `&category=all`;
    }

    return `${targetUrl}${separator}${queryParams}&_t=${Date.now()}`;
  }

  function setCanvasLoadingState(isLoading, message = 'Rendering Front Page...') {
    const loader = document.getElementById('pdfCanvasLoading');
    const textEl = document.getElementById('pdfCanvasLoadingText');
    if (loader) {
      loader.classList.toggle('active', isLoading);
    }
    if (textEl && message) {
      textEl.textContent = message;
    }
  }

  async function loadLivePdfDocument(isPreload = false) {
    if (!window.pdfjsLib) {
      return;
    }

    const cacheKey = `${currentTemplate}_${currentThemeMode}_${currentAccent}_${currentScope}_${(selectedCategories||[]).join(',')}`;
    const canvas = document.getElementById('pdfPreviewCanvas');
    const container = document.getElementById('pdfCanvasContainer');

    // Instant Cache Hit: 0ms render
    if (previewCanvasCache.has(cacheKey)) {
      if (!isPreload && canvas) {
        const cached = previewCanvasCache.get(cacheKey);
        canvas.width = cached.width;
        canvas.height = cached.height;
        canvas.style.width = cached.styleWidth;
        canvas.style.height = cached.styleHeight;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(cached.image, 0, 0);
        setCanvasLoadingState(false);
      }
      return;
    }

    if (!isPreload) {
      setCanvasLoadingState(true, 'Rendering Front Page Preview...');
    }

    const pdfUrl = buildLivePdfUrl();

    try {
      if (currentRenderTask) {
        try { currentRenderTask.cancel(); } catch (e) {}
        currentRenderTask = null;
      }

      const loadingTask = window.pdfjsLib.getDocument(pdfUrl);
      const doc = await loadingTask.promise;
      const page = await doc.getPage(1); // ALWAYS Front Page

      const containerWidth = (container && container.clientWidth) ? container.clientWidth : 440;
      const baseViewport = page.getViewport({ scale: 1 });
      const targetScale = containerWidth / (baseViewport.width || 595.28);
      const renderScale = targetScale * 1.5;
      const viewport = page.getViewport({ scale: renderScale });

      if (canvas) {
        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);
        canvas.style.width = Math.floor(viewport.width / 1.5) + 'px';
        canvas.style.height = Math.floor(viewport.height / 1.5) + 'px';

        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);

        currentRenderTask = page.render({
          canvasContext: ctx,
          viewport: viewport
        });
        await currentRenderTask.promise;
        currentRenderTask = null;

        // Cache image for instant re-use
        const cachedImg = new Image();
        cachedImg.src = canvas.toDataURL();
        previewCanvasCache.set(cacheKey, {
          image: cachedImg,
          width: canvas.width,
          height: canvas.height,
          styleWidth: canvas.style.width,
          styleHeight: canvas.style.height
        });
      }

      try { doc.destroy(); } catch (e) {}
    } catch (err) {
      if (err && err.name !== 'RenderingCancelledException') {
        console.error('Error fetching live front page PDF:', err);
      }
    } finally {
      if (!isPreload) {
        setCanvasLoadingState(false);
      }
    }
  }

  window.preloadFrontPagePreview = function() {
    if (window.pdfjsLib) {
      loadLivePdfDocument(true);
    }
  };

  function triggerLivePreviewUpdate(immediate = false) {
    if (previewDebounceTimer) {
      clearTimeout(previewDebounceTimer);
      previewDebounceTimer = null;
    }
    if (immediate) {
      loadLivePdfDocument(false);
    } else {
      setCanvasLoadingState(true, 'Updating Front Page...');
      previewDebounceTimer = setTimeout(() => {
        loadLivePdfDocument(false);
      }, 200);
    }
  }

  // -------------------------------------------------------------
  // AJAX PDF GENERATION & DOWNLOAD (NO PAGE RELOAD)
  // -------------------------------------------------------------
  let progressInterval = null;

  window.startPdfGenerationDownload = function() {
    const step1 = document.getElementById('pdfScopeModal');
    const step2 = document.getElementById('pdfTemplateModal');
    const step3 = document.getElementById('pdfPreviewModal');
    const loadingModal = document.getElementById('pdfLoadingModal');
    const successModal = document.getElementById('pdfSuccessModal');
    const progressBar = document.getElementById('pdfProgressBar');
    const progressText = document.getElementById('pdfProgressText');

    // 1. Build Target URL with all custom settings
    let targetUrl = PDF_BASE_URL;
    const separator = targetUrl.includes('?') ? '&' : '?';
    const tplParam = encodeURIComponent(currentTemplate || 'obsidian');
    const modeParam = encodeURIComponent(currentThemeMode || 'dark');
    const accentParam = encodeURIComponent(currentAccent || '#8b5cf6');

    let queryParams = `template=${tplParam}&theme_mode=${modeParam}&accent=${accentParam}`;

    if (currentScope === 'specific' && selectedCategories.length > 0) {
      queryParams += `&categories=${encodeURIComponent(selectedCategories.join(','))}`;
    } else {
      queryParams += `&category=all`;
    }

    targetUrl += `${separator}${queryParams}`;

    // 2. Hide Scope, Template & Preview Modals
    if (step1) step1.classList.remove('active');
    if (step2) step2.classList.remove('active');
    if (step3) step3.classList.remove('active');

    // 3. Show Loading Modal Screen
    if (loadingModal) {
      loadingModal.classList.add('active');
      loadingModal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }

    // 4. Smooth Simulated Progress Bar
    let currentPct = 15;
    if (progressBar) progressBar.style.width = '15%';
    if (progressText) progressText.textContent = 'Rendering layout & chapters...';

    const stageMessages = [
      { pct: 35, text: 'Processing high-resolution artwork...' },
      { pct: 60, text: 'Formatting typography & color palettes...' },
      { pct: 85, text: 'Finalizing PDF document chapters...' }
    ];
    let stageIdx = 0;

    clearInterval(progressInterval);
    progressInterval = setInterval(() => {
      if (stageIdx < stageMessages.length) {
        currentPct = stageMessages[stageIdx].pct;
        if (progressBar) progressBar.style.width = currentPct + '%';
        if (progressText) progressText.textContent = stageMessages[stageIdx].text;
        stageIdx++;
      }
    }, 700);

    // 5. AJAX Fetch (No page reload)
    fetch(targetUrl, {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest'
      }
    })
    .then(async (response) => {
      clearInterval(progressInterval);

      if (!response.ok) {
        throw new Error('Failed to generate PDF. (Server returned HTTP ' + response.status + ')');
      }

      // Complete progress bar
      if (progressBar) progressBar.style.width = '100%';
      if (progressText) progressText.textContent = 'Complete! Saving to device...';

      // Determine Filename from Content-Disposition header
      let filename = 'SaaqiFolio-Portfolio.pdf';
      const disposition = response.headers.get('Content-Disposition') || response.headers.get('content-disposition');
      if (disposition && disposition.includes('filename=')) {
        const matches = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
        if (matches && matches[1]) {
          filename = matches[1].replace(/['"]/g, '').trim();
        }
      }

      // Convert response to Blob binary
      const blob = await response.blob();

      // Trigger automatic browser download via Blob URL
      const blobUrl = window.URL.createObjectURL(blob);
      const downloadLink = document.createElement('a');
      downloadLink.style.display = 'none';
      downloadLink.href = blobUrl;
      downloadLink.download = filename;
      document.body.appendChild(downloadLink);
      downloadLink.click();

      // Revoke URL after small delay
      setTimeout(() => {
        window.URL.revokeObjectURL(blobUrl);
        downloadLink.remove();
      }, 1500);

      // Hide Loader Modal
      setTimeout(() => {
        if (loadingModal) {
          loadingModal.classList.remove('active');
          loadingModal.setAttribute('aria-hidden', 'true');
        }

        // Show English Success Popup Modal
        const successFileEl = document.getElementById('pdfSuccessFileName');
        if (successFileEl) successFileEl.textContent = filename;

        if (successModal) {
          successModal.classList.add('active');
          successModal.setAttribute('aria-hidden', 'false');
        }
      }, 400);
    })
    .catch((err) => {
      clearInterval(progressInterval);
      if (loadingModal) {
        loadingModal.classList.remove('active');
        loadingModal.setAttribute('aria-hidden', 'true');
      }
      document.body.style.overflow = '';
      alert('Error generating PDF: ' + (err.message || 'Please try again.'));
    });
  };

  // Close on Escape key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      const successModal = document.getElementById('pdfSuccessModal');
      if (successModal && successModal.classList.contains('active')) {
        closePdfSuccessModal();
      } else {
        closeAllPdfModals();
      }
    }
  });

  ['pdfScopeModal', 'pdfTemplateModal', 'pdfPreviewModal'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener('click', function(e) {
        if (e.target === this) closeAllPdfModals();
      });
    }
  });

  // Auto-trigger modal for automated testing when ?test_modal is in URL
  const testModalParam = new URLSearchParams(window.location.search).get('test_modal');
  if (testModalParam) {
    const triggerTestModal = () => {
      setTimeout(() => {
        if (testModalParam === 'scope') {
          openPdfExportModal();
        } else if (testModalParam === 'template') {
          openPdfExportModal();
          goToPdfStep2();
        } else if (testModalParam === 'preview_swiss') {
          openPdfExportModal();
          selectPdfTemplate('swiss');
          goToPdfStep2();
          goToPdfStep3();
        } else if (testModalParam === 'preview' || testModalParam === 'preview_obsidian') {
          openPdfExportModal();
          selectPdfTemplate('obsidian');
          goToPdfStep2();
          goToPdfStep3();
        } else if (testModalParam === 'preview_cyber') {
          openPdfExportModal();
          selectPdfTemplate('cyber');
          goToPdfStep2();
          goToPdfStep3();
        } else if (testModalParam === 'preview_cyber_page2') {
          openPdfExportModal();
          selectPdfTemplate('cyber');
          goToPdfStep2();
          goToPdfStep3();
          flipPreviewPage(1);
        } else if (testModalParam === 'preview_light') {
          openPdfExportModal();
          selectPdfTemplate('swiss');
          goToPdfStep2();
          goToPdfStep3();
          setPreviewThemeMode('light');
        } else if (testModalParam === 'preview_custom_hex') {
          openPdfExportModal();
          selectPdfTemplate('swiss');
          goToPdfStep2();
          goToPdfStep3();
          onCustomColorPicked('#FF5500');
        }
      }, 80);
    };

    if (document.readyState === 'loading') {
      window.addEventListener('DOMContentLoaded', triggerTestModal);
    } else {
      triggerTestModal();
    }
  }
})();
</script>
