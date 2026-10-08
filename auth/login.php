<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/profile.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean($conn, strtolower($_POST['email']));
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $user = mysqli_stmt_get_result($stmt)->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header('Location: ../dashboard/profile');
        exit;
    } else {
        $error = 'Incorrect email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sign In - SaaqiFolio</title>
<?php
$ogTitle = 'Sign In — SaaqiFolio';
$ogDescription = 'Sign in to SaaqiFolio to manage your portfolio, organize creative categories, and export client PDFs.';
include __DIR__ . '/../includes/og_meta.php';
?>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="alternate icon" type="image/png" href="../assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
<?php include __DIR__ . '/../includes/theme_head.php'; ?>
<script src="../assets/js/ui.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/ui.js'); ?>"></script>
</head>
<body>

<!-- Top Floating Theme Switcher -->
<div class="auth-top-bar">
  <a href="../" class="auth-top-brand" title="SaaqiFolio Home">
    <div class="brand-mark-sm">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="13" height="13"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
    </div>
    <span>SaaqiFolio</span>
  </a>
  <div class="auth-top-actions">
    <?php include __DIR__ . '/../includes/theme_switcher.php'; ?>
  </div>
</div>

<div class="auth-wrap">
  <div class="auth-visual">
    <div class="mark-row">
      <div class="brand-mark-glow">
        <div class="brand-mark">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
        </div>
      </div>
      <div class="brand-name">SaaqiFolio</div>
    </div>
    <div class="auth-headline">Build your creative <em>portfolio</em> in minutes</div>
    <p class="auth-sub">Upload your work, organize with smart categories, and share a sleek, professional link with clients worldwide.</p>
  </div>

  <div class="auth-form-side">
    <div class="auth-card">
      <div class="auth-tabs">
        <button class="auth-tab active" type="button">Sign in</button>
        <a href="signup" class="auth-tab" style="text-decoration:none;display:flex;align-items:center;justify-content:center;">Create account</a>
      </div>

      <?php if (isset($_GET['reset']) && $_GET['reset'] === '1'): ?>
        <div class="auth-success">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          <span>Password reset successfully! Please sign in with your new password.</span>
        </div>
      <?php endif; ?>

      <?php if ($error): ?><div class="auth-error"><?php echo e($error); ?></div><?php endif; ?>

      <form method="POST">
        <div class="field">
          <label>Email</label>
          <input type="email" name="email" placeholder="you@example.com" required value="<?php echo e($_POST['email'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Password</label>
          <div class="password-wrap">
            <input type="password" name="password" placeholder="Your password" required>
            <button type="button" class="btn-toggle-pw" aria-label="Toggle password visibility" tabindex="-1">
              <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" style="display:none;"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
            </button>
          </div>
        </div>
        <button class="btn btn-primary" type="submit">Sign in</button>
      </form>
      <div style="text-align:center;margin-top:14px;">
        <a href="forgot_password" class="upgrade-link" style="margin:0;display:inline;text-decoration:none;">Forgot password?</a>
      </div>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/whatsapp_btn.php'; ?>
<?php include __DIR__ . '/../includes/tutorial_video_modal.php'; ?>
</body>
</html>
