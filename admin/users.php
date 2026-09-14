<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$q = trim($_GET['q'] ?? '');
$planFilter = $_GET['plan'] ?? 'All';

$where = [];
$params = [];
$types = '';

if ($q !== '') {
    $where[] = "(name LIKE ? OR email LIKE ?)";
    $like = "%$q%";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}
if ($planFilter === 'Pro') {
    $where[] = "is_subscribed = 1";
} elseif ($planFilter === 'Trial') {
    $where[] = "is_subscribed = 0";
}

$sql = "SELECT u.*, (SELECT COUNT(*) FROM portfolio_images pi WHERE pi.user_id = u.id) AS image_count
        FROM users u";
if ($where) {
    $sql .= " WHERE " . implode(' AND ', $where);
}
$sql .= " ORDER BY u.id DESC";

if ($params) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $users = mysqli_stmt_get_result($stmt);
} else {
    $users = mysqli_query($conn, $sql);
}

$deletedMsg = isset($_GET['deleted']);
$updatedMsg = isset($_GET['updated']);

$activeNav = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users - Folivo Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="admin-shell" style="flex-direction:column;">
  <?php include __DIR__ . '/_navbar.php'; ?>

  <div class="admin-main" style="max-width:1180px;margin:0 auto;width:100%;">
    <div class="page-head">
      <div class="page-title">All Users</div>
      <div class="page-desc">Every account signed up on Folivo.</div>
    </div>

    <?php if ($deletedMsg): ?><div class="auth-error" style="background:#3DDC9726;color:#3DDC97;">User deleted successfully.</div><?php endif; ?>
    <?php if ($updatedMsg): ?><div class="auth-error" style="background:#3DDC9726;color:#3DDC97;">User updated successfully.</div><?php endif; ?>

    <form method="GET" class="admin-search-row">
      <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Search by name or email...">
      <select name="plan" onchange="this.form.submit()" style="width:auto;">
        <option value="All" <?php echo $planFilter === 'All' ? 'selected' : ''; ?>>All Plans</option>
        <option value="Pro" <?php echo $planFilter === 'Pro' ? 'selected' : ''; ?>>Pro Only</option>
        <option value="Trial" <?php echo $planFilter === 'Trial' ? 'selected' : ''; ?>>Trial Only</option>
      </select>
      <button type="submit" class="btn btn-primary" style="width:auto;padding:0 20px;">Search</button>
    </form>

    <div class="admin-table-wrap">
      <table class="admin-table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Plan</th>
            <th>Images</th>
            <th>PDFs Used</th>
            <th>Joined</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (mysqli_num_rows($users) === 0): ?>
            <tr><td colspan="7" style="text-align:center;padding:30px;color:var(--text-faint);">No users found.</td></tr>
          <?php else: ?>
            <?php while ($u = mysqli_fetch_assoc($users)): ?>
              <tr>
                <td style="font-weight:600;"><?php echo e($u['name']); ?></td>
                <td style="color:var(--text-muted);"><?php echo e($u['email']); ?></td>
                <td><span class="pill <?php echo $u['is_subscribed'] ? 'pill-pro' : 'pill-trial'; ?>"><?php echo $u['is_subscribed'] ? 'Pro' : 'Trial'; ?></span></td>
                <td><?php echo $u['image_count']; ?> / <?php echo $u['is_subscribed'] ? '∞' : FREE_IMAGE_LIMIT; ?></td>
                <td><?php echo (int) $u['pdf_downloads_count']; ?> / <?php echo $u['is_subscribed'] ? '∞' : FREE_PDF_LIMIT; ?></td>
                <td style="color:var(--text-muted);"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                <td style="white-space:nowrap;">
                  <a href="edit_user.php?id=<?php echo $u['id']; ?>" class="icon-btn" title="Edit / View">✎</a>
                  <a href="../p.php?slug=<?php echo urlencode($u['public_slug']); ?>" target="_blank" class="icon-btn" title="View public portfolio">👤</a>
                  <a href="delete_user.php?id=<?php echo $u['id']; ?>" class="icon-btn danger" title="Delete">✕</a>
                </td>
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
