<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard');
    exit;
}

$adminCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM admins"))['cnt'];
if ($adminCount == 0) {
    header('Location: ../admin_setup');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($conn, $_POST['username']);
    $password = $_POST['password'];

    $stmt = mysqli_prepare($conn, "SELECT * FROM admins WHERE username = ?");
    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $admin = mysqli_stmt_get_result($stmt)->fetch_assoc();

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_name'] = $admin['full_name'];
        header('Location: dashboard');
        exit;
    } else {
        $error = 'Incorrect username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login - SaaqiFolio</title>
<?php
$ogTitle = 'Super Admin Login — SaaqiFolio';
$ogDescription = 'Secure administrative access portal for SaaqiFolio.';
include __DIR__ . '/../includes/og_meta.php';
?>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="alternate icon" type="image/png" href="../assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
<?php include __DIR__ . '/../includes/theme_head.php'; ?>
<script src="../assets/js/ui.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/ui.js'); ?>"></script>
</head>
<body>
<div class="auth-top-bar">
  <div class="auth-top-actions">
    <?php include __DIR__ . '/../includes/theme_switcher.php'; ?>
  </div>
</div>
<div class="auth-form-side" style="min-height:100vh;">
  <div class="auth-card">
    <div class="mark-row" style="display:flex;justify-content:center;align-items:center;gap:10px;margin-bottom:20px;">
      <div class="brand-mark-glow">
        <div class="brand-mark">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
        </div>
      </div>
      <div class="brand-name">SaaqiFolio <span style="color:var(--text-muted);font-weight:400;font-size:14px;">Admin</span></div>
    </div>

    <?php if ($error): ?><div class="auth-error"><?php echo e($error); ?></div><?php endif; ?>

    <form method="POST">
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" required autofocus>
      </div>
      <div class="field" style="margin-top:14px;">
        <label>Password</label>
        <div class="password-wrap">
          <input type="password" name="password" required>
          <button type="button" class="btn-toggle-pw" aria-label="Toggle password visibility" tabindex="-1">
            <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" style="display:none;"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
          </button>
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:18px;">Sign in to Admin Panel</button>
    </form>
  </div>
</div>
</body>
</html>
