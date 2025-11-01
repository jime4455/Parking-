<?php
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    setcookie(session_name(), '', time()-42000, '/');
}
session_destroy();
// redirect with message so login shows SweetAlert
header('Location: /Parking%20car/pages/login.php?msg=loggedout');
exit;