<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

/* ── Bulk delete ── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete'])) {
    $ids = array_map('intval', $_POST['selected_ids'] ?? []);
    if ($ids) {
        $in = implode(',', $ids);
        mysqli_query($conn, "DELETE FROM users WHERE id IN ($in)");
    }
    header('Location: users.php?deleted_bulk=1');
    exit;
}

/* ── Filters ── */
$q          = trim($_GET['q'] ?? '');
$planFilter = $_GET['plan'] ?? 'All';
$where = []; $params = []; $types = '';

if ($q !== '') {
    $where[] = "(name LIKE ? OR email LIKE ?)";
    $like = "%$q%";
    $params[] = $like; $params[] = $like; $types .= 'ss';
}
if ($planFilter === 'Pro')   $where[] = "is_subscribed=1";
if ($planFilter === 'Trial') $where[] = "is_subscribed=0";

$sql = "SELECT u.*, (SELECT COUNT(*) FROM portfolio_images pi WHERE pi.user_id=u.id) AS image_count FROM users u";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$sql .= " ORDER BY u.id DESC";

if ($params) {
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $users = mysqli_stmt_get_result($stmt);
} else {
    $users = mysqli_query($conn, $sql);
}

$activeNav = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users — SaaqiFolio Admin</title>
<link rel="icon" type="image/svg+xml" href="../assets/favicon.svg">
<link rel="alternate icon" type="image/png" href="../assets/favicon.png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600;700&family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">
<script src="../assets/js/ui.js" defer></script>
</head>
<body>

<div class="admin-shell">
  <?php include __DIR__ . '/_navbar.php'; ?>
  <div class="admin-main">

    <div class="page-head-row" style="margin-bottom:24px;">
      <div>
        <div class="page-title">All Users</div>
        <div class="page-desc">Manage every account on SaaqiFolio.</div>
      </div>
    </div>

    <!-- Flash messages -->
    <?php if (isset($_GET['deleted'])): ?>
      <div class="auth-error" style="background:rgba(61,220,151,0.08);border-color:rgba(61,220,151,0.2);color:var(--success);" data-auto-dismiss="4000">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        User deleted successfully.
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted_bulk'])): ?>
      <div class="auth-error" style="background:rgba(61,220,151,0.08);border-color:rgba(61,220,151,0.2);color:var(--success);" data-auto-dismiss="4000">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        Selected users have been deleted.
      </div>
    <?php endif; ?>
    <?php if (isset($_GET['updated'])): ?>
      <div class="auth-error" style="background:rgba(61,220,151,0.08);border-color:rgba(61,220,151,0.2);color:var(--success);" data-auto-dismiss="4000">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
        User updated successfully.
      </div>
    <?php endif; ?>

    <!-- Search & Filter -->
    <form method="GET" class="admin-search-row" id="filterForm">
      <div style="position:relative;flex:1;min-width:240px;">
        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text-faint);display:flex;align-items:center;">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        </span>
        <input type="text" name="q" value="<?php echo e($q); ?>" placeholder="Search by name or email..." style="padding-left:38px;height:42px;">
      </div>
      <div class="form-select" data-autosubmit="1" style="width:190px;">
        <input type="hidden" name="plan" value="<?php echo e($planFilter); ?>">
        <button type="button" class="form-select-trigger" style="height:42px;">
          <span><?php echo $planFilter === 'All' ? 'All Plans' : ($planFilter === 'Pro' ? 'Pro Only' : 'Trial Only'); ?></span>
          <span class="fs-caret"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 6 8 10 12 6"/></svg></span>
        </button>
        <ul class="form-select-list" hidden>
          <li class="form-select-item <?php echo $planFilter==='All' ? 'active' : ''; ?>" data-value="All">All Plans</li>
          <li class="form-select-item <?php echo $planFilter==='Pro' ? 'active' : ''; ?>" data-value="Pro">Pro Only</li>
          <li class="form-select-item <?php echo $planFilter==='Trial' ? 'active' : ''; ?>" data-value="Trial">Trial Only</li>
        </ul>
      </div>
      <button type="submit" class="btn btn-primary" style="width:auto;padding:0 22px;height:42px;display:inline-flex;align-items:center;gap:8px;">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <span>Search</span>
      </button>
    </form>

    <!-- Bulk Actions Form -->
    <form method="POST" id="bulkForm">
      <input type="hidden" name="bulk_delete" value="1">

      <!-- Bulk Bar -->
      <div class="bulk-bar" id="bulkBar" hidden>
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="var(--accent)" stroke-width="2.5"><path d="M9 11l3 3L22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>
        <span class="bulk-count" id="bulkCount"><span>0</span> selected</span>
        <button type="button" id="selectAllBtn" class="btn btn-ghost btn-xs" style="width:auto;">Select All</button>
        <button type="button" id="deselectAllBtn" class="btn btn-ghost btn-xs" style="width:auto;">Deselect All</button>
        <div style="flex:1;"></div>
        <button type="button" class="btn btn-danger-ghost btn-sm" style="width:auto;"
          onclick="confirmAction('Delete all selected users? This cannot be undone.', () => document.getElementById('bulkForm').submit(), {confirmLabel:'Delete Selected'})">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
          Bulk Delete
        </button>
      </div>

      <!-- Table -->
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th style="width:40px;">
                <input type="checkbox" id="checkAll" style="accent-color:var(--accent);width:16px;height:16px;"
                       onchange="document.querySelectorAll('.img-cb').forEach(c=>c.checked=this.checked);initBulkSelect();">
              </th>
              <th>Name</th>
              <th>Email</th>
              <th>Plan</th>
              <th>Images</th>
              <th>PDFs Used</th>
              <th>Joined</th>
              <th style="width:100px;text-align:right;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (mysqli_num_rows($users) === 0): ?>
              <tr><td colspan="8" style="text-align:center;padding:36px;color:var(--text-faint);">No users found.</td></tr>
            <?php else: ?>
              <?php while ($u = mysqli_fetch_assoc($users)): ?>
                <tr>
                  <td>
                    <input type="checkbox" name="selected_ids[]" value="<?php echo $u['id']; ?>" class="img-cb"
                           style="accent-color:var(--accent);width:16px;height:16px;">
                  </td>
                  <td>
                    <a href="edit_user.php?id=<?php echo $u['id']; ?>" style="color:var(--text);text-decoration:none;font-weight:600;">
                      <?php echo e($u['name']); ?>
                    </a>
                  </td>
                  <td style="color:var(--text-muted);font-size:13px;"><?php echo e($u['email']); ?></td>
                  <td><span class="pill <?php echo $u['is_subscribed'] ? 'pill-pro' : 'pill-trial'; ?>"><?php echo $u['is_subscribed'] ? 'Pro' : 'Trial'; ?></span></td>
                  <td style="font-family:var(--font-mono);font-size:13px;"><?php echo $u['image_count']; ?></td>
                  <td style="font-family:var(--font-mono);font-size:13px;"><?php echo (int)($u['pdf_count'] ?? 0); ?></td>
                  <td style="color:var(--text-faint);font-size:12.5px;"><?php echo date('d M Y', strtotime($u['created_at'])); ?></td>
                  <td style="text-align:right;white-space:nowrap;">
                    <div style="display:inline-flex;gap:6px;align-items:center;justify-content:flex-end;">
                      <a href="edit_user.php?id=<?php echo $u['id']; ?>" class="icon-btn" title="Edit user">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                      </a>
                      <a href="delete_user.php?id=<?php echo $u['id']; ?>" class="icon-btn danger" title="Delete user"
                         onclick="event.preventDefault();confirmAction('Delete <?php echo e(addslashes($u['name'])); ?>?',()=>window.location.href=this.href,{confirmLabel:'Delete'})">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </form>

  </div>
</div>

</body>
</html>
