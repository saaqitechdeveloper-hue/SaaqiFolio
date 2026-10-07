<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard/profile');
} else {
    header('Location: auth/login');
}
exit;
