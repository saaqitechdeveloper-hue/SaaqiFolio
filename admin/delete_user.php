<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$user = mysqli_stmt_get_result($stmt)->fetch_assoc();

if (!$user) { die('User not found.'); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
    // Clean up this user's uploaded files before removing the DB row.
    if ($user['avatar'] && file_exists(__DIR__ . '/../assets/uploads/avatars/' . $user['avatar'])) {
        @unlink(__DIR__ . '/../assets/uploads/avatars/' . $user['avatar']);
    }
    if ($user['banner'] && file_exists(__DIR__ . '/../assets/uploads/banners/' . $user['banner'])) {
        @unlink(__DIR__ . '/../assets/uploads/banners/' . $user['banner']);
    }
    if ($user['cv_file'] && file_exists(__DIR__ . '/../assets/uploads/cv/' . $user['cv_file'])) {
        @unlink(__DIR__ . '/../assets/uploads/cv/' . $user['cv_file']);
    }
    $imgRes = mysqli_query($conn, "SELECT filename FROM portfolio_images WHERE user_id=$id");
    while ($img = mysqli_fetch_assoc($imgRes)) {
        delete_portfolio_image_files($img['filename']);
    }

    // portfolio_images and payments rows are removed automatically via ON DELETE CASCADE.
    $del = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($del, 'i', $id);

    if (mysqli_stmt_execute($del)) {
        header('Location: users?deleted=1');
        exit;
    } else {
        $error = 'Something went wrong while deleting. Please try again.';
    }
}

$activeNav = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Delete User - SaaqiFolio Admin</title>
<?php
$ogTitle = 'Delete User — SaaqiFolio Admin';
$ogDescription = 'Confirm user deletion and data removal on SaaqiFolio.';
include __DIR__ . '/../includes/og_meta.php';
?>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="alternate icon" type="image/png" href="../assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
<script src="../assets/js/ui.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/ui.js'); ?>"></script>
</head>
<body>

<div class="admin-shell">
  <?php include __DIR__ . '/_navbar.php'; ?>

  <div class="admin-main">
    <div class="profile-card" style="max-width:540px;">
      <h3 style="margin-bottom:12px;">Delete User</h3>

      <?php if ($error): ?><div class="auth-error"><?php echo e($error); ?></div><?php endif; ?>

      <p style="font-size:14.5px;margin-bottom:10px;">
        Are you sure you want to permanently delete
        <strong><?php echo e($user['name']); ?></strong> (<?php echo e($user['email']); ?>)?
      </p>
      <p style="font-size:12.5px;color:var(--danger);font-weight:600;margin-bottom:20px;display:flex;align-items:center;gap:6px;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
        <span>This deletes their entire account: profile, portfolio images, CV, and payment history. This action cannot be undone.</span>
      </p>

      <form method="POST" style="display:flex;gap:10px;">
        <button type="submit" name="confirm_delete" class="btn btn-primary" style="background:var(--danger);">Yes, Delete Permanently</button>
        <a href="edit_user?id=<?php echo $id; ?>" class="btn btn-ghost" style="text-decoration:none;text-align:center;">Cancel</a>
      </form>
    </div>
  </div>
</div>

</body>
</html>
