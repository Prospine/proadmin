<?php

declare(strict_types=1);
session_start();

require_once '../../common/db.php';
require_once '../../common/logger.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

if (!isset($_SESSION['uid'], $_SESSION['branch_id'], $_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
$type = $data['type'] ?? null;
$refundAmount = filter_var($data['refund_amount'] ?? null, FILTER_VALIDATE_FLOAT);
$refundReason = trim($data['refund_reason'] ?? '');

if (!$id || !$type || !$refundAmount) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required refund details (ID, Type, Amount).']);
    exit;
}

if (!in_array($type, ['main', 'item'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid item type for refund.']);
    exit;
}

$table = ($type === 'main') ? 'tests' : 'test_items';
$idColumn = ($type === 'main') ? 'test_id' : 'item_id';

try {
    $pdo->beginTransaction();

    // Fetch the item to validate refund amount
    $stmtFetch = $pdo->prepare("SELECT advance_amount FROM {$table} WHERE {$idColumn} = ?");
    $stmtFetch->execute([$id]);
    $item = $stmtFetch->fetch();

    if (!$item || $refundAmount > (float)$item['advance_amount']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Refund amount cannot exceed the amount paid.']);
        $pdo->rollBack();
        exit;
    }

    // Update the refund status
    $stmtUpdate = $pdo->prepare("UPDATE {$table} SET refund_status = 'initiated' WHERE {$idColumn} = ?");
    $stmtUpdate->execute([$id]);

    // Log the activity
    $logDetails = ['refund_amount' => $refundAmount, 'reason' => $refundReason, 'new_status' => 'initiated'];
    log_activity($pdo, $_SESSION['uid'], $_SESSION['username'], $_SESSION['branch_id'], 'UPDATE', $table, $id, ['refund_status' => 'no'], $logDetails);

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Refund has been successfully initiated.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    error_log("Refund Initiation Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'A database error occurred.']);
}