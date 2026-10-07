<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$success = '';
$error = '';

// Handle Delete of uploaded video file
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_uploaded_video'])) {
    $existingFile = get_setting('tutorial_video_file', '');
    if ($existingFile && file_exists(__DIR__ . '/../assets/uploads/videos/' . $existingFile)) {
        @unlink(__DIR__ . '/../assets/uploads/videos/' . $existingFile);
    }
    set_setting('tutorial_video_file', '');
    $success = 'Uploaded video file was successfully removed.';
}

// Handle Save of tutorial video settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_tutorial_video'])) {
    $enabled = isset($_POST['tutorial_video_enabled']) ? '1' : '0';
    $type = in_array($_POST['tutorial_video_type'] ?? '', ['url', 'file'], true) ? $_POST['tutorial_video_type'] : 'url';
    $url = trim($_POST['tutorial_video_url'] ?? '');
    $title = trim($_POST['tutorial_video_title'] ?? '');
    $desc = trim($_POST['tutorial_video_desc'] ?? '');

    $existingFile = get_setting('tutorial_video_file', '');
    $uploadError = '';

    // Handle video file upload if file is submitted
    if (isset($_FILES['tutorial_video_file_input']) && $_FILES['tutorial_video_file_input']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploaded = handle_video_upload('tutorial_video_file_input', __DIR__ . '/../assets/uploads/videos', 100, $uploadError);
        if ($uploaded) {
            // Delete old video file if present
            if ($existingFile && $existingFile !== $uploaded[0] && file_exists(__DIR__ . '/../assets/uploads/videos/' . $existingFile)) {
                @unlink(__DIR__ . '/../assets/uploads/videos/' . $existingFile);
            }
            $existingFile = $uploaded[0];
            set_setting('tutorial_video_file', $existingFile);
        } elseif ($uploaded === false) {
            $error = $uploadError ?: 'Video upload failed. Please upload a valid MP4, WebM, or MOV file under 100MB.';
        }
    }

    // Safety: If admin selected 'file' mode but no video file exists on disk, warn and fall back
    $fileOnDisk = $existingFile && file_exists(__DIR__ . '/../assets/uploads/videos/' . $existingFile);
    if ($type === 'file' && !$fileOnDisk) {
        if (!$error) {
            $error = 'No video file found. Please upload a video file before setting mode to "Upload Video Directly".';
        }
    }

    set_setting('tutorial_video_enabled', $enabled);
    set_setting('tutorial_video_type', $type);
    set_setting('tutorial_video_url', $url);
    set_setting('tutorial_video_title', $title);
    set_setting('tutorial_video_desc', $desc);

    if (!$error) {
        $success = 'Tutorial video configuration updated successfully!';
    }
}

$videoEnabled = get_setting('tutorial_video_enabled', '1') === '1';
$videoType = get_setting('tutorial_video_type', 'url');
$videoUrl = get_setting('tutorial_video_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ');
$videoFile = get_setting('tutorial_video_file', '');
$videoTitle = get_setting('tutorial_video_title', 'SaaqiFolio Tutorial Walkthrough');
$videoDesc = get_setting('tutorial_video_desc', 'Watch this quick guide to learn how to create your portfolio, organize categories, and export client PDFs.');

$embedUrl = format_embed_video_url($videoUrl);

$siteRoot = get_site_root_url();
$videoFileWebUrl = $videoFile ? ($siteRoot . '/assets/uploads/videos/' . rawurlencode($videoFile)) : '';
$videoFileDiskPath = $videoFile ? (__DIR__ . '/../assets/uploads/videos/' . $videoFile) : '';
$videoFileExists = $videoFile && file_exists($videoFileDiskPath);
$videoFileSizeFormatted = $videoFileExists ? (round(filesize($videoFileDiskPath) / (1024 * 1024), 2) . ' MB') : '';

$activeNav = 'tutorial_video';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tutorial Video Settings — SaaqiFolio Admin</title>
<?php
$ogTitle = 'Tutorial Video Settings — SaaqiFolio Admin';
$ogDescription = 'Configure external video link or upload tutorial video for SaaqiFolio users.';
include __DIR__ . '/../includes/og_meta.php';
?>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="alternate icon" type="image/png" href="../assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
<script src="../assets/js/ui.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/ui.js'); ?>"></script>
</head>
<body>

<div class="admin-shell">
  <?php include __DIR__ . '/_navbar.php'; ?>

  <div class="admin-main">
    <div class="page-head" style="margin-bottom:28px;">
      <div>
        <div class="page-title">Project Tutorial Video</div>
        <div class="page-desc">Configure external video link (YouTube, Vimeo) or directly upload video file displayed across creator and auth pages.</div>
      </div>
      <div>
        <span class="badge-status <?php echo $videoEnabled ? 'badge-completed' : 'badge-failed'; ?>" style="font-size:12px;padding:6px 14px;">
          <?php echo $videoEnabled ? '● Video Popup ACTIVE' : '○ Video Popup DISABLED'; ?>
        </span>
      </div>
    </div>

    <?php if ($success): ?>
      <div class="alert alert-success" style="margin-bottom:24px;display:flex;align-items:center;gap:10px;background:rgba(61,220,151,0.12);border:1px solid rgba(61,220,151,0.3);color:var(--success);padding:12px 18px;border-radius:12px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="18" height="18"><path d="m9 12 2 2 4-4"/><circle cx="12" cy="12" r="10"/></svg>
        <span><?php echo e($success); ?></span>
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert alert-danger" style="margin-bottom:24px;display:flex;align-items:center;gap:10px;background:rgba(255,107,74,0.12);border:1px solid rgba(255,107,74,0.3);color:var(--accent-coral);padding:12px 18px;border-radius:12px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" width="18" height="18"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <span><?php echo e($error); ?></span>
      </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:28px;align-items:start;">
      <!-- Video Configuration Form -->
      <div class="card" style="padding:28px;">
        <h3 style="font-size:16px;margin:0 0 16px;color:var(--text-main);display:flex;align-items:center;gap:8px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" color="var(--accent-purple)"><polygon points="5 3 19 12 5 21 5 3"/></svg>
          Video Controls &amp; Settings
        </h3>

        <form method="POST" enctype="multipart/form-data" id="tutorialSettingsForm">
          <input type="hidden" name="save_tutorial_video" value="1">

          <!-- Enable / Disable Switch -->
          <div style="background:rgba(255,255,255,0.03);border:1px solid var(--glass-border);border-radius:12px;padding:16px;margin-bottom:20px;display:flex;align-items:center;justify-content:space-between;">
            <div>
              <div style="font-size:13.5px;font-weight:600;color:var(--text-main);">Enable Tutorial Video Popup</div>
              <div style="font-size:12px;color:var(--text-muted);">Displays video modal on user pages with floating glowing bulb button.</div>
            </div>
            <label style="position:relative;display:inline-block;width:48px;height:26px;cursor:pointer;">
              <input type="checkbox" name="tutorial_video_enabled" value="1" <?php echo $videoEnabled ? 'checked' : ''; ?> style="opacity:0;width:0;height:0;" id="toggleVideoCb">
              <span style="position:absolute;inset:0;background:<?php echo $videoEnabled ? 'var(--accent-purple)' : 'rgba(255,255,255,0.15)'; ?>;border-radius:26px;transition:0.3s;" id="toggleSlider">
                <span style="position:absolute;top:3px;left:<?php echo $videoEnabled ? '25px' : '3px'; ?>;width:20px;height:20px;border-radius:50%;background:#fff;transition:0.3s;" id="toggleKnob"></span>
              </span>
            </label>
          </div>

          <!-- Video Source Selector (Tabs) -->
          <div style="margin-bottom:18px;">
            <label style="font-size:12.5px;font-weight:600;margin-bottom:8px;display:block;">Choose Video Source</label>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;background:rgba(0,0,0,0.3);padding:6px;border-radius:12px;border:1px solid var(--glass-border);">
              <label class="source-tab-btn <?php echo $videoType === 'url' ? 'active' : ''; ?>" id="tabBtnUrl" onclick="switchVideoSource('url')">
                <input type="radio" name="tutorial_video_type" value="url" <?php echo $videoType === 'url' ? 'checked' : ''; ?> style="display:none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                <span>External Video Link</span>
              </label>

              <label class="source-tab-btn <?php echo $videoType === 'file' ? 'active' : ''; ?>" id="tabBtnFile" onclick="switchVideoSource('file')">
                <input type="radio" name="tutorial_video_type" value="file" <?php echo $videoType === 'file' ? 'checked' : ''; ?> style="display:none;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                <span>Upload Video Directly</span>
              </label>
            </div>
          </div>

          <!-- Section A: External URL -->
          <div id="sectionUrl" style="display:<?php echo $videoType === 'url' ? 'block' : 'none'; ?>;margin-bottom:18px;">
            <div class="field">
              <label style="font-size:12.5px;font-weight:600;margin-bottom:6px;display:block;">Video Link (YouTube, Vimeo, or direct MP4 URL)</label>
              <input type="url" name="tutorial_video_url" class="form-input" style="width:100%;padding:10px 14px;border-radius:10px;background:var(--bg-surface);border:1px solid var(--glass-border);color:var(--text-main);" placeholder="e.g. https://www.youtube.com/watch?v=..." value="<?php echo e($videoUrl); ?>">
              <span style="font-size:11.5px;color:var(--text-muted);margin-top:5px;display:block;">Supports standard YouTube, youtu.be shortlinks, Vimeo, or MP4 cloud links.</span>
            </div>
          </div>

          <!-- Section B: Direct Video File Upload -->
          <div id="sectionFile" style="display:<?php echo $videoType === 'file' ? 'block' : 'none'; ?>;margin-bottom:18px;">
            <?php if ($videoFileExists): ?>
              <div style="background:rgba(124,92,252,0.08);border:1px solid rgba(124,92,252,0.3);border-radius:12px;padding:14px;margin-bottom:14px;">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
                  <div style="display:flex;align-items:center;gap:10px;min-width:0;">
                    <div style="width:36px;height:36px;border-radius:8px;background:var(--grad-primary);display:flex;align-items:center;justify-content:center;color:#fff;flex-shrink:0;">
                      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><polygon points="5 3 19 12 5 21 5 3"/></svg>
                    </div>
                    <div style="min-width:0;">
                      <div style="font-size:13px;font-weight:600;color:var(--text-main);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?php echo e($videoFile); ?>">
                        <?php echo e($videoFile); ?>
                      </div>
                      <div style="font-size:11px;color:var(--accent-purple-light);">
                        Stored &bull; <?php echo e($videoFileSizeFormatted); ?>
                      </div>
                    </div>
                  </div>
                  <button type="button" class="btn btn-ghost btn-sm" onclick="if(confirm('Are you sure you want to delete this uploaded video file?')) document.getElementById('deleteVideoForm').submit();" style="color:var(--accent-coral);border-color:rgba(255,107,74,0.3);width:auto;flex-shrink:0;">
                    Delete
                  </button>
                </div>
              </div>
            <?php endif; ?>

            <div class="field">
              <label style="font-size:12.5px;font-weight:600;margin-bottom:6px;display:block;">
                <?php echo $videoFileExists ? 'Replace Uploaded Video File (MP4, WebM, MOV)' : 'Upload Video File (MP4, WebM, MOV)'; ?>
              </label>
              <input type="file" name="tutorial_video_file_input" accept="video/mp4,video/webm,video/quicktime,video/ogg" class="form-input" style="width:100%;padding:10px 14px;border-radius:10px;background:var(--bg-surface);border:1px dashed var(--glass-border);color:var(--text-main);">
              <span style="font-size:11.5px;color:var(--text-muted);margin-top:5px;display:block;">Max file size: 100MB. Recommended format: .mp4 (H.264 / AAC) for best browser compatibility.</span>
            </div>
          </div>

          <div class="field" style="margin-bottom:18px;">
            <label style="font-size:12.5px;font-weight:600;margin-bottom:6px;display:block;">Modal Title</label>
            <input type="text" name="tutorial_video_title" class="form-input" style="width:100%;padding:10px 14px;border-radius:10px;background:var(--bg-surface);border:1px solid var(--glass-border);color:var(--text-main);" placeholder="e.g. SaaqiFolio Tutorial Walkthrough" value="<?php echo e($videoTitle); ?>">
          </div>

          <div class="field" style="margin-bottom:22px;">
            <label style="font-size:12.5px;font-weight:600;margin-bottom:6px;display:block;">Modal Description</label>
            <textarea name="tutorial_video_desc" rows="3" class="form-input" style="width:100%;padding:10px 14px;border-radius:10px;background:var(--bg-surface);border:1px solid var(--glass-border);color:var(--text-main);resize:vertical;" placeholder="Brief explanation for viewers"><?php echo e($videoDesc); ?></textarea>
          </div>

          <div style="display:flex;gap:12px;">
            <button type="submit" class="btn btn-primary" style="flex:1;">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
              <span>Save Video Settings</span>
            </button>
          </div>
        </form>

        <!-- Hidden form for deleting uploaded video file -->
        <form method="POST" id="deleteVideoForm" style="display:none;">
          <input type="hidden" name="delete_uploaded_video" value="1">
        </form>
      </div>

      <!-- Live Preview Card -->
      <div class="card" style="padding:28px;">
        <h3 style="font-size:16px;margin:0 0 16px;color:var(--text-main);display:flex;align-items:center;gap:8px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18" color="var(--accent-coral)"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
          Live Modal Video Preview
        </h3>

        <?php if ($videoType === 'file' && $videoFileExists): ?>
          <div style="position:relative;width:100%;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:12px;border:1px solid var(--glass-border);background:#000;margin-bottom:16px;">
            <video controls playsinline preload="metadata" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:contain;">
              <source src="<?php echo e($videoFileWebUrl); ?>" type="video/mp4">
              Your browser does not support HTML5 video tag.
            </video>
          </div>
          <div style="font-size:14px;font-weight:600;color:var(--text-main);margin-bottom:4px;"><?php echo e($videoTitle); ?></div>
          <div style="font-size:12px;color:var(--text-muted);line-height:1.5;"><?php echo e($videoDesc); ?></div>
        <?php elseif ($videoType === 'url' && $embedUrl): ?>
          <div style="position:relative;padding-bottom:56.25%;height:0;overflow:hidden;border-radius:12px;border:1px solid var(--glass-border);background:#000;margin-bottom:16px;">
            <iframe src="<?php echo e($embedUrl); ?>" style="position:absolute;top:0;left:0;width:100%;height:100%;border:none;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
          </div>
          <div style="font-size:14px;font-weight:600;color:var(--text-main);margin-bottom:4px;"><?php echo e($videoTitle); ?></div>
          <div style="font-size:12px;color:var(--text-muted);line-height:1.5;"><?php echo e($videoDesc); ?></div>
        <?php else: ?>
          <div style="padding:48px 20px;text-align:center;background:rgba(255,255,255,0.02);border:1px dashed var(--glass-border);border-radius:12px;color:var(--text-muted);">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="40" height="40" style="margin:0 auto 12px;opacity:0.4;"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" x2="10"/></svg>
            <div>No active video preview available.</div>
            <div style="font-size:11.5px;margin-top:4px;">
              <?php echo $videoType === 'file' ? 'Upload an MP4/WebM file on the left to activate preview.' : 'Enter a YouTube or Vimeo link on the left to activate preview.'; ?>
            </div>
          </div>
        <?php endif; ?>

        <!-- Visual demonstration of the Glowing Bulb floating button -->
        <div style="margin-top:24px;padding-top:20px;border-top:1px solid var(--glass-border);">
          <div style="font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.04em;margin-bottom:12px;">Floating Bulb Button Preview</div>
          <div style="display:flex;align-items:center;gap:16px;background:rgba(0,0,0,0.25);padding:14px 18px;border-radius:12px;border:1px solid rgba(255,255,255,0.05);">
            <div class="tutorial-bulb-btn-demo">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="20" height="20"><polygon points="5 3 19 12 5 21 5 3"/></svg>
            </div>
            <div>
              <div style="font-size:12.5px;font-weight:600;color:var(--text-main);">Pulsing Bulb Button with Tooltip</div>
              <div style="font-size:11px;color:var(--text-muted);">Hovering displays "Watch Project Tutorial" and clicking re-opens the video modal anytime.</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<style>
.source-tab-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 8px;
  cursor: pointer;
  font-size: 12.5px;
  font-weight: 600;
  color: var(--text-muted);
  transition: all 0.2s;
  user-select: none;
}
.source-tab-btn.active {
  background: var(--grad-primary);
  color: #fff;
  box-shadow: 0 4px 14px rgba(124, 92, 252, 0.4);
}
.tutorial-bulb-btn-demo {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: linear-gradient(135deg, #7c5cfc, #ff6b4a);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 0 20px rgba(124, 92, 252, 0.6), 0 0 40px rgba(255, 107, 74, 0.4);
  animation: bulbPulse 2s infinite;
}
@keyframes bulbPulse {
  0% { transform: scale(1); box-shadow: 0 0 10px rgba(124, 92, 252, 0.5), 0 0 20px rgba(255, 107, 74, 0.3); }
  50% { transform: scale(1.08); box-shadow: 0 0 25px rgba(124, 92, 252, 0.9), 0 0 50px rgba(255, 107, 74, 0.7); }
  100% { transform: scale(1); box-shadow: 0 0 10px rgba(124, 92, 252, 0.5), 0 0 20px rgba(255, 107, 74, 0.3); }
}
</style>

<script>
function switchVideoSource(type) {
  const tabUrl = document.getElementById('tabBtnUrl');
  const tabFile = document.getElementById('tabBtnFile');
  const secUrl = document.getElementById('sectionUrl');
  const secFile = document.getElementById('sectionFile');
  const radioUrl = document.querySelector('input[name="tutorial_video_type"][value="url"]');
  const radioFile = document.querySelector('input[name="tutorial_video_type"][value="file"]');

  if (type === 'url') {
    tabUrl.classList.add('active');
    tabFile.classList.remove('active');
    secUrl.style.display = 'block';
    secFile.style.display = 'none';
    if (radioUrl) radioUrl.checked = true;
  } else {
    tabFile.classList.add('active');
    tabUrl.classList.remove('active');
    secFile.style.display = 'block';
    secUrl.style.display = 'none';
    if (radioFile) radioFile.checked = true;
  }
}

const cb = document.getElementById('toggleVideoCb');
const slider = document.getElementById('toggleSlider');
const knob = document.getElementById('toggleKnob');
if (cb && slider && knob) {
  cb.addEventListener('change', () => {
    slider.style.background = cb.checked ? 'var(--accent-purple)' : 'rgba(255,255,255,0.15)';
    knob.style.left = cb.checked ? '25px' : '3px';
  });
}
</script>
</body>
</html>
