<?php
/**
 * SaaqiFolio - Project Tutorial Video Modal & Floating Action Button
 * Dynamically configured by Super Admin via database (site_settings table).
 */
require_once __DIR__ . '/functions.php';

$tvEnabled = get_setting('tutorial_video_enabled', '1') === '1';
if (!$tvEnabled) {
    return;
}

$tvType = get_setting('tutorial_video_type', 'url');
$tvRawUrl = get_setting('tutorial_video_url', '');
$tvFile = get_setting('tutorial_video_file', '');

$tvVideoSrc = '';
$tvEmbedUrl = '';

$siteRoot = get_site_root_url();
$hasValidFile = !empty($tvFile) && file_exists(__DIR__ . '/../assets/uploads/videos/' . $tvFile);
$hasValidUrl = !empty($tvRawUrl);

if ($tvType === 'url' && $hasValidUrl) {
    $tvEmbedUrl = format_embed_video_url($tvRawUrl);
} elseif ($tvType === 'file' && $hasValidFile) {
    $tvVideoSrc = $siteRoot . '/assets/uploads/videos/' . rawurlencode($tvFile);
} elseif ($hasValidUrl) {
    $tvType = 'url';
    $tvEmbedUrl = format_embed_video_url($tvRawUrl);
} elseif ($hasValidFile) {
    $tvType = 'file';
    $tvVideoSrc = $siteRoot . '/assets/uploads/videos/' . rawurlencode($tvFile);
} else {
    // Neither valid video file nor URL is configured
    return;
}

$tvTitle = get_setting('tutorial_video_title', 'SaaqiFolio Walkthrough');
$tvDesc = get_setting('tutorial_video_desc', 'Watch this quick tutorial to explore features, customize your portfolio, and export client PDFs.');
?>

<!-- Floating Glowing Bulb Button (Bottom Corner) -->
<div class="tutorial-fab-wrapper" id="tutorialFabWrapper" style="display:none;">
  <button type="button" class="tutorial-fab-btn" id="tutorialFabBtn" aria-label="Watch Project Tutorial" onclick="openTutorialVideoModal();">
    <div class="tutorial-fab-glow"></div>
    <div class="tutorial-fab-ring"></div>
    <div class="tutorial-fab-icon">
      <!-- Lightbulb + Play hybrid icon -->
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22">
        <path d="M15 14c.2-1 .7-1.7 1.5-2.5 1-1 1.5-2.2 1.5-3.5A6 6 0 0 0 6 8c0 1 .2 2.2 1.5 3.5.7.7 1.3 1.5 1.5 2.5"/>
        <path d="M9 18h6"/>
        <path d="M10 22h4"/>
        <polygon points="10 7 15 10 10 13 10 7" fill="currentColor"/>
      </svg>
    </div>
  </button>
  <div class="tutorial-fab-tooltip" id="tutorialFabTooltip">
    <span class="tooltip-dot"></span>
    <span class="tooltip-text" onclick="openTutorialVideoModal();">Watch Project Tutorial</span>
    <button type="button" class="tooltip-close-btn" onclick="event.stopPropagation(); dismissTutorialTooltip();" aria-label="Dismiss tooltip" title="Dismiss tooltip">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="11" height="11">
        <line x1="18" y1="6" x2="6" y2="18"/>
        <line x1="6" y1="6" x2="18" y2="18"/>
      </svg>
    </button>
  </div>
</div>

<!-- Tutorial Video Popup Modal -->
<div class="tutorial-modal-overlay" id="tutorialVideoModal" style="display:none;" onclick="if(event.target===this) closeTutorialVideoModal();">
  <div class="tutorial-modal-card">
    <button type="button" class="tutorial-close-btn" onclick="closeTutorialVideoModal();" aria-label="Close tutorial video" title="Close">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="18" height="18"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
    </button>

    <div class="tutorial-modal-header">
      <div class="tutorial-modal-meta">
        <span class="tutorial-pill">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="11" height="11"><polygon points="5 3 19 12 5 21 5 3" fill="currentColor"/></svg>
          <span>TUTORIAL</span>
        </span>
        <h3 class="tutorial-modal-title"><?php echo e($tvTitle); ?></h3>
      </div>
    </div>

    <?php if (!empty($tvDesc)): ?>
      <p class="tutorial-modal-desc"><?php echo e($tvDesc); ?></p>
    <?php endif; ?>

    <div class="tutorial-video-frame-wrap">
      <?php if ($tvType === 'file'): ?>
        <video id="tutorialVideoNative" controls playsinline preload="auto" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:contain;background:#000;">
          <source src="<?php echo e($tvVideoSrc); ?>" type="video/mp4">
          Your browser does not support HTML5 video tag.
        </video>
      <?php else: ?>
        <iframe id="tutorialVideoIframe" data-src="<?php echo e($tvEmbedUrl); ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; fullscreen" allowfullscreen></iframe>
      <?php endif; ?>
    </div>

    <div class="tutorial-modal-footer">
      <div class="tutorial-footer-hint">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
        <span>You can reopen this tutorial anytime via the floating bulb button.</span>
      </div>
      <button type="button" class="btn btn-primary tutorial-modal-btn" onclick="closeTutorialVideoModal();">
        <span>Got it, let's go!</span>
      </button>
    </div>
  </div>
</div>

<style>
/* ── Floating Action Bulb Button ── */
.tutorial-fab-wrapper {
  position: fixed;
  bottom: 12px;
  right: 12px;
  z-index: 99990;
  display: flex;
  align-items: center;
  gap: 12px;
}

.tutorial-fab-btn {
  position: relative;
  width: 52px;
  height: 52px;
  border-radius: 50%;
  border: none;
  background: linear-gradient(135deg, #7c5cfc, #ff6b4a);
  color: #fff;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 10px 25px -4px rgba(124, 92, 252, 0.5), 0 0 20px rgba(255, 107, 74, 0.4);
  transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.25s ease;
}

.tutorial-fab-btn:hover {
  transform: scale(1.1);
  box-shadow: 0 14px 30px -4px rgba(124, 92, 252, 0.7), 0 0 35px rgba(255, 107, 74, 0.6);
}

.tutorial-fab-icon {
  position: relative;
  z-index: 3;
  display: flex;
  align-items: center;
  justify-content: center;
}

.tutorial-fab-glow {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  background: linear-gradient(135deg, #7c5cfc, #ff6b4a);
  filter: blur(10px);
  opacity: 0.65;
  z-index: 1;
  animation: bulbGlowPulse 2.4s infinite ease-in-out;
}

.tutorial-fab-ring {
  position: absolute;
  inset: -6px;
  border-radius: 50%;
  border: 2px solid rgba(124, 92, 252, 0.5);
  z-index: 2;
  animation: bulbRingPulse 2.4s infinite ease-out;
}

@keyframes bulbGlowPulse {
  0%, 100% { opacity: 0.5; transform: scale(0.95); }
  50% { opacity: 0.9; transform: scale(1.15); filter: blur(14px); }
}

@keyframes bulbRingPulse {
  0% { transform: scale(0.9); opacity: 0.8; }
  50% { transform: scale(1.3); opacity: 0; }
  100% { transform: scale(0.9); opacity: 0; }
}

/* Tooltip */
.tutorial-fab-tooltip {
  position: absolute;
  right: calc(100% + 12px);
  top: 50%;
  transform: translateY(-50%);
  background: rgba(18, 14, 28, 0.94);
  backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.14);
  color: #fff;
  padding: 7px 8px 7px 14px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  white-space: nowrap;
  pointer-events: auto;
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.45);
  display: flex;
  align-items: center;
  gap: 8px;
  transition: opacity 0.25s ease, transform 0.25s ease;
  user-select: none;
}

.tutorial-fab-tooltip .tooltip-text {
  cursor: pointer;
  transition: color 0.15s ease;
}

.tutorial-fab-tooltip .tooltip-text:hover {
  color: #c4b5fd;
}

.tooltip-close-btn {
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: rgba(255, 255, 255, 0.7);
  width: 20px;
  height: 20px;
  border-radius: 50%;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0;
  margin-left: 2px;
  transition: all 0.2s ease;
}

.tooltip-close-btn:hover {
  background: rgba(239, 68, 68, 0.25);
  border-color: rgba(239, 68, 68, 0.5);
  color: #ff6b6b;
  transform: scale(1.1);
}

.tooltip-dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  background: #3ddc97;
  box-shadow: 0 0 8px #3ddc97;
  flex-shrink: 0;
}

@media (max-width: 600px) {
  .tutorial-fab-wrapper {
    bottom: 12px;
    right: 12px;
  }
  .tutorial-fab-btn {
    width: 44px;
    height: 44px;
  }
  .tutorial-fab-tooltip {
    right: 0;
    top: auto;
    bottom: calc(100% + 10px);
    transform: none;
    font-size: 11px;
    padding: 6px 8px 6px 12px;
  }
}

/* ── Modal Overlay & Card ── */
.tutorial-modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 99999;
  background: rgba(8, 6, 14, 0.85);
  backdrop-filter: blur(14px);
  -webkit-backdrop-filter: blur(14px);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  animation: tutFadeIn 0.25s ease-out;
}

.tutorial-modal-card {
  position: relative;
  background: linear-gradient(145deg, rgba(26, 21, 38, 0.98), rgba(16, 12, 24, 0.99));
  border: 1px solid rgba(255, 255, 255, 0.12);
  box-shadow: 0 25px 60px -10px rgba(0, 0, 0, 0.75), 0 0 40px rgba(124, 92, 252, 0.2);
  border-radius: 20px;
  width: 100%;
  max-width: 820px;
  padding: 24px 28px;
  animation: tutSlideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
  box-sizing: border-box;
}

.tutorial-close-btn {
  position: absolute;
  top: 18px;
  right: 18px;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.12);
  color: #fff;
  width: 34px;
  height: 34px;
  border-radius: 50%;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  z-index: 15;
}

.tutorial-close-btn:hover {
  background: rgba(255, 255, 255, 0.18);
  transform: rotate(90deg);
}

.tutorial-modal-header {
  margin-bottom: 8px;
  padding-right: 44px; /* Clearance for close button */
}

.tutorial-modal-meta {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.tutorial-pill {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 3px 10px;
  border-radius: 20px;
  background: rgba(124, 92, 252, 0.15);
  border: 1px solid rgba(124, 92, 252, 0.35);
  color: var(--accent-purple-light, #c4b5fd);
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.05em;
  flex-shrink: 0;
}

.tutorial-modal-title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: var(--text-main, #fff);
  line-height: 1.35;
}

.tutorial-modal-desc {
  margin: 0 0 16px;
  font-size: 13px;
  color: var(--text-muted, #a1a1aa);
  line-height: 1.5;
  padding-right: 28px;
}

.tutorial-video-frame-wrap {
  position: relative;
  width: 100%;
  padding-bottom: 56.25%; /* 16:9 */
  height: 0;
  border-radius: 14px;
  overflow: hidden;
  background: #000;
  border: 1px solid rgba(255, 255, 255, 0.08);
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

.tutorial-video-frame-wrap iframe {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  border: none;
}

.tutorial-modal-footer {
  margin-top: 18px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.tutorial-footer-hint {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 12px;
  color: var(--text-muted, #71717a);
}

.tutorial-modal-btn {
  width: auto;
  padding: 8px 22px;
  white-space: nowrap;
  flex-shrink: 0;
}

/* ── Mobile Responsive Overrides ── */
@media (max-width: 640px) {
  .tutorial-modal-overlay {
    padding: 12px;
  }

  .tutorial-modal-card {
    padding: 16px 14px 18px;
    border-radius: 16px;
    max-height: 94vh;
    overflow-y: auto;
    -webkit-overflow-scrolling: touch;
  }

  .tutorial-close-btn {
    top: 12px;
    right: 12px;
    width: 30px;
    height: 30px;
  }

  .tutorial-modal-header {
    padding-right: 36px;
    margin-bottom: 6px;
  }

  .tutorial-modal-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 6px;
  }

  .tutorial-pill {
    padding: 2px 8px;
    font-size: 10px;
  }

  .tutorial-modal-title {
    font-size: 15.5px;
    line-height: 1.3;
  }

  .tutorial-modal-desc {
    font-size: 12px;
    line-height: 1.45;
    margin-bottom: 12px;
    padding-right: 0;
  }

  .tutorial-video-frame-wrap {
    border-radius: 10px;
  }

  .tutorial-modal-footer {
    flex-direction: column-reverse;
    align-items: stretch;
    gap: 10px;
    margin-top: 14px;
  }

  .tutorial-modal-btn {
    width: 100% !important;
    padding: 11px 16px !important;
    font-size: 13.5px !important;
    text-align: center;
    justify-content: center;
    border-radius: 10px;
  }

  .tutorial-footer-hint {
    font-size: 11px;
    text-align: center;
    justify-content: center;
    gap: 6px;
    line-height: 1.35;
  }
}

@keyframes tutFadeIn {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes tutSlideUp {
  from { opacity: 0; transform: translateY(20px) scale(0.97); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}
</style>

<script>
function openTutorialVideoModal() {
  const modal = document.getElementById('tutorialVideoModal');
  if (!modal) return;

  const iframe = document.getElementById('tutorialVideoIframe');
  if (iframe) {
    const embedSrc = iframe.getAttribute('data-src');
    if (embedSrc) {
      iframe.src = embedSrc;
    }
  }

  const video = document.getElementById('tutorialVideoNative');
  if (video) {
    video.play().catch(() => {});
  }

  modal.style.display = 'flex';
  document.body.style.overflow = 'hidden';
}

function closeTutorialVideoModal() {
  const modal = document.getElementById('tutorialVideoModal');
  if (modal) modal.style.display = 'none';

  const iframe = document.getElementById('tutorialVideoIframe');
  if (iframe) {
    iframe.src = 'about:blank';
  }

  const video = document.getElementById('tutorialVideoNative');
  if (video) {
    video.pause();
    video.currentTime = 0; // Rewind
  }

  document.body.style.overflow = '';
  // Remember dismissal for current session so it doesn't repeatedly auto-popup on every single refresh:
  try {
    sessionStorage.setItem('saaqifolio_tutorial_dismissed', '1');
  } catch (e) {}
}

function dismissTutorialTooltip() {
  const tooltip = document.getElementById('tutorialFabTooltip');
  if (tooltip) {
    tooltip.style.opacity = '0';
    tooltip.style.transform = 'translateY(-50%) scale(0.9)';
    setTimeout(function() {
      tooltip.style.display = 'none';
    }, 220);
  }
  try {
    localStorage.setItem('saaqifolio_tooltip_hidden', '1');
  } catch (e) {}
}

document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeTutorialVideoModal();
});

document.addEventListener('DOMContentLoaded', function() {
  const fab = document.getElementById('tutorialFabWrapper');
  if (fab) fab.style.display = 'flex';

  // Check if tooltip text was previously dismissed by user
  try {
    if (localStorage.getItem('saaqifolio_tooltip_hidden') === '1') {
      const tooltip = document.getElementById('tutorialFabTooltip');
      if (tooltip) tooltip.style.display = 'none';
    }
  } catch (e) {}

  // Check if tutorial modal was already dismissed in this browsing session
  let dismissed = false;
  try {
    dismissed = sessionStorage.getItem('saaqifolio_tutorial_dismissed') === '1';
  } catch (e) {}

  if (!dismissed) {
    // Graceful entrance: wait 800ms after page load so user sees page, then open tutorial
    setTimeout(function() {
      openTutorialVideoModal();
    }, 850);
  }
});
</script>
