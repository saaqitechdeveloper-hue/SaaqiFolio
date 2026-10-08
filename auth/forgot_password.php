<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard/profile');
    exit;
}

// Action: restart reset process
if (isset($_GET['action']) && $_GET['action'] === 'restart') {
    unset($_SESSION['reset_email'], $_SESSION['reset_step'], $_SESSION['reset_otp_sent_at'], $_SESSION['reset_verified']);
    header('Location: forgot_password');
    exit;
}

$error = '';
$success = '';

// Determine current step
if (!empty($_SESSION['reset_verified']) && !empty($_SESSION['reset_email'])) {
    $step = 'new_password';
} elseif (!empty($_SESSION['reset_email']) && ($_SESSION['reset_step'] ?? '') === 'verify_otp') {
    $step = 'verify_otp';
} else {
    $step = 'email';
}

// -------------------------------------------------------------
// STEP 1: Find email, verify account exists, send 4-digit OTP
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_otp'])) {
    $email = clean($conn, strtolower(trim($_POST['email'] ?? '')));

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
        $step = 'email';
    } else {
        // Strictly check if account exists in database first
        $stmt = mysqli_prepare($conn, "SELECT id, name FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $user = $res ? mysqli_fetch_assoc($res) : null;

        if (!$user) {
            $error = 'No account found with this email. Please verify your address or create an account.';
            $step = 'email';
        } else {
            // Account exists! Generate a secure 4-digit OTP
            $otp = strval(random_int(1000, 9999));

            // Invalidate any existing unused OTPs for this email
            $invalStmt = mysqli_prepare($conn, "UPDATE password_resets SET is_used = 1 WHERE email = ? AND is_used = 0");
            mysqli_stmt_bind_param($invalStmt, 's', $email);
            mysqli_stmt_execute($invalStmt);

            // Insert new OTP with 5 minute expiration
            $insStmt = mysqli_prepare($conn, "INSERT INTO password_resets (email, otp, expires_at, is_used) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0)");
            mysqli_stmt_bind_param($insStmt, 'ss', $email, $otp);
            mysqli_stmt_execute($insStmt);

            // Send via PHPMailer SMTP
            $userName = !empty($user['name']) ? $user['name'] : 'Creator';
            $mailResult = send_password_reset_otp($email, $otp, $userName);

            if ($mailResult['success']) {
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_step'] = 'verify_otp';
                $_SESSION['reset_otp_sent_at'] = time();
                $_SESSION['reset_verified'] = false;
                $step = 'verify_otp';
                $success = 'A 4-digit verification code has been sent to ' . htmlspecialchars($email) . '. It is valid for 5 minutes.';
            } else {
                $error = 'Failed to send verification email. ' . ($mailResult['error'] ? htmlspecialchars($mailResult['error']) : 'Please try again.');
                $step = 'email';
            }
        }
    }
}

// -------------------------------------------------------------
// STEP 2: Verify 4-digit OTP
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_otp'])) {
    $email = $_SESSION['reset_email'] ?? null;
    if (!$email) {
        header('Location: forgot_password');
        exit;
    }

    // Support both 4 individual digit boxes and combined input
    $otp = '';
    if (isset($_POST['digits']) && is_array($_POST['digits'])) {
        $otp = implode('', array_slice($_POST['digits'], 0, 4));
    } elseif (isset($_POST['otp'])) {
        $otp = trim($_POST['otp']);
    }

    if (strlen($otp) !== 4 || !ctype_digit($otp)) {
        $error = 'Please enter a valid 4-digit verification code.';
        $step = 'verify_otp';
    } else {
        // Look up OTP record
        $stmt = mysqli_prepare($conn, "SELECT id, expires_at FROM password_resets WHERE email = ? AND otp = ? AND is_used = 0 ORDER BY id DESC LIMIT 1");
        mysqli_stmt_bind_param($stmt, 'ss', $email, $otp);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $record = $res ? mysqli_fetch_assoc($res) : null;

        if (!$record) {
            $error = 'Invalid verification code. Please check your email and try again.';
            $step = 'verify_otp';
        } elseif (strtotime($record['expires_at']) < time()) {
            $error = 'This verification code has expired (valid for 5 minutes). Please request a new code.';
            $step = 'verify_otp';
        } else {
            // Mark code as used
            $markStmt = mysqli_prepare($conn, "UPDATE password_resets SET is_used = 1 WHERE id = ?");
            mysqli_stmt_bind_param($markStmt, 'i', $record['id']);
            mysqli_stmt_execute($markStmt);

            $_SESSION['reset_verified'] = true;
            $_SESSION['reset_step'] = 'new_password';
            $step = 'new_password';
            $success = 'Code verified successfully! Now choose your new password.';
        }
    }
}

// -------------------------------------------------------------
// STEP 2 (Sub): Resend 4-digit OTP (after 5 minutes cooldown)
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend_otp'])) {
    $email = $_SESSION['reset_email'] ?? null;
    if (!$email) {
        header('Location: forgot_password');
        exit;
    }

    $timePassed = time() - ($_SESSION['reset_otp_sent_at'] ?? 0);
    $cooldown = 300; // 5 minutes (300 seconds)

    if ($timePassed < $cooldown) {
        $remaining = $cooldown - $timePassed;
        $mins = ceil($remaining / 60);
        $error = "Please wait {$mins} minute(s) before requesting a new code.";
        $step = 'verify_otp';
    } else {
        // Fetch user name
        $stmt = mysqli_prepare($conn, "SELECT name FROM users WHERE email = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $email);
        mysqli_stmt_execute($stmt);
        $uRes = mysqli_stmt_get_result($stmt);
        $uRow = $uRes ? mysqli_fetch_assoc($uRes) : null;
        $userName = !empty($uRow['name']) ? $uRow['name'] : 'Creator';

        // Generate new 4-digit OTP
        $otp = strval(random_int(1000, 9999));

        // Invalidate old OTPs
        $invalStmt = mysqli_prepare($conn, "UPDATE password_resets SET is_used = 1 WHERE email = ? AND is_used = 0");
        mysqli_stmt_bind_param($invalStmt, 's', $email);
        mysqli_stmt_execute($invalStmt);

        // Insert new OTP with 5 minute expiration
        $insStmt = mysqli_prepare($conn, "INSERT INTO password_resets (email, otp, expires_at, is_used) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0)");
        mysqli_stmt_bind_param($insStmt, 'ss', $email, $otp);
        mysqli_stmt_execute($insStmt);

        $mailResult = send_password_reset_otp($email, $otp, $userName);
        if ($mailResult['success']) {
            $_SESSION['reset_otp_sent_at'] = time();
            $success = 'A new 4-digit code has been sent to ' . htmlspecialchars($email) . '. It is valid for 5 minutes.';
        } else {
            $error = 'Failed to resend verification email. Please try again.';
        }
        $step = 'verify_otp';
    }
}

// -------------------------------------------------------------
// STEP 3: Set new password
// -------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_new_password'])) {
    $email = $_SESSION['reset_email'] ?? null;
    $verified = $_SESSION['reset_verified'] ?? false;

    if (!$email || !$verified) {
        header('Location: forgot_password');
        exit;
    }

    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
        $step = 'new_password';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match. Please re-enter.';
        $step = 'new_password';
    } else {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE email = ?");
        mysqli_stmt_bind_param($stmt, 'ss', $hash, $email);
        mysqli_stmt_execute($stmt);

        // Clean up reset session
        unset($_SESSION['reset_email'], $_SESSION['reset_step'], $_SESSION['reset_otp_sent_at'], $_SESSION['reset_verified']);

        header('Location: login?reset=1');
        exit;
    }
}

// Calculate remaining cooldown for Step 2
$timePassed = time() - ($_SESSION['reset_otp_sent_at'] ?? 0);
$remainingCooldown = max(0, 300 - $timePassed);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - SaaqiFolio</title>
<?php
$ogTitle = 'Reset Password — SaaqiFolio';
$ogDescription = 'Recover or reset your SaaqiFolio account password safely.';
include __DIR__ . '/../includes/og_meta.php';
?>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="alternate icon" type="image/png" href="../assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
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
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <a href="login" class="btn btn-ghost" style="width:auto;padding:8px 14px;font-size:12.5px;text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
          <span>Back to sign in</span>
        </a>
        <?php if ($step !== 'email'): ?>
          <a href="forgot_password?action=restart" class="btn btn-ghost" style="width:auto;padding:8px 12px;font-size:12px;color:var(--text-muted);text-decoration:none;" title="Start over with a different email">
            Use different email
          </a>
        <?php endif; ?>
      </div>

      <?php if ($error): ?>
        <div class="auth-error">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          <span><?php echo e($error); ?></span>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="auth-success">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          <span><?php echo e($success); ?></span>
        </div>
      <?php endif; ?>

      <?php if ($step === 'email'): ?>
        <!-- =================== STEP 1: EMAIL =================== -->
        <h3 style="font-family:var(--font-display);font-size:20px;margin:0 0 6px;">Forgot password?</h3>
        <p class="muted" style="font-size:13px;line-height:1.6;margin:0 0 20px;">
          Enter your account email. We'll verify your account and send a 4-digit code valid for 5 minutes.
        </p>

        <form method="POST">
          <div class="field">
            <label>Email address</label>
            <input type="email" name="email" placeholder="you@example.com" required autofocus value="<?php echo e($_POST['email'] ?? ''); ?>">
          </div>
          <button class="btn btn-primary" type="submit" name="send_otp">
            <span>Send 4-Digit Code</span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
          </button>
        </form>

      <?php elseif ($step === 'verify_otp'): ?>
        <!-- =================== STEP 2: VERIFY OTP =================== -->
        <h3 style="font-family:var(--font-display);font-size:20px;margin:0 0 6px;">Enter 4-Digit Code</h3>
        <p class="muted" style="font-size:13px;line-height:1.6;margin:0 0 18px;">
          We sent a code to <strong style="color:#ffffff;"><?php echo e($_SESSION['reset_email']); ?></strong>. Enter the 4 digits to continue.
        </p>

        <form method="POST" id="otpForm">
          <div class="otp-inputs-row">
            <input type="text" name="digits[]" class="otp-digit-box" maxlength="1" pattern="[0-9]" inputmode="numeric" autocomplete="one-time-code" required autofocus>
            <input type="text" name="digits[]" class="otp-digit-box" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
            <input type="text" name="digits[]" class="otp-digit-box" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
            <input type="text" name="digits[]" class="otp-digit-box" maxlength="1" pattern="[0-9]" inputmode="numeric" required>
          </div>

          <button class="btn btn-primary" type="submit" name="verify_otp" id="btnVerifyOtp">
            <span>Verify & Continue</span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </button>
        </form>

        <!-- Resend OTP Row with 5-minute countdown -->
        <div class="otp-meta-row">
          <div class="otp-timer" id="timerContainer">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <span id="timerText">Code expires in <strong id="countdownClock" style="color:#a78bfa;">05:00</strong></span>
          </div>

          <form method="POST" style="margin:0;display:inline;">
            <button type="submit" name="resend_otp" id="resendBtn" class="btn-resend-otp" <?php echo ($remainingCooldown > 0) ? 'disabled' : ''; ?>>
              <span>Resend OTP</span>
            </button>
          </form>
        </div>

        <script>
        (function() {
          const boxes = Array.from(document.querySelectorAll('.otp-digit-box'));
          const form = document.getElementById('otpForm');
          const resendBtn = document.getElementById('resendBtn');
          const countdownClock = document.getElementById('countdownClock');
          const timerText = document.getElementById('timerText');
          let remainingSeconds = <?php echo (int)$remainingCooldown; ?>;

          // Auto-focus first box
          if (boxes.length && boxes[0]) {
            boxes[0].focus();
          }

          // Handle single-digit input & navigation
          boxes.forEach((box, index) => {
            box.addEventListener('input', (e) => {
              const val = e.target.value.replace(/[^0-9]/g, '');
              e.target.value = val ? val.charAt(0) : '';

              if (e.target.value && index < boxes.length - 1) {
                boxes[index + 1].focus();
                boxes[index + 1].select();
              }
            });

            box.addEventListener('keydown', (e) => {
              if (e.key === 'Backspace' && !box.value && index > 0) {
                boxes[index - 1].focus();
                boxes[index - 1].value = '';
              } else if (e.key === 'ArrowLeft' && index > 0) {
                boxes[index - 1].focus();
              } else if (e.key === 'ArrowRight' && index < boxes.length - 1) {
                boxes[index + 1].focus();
              }
            });

            // Handle Paste: "7391"
            box.addEventListener('paste', (e) => {
              e.preventDefault();
              const pasted = (e.clipboardData || window.clipboardData).getData('text');
              const digits = pasted.replace(/[^0-9]/g, '').slice(0, 4);
              if (digits.length > 0) {
                digits.split('').forEach((d, i) => {
                  if (boxes[i]) boxes[i].value = d;
                });
                const focusIdx = Math.min(digits.length, boxes.length - 1);
                boxes[focusIdx].focus();
              }
            });
          });

          // 5-Minute Timer Countdown
          function tickTimer() {
            if (remainingSeconds <= 0) {
              if (resendBtn) {
                resendBtn.disabled = false;
              }
              if (timerText) {
                timerText.innerHTML = '<span style="color:#f43f5e;">Code expired.</span>';
              }
              return;
            }

            const m = String(Math.floor(remainingSeconds / 60)).padStart(2, '0');
            const s = String(remainingSeconds % 60).padStart(2, '0');
            if (countdownClock) {
              countdownClock.textContent = `${m}:${s}`;
            }

            remainingSeconds--;
            setTimeout(tickTimer, 1000);
          }

          tickTimer();
        })();
        </script>

      <?php else: ?>
        <!-- =================== STEP 3: NEW PASSWORD =================== -->
        <h3 style="font-family:var(--font-display);font-size:20px;margin:0 0 6px;">Set New Password</h3>
        <p class="muted" style="font-size:13px;line-height:1.6;margin:0 0 20px;">
          Enter a secure new password for <strong style="color:#ffffff;"><?php echo e($_SESSION['reset_email']); ?></strong>.
        </p>

        <form method="POST">
          <div class="field">
            <label>New password</label>
            <div class="password-wrap">
              <input type="password" name="password" placeholder="At least 6 characters" required minlength="6" autofocus>
              <button type="button" class="btn-toggle-pw" aria-label="Toggle password visibility" tabindex="-1">
                <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" style="display:none;"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
              </button>
            </div>
          </div>
          <div class="field">
            <label>Confirm new password</label>
            <div class="password-wrap">
              <input type="password" name="confirm" placeholder="Re-enter new password" required minlength="6">
              <button type="button" class="btn-toggle-pw" aria-label="Toggle password visibility" tabindex="-1">
                <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" style="display:none;"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
              </button>
            </div>
          </div>
          <button class="btn btn-primary" type="submit" name="set_new_password">
            <span>Reset Password & Sign In</span>
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/tutorial_video_modal.php'; ?>
</body>
</html>
