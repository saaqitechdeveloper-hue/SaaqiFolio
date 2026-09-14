<?php
session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
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
        header('Location: dashboard.php');
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
<title>Admin Login - Folivo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-form-side" style="min-height:100vh;">
  <div class="auth-card">
    <div class="mark-row" style="display:flex;justify-content:center;align-items:center;gap:10px;margin-bottom:20px;"><div class="brand-mark"></div><div class="brand-name">Folivo <span style="color:var(--text-muted);font-weight:400;font-size:14px;">Admin</span></div></div>

    <?php if ($error): ?><div class="auth-error"><?php echo e($error); ?></div><?php endif; ?>

    <form method="POST">
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" required autofocus>
      </div>
      <div class="field" style="margin-top:14px;">
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="margin-top:18px;">Sign in to Admin Panel</button>
    </form>
  </div>
</div>
</body>
</html>
