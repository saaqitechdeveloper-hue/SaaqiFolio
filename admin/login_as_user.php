<?php
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($userId <= 0) {
    header('Location: users');
    exit;
}

$stmt = mysqli_prepare($conn, "SELECT id, name, email FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $userId);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$targetUser = $res ? mysqli_fetch_assoc($res) : null;

if (!$targetUser) {
    header('Location: users?error=user_not_found');
    exit;
}

// Preserve admin session credentials
$_SESSION['admin_impersonator_id'] = $_SESSION['admin_id'];
$_SESSION['admin_impersonator_email'] = $_SESSION['admin_email'] ?? 'Admin';
$_SESSION['admin_mode'] = true;

// Directly authenticate as the target user
$_SESSION['user_id'] = (int)$targetUser['id'];

// Redirect to user dashboard
header('Location: ../dashboard/profile');
exit;
