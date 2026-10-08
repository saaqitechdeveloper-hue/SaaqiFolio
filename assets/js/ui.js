/**
 * SAAQIFOLIO — UI Engine v3.0
 *
 * 1) Custom dropdowns (div/span/ul/li) — .form-select & .cat-dropdown-wrap
 * 2) wireDropzone() — drag-and-drop + click-to-browse + file preview
 * 3) ajaxUploadFiles() — AJAX upload with animated loader + countdown timer
 * 4) Bulk selection helpers
 * 5) Toast notifications
 * 6) Smooth scroll & misc polish
 */
// Prevent browser "Confirm Form Resubmission" alert on reload / back / forward navigation
if (window.history && window.history.replaceState) {
  window.history.replaceState(null, null, window.location.href);
}

/* ──────────────────────────────────────────────
   CUSTOM DROPDOWN DELEGATION
────────────────────────────────────────────── */
document.addEventListener('click', function (e) {

  /* Generic .form-select */
  const fsTrigger = e.target.closest('.form-select-trigger');
  if (fsTrigger) {
    const wrap = fsTrigger.closest('.form-select');
    const willOpen = !wrap.classList.contains('open');
    document.querySelectorAll('.form-select.open').forEach(w => w !== wrap && w.classList.remove('open'));
    wrap.classList.toggle('open', willOpen);
    const list = wrap.querySelector('.form-select-list');
    if (list) list.hidden = !willOpen;
    return;
  }

  const fsItem = e.target.closest('.form-select-item');
  if (fsItem) {
    const wrap = fsItem.closest('.form-select');
    const hidden = wrap.querySelector('input[type="hidden"]');
    const trigger = wrap.querySelector('.form-select-trigger');
    if (hidden) hidden.value = fsItem.dataset.value;
    if (trigger) {
      trigger.innerHTML = `<span>${fsItem.textContent.trim()}</span><span class="fs-caret"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 6 8 10 12 6"/></svg></span>`;
    }
    wrap.querySelectorAll('.form-select-item').forEach(li => li.classList.remove('active'));
    fsItem.classList.add('active');
    wrap.classList.remove('open');
    const list = wrap.querySelector('.form-select-list');
    if (list) list.hidden = true;
    if (wrap.dataset.autosubmit === '1' && hidden && hidden.form) {
      hidden.form.submit();
    }
    return;
  }

  /* Per-image category dropdown */
  const catTrigger = e.target.closest('.cat-trigger');
  if (catTrigger) {
    const wrap = catTrigger.closest('.cat-dropdown-wrap');
    const menu = wrap.querySelector('.cat-menu');
    const card = catTrigger.closest('.card');
    const willOpen = menu.hidden;

    document.querySelectorAll('.cat-menu').forEach(m => { if (m !== menu) m.hidden = true; });
    document.querySelectorAll('.card.cat-menu-open').forEach(c => { if (c !== card) c.classList.remove('cat-menu-open'); });

    menu.hidden = !willOpen;
    if (card) {
      card.classList.toggle('cat-menu-open', willOpen);
    }
    return;
  }

  const catItem = e.target.closest('.cat-menu-item');
  if (catItem) {
    const wrap = catItem.closest('.cat-dropdown-wrap');
    const input = wrap.querySelector('input[name="category"]');
    const form = wrap.querySelector('form.cat-inline-form');
    if (input) input.value = catItem.dataset.value;
    if (form) form.submit();
    return;
  }

  /* Close on outside click */
  document.querySelectorAll('.form-select.open').forEach(w => {
    w.classList.remove('open');
    const list = w.querySelector('.form-select-list');
    if (list) list.hidden = true;
  });
  document.querySelectorAll('.cat-menu').forEach(m => { m.hidden = true; });
  document.querySelectorAll('.card.cat-menu-open').forEach(c => { c.classList.remove('cat-menu-open'); });
});

document.addEventListener('keydown', function (e) {
  if (e.key !== 'Escape') return;
  document.querySelectorAll('.form-select.open').forEach(w => {
    w.classList.remove('open');
    const list = w.querySelector('.form-select-list');
    if (list) list.hidden = true;
  });
  document.querySelectorAll('.cat-menu').forEach(m => { m.hidden = true; });
  document.querySelectorAll('.card.cat-menu-open').forEach(c => { c.classList.remove('cat-menu-open'); });
});

/* ──────────────────────────────────────────────
   DROPZONE
────────────────────────────────────────────── */
/**
 * Wires drag-and-drop + click-to-browse to a file input.
 * @param {Element} zoneEl  - The .dropzone or .mini-dropzone element
 * @param {HTMLInputElement} inputEl - Hidden file input
 * @param {Function} onFiles - Callback(FileList)
 */
function wireDropzone(zoneEl, inputEl, onFiles) {
  if (!zoneEl || !inputEl) return;

  zoneEl.addEventListener('click', function (e) {
    if (e.target.closest('button, a, input, select, textarea, label, [data-browse-btn], [data-no-dropzone-click], .btn-behance-featured, .behance-modal-backdrop')) {
      return;
    }
    inputEl.click();
  });

  const browseBtn = zoneEl.querySelector('[data-browse-btn]');
  if (browseBtn) {
    browseBtn.addEventListener('click', function (e) {
      e.preventDefault();
      e.stopPropagation();
      inputEl.click();
    });
  }

  let dragCounter = 0;

  zoneEl.addEventListener('dragenter', function (e) {
    e.preventDefault();
    e.stopPropagation();
    dragCounter++;
    zoneEl.classList.add('drag-active');
  });

  zoneEl.addEventListener('dragover', function (e) {
    e.preventDefault();
    e.stopPropagation();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'copy';
    zoneEl.classList.add('drag-active');
  });

  zoneEl.addEventListener('dragleave', function (e) {
    e.preventDefault();
    e.stopPropagation();
    dragCounter--;
    if (dragCounter <= 0) {
      dragCounter = 0;
      zoneEl.classList.remove('drag-active');
    }
  });

  zoneEl.addEventListener('drop', function (e) {
    e.preventDefault();
    e.stopPropagation();
    dragCounter = 0;
    zoneEl.classList.remove('drag-active');
    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
      onFiles(e.dataTransfer.files);
    }
  });

  inputEl.addEventListener('change', function () {
    if (inputEl.files && inputEl.files.length) {
      const filesCopy = Array.from(inputEl.files);
      onFiles(filesCopy);
      inputEl.value = '';
    }
  });
}

/* ──────────────────────────────────────────────
   AJAX UPLOAD WITH CONCURRENT QUEUE & LOADER
────────────────────────────────────────────── */
/**
 * Uploads files via AJAX using a resilient concurrent worker queue (max 3 concurrent uploads),
 * preventing browser tab freezes and PHP payload limits when uploading 20-100+ images.
 * @param {HTMLFormElement} form
 * @param {HTMLInputElement} fileInput
 * @param {FileList} files
 * @param {{ overlay, timerEl, titleEl, subEl }} loaderEls
 */
async function ajaxUploadFiles(form, fileInput, files, loaderEls) {
  if (!files || !files.length) return;

  const fileQueue = Array.from(files);
  const totalFiles = fileQueue.length;
  const CONCURRENCY = Math.min(3, totalFiles);

  const { overlay, timerEl, titleEl, subEl } = loaderEls || {};

  let seconds = 0;
  if (overlay) overlay.hidden = false;
  if (timerEl) timerEl.textContent = '0s';
  if (titleEl) titleEl.textContent = `Uploading 1 of ${totalFiles} pieces (0%)...`;

  const tick = setInterval(function () {
    seconds++;
    if (timerEl) timerEl.textContent = seconds + 's';
  }, 1000);

  let targetUrl = (form && form.getAttribute('action')) ? form.getAttribute('action') : '';
  if (!targetUrl || targetUrl === '#') {
    targetUrl = window.location.pathname + window.location.search;
  }

  let queueIdx = 0;
  let successCount = 0;
  let failCount = 0;
  let lastError = '';

  function updateProgress() {
    const done = successCount + failCount;
    const pct = Math.round((done / totalFiles) * 100);
    const current = Math.min(done + 1, totalFiles);
    if (titleEl) {
      titleEl.textContent = `Uploading ${current} of ${totalFiles} pieces (${pct}%)...`;
    }
  }

  async function worker() {
    while (queueIdx < fileQueue.length) {
      const idx = queueIdx++;
      const file = fileQueue[idx];

      updateProgress();

      const fd = new FormData();
      fd.append('single_image', file);
      fd.append('upload_images', '1');
      fd.append('ajax', '1');

      try {
        const resp = await fetch(targetUrl, { method: 'POST', body: fd });
        const text = await resp.text();
        let data = null;
        try {
          data = JSON.parse(text);
        } catch (e) {
          console.error('Server response error on file', file.name, text);
        }

        if (data && data.success) {
          successCount += (data.uploaded || 1);
        } else {
          failCount++;
          if (data && data.error) lastError = data.error;
        }
      } catch (err) {
        console.error('Network upload error on file', file.name, err);
        failCount++;
      }

      updateProgress();
    }
  }

  const workers = [];
  for (let i = 0; i < CONCURRENCY; i++) {
    workers.push(worker());
  }

  await Promise.all(workers);
  clearInterval(tick);

  if (overlay) overlay.hidden = true;

  if (successCount > 0) {
    if (typeof showToast === 'function') {
      showToast(`${successCount} artwork piece(s) uploaded and converted to AVIF!`, 'success');
    }
    if (typeof window.refreshPortfolioGallery === 'function') {
      window.refreshPortfolioGallery();
    } else {
      setTimeout(() => {
        window.location.href = 'portfolio?category=All';
      }, 350);
    }
    window.dispatchEvent(new CustomEvent('saaqi:sync', { detail: { action: 'files_uploaded', count: successCount } }));
  } else {
    showToast(lastError || 'Upload failed. Please check file formats and size.', 'danger');
  }
}

/**
 * Enables full-window drag-and-drop for uploads.
 */
function initGlobalPageDrop(overlayEl, formEl, inputEl, loaderEls) {
  if (!overlayEl || !formEl || !inputEl) return;

  let windowDragCounter = 0;

  // Prevent default browser file open on window
  ['dragenter', 'dragover'].forEach(evt => {
    window.addEventListener(evt, function (e) {
      e.preventDefault();
      if (e.dataTransfer && Array.from(e.dataTransfer.types || []).includes('Files')) {
        e.dataTransfer.dropEffect = 'copy';
      }
    });
  });

  window.addEventListener('drop', function (e) {
    e.preventDefault();
  });

  window.addEventListener('dragenter', function (e) {
    if (e.dataTransfer && Array.from(e.dataTransfer.types || []).includes('Files')) {
      windowDragCounter++;
      overlayEl.hidden = false;
    }
  });

  window.addEventListener('dragleave', function (e) {
    windowDragCounter--;
    if (windowDragCounter <= 0) {
      windowDragCounter = 0;
      overlayEl.hidden = true;
    }
  });

  window.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      windowDragCounter = 0;
      overlayEl.hidden = true;
    }
  });

  overlayEl.addEventListener('dragover', function (e) {
    e.preventDefault();
    if (e.dataTransfer) e.dataTransfer.dropEffect = 'copy';
  });

  overlayEl.addEventListener('drop', function (e) {
    e.preventDefault();
    e.stopPropagation();
    windowDragCounter = 0;
    overlayEl.hidden = true;

    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
      ajaxUploadFiles(formEl, inputEl, e.dataTransfer.files, loaderEls);
    }
  });
}

/* ──────────────────────────────────────────────
   BULK SELECTION
────────────────────────────────────────────── */
function initBulkSelect() {
  const checkboxes = document.querySelectorAll('.img-cb');
  const bulkBar = document.getElementById('bulkBar');
  const bulkCountEl = document.getElementById('bulkCount');
  const selectAllBtn = document.getElementById('selectAllBtn');
  const deselectAllBtn = document.getElementById('deselectAllBtn');

  function updateBulk() {
    const checked = document.querySelectorAll('.img-cb:checked');
    const count = checked.length;
    if (bulkBar) bulkBar.hidden = count === 0;
    if (bulkCountEl) bulkCountEl.innerHTML = `<span>${count}</span> image${count !== 1 ? 's' : ''} selected`;
    document.querySelectorAll('.card').forEach(card => {
      const cb = card.querySelector('.img-cb');
      if (cb) card.classList.toggle('selected', cb.checked);
    });
  }

  checkboxes.forEach(cb => cb.addEventListener('change', updateBulk));

  if (selectAllBtn) {
    selectAllBtn.addEventListener('click', () => {
      checkboxes.forEach(cb => { cb.checked = true; });
      updateBulk();
    });
  }

  if (deselectAllBtn) {
    deselectAllBtn.addEventListener('click', () => {
      checkboxes.forEach(cb => { cb.checked = false; });
      updateBulk();
    });
  }
}

/* ──────────────────────────────────────────────
   TOAST NOTIFICATIONS
────────────────────────────────────────────── */
function showToast(message, type = 'success', duration = 4000) {
  let stack = document.querySelector('.toast-stack');
  if (!stack) {
    stack = document.createElement('div');
    stack.className = 'toast-stack';
    document.body.appendChild(stack);
  }

  const iconSVG = type === 'success'
    ? `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16" style="color:var(--success)"><path d="M20 6L9 17l-5-5"/></svg>`
    : `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="16" height="16" style="color:var(--danger)"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`;

  const toast = document.createElement('div');
  toast.className = `toast-notif ${type}`;
  toast.innerHTML = `${iconSVG}<span>${message}</span>`;
  stack.appendChild(toast);

  setTimeout(() => {
    toast.style.animation = 'toastSlide .2s ease reverse';
    setTimeout(() => toast.remove(), 200);
  }, duration);
}

/* ──────────────────────────────────────────────
   COPY TO CLIPBOARD
────────────────────────────────────────────── */
function copyToClipboard(text, feedbackEl) {
  navigator.clipboard.writeText(text).then(() => {
    if (feedbackEl) {
      feedbackEl.hidden = false;
      setTimeout(() => { feedbackEl.hidden = true; }, 2000);
    }
    showToast('Link copied to clipboard!', 'success', 2500);
  }).catch(() => {
    showToast('Could not copy. Please copy manually.', 'danger');
  });
}

/* ──────────────────────────────────────────────
   SMOOTH SCROLL + ACTIVE NAV
────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', function () {
  /* Smooth scroll for anchor links */
  document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', function (e) {
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  /* Auto-init bulk select if present */
  if (document.querySelector('.img-cb')) initBulkSelect();

  /* Auto-init password visibility toggles */
  initPasswordToggles();

  /* Auto-dismiss flash messages */
  document.querySelectorAll('[data-auto-dismiss]').forEach(el => {
    const delay = parseInt(el.dataset.autoDismiss) || 4000;
    setTimeout(() => {
      el.style.transition = 'opacity .3s';
      el.style.opacity = '0';
      setTimeout(() => el.remove(), 300);
    }, delay);
  });
});

/* ──────────────────────────────────────────────
   PASSWORD VISIBILITY TOGGLE
────────────────────────────────────────────── */
function initPasswordToggles() {
  document.querySelectorAll('input[type="password"]').forEach(input => {
    let wrap = input.closest('.password-wrap');
    if (!wrap) {
      wrap = document.createElement('div');
      wrap.className = 'password-wrap';
      input.parentNode.insertBefore(wrap, input);
      wrap.appendChild(input);
    }

    if (!wrap.querySelector('.btn-toggle-pw')) {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn-toggle-pw';
      btn.setAttribute('aria-label', 'Toggle password visibility');
      btn.setAttribute('tabindex', '-1');
      btn.innerHTML = `
        <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
        <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" style="display:none;"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
      `;
      wrap.appendChild(btn);
    }
  });
}

document.addEventListener('click', function (e) {
  const btn = e.target.closest('.btn-toggle-pw');
  if (!btn) return;
  const wrap = btn.closest('.password-wrap');
  if (!wrap) return;
  const input = wrap.querySelector('input');
  if (!input) return;

  const isPass = input.type === 'password';
  input.type = isPass ? 'text' : 'password';

  const showIcon = btn.querySelector('.eye-show');
  const hideIcon = btn.querySelector('.eye-hide');
  if (showIcon && hideIcon) {
    showIcon.style.display = isPass ? 'none' : 'block';
    hideIcon.style.display = isPass ? 'block' : 'none';
  }
});

/* ──────────────────────────────────────────────
   CONFIRM DIALOG (replaces native confirm())
────────────────────────────────────────────── */
function confirmAction(message, onConfirm, options = {}) {
  const { confirmLabel = 'Confirm', dangerLabel = true } = options;
  const overlay = document.createElement('div');
  overlay.className = 'modal-overlay';
  overlay.innerHTML = `
    <div class="modal" role="dialog" aria-modal="true">
      <div class="modal-title">${message}</div>
      <p style="color:var(--text-muted);font-size:14px;margin:8px 0 0;">This action cannot be undone.</p>
      <div class="modal-actions">
        <button class="btn btn-ghost" id="confirmCancel" style="width:auto;flex:1;">Cancel</button>
        <button class="btn ${dangerLabel ? 'btn-danger-ghost' : 'btn-primary'}" id="confirmOk" style="width:auto;flex:1;">${confirmLabel}</button>
      </div>
    </div>
  `;
  document.body.appendChild(overlay);
  overlay.querySelector('#confirmCancel').addEventListener('click', () => overlay.remove());
  overlay.querySelector('#confirmOk').addEventListener('click', () => { overlay.remove(); onConfirm(); });
  overlay.addEventListener('click', e => { if (e.target === overlay) overlay.remove(); });
}

/* ──────────────────────────────────────────────
   CUSTOM ALERT DIALOG (replaces native alert())
────────────────────────────────────────────── */
window._nativeAlert = window.alert;

let _customAlertQueue = [];
let _isCustomAlertOpen = false;

window.customAlert = function(message, options = {}) {
  return new Promise((resolve) => {
    if (typeof options === 'string') {
      options = { title: options };
    }
    const item = {
      message: String(message !== undefined && message !== null ? message : ''),
      title: options.title || '',
      type: options.type || '',
      btnText: options.btnText || 'Got it',
      resolve: resolve
    };

    _customAlertQueue.push(item);
    if (!_isCustomAlertOpen) {
      _processNextCustomAlert();
    }
  });
};

// Global override for native browser alert across all scripts
window.alert = function(message, options) {
  return window.customAlert(message, options);
};

function _processNextCustomAlert() {
  if (_customAlertQueue.length === 0) {
    _isCustomAlertOpen = false;
    return;
  }

  _isCustomAlertOpen = true;
  const current = _customAlertQueue.shift();

  // Detect type and title if not specified
  let type = current.type;
  let title = current.title;
  const lowerMsg = current.message.toLowerCase();

  if (!type) {
    if (lowerMsg.includes('error') || lowerMsg.includes('failed') || lowerMsg.includes('wrong') || lowerMsg.includes('cannot')) {
      type = 'danger';
    } else if (lowerMsg.includes('success') || lowerMsg.includes('copied') || lowerMsg.includes('complete') || lowerMsg.includes('saved')) {
      type = 'success';
    } else if (lowerMsg.includes('please') || lowerMsg.includes('select') || lowerMsg.includes('upload') || lowerMsg.includes('warning') || lowerMsg.includes('limit') || lowerMsg.includes('upgrade')) {
      type = 'warning';
    } else {
      type = 'info';
    }
  }

  if (!title) {
    if (type === 'danger') title = 'Error';
    else if (type === 'success') title = 'Success';
    else if (type === 'warning') title = 'Attention';
    else title = 'Notice';
  }

  // Choose icon SVG based on alert type
  let iconSvg = '';
  if (type === 'warning') {
    iconSvg = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>`;
  } else if (type === 'danger') {
    iconSvg = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>`;
  } else if (type === 'success') {
    iconSvg = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>`;
  } else {
    iconSvg = `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>`;
  }

  // Format message lines safely
  const paragraphs = current.message.split('\n').filter(Boolean).map(line => {
    const esc = line.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    return `<p>${esc}</p>`;
  }).join('');

  // Remove any stale alert overlay
  const existing = document.getElementById('customAlertOverlay');
  if (existing) existing.remove();

  const overlay = document.createElement('div');
  overlay.className = 'custom-alert-overlay';
  overlay.id = 'customAlertOverlay';
  overlay.setAttribute('role', 'dialog');
  overlay.setAttribute('aria-modal', 'true');
  overlay.setAttribute('tabindex', '-1');

  overlay.innerHTML = `
    <div class="custom-alert-card">
      <button type="button" class="custom-alert-close" aria-label="Close dialog" title="Close">&times;</button>
      <div class="custom-alert-header">
        <div class="custom-alert-icon-wrap ${type}">
          ${iconSvg}
        </div>
        <div class="custom-alert-title">${title}</div>
      </div>
      <div class="custom-alert-body">
        ${paragraphs || '<p>' + current.message + '</p>'}
      </div>
      <div class="custom-alert-footer">
        <button type="button" class="btn btn-primary custom-alert-btn" id="customAlertOkBtn">${current.btnText}</button>
      </div>
    </div>
  `;

  document.body.appendChild(overlay);

  // Trigger smooth entrance animation
  requestAnimationFrame(() => {
    overlay.classList.add('active');
  });

  const prevOverflow = document.body.style.overflow;
  document.body.style.overflow = 'hidden';

  const okBtn = overlay.querySelector('#customAlertOkBtn');
  const closeBtn = overlay.querySelector('.custom-alert-close');

  if (okBtn) {
    setTimeout(() => okBtn.focus(), 60);
  }

  let isClosing = false;
  const dismiss = () => {
    if (isClosing) return;
    isClosing = true;

    overlay.classList.remove('active');
    document.removeEventListener('keydown', keyHandler);

    setTimeout(() => {
      document.body.style.overflow = prevOverflow;
      overlay.remove();
      if (typeof current.resolve === 'function') {
        current.resolve();
      }
      _processNextCustomAlert();
    }, 220);
  };

  const keyHandler = (e) => {
    if (e.key === 'Escape' || e.key === 'Enter') {
      e.preventDefault();
      dismiss();
    }
  };

  document.addEventListener('keydown', keyHandler);
  if (okBtn) okBtn.addEventListener('click', dismiss);
  if (closeBtn) closeBtn.addEventListener('click', dismiss);
  overlay.addEventListener('click', (e) => {
    if (e.target === overlay) dismiss();
  });
}

/* ──────────────────────────────────────────────
   THEME SWITCHER ENGINE (LIGHT / DARK)
────────────────────────────────────────────── */
window.getCurrentTheme = function() {
  return document.documentElement.getAttribute('data-theme') || 
         localStorage.getItem('folivo_theme') || 
         'dark';
};

window.setTheme = function(theme, animate = true) {
  const root = document.documentElement;
  const normalizedTheme = theme === 'light' ? 'light' : 'dark';

  const applyThemeUpdate = () => {
    root.setAttribute('data-theme', normalizedTheme);
    try {
      localStorage.setItem('folivo_theme', normalizedTheme);
    } catch (err) {}

    // Synchronize all theme toggle buttons on the page
    document.querySelectorAll('.theme-toggle-btn').forEach(btn => {
      btn.setAttribute('data-current-theme', normalizedTheme);
      btn.setAttribute('aria-checked', normalizedTheme === 'light' ? 'true' : 'false');
      const label = btn.querySelector('.theme-toggle-label');
      if (label) {
        label.textContent = normalizedTheme === 'light' ? 'Light' : 'Dark';
      }
      btn.setAttribute('title', normalizedTheme === 'light' ? 'Switch to Dark Mode' : 'Switch to Light Mode');
    });
  };

  // If View Transitions API is supported, use zero-reflow GPU blending
  if (animate && document.startViewTransition && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.startViewTransition(() => {
      applyThemeUpdate();
    });
  } else {
    if (animate) {
      root.classList.add('theme-transitioning');
    }
    applyThemeUpdate();
    if (animate) {
      setTimeout(() => {
        root.classList.remove('theme-transitioning');
      }, 220);
    }
  }
};

window.toggleTheme = function() {
  const current = window.getCurrentTheme();
  const next = current === 'light' ? 'dark' : 'light';
  window.setTheme(next, true);
};

// Auto-sync theme buttons on initial load
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function() {
    window.setTheme(window.getCurrentTheme(), false);
  });
} else {
  window.setTheme(window.getCurrentTheme(), false);
}

/* ──────────────────────────────────────────────
   SAAQIFOLIO REACTIVE STATE & ZERO-RELOAD ENGINE
   Real-time multi-tab and UI state synchronization without browser lag or CPU polling
────────────────────────────────────────────── */
window.SaaqiState = window.SaaqiState || {
  is_subscribed: false,
  total_images: 0,
  remaining_pdf: 3,
  can_export_pdf: true,
  at_image_limit: false
};

// 1. Universal Export Button Synchronizer
window.syncDashboardPdfButtons = function(remaining, isSubscribed) {
  const isSub = (isSubscribed === true || remaining === 'unlimited');
  const rem = isSub ? 'unlimited' : (typeof remaining === 'number' ? remaining : parseInt(remaining, 10));
  const canExport = isSub || (rem > 0);

  window.SaaqiState.is_subscribed = isSub;
  window.SaaqiState.remaining_pdf = rem;
  window.SaaqiState.can_export_pdf = canExport;

  // Find all PDF export buttons across the dashboard
  const exportButtons = document.querySelectorAll('button[onclick*="openPdfExportModal"], a[href*="upgrade"][class*="btn-ghost"], .btn-pdf, [data-pdf-export-btn]');
  
  exportButtons.forEach(el => {
    if (canExport) {
      const textSpan = el.querySelector('span');
      const suffix = isSub ? '' : ` (${rem} left)`;
      if (textSpan) {
        textSpan.textContent = `Export Your Portfolio${suffix}`;
      }
      if (el.tagName.toLowerCase() === 'a') {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = el.className.replace('text-accent', '').trim();
        btn.setAttribute('onclick', 'openPdfExportModal()');
        btn.style.width = el.style.width || 'auto';
        btn.innerHTML = `
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
          <span>Export Your Portfolio${suffix}</span>
        `;
        el.replaceWith(btn);
      }
    } else {
      if (el.tagName.toLowerCase() === 'button') {
        const link = document.createElement('a');
        link.href = 'upgrade?pdf_limit=1';
        link.className = (el.className + ' text-accent').trim();
        link.style.width = el.style.width || 'auto';
        link.style.textDecoration = 'none';
        link.innerHTML = `
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
          <span>Export Your Portfolio (Upgrade)</span>
        `;
        el.replaceWith(link);
      } else {
        const textSpan = el.querySelector('span');
        if (textSpan) textSpan.textContent = 'Export Your Portfolio (Upgrade)';
      }
    }
  });
};

// 2. Reactive Gallery Refresh (Transition from dropzone to active cards or refresh cards)
window.refreshPortfolioGallery = async function() {
  const currentPath = window.location.pathname;
  if (!currentPath.includes('portfolio') && !currentPath.includes('profile')) return;

  try {
    const isDashboard = currentPath.includes('/dashboard');
    const targetUrl = isDashboard ? 'portfolio' : window.location.href;
    const resp = await fetch(targetUrl, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
    if (!resp.ok) return;

    const html = await resp.text();
    const parser = new DOMParser();
    const doc = parser.parseFromString(html, 'text/html');

    const newMain = doc.querySelector('.page-wrap');
    const currMain = document.querySelector('.page-wrap');
    if (newMain && currMain) {
      currMain.innerHTML = newMain.innerHTML;
      if (typeof initPortfolioDropzones === 'function') initPortfolioDropzones();
      if (typeof initSortableGrids === 'function') initSortableGrids();
      if (typeof initBulkSelect === 'function') initBulkSelect();
    }

    if (window.SaaqiSync) {
      window.SaaqiSync.check();
    }
  } catch (e) {
    console.error('refreshPortfolioGallery error:', e);
  }
};

// 3. Event-Driven Reactive Sync Engine
window.SaaqiSync = {
  inFlight: false,
  lastCheck: 0,
  
  async check() {
    const now = Date.now();
    if (this.inFlight || (now - this.lastCheck < 1500)) return;
    this.inFlight = true;
    this.lastCheck = now;

    try {
      const isDashboard = window.location.pathname.includes('/dashboard');
      const statusUrl = isDashboard ? 'ajax_status.php' : 'dashboard/ajax_status.php';
      const resp = await fetch(statusUrl, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        cache: 'no-store'
      });
      if (!resp.ok) return;
      const data = await resp.json();
      if (!data || !data.success) return;

      window.SaaqiState = data;

      // Sync PDF Buttons
      if (typeof window.syncDashboardPdfButtons === 'function') {
        window.syncDashboardPdfButtons(data.remaining_pdf, data.is_subscribed);
      }

      // Sync Modal Data
      if (typeof window.refreshPdfModalData === 'function') {
        window.refreshPdfModalData(data);
      }

      // Sync header image count
      const metaTagPill = document.querySelector('.meta-tag-pill');
      if (metaTagPill && typeof data.total_images === 'number') {
        metaTagPill.textContent = `${data.total_images} piece${data.total_images !== 1 ? 's' : ''}`;
      }
    } catch (err) {
      // Ignore background sync errors
    } finally {
      this.inFlight = false;
    }
  }
};

// Sync on tab focus (multi-tab sync without CPU polling)
document.addEventListener('visibilitychange', function() {
  if (document.visibilityState === 'visible' && window.SaaqiSync) {
    window.SaaqiSync.check();
  }
});

// Periodic idle check (every 45s, only if tab is active and visible)
setInterval(function() {
  if (document.visibilityState === 'visible' && window.SaaqiSync) {
    window.SaaqiSync.check();
  }
}, 45000);

// Global custom event listener
window.addEventListener('saaqi:sync', function() {
  if (window.SaaqiSync) window.SaaqiSync.check();
});
