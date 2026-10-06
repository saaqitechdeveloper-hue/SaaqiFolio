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
    if (e.target.closest('[data-browse-btn]')) return;
    inputEl.click();
  });

  const browseBtn = zoneEl.querySelector('[data-browse-btn]');
  if (browseBtn) {
    browseBtn.addEventListener('click', function (e) {
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
    if (inputEl.files && inputEl.files.length) onFiles(inputEl.files);
  });
}

/* ──────────────────────────────────────────────
   AJAX UPLOAD WITH LOADER
────────────────────────────────────────────── */
/**
 * Uploads files via AJAX, shows the loader overlay with timer, reloads on success.
 * @param {HTMLFormElement} form
 * @param {HTMLInputElement} fileInput
 * @param {FileList} files
 * @param {{ overlay, timerEl, titleEl, subEl }} loaderEls
 */
function ajaxUploadFiles(form, fileInput, files, loaderEls) {
  if (!files || !files.length) return;

  const dt = new DataTransfer();
  Array.from(files).forEach(f => dt.items.add(f));
  if (fileInput) {
    fileInput.files = dt.files;
  }

  const fd = new FormData(form || undefined);
  // Guarantee files are in the FormData explicitly!
  fd.delete('images[]');
  Array.from(files).forEach(f => {
    fd.append('images[]', f);
  });
  if (!fd.has('upload_images')) fd.append('upload_images', '1');
  if (!fd.has('ajax')) fd.append('ajax', '1');

  const { overlay, timerEl, titleEl, subEl } = loaderEls || {};

  const messages = [
    'Analyzing your images with AI...',
    'Classifying content and categories...',
    'Almost there — wrapping things up...',
    'Finalizing your portfolio update...'
  ];

  let seconds = 0;
  if (overlay) overlay.hidden = false;
  if (timerEl) timerEl.textContent = '0s';
  if (titleEl) titleEl.textContent = messages[0];

  const tick = setInterval(function () {
    seconds++;
    if (timerEl) timerEl.textContent = seconds + 's';
    const msgIndex = Math.min(Math.floor(seconds / 5), messages.length - 1);
    if (titleEl) titleEl.textContent = messages[msgIndex];
  }, 1000);

  const targetUrl = (form && form.getAttribute('action')) ? form.getAttribute('action') : window.location.href;

  fetch(targetUrl, { method: 'POST', body: fd })
    .then(async r => {
      const text = await r.text();
      try {
        return JSON.parse(text);
      } catch (err) {
        console.error('Raw server response:', text);
        throw new Error('Invalid JSON response from server');
      }
    })
    .then(data => {
      clearInterval(tick);
      if (data && data.debug) console.log('AI classify debug:', data.debug);
      if (data && data.error && !data.success) {
        if (overlay) overlay.hidden = true;
        showToast(data.error, 'danger');
        return;
      }
      window.location.reload();
    })
    .catch(function (err) {
      clearInterval(tick);
      if (overlay) overlay.hidden = true;
      console.error('Upload error:', err);
      showToast('Upload failed. Please check file format and size.', 'danger');
    });
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
