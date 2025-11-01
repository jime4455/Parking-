<?php
// enable errors for debugging (remove in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$host = '127.0.0.1';
$user = 'root';
$pass = '';
$db   = 'parking_car'; // ປ້ອນຊື່ຖານຂໍ້ມູນຂອ້ງທ່ານ

$mysqli = new mysqli($host, $user, $pass, $db);
if ($mysqli->connect_errno) {
    die('Connect error: ' . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ຕົວຢ່າງຟີວອນ: name="plate", name="owner"
    $plate = $_POST['plate'] ?? '';
    $owner = $_POST['owner'] ?? '';

    $stmt = $mysqli->prepare("INSERT INTO cars (plate, owner) VALUES (?, ?)");
    if (! $stmt) {
        die('Prepare failed: ' . $mysqli->error);
    }
    $stmt->bind_param('ss', $plate, $owner);
    if (! $stmt->execute()) {
        die('Execute failed: ' . $stmt->error);
    }

    $stmt->close();
    $mysqli->close();

    // redirect back or show success
    header('Location: list.php');
    exit;
}
?>