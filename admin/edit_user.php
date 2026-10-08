<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
$error = '';
$success = '';

function get_user_admin($conn, $id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    return mysqli_stmt_get_result($stmt)->fetch_assoc();
}

$user = get_user_admin($conn, $id);
if (!$user) { die('User not found.'); }

// ---------- Save profile field edits ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $name = clean($conn, $_POST['name']);
    $email = clean($conn, strtolower($_POST['email']));
    $profession = clean($conn, $_POST['profession']);
    $phone = clean($conn, $_POST['phone']);
    $address = clean($conn, $_POST['address']);
    $bio = clean($conn, $_POST['bio']);

    $check = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
    mysqli_stmt_bind_param($check, 'si', $email, $id);
    mysqli_stmt_execute($check);

    if (mysqli_stmt_get_result($check)->num_rows > 0) {
        $error = 'Another account already uses that email.';
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, profession=?, phone=?, address=?, bio=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssssssi', $name, $email, $profession, $phone, $address, $bio, $id);
        mysqli_stmt_execute($stmt);
        $success = 'User details updated.';
        $user = get_user_admin($conn, $id);
    }
}

// ---------- Toggle Pro subscription (admin override — add or remove) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_subscription'])) {
    $newVal = $user['is_subscribed'] ? 0 : 1;
    $stmt = mysqli_prepare($conn, "UPDATE users SET is_subscribed=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'ii', $newVal, $id);
    mysqli_stmt_execute($stmt);
    $success = $newVal ? 'Pro subscription granted.' : 'Pro subscription removed — account is back to Trial.';
    $user = get_user_admin($conn, $id);
}

// ---------- Reset PDF download counter ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_pdf_count'])) {
    $stmt = mysqli_prepare($conn, "UPDATE users SET pdf_downloads_count=0 WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $success = 'PDF download count reset to 0.';
    $user = get_user_admin($conn, $id);
}

// ---------- Set a new password for this user (admin support action) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $newPass = $_POST['new_password'];
    if (strlen($newPass) < 6) {
        $error = 'New password must be at least 6 characters.';
    } else {
        $hash = password_hash($newPass, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET password=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'si', $hash, $id);
        mysqli_stmt_execute($stmt);
        $success = "Password reset for {$user['name']}.";
    }
}

// ---------- Stats for this user ----------
$imageCount = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM portfolio_images WHERE user_id=$id"))['cnt'];
$paymentsRes = mysqli_query($conn, "SELECT * FROM payments WHERE user_id=$id ORDER BY id DESC");

$activeNav = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit <?php echo e($user['name']); ?> - SaaqiFolio Admin</title>
<?php
$ogTitle = 'Edit User — SaaqiFolio Admin';
$ogDescription = 'Manage user profile details and subscription settings on SaaqiFolio.';
include __DIR__ . '/../includes/og_meta.php';
?>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="alternate icon" type="image/png" href="../assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/style.css'); ?>">
<?php include __DIR__ . '/../includes/theme_head.php'; ?>
<script src="../assets/js/ui.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/ui.js'); ?>"></script>
</head>
<body>

<div class="admin-shell">
  <?php include __DIR__ . '/_navbar.php'; ?>

  <div class="admin-main">
    <a href="users" style="color:var(--text-muted);text-decoration:none;font-size:13px;">← Back to all users</a>

    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-top:14px;margin-bottom:18px;">
      <div class="page-head" style="margin:0;">
        <div class="page-title"><?php echo e($user['name']); ?></div>
        <div class="page-desc"><?php echo e($user['public_slug']); ?> · Joined <?php echo date('d M Y', strtotime($user['created_at'])); ?></div>
      </div>
      <div style="display:flex;align-items:center;gap:12px;">
        <?php include __DIR__ . '/../includes/theme_switcher.php'; ?>
        <a href="login_as_user?id=<?php echo $user['id']; ?>" class="btn btn-primary" style="width:auto;padding:8px 18px;font-size:13px;display:inline-flex;align-items:center;gap:8px;"
           onclick="return confirm('Directly login to <?php echo e(addslashes($user['name'])); ?>\'s account in Admin Mode?');">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          <span>Login as User (Admin Mode)</span>
        </a>
      </div>
    </div>

    <?php if ($success): ?><div class="auth-error" style="background:#3DDC9726;color:#3DDC97;"><?php echo e($success); ?></div><?php endif; ?>
    <?php if ($error): ?><div class="auth-error"><?php echo e($error); ?></div><?php endif; ?>

    <!-- Quick stats + subscription control -->
    <div class="admin-stats-grid" style="margin-bottom:20px;">
      <div class="admin-stat-card">
        <div class="stat-label">Plan</div>
        <div class="stat-value" style="font-size:18px;">
          <span class="pill <?php echo $user['is_subscribed'] ? 'pill-pro' : 'pill-trial'; ?>"><?php echo $user['is_subscribed'] ? 'Pro' : 'Trial'; ?></span>
        </div>
      </div>
      <div class="admin-stat-card">
        <div class="stat-label">Portfolio Images</div>
        <div class="stat-value"><?php echo $imageCount; ?><?php echo $user['is_subscribed'] ? '' : ' / ' . FREE_IMAGE_LIMIT; ?></div>
      </div>
      <div class="admin-stat-card">
        <div class="stat-label">PDF Downloads Used</div>
        <div class="stat-value"><?php echo (int) $user['pdf_downloads_count']; ?><?php echo $user['is_subscribed'] ? '' : ' / ' . FREE_PDF_LIMIT; ?></div>
      </div>
    </div>

    <div class="profile-card" style="margin-bottom:20px;display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
      <form method="POST" onsubmit="return confirm('<?php echo $user['is_subscribed'] ? 'Remove this user\'s Pro subscription?' : 'Grant this user a free Pro subscription?'; ?>');">
        <button type="submit" name="toggle_subscription" class="btn <?php echo $user['is_subscribed'] ? 'btn-ghost' : 'btn-primary'; ?>" style="width:auto;padding:10px 18px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="16" height="16"><path d="m2 4 3 12h14l3-12-6 7-4-7-4 7-6-7zm3 16h14"/></svg>
          <span><?php echo $user['is_subscribed'] ? 'Remove Pro Subscription' : 'Grant Pro Subscription'; ?></span>
        </button>
      </form>
      <form method="POST" onsubmit="return confirm('Reset this user\'s PDF download count back to 0?');">
        <button type="submit" name="reset_pdf_count" class="btn btn-ghost" style="width:auto;padding:10px 18px;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
          <span>Reset PDF Count</span>
        </button>
      </form>
      <a href="delete_user?id=<?php echo $id; ?>" class="btn btn-danger-ghost" style="width:auto;padding:10px 18px;text-decoration:none;">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="15" height="15"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        <span>Delete User</span>
      </a>
    </div>

    <!-- Editable profile fields -->
    <form method="POST" class="profile-card" style="margin-bottom:20px;">
      <h4 style="margin-bottom:16px;">Account Details</h4>
      <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
        <div class="field">
          <label>Full Name</label>
          <input type="text" name="name" value="<?php echo e($user['name']); ?>" required>
        </div>
        <div class="field">
          <label>Email</label>
          <input type="email" name="email" value="<?php echo e($user['email']); ?>" required>
        </div>
        <div class="field">
          <label>Profession</label>
          <div class="form-select">
            <input type="hidden" name="profession" value="<?php echo e($user['profession']); ?>">
            <button type="button" class="form-select-trigger"><span><?php echo e($user['profession'] ?: 'Select profession'); ?></span><span class="fs-caret">▾</span></button>
            <ul class="form-select-list" hidden>
              <?php foreach (get_professions() as $p): ?>
                <li class="form-select-item <?php echo $user['profession'] === $p ? 'active' : ''; ?>" data-value="<?php echo e($p); ?>"><?php echo e($p); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
        <div class="field">
          <label>Phone</label>
          <input type="text" name="phone" value="<?php echo e($user['phone']); ?>">
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label>Address</label>
          <input type="text" name="address" value="<?php echo e($user['address']); ?>">
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label>Bio</label>
          <textarea name="bio"><?php echo e($user['bio']); ?></textarea>
        </div>
      </div>
      <button type="submit" name="save_user" class="btn btn-primary" style="width:auto;padding:10px 22px;margin-top:16px;">Save Changes</button>
    </form>

    <!-- Admin support: reset password -->
    <form method="POST" class="profile-card" style="margin-bottom:20px;">
      <h4 style="margin-bottom:10px;">Reset Password</h4>
      <p class="muted" style="font-size:12.5px;margin-bottom:12px;">Use this if the user is locked out and needs a new password set on their behalf.</p>
      <div style="display:flex;gap:10px;align-items:center;">
        <div class="password-wrap" style="flex:1;">
          <input type="password" name="new_password" placeholder="New password (min 6 characters)" minlength="6" required style="width:100%;">
          <button type="button" class="btn-toggle-pw" aria-label="Toggle password visibility" tabindex="-1">
            <svg class="eye-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="eye-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="18" height="18" style="display:none;"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
          </button>
        </div>
        <button type="submit" name="reset_password" class="btn btn-ghost" style="width:auto;padding:0 18px;white-space:nowrap;height:42px;">Set Password</button>
      </div>
    </form>

    <!-- Payment history -->
    <h4 style="margin-bottom:12px;color:var(--text-muted);font-size:13px;text-transform:uppercase;letter-spacing:.05em;">Payment History</h4>
    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead><tr><th>Date</th><th>Gateway</th><th>Amount</th><th>Status</th><th>Txn Ref</th></tr></thead>
        <tbody>
          <?php if (mysqli_num_rows($paymentsRes) === 0): ?>
            <tr><td colspan="5" style="text-align:center;padding:24px;color:var(--text-faint);">No payment attempts yet.</td></tr>
          <?php else: ?>
            <?php while ($p = mysqli_fetch_assoc($paymentsRes)): ?>
              <tr>
                <td style="color:var(--text-muted);"><?php echo date('d M Y, h:i A', strtotime($p['created_at'])); ?></td>
                <td><?php echo e($p['gateway']); ?></td>
                <td>Rs. <?php echo number_format($p['amount']); ?></td>
                <td><span class="pill <?php echo $p['status'] === 'Completed' ? 'pill-active' : 'pill-trial'; ?>"><?php echo e($p['status']); ?></span></td>
                <td style="font-family:var(--font-mono);font-size:11.5px;color:var(--text-faint);"><?php echo e($p['txn_ref']); ?></td>
              </tr>
            <?php endwhile; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

  </div>
</div>

</body>
</html>
