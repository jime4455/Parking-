<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// allow specific scripts without auth by basename
$allow = ['login.php', 'create_admin.php'];

$script = basename($_SERVER['SCRIPT_NAME']);
if (!in_array($script, $allow)) {
    if (empty($_SESSION['user'])) {
        // encode space in URL
        header('Location: /Parking%20car/pages/login.php');
        exit;
    }
}