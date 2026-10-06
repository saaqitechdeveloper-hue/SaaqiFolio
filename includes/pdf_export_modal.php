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
    const modals = [document.getElementById('pdfScopeModal'), document.getElementById('pdfTemplateModal')];
    modals.forEach(m => {
      if (m) {
        m.classList.remove('active');
        m.setAttribute('aria-hidden', 'true');
      }
    });
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

  window.startPdfGenerationDownload = function() {
    const btn = document.getElementById('btnStartPdfGen');
    if (btn) {
      btn.disabled = true;
      btn.innerHTML = `<span class="spinner-border spinner-border-sm" style="width:14px;height:14px;border:2px solid #fff;border-right-color:transparent;border-radius:50%;display:inline-block;animation:spin 0.6s linear infinite;margin-right:8px;"></span> Generating PDF...`;
    }

    let targetUrl = PDF_BASE_URL;
    const separator = targetUrl.includes('?') ? '&' : '?';
    const tplParam = encodeURIComponent(currentTemplate || 'obsidian');

    if (currentScope === 'specific' && selectedCategories.length > 0) {
      targetUrl += `${separator}categories=${encodeURIComponent(selectedCategories.join(','))}&template=${tplParam}`;
    } else {
      targetUrl += `${separator}category=all&template=${tplParam}`;
    }

    // Close modals after short delay and initiate download
    setTimeout(() => {
      window.location.href = targetUrl;
      setTimeout(() => {
        closeAllPdfModals();
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = `<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg><span>Generate &amp; Download PDF</span>`;
        }
      }, 1000);
    }, 400);
  };

  // Close on Escape or click outside
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeAllPdfModals();
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
