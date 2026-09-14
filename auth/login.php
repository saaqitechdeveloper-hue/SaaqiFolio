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
        header('Location: ../dashboard/profile.php');
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
<title>Sign In - Folivo</title>
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
      <div class="auth-tabs">
        <button class="auth-tab active" type="button">Sign in</button>
        <a href="signup.php" class="auth-tab" style="text-decoration:none;display:flex;align-items:center;justify-content:center;">Create account</a>
      </div>

      <?php if ($error): ?><div class="auth-error"><?php echo e($error); ?></div><?php endif; ?>

      <form method="POST">
        <div class="field">
          <label>Email</label>
          <input type="email" name="email" placeholder="you@example.com" required value="<?php echo e($_POST['email'] ?? ''); ?>">
        </div>
        <div class="field">
          <label>Password</label>
          <input type="password" name="password" placeholder="Your password" required>
        </div>
        <button class="btn btn-primary" type="submit">Sign in</button>
      </form>
      <div style="text-align:center;margin-top:14px;">
        <a href="forgot_password.php" class="upgrade-link" style="margin:0;display:inline;text-decoration:none;">Password bhool gaye?</a>
      </div>
    </div>
  </div>
</div>

</body>
</html>
