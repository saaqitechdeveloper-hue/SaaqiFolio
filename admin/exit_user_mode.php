<?php
session_start();

if (!empty($_SESSION['admin_impersonator_id'])) {
    // Restore admin credentials
    $_SESSION['admin_id'] = $_SESSION['admin_impersonator_id'];
    if (!empty($_SESSION['admin_impersonator_email'])) {
        $_SESSION['admin_email'] = $_SESSION['admin_impersonator_email'];
    }

    // Clean user and impersonation keys
    unset(
        $_SESSION['admin_impersonator_id'],
        $_SESSION['admin_impersonator_email'],
        $_SESSION['admin_mode'],
        $_SESSION['user_id']
    );

    header('Location: users?returned=1');
    exit;
}

header('Location: login');
exit;
