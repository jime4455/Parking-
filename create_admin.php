<?php
require __DIR__ . '/../includes/db.php';

$username = 'admin';
$plain = 'admin 123'; // exact password requested
$hash = password_hash($plain, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('SELECT id FROM login WHERE username = ?');
$stmt->execute([$username]);
if ($stmt->fetch()) {
    echo "Admin already exists\n";
    exit;
}

$ins = $pdo->prepare('INSERT INTO login (username,password) VALUES (?,?)');
$ins->execute([$username, $hash]);
echo "Admin created\n";