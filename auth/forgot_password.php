<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/profile.php');
    exit;
}

$error = '';
$step = $_SESSION['reset_email'] ?? null ? 'reset' : 'email';

// Step 1: submit email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['find_email'])) {
    $email = clean($conn, strtolower($_POST['email']));
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);

    if (mysqli_stmt_get_result($stmt)->num_rows > 0) {
        $_SESSION['reset_email'] = $email;
        $step = 'reset';
    } else {
        $error = 'No account found with this email.';
        $step = 'email';
    }
}

// Step 2: set new password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $email = $_SESSION['reset_email'] ?? null;
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];

    if (!$email) {
        header('Location: forgot_password.php');
        exit;
    }

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
        $step = 'reset';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
        $step = 'reset';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 'ss', $hash, $email);
        mysqli_stmt_execute($stmt);
        unset($_SESSION['reset_email']);
        header('Location: login.php?reset=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - SaaqiFolio</title>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="alternate icon" type="image/png" href="../assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<script src="../assets/js/ui.js" defer></script>
</head>
<body>

<div class="auth-wrap">
  <div class="auth-visual">
    <div class="mark-row">
      <div class="brand-mark"></div>
      <div class="brand-name">SaaqiFolio</div>
    </div>
    <div class="auth-headline">Build your creative <em>portfolio</em> in minutes</div>
    <p class="auth-sub">Upload your work, organize with smart categories, and share a sleek, professional link with clients worldwide.</p>
  </div>

  <div class="auth-form-side">
    <div class="auth-card">
      <a href="login.php" class="btn btn-ghost" style="width:auto;padding:8px 14px;font-size:12.5px;margin-bottom:20px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
        <span>Back to sign in</span>
      </a>

      <?php if ($error): ?><div class="auth-error"><?php echo e($error); ?></div><?php endif; ?>

      <?php if ($step === 'email'): ?>
        <h3 style="font-family:var(--font-display);font-size:19px;margin:0 0 6px;">Forgot password?</h3>
        <p class="muted" style="font-size:13px;line-height:1.6;margin:0 0 20px;">Enter your account email address and we'll help you reset your password right away.</p>
        <form method="POST">
          <div class="field">
            <label>Email</label>
            <input type="email" name="email" placeholder="you@example.com" required>
          </div>
          <button class="btn btn-primary" type="submit" name="find_email">Continue</button>
        </form>
      <?php else: ?>
        <h3 style="font-family:var(--font-display);font-size:19px;margin:0 0 6px;">Set a new password</h3>
        <p class="muted" style="font-size:13px;line-height:1.6;margin:0 0 20px;">Enter a secure new password for <?php echo e($_SESSION['reset_email']); ?>.</p>
        <form method="POST">
          <div class="field">
            <label>New password</label>
            <div class="password-wrap">
              <input type="password" name="password" placeholder="New password" required minlength="6">
              <button type="button" class="btn-toggle-pw" aria-label="Toggle password visibility" tabindex="-1">
                <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" style="display:none;"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
              </button>
            </div>
          </div>
          <div class="field">
            <label>Confirm password</label>
            <div class="password-wrap">
              <input type="password" name="confirm" placeholder="Confirm new password" required minlength="6">
              <button type="button" class="btn-toggle-pw" aria-label="Toggle password visibility" tabindex="-1">
                <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" style="display:none;"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
              </button>
            </div>
          </div>
          <button class="btn btn-primary" type="submit" name="reset_password">Reset password</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
