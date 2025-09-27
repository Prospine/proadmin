<?php

declare(strict_types=1);
session_start();

require_once '../../common/auth.php';
require_once '../../common/db.php'; // PDO connection
require_once '../../common/logger.php'; // 1. Added the logger

header('Content-Type: application/json');

// Get session variables for logging
if (!isset($_SESSION['uid'], $_SESSION['branch_id'], $_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Session details are incomplete.']);
    exit;
}
$branchId = $_SESSION['branch_id'];
$userId = $_SESSION['uid'];
$username = $_SESSION['username'];


$type = $_POST['type'] ?? '';
$id = $_POST['id'] ?? null;
$status = $_POST['status'] ?? '';

if (!$id || !$status || !in_array($type, ['quick', 'test'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

// Determine the table and primary key column based on the type
$tableName = ($type === 'quick') ? 'quick_inquiry' : 'test_inquiry';
$primaryKey = 'inquiry_id';

try {
    // 2. Start a transaction to ensure data integrity
    $pdo->beginTransaction();

    // 3. Fetch the record's current state BEFORE updating
    $stmtFetch = $pdo->prepare("SELECT status FROM {$tableName} WHERE {$primaryKey} = :id AND branch_id = :branch_id");
    $stmtFetch->execute([':id' => $id, ':branch_id' => $branchId]);
    $oldRecord = $stmtFetch->fetch(PDO::FETCH_ASSOC);

    if (!$oldRecord) {
        throw new Exception("Record not found or access denied.");
    }
    $oldStatus = $oldRecord['status'];

    // 4. Perform the update
    $stmtUpdate = $pdo->prepare("UPDATE {$tableName} SET status = :status WHERE {$primaryKey} = :id AND branch_id = :branch_id");
    $stmtUpdate->execute([
        ':status' => $status,
        ':id' => $id,
        ':branch_id' => $branchId
    ]);

    // 5. Log the change, including the "before" and "after" states
    log_activity(
        $pdo,
        $userId,
        $username,
        $branchId,
        'UPDATE',
        $tableName,
        (int)$id,
        ['status' => $oldStatus],         // details_before
        ['status' => $status]             // details_after
    );

    // 6. If everything was successful, commit the transaction
    $pdo->commit();

    echo json_encode(['success' => true, 'message' => 'Status updated successfully.']);
} catch (Exception $e) {
    // If any step fails, roll back the transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
