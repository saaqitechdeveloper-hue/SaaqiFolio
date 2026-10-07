<?php
/**
 * SAAQIFOLIO - 2-Step PDF Export Modal Component
 *
 * Variables expected:
 * - $pdfModalBaseUrl: URL prefix (e.g. 'download_pdf.php' or 'portfolio_pdf.php?slug=xyz')
 * - $pdfTotalImages: int total portfolio images
 * - $pdfCategoriesCount: array [category_name => count]
 */
$pdfModalBaseUrl = $pdfModalBaseUrl ?? 'download_pdf.php';
$pdfTotalImages = (int)($pdfTotalImages ?? 0);
$pdfCategoriesCount = $pdfCategoriesCount ?? [];
?>

<!-- STEP 1 MODAL: Select Scope (All vs Specific Category) -->
<div id="pdfScopeModal" class="pdf-modal-backdrop" aria-hidden="true">
  <div class="pdf-modal-card">
    <button type="button" class="pdf-modal-close" onclick="closeAllPdfModals()" title="Close">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <div class="pdf-step-indicator">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
      <span>Step 1 of 2 &bull; Export Scope</span>
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
        <span>Next: Choose Template</span>
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="9 18 15 12 9 6"/></svg>
      </button>
    </div>
  </div>
</div>

<!-- STEP 2 MODAL: Choose PDF Design Template -->
<div id="pdfTemplateModal" class="pdf-modal-backdrop" aria-hidden="true">
  <div class="pdf-modal-card" style="max-width: 660px;">
    <button type="button" class="pdf-modal-close" onclick="closeAllPdfModals()" title="Close">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <div class="pdf-step-indicator">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
      <span>Step 2 of 2 &bull; Design Template</span>
    </div>

    <h3 class="pdf-modal-title">Select PDF Presentation Style</h3>
    <p class="pdf-modal-desc">
      Choose the aesthetic that best complements your creative brand and presentation goals.
    </p>

    <!-- 3 Templates Grid -->
    <div class="pdf-templates-grid">
      <!-- 1. Obsidian Noir -->
      <div class="pdf-template-card selected" data-template="obsidian" onclick="selectPdfTemplate('obsidian')">
        <div class="pdf-tpl-preview obsidian">
          <div class="pdf-tpl-palette">
            <span class="pdf-tpl-dot" style="background:#8b5cf6;"></span>
            <span class="pdf-tpl-dot" style="background:#f43f5e;"></span>
            <span class="pdf-tpl-dot" style="background:#1e1a34;"></span>
          </div>
          <div class="pdf-tpl-lines">
            <span class="pdf-tpl-line" style="background:#8b5cf6;width:55%;"></span>
            <span class="pdf-tpl-line" style="background:rgba(255,255,255,0.7);width:85%;"></span>
            <span class="pdf-tpl-line" style="background:rgba(255,255,255,0.3);width:40%;"></span>
          </div>
        </div>
        <div class="pdf-tpl-name">Obsidian Noir</div>
        <div class="pdf-tpl-desc">Signature dark luxury. Deep obsidian glass with glowing violet &amp; coral accents. Perfect for 3D, UI &amp; tech portfolios.</div>
      </div>

      <!-- 2. Minimalist Atelier -->
      <div class="pdf-template-card" data-template="atelier" onclick="selectPdfTemplate('atelier')">
        <div class="pdf-tpl-preview atelier">
          <div class="pdf-tpl-palette">
            <span class="pdf-tpl-dot" style="background:#1c1917;"></span>
            <span class="pdf-tpl-dot" style="background:#b45309;"></span>
            <span class="pdf-tpl-dot" style="background:#e7e5e4;"></span>
          </div>
          <div class="pdf-tpl-lines">
            <span class="pdf-tpl-line" style="background:#b45309;width:50%;"></span>
            <span class="pdf-tpl-line" style="background:#1c1917;width:80%;"></span>
            <span class="pdf-tpl-line" style="background:#78716c;width:45%;"></span>
          </div>
        </div>
        <div class="pdf-tpl-name">Minimalist Atelier</div>
        <div class="pdf-tpl-desc">Clean editorial gallery. Warm ivory parchment with onyx typography and champagne gold accents. Timeless &amp; elegant.</div>
      </div>

      <!-- 3. Creative Studio -->
      <div class="pdf-template-card" data-template="creative" onclick="selectPdfTemplate('creative')">
        <div class="pdf-tpl-preview creative">
          <div class="pdf-tpl-palette">
            <span class="pdf-tpl-dot" style="background:#06b6d4;"></span>
            <span class="pdf-tpl-dot" style="background:#ec4899;"></span>
            <span class="pdf-tpl-dot" style="background:#1e293b;"></span>
          </div>
          <div class="pdf-tpl-lines">
            <span class="pdf-tpl-line" style="background:#06b6d4;width:60%;"></span>
            <span class="pdf-tpl-line" style="background:rgba(255,255,255,0.8);width:75%;"></span>
            <span class="pdf-tpl-line" style="background:rgba(255,255,255,0.35);width:45%;"></span>
          </div>
        </div>
        <div class="pdf-tpl-name">Creative Studio</div>
        <div class="pdf-tpl-desc">Modern vibrant duo. Midnight navy canvas energized with electric cyan and neon magenta highlights. Bold &amp; punchy.</div>
      </div>
    </div>

    <div class="pdf-modal-foot">
      <button type="button" class="btn btn-ghost" onclick="backToPdfStep1()" style="width:auto;padding:9px 18px;font-size:13.5px;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
        <span>Back to Scope</span>
      </button>
      <button type="button" class="btn btn-primary" onclick="startPdfGenerationDownload()" id="btnStartPdfGen" style="width:auto;padding:9px 24px;font-size:13.5px;">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
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

    <h3 style="font-family:var(--font-display);font-size:21px;font-weight:700;color:#ffffff;margin:20px 0 8px 0;">
      PDF Downloaded Successfully!
    </h3>

    <p style="font-size:13.5px;line-height:1.65;color:#cbd5e1;margin:0 0 16px 0;">
      Your portfolio PDF has been generated and downloaded to your device. <br><strong style="color:#ffffff;">Please check your Downloads folder.</strong>
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

<script>
(function() {
  const PDF_BASE_URL = <?php echo json_encode($pdfModalBaseUrl); ?>;
  let currentScope = 'all'; // 'all' or 'specific'
  let currentCategory = 'all';
  let currentTemplate = 'obsidian';

  window.openPdfExportModal = function() {
    const scopeModal = document.getElementById('pdfScopeModal');
    if (scopeModal) {
      scopeModal.classList.add('active');
      scopeModal.setAttribute('aria-hidden', 'false');
      document.body.style.overflow = 'hidden';
    }
  };

  window.closeAllPdfModals = function() {
    const modals = [
      document.getElementById('pdfScopeModal'),
      document.getElementById('pdfTemplateModal'),
      document.getElementById('pdfLoadingModal'),
      document.getElementById('pdfSuccessModal')
    ];
    modals.forEach(m => {
      if (m) {
        m.classList.remove('active');
        m.setAttribute('aria-hidden', 'true');
      }
    });
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

  let selectedCategories = [];

  // Initialize selected categories from initial checked elements
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
  };

  // -------------------------------------------------------------
  // AJAX PDF GENERATION & DOWNLOAD (NO PAGE RELOAD)
  // -------------------------------------------------------------
  let progressInterval = null;

  window.startPdfGenerationDownload = function() {
    const btn = document.getElementById('btnStartPdfGen');
    const step1 = document.getElementById('pdfScopeModal');
    const step2 = document.getElementById('pdfTemplateModal');
    const loadingModal = document.getElementById('pdfLoadingModal');
    const successModal = document.getElementById('pdfSuccessModal');
    const progressBar = document.getElementById('pdfProgressBar');
    const progressText = document.getElementById('pdfProgressText');

    // 1. Build Target URL
    let targetUrl = PDF_BASE_URL;
    const separator = targetUrl.includes('?') ? '&' : '?';
    const tplParam = encodeURIComponent(currentTemplate || 'obsidian');

    if (currentScope === 'specific' && selectedCategories.length > 0) {
      targetUrl += `${separator}categories=${encodeURIComponent(selectedCategories.join(','))}&template=${tplParam}`;
    } else {
      targetUrl += `${separator}category=all&template=${tplParam}`;
    }

    // 2. Hide Scope & Template Modals
    if (step1) step1.classList.remove('active');
    if (step2) step2.classList.remove('active');

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

  // Close on Escape
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

  ['pdfScopeModal', 'pdfTemplateModal'].forEach(id => {
    const el = document.getElementById(id);
    if (el) {
      el.addEventListener('click', function(e) {
        if (e.target === this) closeAllPdfModals();
      });
    }
  });
})();
</script>
