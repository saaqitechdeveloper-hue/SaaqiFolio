<?php
/**
 * SaaqiFolio - Interactive Gallery Lightbox Modal
 * Features:
 * - High-Res Image Preloader (smooth spinner while image loads in background)
 * - Zoom In (+), Zoom Out (-), 1:1 Reset
 * - Mouse Wheel Zoom & Double-Click to Zoom
 * - Click & Drag Pan when zoomed
 * - Fullscreen toggle
 * - Previous / Next navigation & keyboard shortcuts
 * - jQuery plugin compatibility bridge
 */
?>
<div id="imageLightboxModal" class="img-lightbox-backdrop" aria-hidden="true" onclick="closeImageLightbox(event)">
  <div class="img-lightbox-dialog" onclick="event.stopPropagation()">
    
    <!-- Top Floating Toolbar -->
    <div class="img-lightbox-toolbar" role="toolbar" aria-label="Image controls">
      <!-- Zoom Out (-) -->
      <button type="button" class="img-lightbox-tool-btn" id="imgZoomOutBtn" onclick="lightboxZoomOut()" title="Zoom Out (-)" aria-label="Zoom Out">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          <line x1="8" y1="11" x2="14" y2="11"></line>
        </svg>
      </button>

      <!-- Zoom Percentage Badge -->
      <span class="img-lightbox-zoom-badge" id="imgZoomBadge" title="Current zoom level">100%</span>

      <!-- Zoom In (+) -->
      <button type="button" class="img-lightbox-tool-btn" id="imgZoomInBtn" onclick="lightboxZoomIn()" title="Zoom In (+)" aria-label="Zoom In">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="11" cy="11" r="8"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
          <line x1="11" y1="8" x2="11" y2="14"></line>
          <line x1="8" y1="11" x2="14" y2="11"></line>
        </svg>
      </button>

      <!-- Reset / Actual Size -->
      <button type="button" class="img-lightbox-tool-btn" id="imgZoomResetBtn" onclick="lightboxZoomReset()" title="Reset / Fit (1:1)" aria-label="Reset Zoom">
        <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"></path>
          <path d="M3 3v5h5"></path>
        </svg>
      </button>

      <!-- Fullscreen Toggle -->
      <button type="button" class="img-lightbox-tool-btn" id="imgFullscreenBtn" onclick="lightboxToggleFullscreen()" title="Toggle Fullscreen" aria-label="Fullscreen">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
        </svg>
      </button>

      <!-- Close (X) -->
      <button type="button" class="img-lightbox-tool-btn close-btn" onclick="closeImageLightbox()" title="Close (Esc)" aria-label="Close">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
          <line x1="18" y1="6" x2="6" y2="18"></line>
          <line x1="6" y1="6" x2="18" y2="18"></line>
        </svg>
      </button>
    </div>

    <!-- Nav Arrows (Hidden if single image) -->
    <button type="button" class="img-lightbox-nav prev" id="imgLightboxPrev" onclick="navigateImageLightbox(-1)" aria-label="Previous image" title="Previous (Left arrow)">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="15 18 9 12 15 6"></polyline>
      </svg>
    </button>
    <button type="button" class="img-lightbox-nav next" id="imgLightboxNext" onclick="navigateImageLightbox(1)" aria-label="Next image" title="Next (Right arrow)">
      <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <polyline points="9 18 15 12 9 6"></polyline>
      </svg>
    </button>

    <!-- Image Viewport with Loader -->
    <div class="img-lightbox-viewport" id="imgLightboxViewport">
      <!-- High-Res Loader Spinner -->
      <div class="img-lightbox-loader" id="imgLightboxLoader">
        <div class="img-lightbox-spinner"></div>
        <span class="img-lightbox-loader-text">Loading High-Res Artwork...</span>
      </div>

      <!-- Target Image Preview -->
      <img id="imgLightboxTarget" src="" alt="Portfolio Image Preview" class="img-lightbox-img" draggable="false">
    </div>

    <!-- Bottom Caption -->
    <div class="img-lightbox-caption" id="imgLightboxCaption">
      <span class="img-lightbox-category" id="imgLightboxCat"></span>
      <span class="img-lightbox-counter" id="imgLightboxCounter"></span>
    </div>
  </div>
</div>

<script>
(function() {
  let lightboxItems = [];
  let currentLightboxIdx = -1;

  // Zoom & Pan State
  let currentZoom = 1.0;
  let panX = 0;
  let panY = 0;
  let isDragging = false;
  let startX = 0;
  let startY = 0;
  let origPanX = 0;
  let origPanY = 0;
  let activePreloadToken = 0;

  // Collect all portfolio thumbs on the page
  function collectLightboxItems() {
    const thumbs = Array.from(document.querySelectorAll('.card .thumb-wrap img.thumb, .cat-section .card img.thumb, .grid .card img.thumb, .featured-thumb img'));
    lightboxItems = thumbs.map((img) => {
      const card = img.closest('.card');
      const catSection = img.closest('.cat-section');
      let cat = '';
      if (card && card.dataset.category) {
        cat = card.dataset.category;
      } else if (catSection) {
        const catTitle = catSection.querySelector('.cat-section-title');
        if (catTitle) cat = catTitle.textContent.trim();
      }
      const full = img.dataset.fullSrc || img.getAttribute('data-full-src') || img.src;
      return {
        src: full,
        thumb: img.src,
        category: cat,
        el: img
      };
    });
  }

  // Update Zoom Indicator Badge
  function updateZoomBadge() {
    const badge = document.getElementById('imgZoomBadge');
    if (badge) {
      badge.textContent = Math.round(currentZoom * 100) + '%';
    }
  }

  // Apply GPU-accelerated Transform
  function applyTransform(smooth = true) {
    const img = document.getElementById('imgLightboxTarget');
    if (!img) return;

    if (!smooth || isDragging) {
      img.style.transition = 'none';
    } else {
      img.style.transition = 'transform 0.18s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.22s ease';
    }

    img.style.transform = `translate3d(${panX}px, ${panY}px, 0) scale(${currentZoom})`;
    img.classList.toggle('is-zoomed', currentZoom > 1.01);
    updateZoomBadge();
  }

  // Zoom In
  window.lightboxZoomIn = function() {
    currentZoom = Math.min(4.0, +(currentZoom + 0.35).toFixed(2));
    applyTransform(true);
  };

  // Zoom Out
  window.lightboxZoomOut = function() {
    currentZoom = Math.max(1.0, +(currentZoom - 0.35).toFixed(2));
    if (currentZoom <= 1.01) {
      currentZoom = 1.0;
      panX = 0;
      panY = 0;
    }
    applyTransform(true);
  };

  // Reset Zoom
  window.lightboxZoomReset = function() {
    currentZoom = 1.0;
    panX = 0;
    panY = 0;
    applyTransform(true);
  };

  // Toggle Fullscreen
  window.lightboxToggleFullscreen = function() {
    const modal = document.getElementById('imageLightboxModal');
    if (!document.fullscreenElement) {
      if (modal && modal.requestFullscreen) modal.requestFullscreen();
    } else {
      if (document.exitFullscreen) document.exitFullscreen();
    }
  };

  // Open Lightbox
  window.openImageLightbox = function(targetSrc, targetCat = '') {
    collectLightboxItems();
    if (typeof targetSrc === 'number') {
      currentLightboxIdx = targetSrc;
    } else {
      currentLightboxIdx = lightboxItems.findIndex(i => i.src === targetSrc || i.thumb === targetSrc);
      if (currentLightboxIdx === -1 && targetSrc) {
        lightboxItems.push({ src: targetSrc, thumb: targetSrc, category: targetCat });
        currentLightboxIdx = lightboxItems.length - 1;
      }
    }

    renderLightboxImage();

    const modal = document.getElementById('imageLightboxModal');
    if (modal) {
      modal.classList.add('open');
      modal.setAttribute('aria-hidden', 'false');
      document.body.classList.add('lightbox-open');
    }
  };

  // Close Lightbox
  window.closeImageLightbox = function(e) {
    if (e && e.target && e.target !== e.currentTarget && !e.target.closest('.close-btn')) {
      return;
    }
    lightboxZoomReset();
    const modal = document.getElementById('imageLightboxModal');
    if (modal) {
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('lightbox-open');
      if (document.fullscreenElement && document.exitFullscreen) {
        document.exitFullscreen();
      }
    }
  };

  // Navigate Previous / Next
  window.navigateImageLightbox = function(step) {
    if (lightboxItems.length <= 1) return;
    currentLightboxIdx = (currentLightboxIdx + step + lightboxItems.length) % lightboxItems.length;
    renderLightboxImage();
  };

  // Render Image with Background Preload & Spinner
  function renderLightboxImage() {
    if (currentLightboxIdx < 0 || currentLightboxIdx >= lightboxItems.length) return;
    const item = lightboxItems[currentLightboxIdx];
    const imgEl = document.getElementById('imgLightboxTarget');
    const loaderEl = document.getElementById('imgLightboxLoader');
    const catEl = document.getElementById('imgLightboxCat');
    const counterEl = document.getElementById('imgLightboxCounter');
    const prevBtn = document.getElementById('imgLightboxPrev');
    const nextBtn = document.getElementById('imgLightboxNext');

    // Reset zoom state on every image change
    currentZoom = 1.0;
    panX = 0;
    panY = 0;
    applyTransform(false);

    // Show Loader & hide current image
    if (loaderEl) loaderEl.style.display = 'flex';
    if (imgEl) imgEl.classList.remove('loaded');

    if (catEl) {
      catEl.textContent = item.category || 'Portfolio Work';
    }
    if (counterEl) {
      counterEl.textContent = `${currentLightboxIdx + 1} / ${lightboxItems.length}`;
    }

    const hasNav = lightboxItems.length > 1;
    if (prevBtn) prevBtn.style.display = hasNav ? 'flex' : 'none';
    if (nextBtn) nextBtn.style.display = hasNav ? 'flex' : 'none';

    // Background High-Res Image Preload
    const thisToken = ++activePreloadToken;
    const preloader = new Image();

    preloader.onload = function() {
      if (thisToken !== activePreloadToken) return; // Stale request guard
      if (imgEl) {
        imgEl.src = preloader.src;
        imgEl.classList.add('loaded');
      }
      if (loaderEl) loaderEl.style.display = 'none';
    };

    preloader.onerror = function() {
      if (thisToken !== activePreloadToken) return;
      if (imgEl) {
        imgEl.src = item.thumb || item.src;
        imgEl.classList.add('loaded');
      }
      if (loaderEl) loaderEl.style.display = 'none';
    };

    preloader.src = item.src;
  }

  // Pointer Drag & Pan Setup
  function initPanEvents() {
    const viewport = document.getElementById('imgLightboxViewport');
    const imgEl = document.getElementById('imgLightboxTarget');
    if (!viewport || !imgEl) return;

    viewport.addEventListener('pointerdown', function(e) {
      if (currentZoom <= 1.01 || e.button !== 0) return;
      if (e.target.closest('.img-lightbox-toolbar') || e.target.closest('.img-lightbox-nav')) return;
      isDragging = true;
      startX = e.clientX;
      startY = e.clientY;
      origPanX = panX;
      origPanY = panY;
      imgEl.classList.add('is-dragging');
      try { viewport.setPointerCapture(e.pointerId); } catch(err){}
    });

    viewport.addEventListener('pointermove', function(e) {
      if (!isDragging) return;
      panX = origPanX + (e.clientX - startX);
      panY = origPanY + (e.clientY - startY);
      applyTransform(false);
    });

    function endDrag(e) {
      if (!isDragging) return;
      isDragging = false;
      imgEl.classList.remove('is-dragging');
      try { viewport.releasePointerCapture(e.pointerId); } catch(err){}
    }

    viewport.addEventListener('pointerup', endDrag);
    viewport.addEventListener('pointercancel', endDrag);

    // Mouse Wheel Zoom
    viewport.addEventListener('wheel', function(e) {
      e.preventDefault();
      if (e.deltaY < 0) {
        lightboxZoomIn();
      } else {
        lightboxZoomOut();
      }
    }, { passive: false });

    // Double-Click Toggle Zoom
    viewport.addEventListener('dblclick', function(e) {
      if (e.target.closest('.img-lightbox-toolbar') || e.target.closest('.img-lightbox-nav')) return;
      if (currentZoom > 1.01) {
        lightboxZoomReset();
      } else {
        currentZoom = 2.2;
        applyTransform(true);
      }
    });
  }

  // Keyboard Shortcuts
  document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('imageLightboxModal');
    if (!modal || !modal.classList.contains('open')) return;

    if (e.key === 'Escape') {
      closeImageLightbox();
    } else if (e.key === 'ArrowLeft') {
      navigateImageLightbox(-1);
    } else if (e.key === 'ArrowRight') {
      navigateImageLightbox(1);
    } else if (e.key === '+' || e.key === '=') {
      lightboxZoomIn();
    } else if (e.key === '-' || e.key === '_') {
      lightboxZoomOut();
    } else if (e.key === '0') {
      lightboxZoomReset();
    }
  });

  // Global Delegated Thumbnail Click Trigger
  document.addEventListener('click', function(e) {
    if (e.target.closest('.delete-img-form') || e.target.closest('.card-overlay-actions') || e.target.closest('.img-select-box') || e.target.closest('.card-drag-handle') || e.target.closest('.cat-dropdown-wrap') || e.target.closest('.card-foot')) {
      return;
    }

    const card = e.target.closest('.card');
    const thumbWrap = e.target.closest('.thumb-wrap');
    const thumbImg = e.target.closest('img.thumb');

    if (thumbImg || (card && thumbWrap)) {
      const img = thumbImg || (thumbWrap ? thumbWrap.querySelector('img.thumb') : null);
      if (img && (img.dataset.fullSrc || img.src)) {
        e.preventDefault();
        e.stopPropagation();
        openImageLightbox(img.dataset.fullSrc || img.src);
      }
    }
  });

  // jQuery Gallery Compatibility Bridge
  if (typeof window.jQuery !== 'undefined') {
    window.jQuery.fn.imageLightbox = function() {
      return this.each(function() {
        window.jQuery(this).on('click', function(e) {
          e.preventDefault();
          const target = window.jQuery(this).data('full-src') || window.jQuery(this).attr('src');
          openImageLightbox(target);
        });
      });
    };
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPanEvents);
  } else {
    initPanEvents();
  }
})();
</script>
