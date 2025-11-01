<?php
$dsn = 'mysql:host=127.0.0.1;dbname=parking_car;charset=utf8mb4';
$db_user = 'root';
$db_pass = ''; // XAMPP default - change if you set a password
$db_port = 3306; // Default MySQL port

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}