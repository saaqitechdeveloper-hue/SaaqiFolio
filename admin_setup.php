<?php
/**
 * FOLIVO - One-time Admin Panel Setup
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
<title>Admin Setup - Folivo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-form-side" style="min-height:100vh;">
  <div class="auth-card">
    <div class="mark-row" style="display:flex;justify-content:center;align-items:center;gap:10px;margin-bottom:14px;"><div class="brand-mark"></div><div class="brand-name">Folivo</div></div>
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
          <input type="password" name="password" required minlength="6">
        </div>
        <button type="submit" class="btn btn-primary" style="margin-top:18px;">Create Admin Account</button>
      </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
