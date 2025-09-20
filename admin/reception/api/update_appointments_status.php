<?php

declare(strict_types=1);
session_start();

require_once '../../common/auth.php';
require_once '../../common/db.php'; // PDO connection

header('Content-Type: application/json');

if (!isset($_SESSION['uid'], $_SESSION['branch_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$branchId = $_SESSION['branch_id'];
$type = $_POST['type'] ?? '';
$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? '';

if (!$id || !$status || !in_array($type, ['quick', 'test'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    if ($type === 'quick') {
        $stmt = $pdo->prepare("UPDATE appointment_requests SET status = :status WHERE id = :id AND branch_id = :branch_id");
    } else {
        $stmt = $pdo->prepare("UPDATE appointments SET status = :status WHERE id = :id AND branch_id = :branch_id");
    }

    $stmt->execute([
        ':status' => $status,
        ':id' => $id,
        ':branch_id' => $branchId
    ]);

    echo json_encode(['success' => true, 'message' => 'Status updated']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
