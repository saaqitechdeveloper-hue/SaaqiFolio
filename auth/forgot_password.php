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
<title>Reset Password - Folivo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="auth-wrap">
  <div class="auth-visual">
    <div class="mark-row">
      <div class="brand-mark"></div>
      <div class="brand-name">Folivo</div>
    </div>
    <div class="auth-headline">Apna design <em>portfolio</em> minutes mein banayein</div>
    <p class="auth-sub">Apna kaam upload karein, categories mein organize karein, aur clients ko ek professional link share karein.</p>
  </div>

  <div class="auth-form-side">
    <div class="auth-card">
      <a href="login.php" class="btn btn-ghost" style="width:auto;padding:8px 14px;font-size:12.5px;margin-bottom:20px;text-decoration:none;display:inline-flex;">← Sign in par wapas</a>

      <?php if ($error): ?><div class="auth-error"><?php echo e($error); ?></div><?php endif; ?>

      <?php if ($step === 'email'): ?>
        <h3 style="font-family:var(--font-display);font-size:19px;margin:0 0 6px;">Password bhool gaye?</h3>
        <p class="muted" style="font-size:13px;line-height:1.6;margin:0 0 20px;">Apna account email likhein — hum aapko turant naya password set karne dete hain.</p>
        <form method="POST">
          <div class="field">
            <label>Email</label>
            <input type="email" name="email" placeholder="you@example.com" required>
          </div>
          <button class="btn btn-primary" type="submit" name="find_email">Continue</button>
        </form>
      <?php else: ?>
        <h3 style="font-family:var(--font-display);font-size:19px;margin:0 0 6px;">Naya password set karein</h3>
        <p class="muted" style="font-size:13px;line-height:1.6;margin:0 0 20px;"><?php echo e($_SESSION['reset_email']); ?> ke liye naya password likhein.</p>
        <form method="POST">
          <div class="field">
            <label>New password</label>
            <input type="password" name="password" placeholder="Naya password" required minlength="6">
          </div>
          <div class="field">
            <label>Confirm password</label>
            <input type="password" name="confirm" placeholder="Dobara likhein" required minlength="6">
          </div>
          <button class="btn btn-primary" type="submit" name="reset_password">Reset password</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
