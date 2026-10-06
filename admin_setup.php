<?php
/**
 * SAAQIFOLIO - One-time Admin Panel Setup
 * Run this ONCE in your browser after importing/updating database/schema.sql
 * to create your first Admin Panel login. DELETE THIS FILE after use.
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$message = '';
$done = false;

$existing = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM admins");
$existingCount = mysqli_fetch_assoc($existing)['cnt'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $existingCount == 0) {
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $fullName = mysqli_real_escape_string($conn, trim($_POST['full_name']));
    $password = $_POST['password'];

    if ($username && $fullName && strlen($password) >= 6) {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($conn, "INSERT INTO admins (username, password, full_name) VALUES (?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sss', $username, $hash, $fullName);
        mysqli_stmt_execute($stmt);
        $done = true;
    } else {
        $message = 'Please fill all fields. Password must be at least 6 characters.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Setup - SaaqiFolio</title>
<link rel="icon" type="image/svg+xml" href="assets/favicon.svg">
<link rel="alternate icon" type="image/png" href="assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-form-side" style="min-height:100vh;">
  <div class="auth-card">
    <div class="mark-row" style="display:flex;justify-content:center;align-items:center;gap:10px;margin-bottom:14px;"><div class="brand-mark"></div><div class="brand-name">SaaqiFolio</div></div>
    <h1 style="text-align:center;font-size:18px;">Admin Panel Setup</h1>

    <?php if ($existingCount > 0): ?>
      <div class="auth-error" style="margin-top:16px;">
        An admin account already exists. For security, delete this file
        (admin_setup.php) from your server now.
      </div>
      <a href="admin/login.php" class="btn btn-primary" style="display:block;text-align:center;margin-top:14px;text-decoration:none;">Go to Admin Login</a>

    <?php elseif ($done): ?>
      <div class="auth-error" style="background:#3DDC9726;color:#3DDC97;margin-top:16px;">
        Admin account created successfully! Please delete admin_setup.php
        from your server now for security.
      </div>
      <a href="admin/login.php" class="btn btn-primary" style="display:block;text-align:center;margin-top:14px;text-decoration:none;">Go to Admin Login</a>

    <?php else: ?>
      <?php if ($message): ?><div class="auth-error" style="margin-top:16px;"><?php echo e($message); ?></div><?php endif; ?>
      <form method="POST" style="margin-top:20px;">
        <div class="field">
          <label>Full Name</label>
          <input type="text" name="full_name" required>
        </div>
        <div class="field" style="margin-top:14px;">
          <label>Admin Username</label>
          <input type="text" name="username" required>
        </div>
        <div class="field" style="margin-top:14px;">
          <label>Password (min 6 characters)</label>
          <div class="password-wrap">
            <input type="password" name="password" required minlength="6">
            <button type="button" class="btn-toggle-pw" aria-label="Toggle password visibility" tabindex="-1">
              <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" style="display:none;"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
            </button>
          </div>
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:18px;">Create Admin Account</button>
      </form>
    <?php endif; ?>
  </div>
</div>
<script src="assets/js/ui.js" defer></script>
</body>
</html>
