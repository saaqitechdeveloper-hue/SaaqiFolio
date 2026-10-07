<?php
/**
 * SaaqiFolio - Fullscreen Image Lightbox Modal
 * Max-width: 96vw, Max-height: 98dvh, object-fit: contain
 */
?>
<div id="imageLightboxModal" class="img-lightbox-backdrop" aria-hidden="true" onclick="closeImageLightbox(event)">
  <div class="img-lightbox-dialog" onclick="event.stopPropagation()">
    <!-- Close Button -->
    <button type="button" class="img-lightbox-close" onclick="closeImageLightbox()" aria-label="Close image popup" title="Close (Esc)">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
        <line x1="18" y1="6" x2="6" y2="18"></line>
        <line x1="6" y1="6" x2="18" y2="18"></line>
      </svg>
    </button>

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

    <!-- Image Viewport -->
    <div class="img-lightbox-viewport">
      <img id="imgLightboxTarget" src="" alt="Portfolio Image Preview" class="img-lightbox-img">
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

  // Collect all portfolio thumbs on the page
  function collectLightboxItems() {
    // Collect from public portfolio and dashboard portfolio
    const thumbs = Array.from(document.querySelectorAll('.card .thumb-wrap img.thumb, .cat-section .card img.thumb, .grid .card img.thumb'));
    lightboxItems = thumbs.map((img, idx) => {
      // Find category if available
      const card = img.closest('.card');
      const catSection = img.closest('.cat-section');
      let cat = '';
      if (card && card.dataset.category) {
        cat = card.dataset.category;
      } else if (catSection) {
        const catTitle = catSection.querySelector('.cat-section-title');
        if (catTitle) cat = catTitle.textContent.trim();
      }
      return {
        src: img.src,
        category: cat,
        el: img
      };
    });
  }

  // Open Lightbox with specified index or source
  window.openImageLightbox = function(targetSrc, targetCat = '') {
    collectLightboxItems();
    if (typeof targetSrc === 'number') {
      currentLightboxIdx = targetSrc;
    } else {
      currentLightboxIdx = lightboxItems.findIndex(i => i.src === targetSrc);
      if (currentLightboxIdx === -1 && targetSrc) {
        // Fallback for direct URL
        lightboxItems.push({ src: targetSrc, category: targetCat });
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
    if (e && e.target && e.target !== e.currentTarget && !e.target.closest('.img-lightbox-close')) {
      return;
    }
    const modal = document.getElementById('imageLightboxModal');
    if (modal) {
      modal.classList.remove('open');
      modal.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('lightbox-open');
    }
  };

  // Navigate Previous / Next
  window.navigateImageLightbox = function(step) {
    if (lightboxItems.length <= 1) return;
    currentLightboxIdx = (currentLightboxIdx + step + lightboxItems.length) % lightboxItems.length;
    renderLightboxImage();
  };

  function renderLightboxImage() {
    if (currentLightboxIdx < 0 || currentLightboxIdx >= lightboxItems.length) return;
    const item = lightboxItems[currentLightboxIdx];
    const imgEl = document.getElementById('imgLightboxTarget');
    const catEl = document.getElementById('imgLightboxCat');
    const counterEl = document.getElementById('imgLightboxCounter');
    const prevBtn = document.getElementById('imgLightboxPrev');
    const nextBtn = document.getElementById('imgLightboxNext');

    if (imgEl) {
      imgEl.src = item.src;
    }
    if (catEl) {
      catEl.textContent = item.category || 'Portfolio Work';
    }
    if (counterEl) {
      counterEl.textContent = `${currentLightboxIdx + 1} / ${lightboxItems.length}`;
    }

    // Toggle navigation buttons if multiple images
    const hasNav = lightboxItems.length > 1;
    if (prevBtn) prevBtn.style.display = hasNav ? 'flex' : 'none';
    if (nextBtn) nextBtn.style.display = hasNav ? 'flex' : 'none';
  }

  // Keyboard controls: Escape, Left Arrow, Right Arrow
  document.addEventListener('keydown', function(e) {
    const modal = document.getElementById('imageLightboxModal');
    if (!modal || !modal.classList.contains('open')) return;

    if (e.key === 'Escape') {
      closeImageLightbox();
    } else if (e.key === 'ArrowLeft') {
      navigateImageLightbox(-1);
    } else if (e.key === 'ArrowRight') {
      navigateImageLightbox(1);
    }
  });

  // Attach click listener to portfolio thumbs automatically
  document.addEventListener('click', function(e) {
    // Avoid triggering when clicking overlay delete, checkbox, or drag handle
    if (e.target.closest('.delete-img-form') || e.target.closest('.card-overlay-actions') || e.target.closest('.img-select-box') || e.target.closest('.card-drag-handle') || e.target.closest('.cat-dropdown-wrap') || e.target.closest('.card-foot')) {
      return;
    }

    const card = e.target.closest('.card');
    const thumbWrap = e.target.closest('.thumb-wrap');
    const thumbImg = e.target.closest('img.thumb');

    if (thumbImg || (card && thumbWrap)) {
      const img = thumbImg || (thumbWrap ? thumbWrap.querySelector('img.thumb') : null);
      if (img && img.src) {
        e.preventDefault();
        e.stopPropagation();
        openImageLightbox(img.src);
      }
    }
  });
})();
</script>
