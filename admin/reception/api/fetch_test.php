<?php
require_once '../../common/db.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM tests WHERE test_id = :id");
$stmt->execute(['id' => $id]);
$test = $stmt->fetch(PDO::FETCH_ASSOC);

echo json_encode($test);
