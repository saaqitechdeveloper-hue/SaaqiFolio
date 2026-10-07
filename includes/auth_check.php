<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . (strpos($_SERVER['PHP_SELF'], '/dashboard/') !== false ? '../auth/login' : 'auth/login'));
    exit;
}
