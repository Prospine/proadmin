<?php

declare(strict_types=1);
session_start();

require_once '../../common/db.php';
header('Content-Type: application/json');

if (!isset($_SESSION['uid'], $_SESSION['branch_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$currentUserId = $_SESSION['uid'];
$branchId = $_SESSION['branch_id'];

try {
    // Fetch all users in the same branch, excluding the current user
    $stmt = $pdo->prepare(
        "SELECT id, username, role FROM users WHERE branch_id = ? AND id != ? ORDER BY username ASC"
    );
    $stmt->execute([$branchId, $currentUserId]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'users' => $users]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
