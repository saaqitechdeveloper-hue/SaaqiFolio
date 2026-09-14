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
        $p = __DIR__ . '/../assets/uploads/portfolio/' . $img['filename'];
        if (file_exists($p)) @unlink($p);
    }

    // portfolio_images and payments rows are removed automatically via ON DELETE CASCADE.
    $del = mysqli_prepare($conn, "DELETE FROM users WHERE id = ?");
    mysqli_stmt_bind_param($del, 'i', $id);

    if (mysqli_stmt_execute($del)) {
        header('Location: users.php?deleted=1');
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
<title>Delete User - Folivo Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="admin-shell" style="flex-direction:column;">
  <?php include __DIR__ . '/_navbar.php'; ?>

  <div class="admin-main" style="max-width:520px;margin:60px auto;width:100%;">
    <div class="profile-card">
      <h3 style="margin-bottom:12px;">Delete User</h3>

      <?php if ($error): ?><div class="auth-error"><?php echo e($error); ?></div><?php endif; ?>

      <p style="font-size:14.5px;margin-bottom:10px;">
        Are you sure you want to permanently delete
        <strong><?php echo e($user['name']); ?></strong> (<?php echo e($user['email']); ?>)?
      </p>
      <p style="font-size:12.5px;color:var(--danger);font-weight:600;margin-bottom:20px;">
        ⚠️ This deletes their entire account: profile, portfolio images, CV,
        and payment history. This action cannot be undone.
      </p>

      <form method="POST" style="display:flex;gap:10px;">
        <button type="submit" name="confirm_delete" class="btn btn-primary" style="background:var(--danger);">Yes, Delete Permanently</button>
        <a href="edit_user.php?id=<?php echo $id; ?>" class="btn btn-ghost" style="text-decoration:none;text-align:center;">Cancel</a>
      </form>
    </div>
  </div>
</div>

</body>
</html>
