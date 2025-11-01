<?php
require __DIR__ . '/../includes/db.php';

// ຮັບຄ່າຄົ້ນຫາຈາກ AJAX request
$q = trim($_GET['q'] ?? '');
$params = [];
$sql = 'SELECT v.*, t.code AS type_code, t.name AS type_name FROM vehicles v JOIN vehicle_types t ON v.type_id=t.id';

if ($q !== '') {
    $sql .= ' WHERE v.ref_code LIKE ? OR v.plate LIKE ? OR v.owner_name LIKE ?';
    $like = "%$q%";
    $params = [$like, $like, $like];
}

$sql .= ' ORDER BY v.created_at DESC LIMIT 200';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ສົ່ງຂໍ້ມູນກັບໃນຮູບແບບ JSON
header('Content-Type: application/json');
echo json_encode($rows);
?>