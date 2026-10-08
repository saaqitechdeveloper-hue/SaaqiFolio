<?php
/**
 * SaaqiFolio - Behance Portfolio Import Modal Component
 * 100% AJAX, Real-time Loader, Zero Page Reload
 */
?>
<div id="behanceImportModal" class="behance-modal-backdrop" aria-hidden="true">
  <div class="behance-modal-card">
    <button type="button" class="behance-modal-close" onclick="closeBehanceModal()" title="Close">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <!-- STEP 1: INPUT URL -->
    <div id="behanceStepInput" class="behance-step-pane active">
      <div class="behance-modal-badge">
        <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
          <path d="M22 7h-7v-2h7v2zm1.726 10c-.442 1.297-2.029 3-5.101 3-4.356 0-5.746-3.081-5.746-5.918 0-3.327 1.884-6.082 5.753-6.082 4.093 0 5.282 3.013 4.966 6.136h-7.669c.074 1.705.952 2.824 2.83 2.824 1.488 0 2.228-.696 2.617-1.488l2.35.528zm-4.992-4.832c-.067-1.121-.692-2.128-2.316-2.128-1.503 0-2.296 1.007-2.42 2.128h4.736zm-11.734-7.168h4.59c1.944 0 3.41.486 3.41 2.378 0 1.258-.707 1.954-1.636 2.254 1.343.434 2.052 1.439 2.052 2.766 0 2.146-1.748 2.602-3.824 2.602h-4.592v-10zm2.748 3.972h1.611c.783 0 1.505-.125 1.505-1.07 0-.82-.577-.962-1.396-.962h-1.72v2.032zm0 4.068h1.838c.969 0 1.758-.154 1.758-1.229 0-.962-.738-1.122-1.654-1.122h-1.942v2.351z"/>
        </svg>
        <span>Behance Smart Importer</span>
      </div>

      <h3 class="behance-modal-title">Import from Behance</h3>
      <p class="behance-modal-desc">
        Paste any public Behance project gallery or profile URL. Our smart AI extracts the original high-resolution artworks, generates ultra-fast thumbnails, and automatically categorizes them for your portfolio.
      </p>

      <div class="behance-input-group">
        <div class="behance-input-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
        </div>
        <input type="url" id="behanceUrlInput" class="behance-input" placeholder="https://www.behance.net/gallery/256796453/..." autocomplete="off">
        <button type="button" class="behance-paste-btn" onclick="pasteBehanceClipboard()" title="Paste from clipboard">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/></svg>
          <span>Paste</span>
        </button>
      </div>

      <div id="behanceInputError" class="behance-error-alert" style="display:none;"></div>

      <div class="behance-modal-foot">
        <button type="button" class="btn btn-ghost btn-sm" onclick="closeBehanceModal()">Cancel</button>
        <button type="button" class="btn btn-primary btn-sm" id="btnBehanceExtract" onclick="startBehanceExtraction()">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
          <span>Extract Artworks</span>
        </button>
      </div>
    </div>

    <!-- STEP 2: EXTRACTING SPINNER / LOADER -->
    <div id="behanceStepExtracting" class="behance-step-pane">
      <div class="behance-loader-center">
        <div class="behance-pulse-icon-wrap">
          <div class="behance-glow-orbit"></div>
          <div class="behance-brand-icon">
            <svg viewBox="0 0 24 24" fill="currentColor" width="34" height="34"><path d="M22 7h-7v-2h7v2zm1.726 10c-.442 1.297-2.029 3-5.101 3-4.356 0-5.746-3.081-5.746-5.918 0-3.327 1.884-6.082 5.753-6.082 4.093 0 5.282 3.013 4.966 6.136h-7.669c.074 1.705.952 2.824 2.83 2.824 1.488 0 2.228-.696 2.617-1.488l2.35.528zm-4.992-4.832c-.067-1.121-.692-2.128-2.316-2.128-1.503 0-2.296 1.007-2.42 2.128h4.736zm-11.734-7.168h4.59c1.944 0 3.41.486 3.41 2.378 0 1.258-.707 1.954-1.636 2.254 1.343.434 2.052 1.439 2.052 2.766 0 2.146-1.748 2.602-3.824 2.602h-4.592v-10zm2.748 3.972h1.611c.783 0 1.505-.125 1.505-1.07 0-.82-.577-.962-1.396-.962h-1.72v2.032zm0 4.068h1.838c.969 0 1.758-.154 1.758-1.229 0-.962-.738-1.122-1.654-1.122h-1.942v2.351z"/></svg>
          </div>
        </div>
        <h4 class="behance-loader-title">Scanning Behance...</h4>
        <p class="behance-loader-desc" id="behanceExtractStatusText">Connecting to Behance CDN &amp; parsing high-resolution projects...</p>
      </div>
    </div>

    <!-- STEP 3: IMPORTING & AI CATEGORIZING PROGRESS -->
    <div id="behanceStepProgress" class="behance-step-pane">
      <div class="behance-progress-header">
        <div class="behance-project-info">
          <span class="behance-proj-title" id="behanceProjTitle">Project Artworks</span>
          <span class="behance-proj-pill" id="behanceTotalPill">0 Pieces Found</span>
        </div>
        <span class="behance-pct-badge" id="behancePctBadge">0%</span>
      </div>

      <!-- Modern Glowing Progress Track -->
      <div class="behance-progress-bar-wrap">
        <div class="behance-progress-fill" id="behanceProgressFill" style="width: 0%;"></div>
      </div>

      <div class="behance-current-stage" id="behanceCurrentStageText">
        Preparing download &amp; AI categorization...
      </div>

      <!-- Live Artworks Processing Feed -->
      <div class="behance-queue-list" id="behanceQueueList"></div>
    </div>

    <!-- STEP 4: COMPLETED STATE -->
    <div id="behanceStepComplete" class="behance-step-pane">
      <div class="behance-complete-center">
        <div class="behance-success-ring">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="36" height="36"><polyline points="20 6 9 17 4 12"/></svg>
        </div>
        <h3 class="behance-complete-title">Import Completed!</h3>
        <p class="behance-complete-desc" id="behanceCompleteMsg">All artworks have been imported, optimized to AVIF, and categorized with AI.</p>
        <button type="button" class="btn btn-primary" onclick="closeBehanceModal()" style="width:auto;padding:10px 24px;">
          <span>View in Portfolio</span>
        </button>
      </div>
    </div>

  </div>
</div>

<style>
/* ─────────────────────────────────────────────────────────────
   BEHANCE FEATURED FLOATING BUTTON STYLES
   ───────────────────────────────────────────────────────────── */
.btn-behance-featured {
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  background: linear-gradient(135deg, #0057ff 0%, #2563eb 50%, #7c3aed 100%);
  color: #ffffff !important;
  font-weight: 600;
  font-size: 13px;
  border: 1px solid rgba(255, 255, 255, 0.28);
  border-radius: 999px;
  padding: 8px 16px;
  cursor: pointer;
  text-decoration: none;
  overflow: hidden;
  box-shadow: 0 4px 18px rgba(0, 87, 255, 0.42), 0 0 14px rgba(124, 58, 237, 0.28);
  animation: behanceBtnFloat 3.2s ease-in-out infinite;
  transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  vertical-align: middle;
}

.btn-behance-featured svg {
  color: #fff !important;
  flex-shrink: 0;
  filter: drop-shadow(0 1px 2px rgba(0, 0, 0, 0.3));
}

.btn-behance-featured:hover {
  transform: translateY(-3px) scale(1.03) !important;
  box-shadow: 0 8px 28px rgba(0, 87, 255, 0.65), 0 0 22px rgba(124, 58, 237, 0.5) !important;
  color: #ffffff !important;
  border-color: rgba(255, 255, 255, 0.5);
}

.btn-behance-featured:active {
  transform: translateY(-1px) scale(0.99);
}

/* Subtle gloss shimmer sweep across button */
.btn-behance-featured::before {
  content: '';
  position: absolute;
  top: 0;
  left: -120%;
  width: 60%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.35), transparent);
  transform: skewX(-24deg);
  animation: behanceBtnShimmer 3.8s infinite 1s;
  pointer-events: none;
}

@keyframes behanceBtnShimmer {
  0% { left: -120%; }
  35%, 100% { left: 220%; }
}

@keyframes behanceBtnFloat {
  0%, 100% {
    transform: translateY(0);
    box-shadow: 0 4px 16px rgba(0, 87, 255, 0.38), 0 0 10px rgba(124, 58, 237, 0.25);
  }
  50% {
    transform: translateY(-3.5px);
    box-shadow: 0 8px 24px rgba(0, 87, 255, 0.6), 0 0 18px rgba(124, 58, 237, 0.42);
  }
}

.behance-sparkle-pill {
  font-size: 9.5px;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  background: rgba(255, 255, 255, 0.22);
  border: 1px solid rgba(255, 255, 255, 0.38);
  padding: 1.5px 6px;
  border-radius: 999px;
  color: #fff;
  line-height: 1.3;
  margin-left: 2px;
}

/* ─────────────────────────────────────────────────────────────
   BEHANCE SMART IMPORTER MODAL STYLES
   ───────────────────────────────────────────────────────────── */
.behance-modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(5, 4, 10, 0.85);
  backdrop-filter: blur(18px);
  -webkit-backdrop-filter: blur(18px);
  z-index: 10001;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}
.behance-modal-backdrop.active {
  opacity: 1;
  pointer-events: auto;
}
.behance-modal-card {
  width: 100%;
  max-width: 540px;
  background: rgba(15, 12, 27, 0.96);
  border: 1px solid rgba(139, 92, 246, 0.22);
  box-shadow: 0 24px 60px rgba(0, 0, 0, 0.85), 0 0 50px rgba(0, 87, 255, 0.15);
  border-radius: var(--radius-lg, 16px);
  padding: 28px;
  position: relative;
  overflow: hidden;
  transform: translateY(16px) scale(0.98);
  transition: transform 0.22s cubic-bezier(0.16, 1, 0.3, 1);
}
.behance-modal-backdrop.active .behance-modal-card {
  transform: translateY(0) scale(1);
}
.behance-modal-close {
  position: absolute;
  top: 18px;
  right: 18px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.06);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #94a3b8;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.18s ease;
}
.behance-modal-close:hover {
  background: rgba(244, 63, 94, 0.2);
  border-color: rgba(244, 63, 94, 0.4);
  color: #fff;
}
.behance-step-pane {
  display: none;
}
.behance-step-pane.active {
  display: block;
}
.behance-modal-badge {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: #3b82f6;
  background: rgba(0, 87, 255, 0.14);
  border: 1px solid rgba(0, 87, 255, 0.32);
  padding: 4px 11px;
  border-radius: 999px;
  margin-bottom: 14px;
}
.behance-modal-title {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 22px;
  font-weight: 700;
  color: #fff;
  margin: 0 0 8px 0;
}
.behance-modal-desc {
  font-size: 13px;
  line-height: 1.55;
  color: #94a3b8;
  margin: 0 0 20px 0;
}
.behance-input-group {
  position: relative;
  display: flex;
  align-items: center;
  background: rgba(0, 0, 0, 0.45);
  border: 1px solid rgba(255, 255, 255, 0.12);
  border-radius: 10px;
  padding: 4px 6px 4px 14px;
  transition: border-color 0.18s, box-shadow 0.18s;
}
.behance-input-group:focus-within {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
}
.behance-input-icon {
  color: #64748b;
  display: flex;
  align-items: center;
  margin-right: 10px;
  flex-shrink: 0;
}
.behance-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: #fff;
  font-size: 13.5px;
  font-family: inherit;
  padding: 8px 0;
}
#behanceUrlInput {
  box-shadow: none !important;
  padding-left: 10px !important;
  padding-right: 10px !important;
}
.behance-input::placeholder {
  color: #64748b;
}
.behance-paste-btn {
  background: rgba(255, 255, 255, 0.07);
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #cbd5e1;
  border-radius: 6px;
  font-size: 11px;
  font-weight: 600;
  padding: 6px 10px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  cursor: pointer;
  transition: all 0.18s ease;
  flex-shrink: 0;
}
.behance-paste-btn:hover {
  background: rgba(255, 255, 255, 0.12);
  color: #fff;
}
.behance-sample-chip-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}
.behance-chip-label {
  font-size: 11px;
  color: #64748b;
}
.behance-sample-chip {
  background: rgba(139, 92, 246, 0.12);
  border: 1px solid rgba(139, 92, 246, 0.25);
  color: #c4b5fd;
  border-radius: 999px;
  font-size: 11px;
  font-weight: 500;
  padding: 3px 10px;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  cursor: pointer;
  transition: all 0.18s ease;
}
.behance-sample-chip:hover {
  background: rgba(139, 92, 246, 0.22);
  border-color: rgba(139, 92, 246, 0.4);
  color: #fff;
}
.chip-sparkle {
  color: #a78bfa;
}
.behance-error-alert {
  margin-top: 14px;
  padding: 10px 14px;
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 8px;
  color: #fca5a5;
  font-size: 12.5px;
  line-height: 1.45;
}
.behance-modal-foot {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 22px;
  padding-top: 16px;
  border-top: 1px solid rgba(255, 255, 255, 0.08);
}

/* Loader stage */
.behance-loader-center {
  padding: 36px 12px;
  text-align: center;
}
.behance-pulse-icon-wrap {
  position: relative;
  width: 80px;
  height: 80px;
  margin: 0 auto 20px auto;
  display: flex;
  align-items: center;
  justify-content: center;
}
.behance-glow-orbit {
  position: absolute;
  inset: 0;
  border-radius: 50%;
  border: 2px dashed rgba(59, 130, 246, 0.6);
  animation: behanceOrbit 3s linear infinite;
}
@keyframes behanceOrbit {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
.behance-brand-icon {
  width: 58px;
  height: 58px;
  background: linear-gradient(135deg, #0057ff, #0036b3);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  box-shadow: 0 0 30px rgba(0, 87, 255, 0.5);
  animation: behancePulse 1.8s ease-in-out infinite;
}
@keyframes behancePulse {
  0%, 100% { transform: scale(1); }
  50% { transform: scale(1.06); }
}
.behance-loader-title {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 18px;
  font-weight: 700;
  color: #fff;
  margin: 0 0 6px 0;
}
.behance-loader-desc {
  font-size: 13px;
  color: #94a3b8;
  margin: 0;
}

/* Progress stage */
.behance-progress-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 14px;
  padding-right: 46px; /* Clearance for top-right close button */
}
.behance-project-info {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  flex: 1;
}
.behance-proj-title {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 15px;
  font-weight: 700;
  color: #fff;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.behance-proj-pill {
  font-size: 11px;
  font-weight: 600;
  padding: 2px 8px;
  border-radius: 999px;
  background: rgba(139, 92, 246, 0.18);
  border: 1px solid rgba(139, 92, 246, 0.3);
  color: #c4b5fd;
  flex-shrink: 0;
}
.behance-pct-badge {
  font-family: 'JetBrains Mono', monospace;
  font-size: 12px;
  font-weight: 700;
  color: #3b82f6;
  background: rgba(59, 130, 246, 0.12);
  border: 1px solid rgba(59, 130, 246, 0.28);
  padding: 3px 8px;
  border-radius: 6px;
  flex-shrink: 0;
  margin-left: 10px;
}
.behance-progress-bar-wrap {
  width: 100%;
  height: 7px;
  background: rgba(255, 255, 255, 0.08);
  border-radius: 999px;
  overflow: hidden;
  margin-bottom: 12px;
}
.behance-progress-fill {
  height: 100%;
  background: linear-gradient(90deg, #0057ff, #8b5cf6, #ec4899);
  border-radius: 999px;
  transition: width 0.3s ease;
}
.behance-current-stage {
  font-size: 12.5px;
  color: #94a3b8;
  margin-bottom: 16px;
  min-height: 18px;
}
.behance-queue-list {
  max-height: 220px;
  overflow-y: auto;
  display: flex;
  flex-direction: column;
  gap: 8px;
  padding-right: 4px;
}
.behance-queue-list::-webkit-scrollbar {
  width: 4px;
}
.behance-queue-list::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.15);
  border-radius: 4px;
}
.behance-queue-item {
  display: flex;
  align-items: center;
  gap: 12px;
  background: rgba(255, 255, 255, 0.03);
  border: 1px solid rgba(255, 255, 255, 0.06);
  border-radius: 8px;
  padding: 8px 12px;
  transition: all 0.2s ease;
}
.behance-queue-item.processing {
  border-color: rgba(59, 130, 246, 0.4);
  background: rgba(59, 130, 246, 0.08);
}
.behance-queue-item.done {
  border-color: rgba(16, 185, 129, 0.3);
  background: rgba(16, 185, 129, 0.06);
}
.behance-queue-thumb {
  width: 36px;
  height: 36px;
  border-radius: 6px;
  object-fit: cover;
  background: #1e1b2e;
  flex-shrink: 0;
}
.behance-queue-title {
  font-size: 12px;
  font-weight: 500;
  color: #e2e8f0;
  flex: 1;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.behance-queue-status {
  font-size: 11px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  flex-shrink: 0;
}
.behance-queue-status.waiting {
  color: #64748b;
}
.behance-queue-status.running {
  color: #3b82f6;
}
.behance-queue-status.completed {
  color: #10b981;
}

/* Complete stage */
.behance-complete-center {
  padding: 24px 12px 10px 12px;
  text-align: center;
}
.behance-success-ring {
  width: 68px;
  height: 68px;
  border-radius: 50%;
  background: rgba(16, 185, 129, 0.15);
  border: 2px solid rgba(16, 185, 129, 0.4);
  color: #10b981;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px auto;
  box-shadow: 0 0 30px rgba(16, 185, 129, 0.3);
}
.behance-complete-title {
  font-family: 'Space Grotesk', sans-serif;
  font-size: 22px;
  font-weight: 700;
  color: #fff;
  margin: 0 0 8px 0;
}
.behance-complete-desc {
  font-size: 13.5px;
  color: #94a3b8;
  margin: 0 0 24px 0;
}

/* Fade in for freshly inserted cards */
@keyframes behanceNewCardFadeIn {
  from {
    opacity: 0;
    transform: translateY(12px) scale(0.96);
  }
  to {
    opacity: 1;
    transform: translateY(0) scale(1);
  }
}
/* ─────────────────────────────────────────────────────────────
   BEHANCE MODAL: LIGHT MODE OVERRIDES
   ───────────────────────────────────────────────────────────── */
html[data-theme="light"] .behance-modal-backdrop {
  background: rgba(15, 23, 42, 0.45);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
}

html[data-theme="light"] .behance-modal-card {
  background: #ffffff !important;
  border: 1px solid rgba(15, 23, 42, 0.12) !important;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.18), 0 0 40px rgba(0, 87, 255, 0.08) !important;
}

html[data-theme="light"] .behance-modal-close {
  background: rgba(15, 23, 42, 0.06);
  border: 1px solid rgba(15, 23, 42, 0.1);
  color: #475569;
}
html[data-theme="light"] .behance-modal-close:hover {
  background: rgba(244, 63, 94, 0.12);
  border-color: rgba(244, 63, 94, 0.35);
  color: #e11d48;
}

/* Step 1 Input */
html[data-theme="light"] .behance-modal-badge {
  background: rgba(0, 87, 255, 0.08);
  border: 1px solid rgba(0, 87, 255, 0.22);
  color: #0057ff;
}
html[data-theme="light"] .behance-modal-title {
  color: #0f172a !important;
}
html[data-theme="light"] .behance-modal-desc {
  color: #475569 !important;
}
html[data-theme="light"] .behance-input-group {
  background: #f8fafc !important;
  border: 1.5px solid rgba(15, 23, 42, 0.14) !important;
}
html[data-theme="light"] .behance-input-icon {
  color: #64748b;
}
html[data-theme="light"] .behance-input {
  color: #0f172a !important;
}
html[data-theme="light"] .behance-input::placeholder {
  color: #94a3b8 !important;
}
html[data-theme="light"] .behance-paste-btn {
  background: #ffffff !important;
  border: 1px solid rgba(15, 23, 42, 0.14) !important;
  color: #334155 !important;
  box-shadow: 0 1px 4px rgba(15, 23, 42, 0.05);
}
html[data-theme="light"] .behance-paste-btn:hover {
  background: #f1f5f9 !important;
  color: #0057ff !important;
  border-color: rgba(0, 87, 255, 0.35) !important;
}
html[data-theme="light"] .behance-sample-chip {
  background: #f1f5f9;
  border: 1px solid rgba(15, 23, 42, 0.1);
  color: #475569;
}
html[data-theme="light"] .behance-sample-chip:hover {
  background: rgba(0, 87, 255, 0.08);
  border-color: rgba(0, 87, 255, 0.3);
  color: #0057ff;
}
html[data-theme="light"] .behance-modal-foot {
  border-top: 1px solid rgba(15, 23, 42, 0.08);
}

/* Step 2 Scanning */
html[data-theme="light"] .behance-loader-title {
  color: #0f172a !important;
}
html[data-theme="light"] .behance-loader-desc {
  color: #475569 !important;
}

/* Step 3 Live Progress Feed */
html[data-theme="light"] .behance-proj-title {
  color: #0f172a !important;
}
html[data-theme="light"] .behance-proj-pill {
  background: rgba(124, 58, 237, 0.1) !important;
  border: 1px solid rgba(124, 58, 237, 0.25) !important;
  color: #7c3aed !important;
}
html[data-theme="light"] .behance-pct-badge {
  background: rgba(37, 99, 235, 0.1) !important;
  border: 1px solid rgba(37, 99, 235, 0.28) !important;
  color: #2563eb !important;
}
html[data-theme="light"] .behance-progress-bar-wrap {
  background: rgba(15, 23, 42, 0.08) !important;
}
html[data-theme="light"] .behance-current-stage {
  color: #475569 !important;
}
html[data-theme="light"] .behance-queue-list::-webkit-scrollbar-thumb {
  background: rgba(15, 23, 42, 0.18) !important;
}
html[data-theme="light"] .behance-queue-item {
  background: #f8fafc !important;
  border: 1px solid rgba(15, 23, 42, 0.09) !important;
}
html[data-theme="light"] .behance-queue-item.processing {
  border-color: rgba(37, 99, 235, 0.45) !important;
  background: rgba(37, 99, 235, 0.08) !important;
}
html[data-theme="light"] .behance-queue-item.done {
  border-color: rgba(16, 185, 129, 0.45) !important;
  background: rgba(16, 185, 129, 0.08) !important;
}
html[data-theme="light"] .behance-queue-thumb {
  background: #e2e8f0;
}
html[data-theme="light"] .behance-queue-title {
  color: #0f172a !important;
  font-weight: 600 !important;
}
html[data-theme="light"] .behance-queue-status.waiting {
  color: #64748b !important;
}
html[data-theme="light"] .behance-queue-status.running {
  color: #2563eb !important;
}
html[data-theme="light"] .behance-queue-status.completed {
  color: #059669 !important;
}

/* Step 4 Complete State */
html[data-theme="light"] .behance-complete-title {
  color: #0f172a !important;
}
html[data-theme="light"] .behance-complete-desc {
  color: #475569 !important;
}
</style>

<script>
(function() {
  let isImporting = false;

  window.openBehanceImportModal = function() {
    const modal = document.getElementById('behanceImportModal');
    if (!modal) return;
    switchStep('behanceStepInput');
    const errBox = document.getElementById('behanceInputError');
    if (errBox) errBox.style.display = 'none';
    modal.classList.add('active');
    modal.setAttribute('aria-hidden', 'false');
    const input = document.getElementById('behanceUrlInput');
    if (input) {
      setTimeout(() => input.focus(), 80);
    }
  };

  window.closeBehanceModal = function() {
    if (isImporting) {
      if (!confirm('Import is currently in progress. Are you sure you want to close?')) {
        return;
      }
    }
    const modal = document.getElementById('behanceImportModal');
    if (modal) {
      modal.classList.remove('active');
      modal.setAttribute('aria-hidden', 'true');
    }
    isImporting = false;
  };

  window.pasteBehanceClipboard = async function() {
    try {
      const text = await navigator.clipboard.readText();
      const input = document.getElementById('behanceUrlInput');
      if (input && text) {
        input.value = text.trim();
        input.focus();
      }
    } catch(err) {
      console.warn('Clipboard read failed: ', err);
    }
  };

  window.setSampleBehanceUrl = function(url) {
    const input = document.getElementById('behanceUrlInput');
    if (input) {
      input.value = url;
      input.focus();
    }
  };

  function switchStep(stepId) {
    document.querySelectorAll('.behance-step-pane').forEach(p => p.classList.remove('active'));
    const target = document.getElementById(stepId);
    if (target) target.classList.add('active');
  }

  function showError(msg) {
    const errBox = document.getElementById('behanceInputError');
    if (errBox) {
      errBox.textContent = msg;
      errBox.style.display = 'block';
    }
  }

  // 1. EXTRACT ARTWORKS VIA AJAX
  window.startBehanceExtraction = async function() {
    const input = document.getElementById('behanceUrlInput');
    const url = input ? input.value.trim() : '';

    if (!url) {
      showError('Please enter a valid Behance gallery or profile URL.');
      return;
    }

    if (!url.toLowerCase().includes('behance.net')) {
      showError('Please provide a valid Behance link (e.g. https://www.behance.net/gallery/...)');
      return;
    }

    const errBox = document.getElementById('behanceInputError');
    if (errBox) errBox.style.display = 'none';

    // Transition to Extraction Loader
    switchStep('behanceStepExtracting');
    isImporting = true;

    function getBehanceEndpointUrl() {
      return window.location.href.split('#')[0];
    }

    try {
      const fd = new FormData();
      fd.append('action', 'behance_extract');
      fd.append('url', url);

      const resp = await fetch(getBehanceEndpointUrl(), {
        method: 'POST',
        body: fd
      });

      const text = await resp.text();
      let res = null;
      try {
        res = JSON.parse(text);
      } catch (parseErr) {
        console.error('Non-JSON response:', text);
        switchStep('behanceStepInput');
        const preview = text.replace(/<[^>]+>/g, '').trim().substring(0, 150);
        showError('Server error: ' + (preview || 'Invalid response from server.'));
        isImporting = false;
        return;
      }

      if (!res.success) {
        switchStep('behanceStepInput');
        showError(res.error || 'Failed to extract artworks from Behance.');
        isImporting = false;
        return;
      }

      const images = res.images || [];
      if (images.length === 0) {
        switchStep('behanceStepInput');
        showError('No image artworks found in this Behance link.');
        isImporting = false;
        return;
      }

      // Start Sequential Importer Pipeline
      runImportPipeline(res);

    } catch (err) {
      console.error(err);
      switchStep('behanceStepInput');
      showError('Connection error: ' + (err.message || 'Please check your connection and try again.'));
      isImporting = false;
    }
  };

  // 2. SEQUENTIAL IMPORT & AI CATEGORIZATION (100% AJAX, ZERO RELOAD)
  async function runImportPipeline(data) {
    switchStep('behanceStepProgress');

    const titleEl = document.getElementById('behanceProjTitle');
    const totalPill = document.getElementById('behanceTotalPill');
    const progressBar = document.getElementById('behanceProgressFill');
    const pctBadge = document.getElementById('behancePctBadge');
    const stageText = document.getElementById('behanceCurrentStageText');
    const queueList = document.getElementById('behanceQueueList');

    if (titleEl) titleEl.textContent = data.title || 'Behance Project';
    if (totalPill) totalPill.textContent = `${data.total} Pieces`;

    const images = data.images || [];
    const total = images.length;

    // Render initial queue list
    if (queueList) {
      queueList.innerHTML = images.map((item, idx) => `
        <div class="behance-queue-item" id="behanceQItem_${idx}">
          <img src="${item.preview || item.url}" class="behance-queue-thumb" alt="Preview" onerror="this.style.display='none';">
          <div class="behance-queue-title">${item.title || ('Artwork #' + (idx + 1))}</div>
          <div class="behance-queue-status waiting" id="behanceQStatus_${idx}">
            <span>Waiting</span>
          </div>
        </div>
      `).join('');
    }

    let importedSuccess = 0;

    for (let i = 0; i < total; i++) {
      if (!isImporting) break; // User cancelled

      const item = images[i];
      const qItem = document.getElementById(`behanceQItem_${i}`);
      const qStatus = document.getElementById(`behanceQStatus_${i}`);

      if (qItem) qItem.classList.add('processing');
      if (qStatus) {
        qStatus.className = 'behance-queue-status running';
        qStatus.innerHTML = `
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="13" height="13" class="spin" style="animation:behanceOrbit 1s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
          <span>AI Categorizing...</span>
        `;
      }

      const currentIdx = i + 1;
      const currentPct = Math.round((i / total) * 100);
      if (progressBar) progressBar.style.width = currentPct + '%';
      if (pctBadge) pctBadge.textContent = currentPct + '%';
      if (stageText) {
        stageText.innerHTML = `Processing piece <strong>${currentIdx} of ${total}</strong>: Downloading &amp; analyzing with AI...`;
      }

      try {
        const fd = new FormData();
        fd.append('action', 'behance_import_item');
        fd.append('url', item.url);
        fd.append('title', item.title || '');
        fd.append('alt', item.alt || '');

        const resp = await fetch(window.location.href.split('#')[0], {
          method: 'POST',
          body: fd
        });

        const text = await resp.text();
        let res = null;
        try {
          res = JSON.parse(text);
        } catch(parseErr) {
          console.error('Non-JSON response:', text);
        }

        if (res && res.success) {
          importedSuccess++;
          if (qItem) {
            qItem.classList.remove('processing');
            qItem.classList.add('done');
          }
          if (qStatus) {
            qStatus.className = 'behance-queue-status completed';
            qStatus.innerHTML = `
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" width="13" height="13"><polyline points="20 6 9 17 4 12"/></svg>
              <span>${res.category || 'Categorized'}</span>
            `;
          }

          // DYNAMIC LIVE DOM INJECTION (NO RELOAD)
          insertImportedCardToGallery(res.card_html, res.category);

        } else {
          if (qItem) qItem.classList.remove('processing');
          if (qStatus) {
            qStatus.className = 'behance-queue-status error';
            qStatus.style.color = '#ef4444';
            qStatus.textContent = (res && res.error) ? res.error : 'Failed';
          }
        }

      } catch (err) {
        console.error(err);
        if (qStatus) {
          qStatus.className = 'behance-queue-status error';
          qStatus.style.color = '#ef4444';
          qStatus.textContent = 'Connection error';
        }
      }
    }

    if (progressBar) progressBar.style.width = '100%';
    if (pctBadge) pctBadge.textContent = '100%';

    // Step 4: Complete Screen
    const completeMsg = document.getElementById('behanceCompleteMsg');
    if (completeMsg) {
      completeMsg.textContent = `${importedSuccess} of ${total} artworks successfully imported and AI-categorized.`;
    }
    switchStep('behanceStepComplete');
    isImporting = false;

    if (typeof showToast === 'function') {
      showToast(`${importedSuccess} artwork(s) imported from Behance!`, 'success', 3500);
    }
  }

  // 3. ZERO-PAGE-RELOAD DYNAMIC CARD INJECTION
  function insertImportedCardToGallery(cardHtml, category) {
    if (!cardHtml) return;

    // A. If page is currently in the empty dropzone state, transition page-wrap
    const emptyDropzone = document.getElementById('dropzone');
    if (emptyDropzone) {
      // Reload only the main content area via AJAX fetch of 'portfolio' without reloading the entire window
      fetch(window.location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.text())
        .then(html => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const newMain = doc.querySelector('.page-wrap');
          const currMain = document.querySelector('.page-wrap');
          if (newMain && currMain) {
            currMain.innerHTML = newMain.innerHTML;
            if (typeof initPortfolioDropzones === 'function') initPortfolioDropzones();
            if (typeof initSortableGrids === 'function') initSortableGrids();
          }
        })
        .catch(() => {});
      return;
    }

    // B. Normal populated portfolio grid
    const targetCat = category || 'Other';
    let targetSection = document.querySelector(`.cat-section[data-cat="${targetCat}"]`);

    if (!targetSection) {
      // If category section doesn't exist yet on 'All' view, create it dynamically
      const allTabsSegmented = document.querySelector('.tabs-segmented');
      if (allTabsSegmented) {
        const catSectionTpl = document.createElement('section');
        catSectionTpl.className = 'cat-section';
        catSectionTpl.setAttribute('data-cat', targetCat);
        const catSlug = targetCat.toLowerCase().replace(/[^a-z0-9]+/g, '-');
        catSectionTpl.innerHTML = `
          <div class="cat-section-header">
            <div class="cat-title-wrap">
              <span class="cat-bullet cat-bullet-${catSlug}"></span>
              <h3 class="cat-section-title">${targetCat}</h3>
              <span class="cat-section-count">0</span>
            </div>
            <span class="cat-reorder-hint" title="Drag and drop cards to change arrangement in this category">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="12" height="12"><path d="m3 9 4-4 4 4M7 5v14M21 15l-4 4-4-4M17 19V5"/></svg>
              Drag to reorder
            </span>
          </div>
          <div class="grid sortable-grid" data-category="${targetCat}"></div>
        `;
        // Insert after tabs
        allTabsSegmented.parentNode.insertBefore(catSectionTpl, allTabsSegmented.nextSibling);
        targetSection = catSectionTpl;
      }
    }

    if (targetSection) {
      const grid = targetSection.querySelector('.sortable-grid');
      const countEl = targetSection.querySelector('.cat-section-count');
      if (grid) {
        const temp = document.createElement('div');
        temp.innerHTML = cardHtml.trim();
        const newCard = temp.firstElementChild;
        if (newCard) {
          newCard.classList.add('behance-new-card');
          grid.prepend(newCard);
          if (countEl) {
            countEl.textContent = grid.querySelectorAll('.card').length;
          }
        }
      }
    }

    // Update Category Tabs Badges & Header Count
    updateTabsAndHeaderCount(targetCat);

    // Re-init sortable drag listeners on new cards
    if (typeof initSortableGrids === 'function') {
      initSortableGrids();
    }
  }

  function updateTabsAndHeaderCount(targetCat) {
    // Total count pill in page header
    const metaTagPill = document.querySelector('.meta-tag-pill');
    if (metaTagPill) {
      const match = metaTagPill.textContent.match(/\d+/);
      if (match) {
        const newTotal = parseInt(match[0], 10) + 1;
        metaTagPill.textContent = `${newTotal} piece${newTotal !== 1 ? 's' : ''}`;
      }
    }

    // All tab badge
    const allTabCount = document.querySelector('.tabs-segmented .tab-item:first-child .tab-count');
    if (allTabCount) {
      allTabCount.textContent = parseInt(allTabCount.textContent || '0', 10) + 1;
    }

    // Specific category tab badge
    document.querySelectorAll('.tabs-segmented .tab-item').forEach(tab => {
      const label = tab.querySelector('.tab-label');
      if (label && label.textContent.trim() === targetCat) {
        const count = tab.querySelector('.tab-count');
        if (count) count.textContent = parseInt(count.textContent || '0', 10) + 1;
      }
    });
  }

  // Close on Escape or click outside
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
      const modal = document.getElementById('behanceImportModal');
      if (modal && modal.classList.contains('active')) {
        closeBehanceModal();
      }
    }
  });

  const modalEl = document.getElementById('behanceImportModal');
  if (modalEl) {
    modalEl.addEventListener('click', function(e) {
      if (e.target === this) closeBehanceModal();
    });
  }
})();
</script>
