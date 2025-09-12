<?php

require_once '../../common/auth.php';
require_once '../../common/db.php'; // PDO connection

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

$id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("SELECT * FROM registration WHERE registration_id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

header('Content-Type: application/json');
echo json_encode($data);
