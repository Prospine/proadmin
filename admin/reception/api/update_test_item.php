<?php

declare(strict_types=1);
session_start();

require_once '../../common/db.php';
require_once '../../common/logger.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

if (!isset($_SESSION['uid'], $_SESSION['branch_id'], $_SESSION['username'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'User not authenticated.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$testId = filter_var($data['test_id'] ?? null, FILTER_VALIDATE_INT);
$itemId = filter_var($data['item_id'] ?? null, FILTER_VALIDATE_INT);

if (!$testId) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid Test ID.']);
    exit;
}

$table = $itemId ? 'test_items' : 'tests';
$idColumn = $itemId ? 'item_id' : 'test_id';
$idValue = $itemId ?: $testId;

try {
    $pdo->beginTransaction();

    // Handle Test Status Update
    if (isset($data['test_status'])) {
        $stmt = $pdo->prepare("UPDATE {$table} SET test_status = ? WHERE {$idColumn} = ?");
        $stmt->execute([$data['test_status'], $idValue]);
        log_activity($pdo, $_SESSION['uid'], $_SESSION['username'], $_SESSION['branch_id'], 'UPDATE', $table, $idValue, ['test_status' => 'previous'], ['test_status' => $data['test_status']]);
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Test status updated successfully.']);
        exit;
    }

    // Handle Payment Status Update
    if (isset($data['payment_status'])) {
        $stmt = $pdo->prepare("UPDATE {$table} SET payment_status = ? WHERE {$idColumn} = ?");
        $stmt->execute([$data['payment_status'], $idValue]);
        log_activity($pdo, $_SESSION['uid'], $_SESSION['username'], $_SESSION['branch_id'], 'UPDATE', $table, $idValue, ['payment_status' => 'previous'], ['payment_status' => $data['payment_status']]);
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Payment status updated successfully.']);
        exit;
    }

    // Handle Payment (Due Amount) Update
    if (isset($data['amount'])) {
        $amount = filter_var($data['amount'], FILTER_VALIDATE_FLOAT);
        if ($amount <= 0) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Invalid payment amount.']);
            exit;
        }

        // Fetch current financial data
        $stmt = $pdo->prepare("SELECT total_amount, advance_amount, due_amount FROM {$table} WHERE {$idColumn} = ?");
        $stmt->execute([$idValue]);
        $item = $stmt->fetch();

        if (!$item) {
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Test item not found.']);
            exit;
        }

        if ($amount > (float)$item['due_amount']) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Payment exceeds due amount for this item.']);
            exit;
        }

        $newAdvance = (float)$item['advance_amount'] + $amount;
        $newDue = (float)$item['due_amount'] - $amount;
        $newPaymentStatus = ($newDue <= 0) ? 'paid' : 'partial';

        $updateStmt = $pdo->prepare("UPDATE {$table} SET advance_amount = ?, due_amount = ?, payment_status = ? WHERE {$idColumn} = ?");
        $updateStmt->execute([$newAdvance, $newDue, $newPaymentStatus, $idValue]);

        log_activity($pdo, $_SESSION['uid'], $_SESSION['username'], $_SESSION['branch_id'], 'UPDATE', $table, $idValue, ['paid' => $amount], ['new_due' => $newDue, 'payment_status' => $newPaymentStatus]);
        $pdo->commit();
        echo json_encode(['status' => 'success', 'message' => 'Payment updated successfully.']);
        exit;
    }

    // If no action was taken
    $pdo->rollBack();
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'No valid action specified.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    error_log("Update Test Item Error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>